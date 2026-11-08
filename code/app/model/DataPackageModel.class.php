<?php
/**
 * 文件名：DataPackageModel.class.php
 * 功能：全站数据包（FR-DB-04 备份恢复）——导出/导入整站运行数据（全表 + 上传文件 + manifest）
 * 作者：黄浩天（2413421）
 * 创建日期：2026-11-09
 * 修改：黄浩天（2413421）第四轮锐评修复——随机哨兵防数据碰撞、文件恢复失败可回滚、
 *       ZipArchive 返回值校验、ZIP 条目体积预检、.htaccess 兜底（2026-11-09）
 * 所属模块：数据与运维 / 数据包
 *
 * 设计要点：
 *   1) 数据包与代码模板解耦：install.sql 只承载表结构 + 演示种子；真实内容随数据包迁移，永不回填仓库；
 *   2) 包结构：manifest.json + data.sql（全表 INSERT，随机哨兵逐语句分隔，兼容独立 mysql CLI）+
 *      uploads_public/ + uploads_storage/；不含 .env 等密钥，可跨机器移植；
 *   3) 导入为整库覆盖，仅限 data.manage 权限；清表用 DELETE（事务安全），FK 开关 try/finally 保证恢复；
 *   4) 文件恢复采用"旧内容挪入 aside → 新内容就位 → 成功删 aside / 失败回滚 aside"，
 *      避免"库已提交而文件丢失"的不一致。
 */

declare(strict_types=1);

final class DataPackageModel
{
    private const FORMAT = 'teamhub-data-package';
    private const FORMAT_VERSION = 1;
    private const SENTINEL_PREFIX = '-- @@TEAMHUB-STMT@@:';   // 随机哨兵前缀（后接随机 hex）
    private const SENTINEL_HEADER = '-- TEAMHUB-SENTINEL-HEX:'; // 包内首行记录哨兵 hex
    private const LEGACY_SENTINEL = '-- @@STATEMENT@@';        // 旧版固定哨兵（兼容读取）
    private const MAX_DATA_SQL_BYTES = 67108864;               // data.sql 解压后上限 64MB
    private const MAX_MANIFEST_BYTES = 2097152;                // manifest 上限 2MB
    private const MAX_UPLOADS_TOTAL_BYTES = 536870912;         // 上传文件解压总量上限 512MB
    private const MAX_ALL_TOTAL_BYTES = 1073741824;            // 全部条目解压总量上限 1GB

    /**
     * 当前代码的 schema 版本（schema_migrations 最大版本号，无迁移记录时为 0）。
     */
    public static function schemaVersion(): int
    {
        try {
            return (int) Db::fetchValue('SELECT COALESCE(MAX(id), 0) FROM schema_migrations');
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * 全部基础表名（information_schema，排除视图）。
     *
     * @return string[]
     */
    public static function baseTables(): array
    {
        $rows = Db::fetchAll(
            "SELECT table_name AS name FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
             ORDER BY table_name"
        );
        return array_map(static fn(array $r): string => (string) $r['name'], $rows);
    }

    /**
     * 各表行数（导出前预览 / 导入后核对）。
     *
     * @return array<string, int>
     */
    public static function tableRowCounts(): array
    {
        $counts = [];
        foreach (self::baseTables() as $table) {
            $counts[$table] = (int) Db::fetchValue("SELECT COUNT(*) FROM `{$table}`");
        }
        return $counts;
    }

    /**
     * 导出数据包。
     *
     * @return array{path: string, filename: string, manifest: array<string, mixed>}
     * @throws RuntimeException 目录不可写 / 缺少 zip 扩展 / 打包失败时抛出
     */
    public static function export(int $userId): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('服务器 PHP 缺少 zip 扩展，无法导出数据包。');
        }

        $manifest = [
            'format' => self::FORMAT,
            'format_version' => self::FORMAT_VERSION,
            'exported_at' => date('c'),
            'exported_by' => $userId,
            'schema_version' => self::schemaVersion(),
            'table_rows' => self::tableRowCounts(),
            'notes' => '数据包含全部业务表与运维日志表；不含 .env 等密钥。导入将整库覆盖，仅限超级管理员。',
        ];

        $exportDir = self::ensureExportDir();

        // 文件名含随机后缀：同秒并发导出也不冲突
        $filename = 'teamhub-data-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.zip';
        $path = $exportDir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('创建数据包文件失败。');
        }
        $cleanup = static function () use ($path): void {
            if (is_file($path)) {
                @unlink($path); // 失败路径不留半截孤儿 zip
            }
        };

        $sentinelHex = bin2hex(random_bytes(16));
        $sentinel = self::SENTINEL_PREFIX . $sentinelHex;

        if ($zip->addFromString('manifest.json',
                json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== true) {
            $zip->close();
            $cleanup();
            throw new RuntimeException('写入 manifest 失败。');
        }
        if ($zip->addFromString('data.sql', self::generateDataSql($sentinel)) !== true) {
            $zip->close();
            $cleanup();
            throw new RuntimeException('写入 data.sql 失败。');
        }
        self::addTreeToZip($zip, APP_ROOT . '/public/static/uploads', 'uploads_public');
        self::addTreeToZip($zip, APP_ROOT . '/storage/uploads', 'uploads_storage');

        if ($zip->close() !== true || filesize($path) === 0) {
            $cleanup();
            throw new RuntimeException('数据包写入未完成（磁盘空间或权限异常），请重试。');
        }

        return ['path' => $path, 'filename' => $filename, 'manifest' => $manifest];
    }

    /**
     * 导入数据包：整库覆盖为快照时点。
     *
     * @return array<string, mixed> 摘要（manifest + 实际执行语句数 + 文件数）
     * @throws RuntimeException 包损坏 / manifest 不合法 / schema 版本不兼容 / 文件恢复失败时抛出
     */
    public static function import(string $zipPath, int $userId): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('服务器 PHP 缺少 zip 扩展，无法导入数据包。');
        }
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('无法读取数据包文件：可能已损坏，或不是本系统导出的 ZIP。');
        }

        // ---- 1) ZIP 条目体积预检（防 zip 炸弹 / 内存耗尽） ----
        // getFromName/extractTo 读取"首个同名条目"，预检口径保持一致：同名重复条目按首条计；
        // 同时对全部条目累计解压总量设预算，防任意名巨型条目写满磁盘。
        $dataSqlSize = 0;
        $manifestSize = 0;
        $uploadsTotal = 0;
        $allTotal = 0;
        $seen = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false) {
                continue;
            }
            $name = (string) $stat['name'];
            $size = (int) ($stat['size'] ?? 0);
            $allTotal += $size;
            if (isset($seen[$name])) {
                continue; // 首个同名条目才参与单独预算（与 getFromName 读取口径一致）
            }
            $seen[$name] = true;
            if ($name === 'data.sql') {
                $dataSqlSize = $size;
            } elseif ($name === 'manifest.json') {
                $manifestSize = $size;
            } elseif (str_starts_with($name, 'uploads_')) {
                $uploadsTotal += $size;
            }
        }
        if ($dataSqlSize === 0) {
            $zip->close();
            throw new RuntimeException('数据包缺少 data.sql，不是本系统导出的数据包。');
        }
        if ($dataSqlSize > self::MAX_DATA_SQL_BYTES || $manifestSize > self::MAX_MANIFEST_BYTES
            || $uploadsTotal > self::MAX_UPLOADS_TOTAL_BYTES || $allTotal > self::MAX_ALL_TOTAL_BYTES) {
            $zip->close();
            throw new RuntimeException('数据包体积超出安全上限（data.sql≤64MB，manifest≤2MB，上传文件≤512MB，总量≤1GB），拒绝导入。');
        }

        // ---- 2) manifest 校验 ----
        $manifestRaw = $zip->getFromName('manifest.json');
        if ($manifestRaw === false || strlen($manifestRaw) > self::MAX_MANIFEST_BYTES) {
            $zip->close();
            throw new RuntimeException('数据包缺少 manifest.json，不是本系统导出的数据包。');
        }
        $manifest = json_decode($manifestRaw, true);
        if (!is_array($manifest)
            || ($manifest['format'] ?? '') !== self::FORMAT
            || (int) ($manifest['format_version'] ?? 0) > self::FORMAT_VERSION) {
            $zip->close();
            throw new RuntimeException('manifest 校验失败：格式不认识或版本过新，拒绝导入。');
        }

        $packageSchema = (int) ($manifest['schema_version'] ?? 0);
        $currentSchema = self::schemaVersion();
        if ($packageSchema > $currentSchema) {
            $zip->close();
            throw new RuntimeException(
                "数据包由更新的代码结构导出（包 schema v{$packageSchema} > 当前 v{$currentSchema}），"
                . '请先升级代码再导入，避免数据结构不兼容。'
            );
        }

        $dataSql = $zip->getFromName('data.sql');
        if ($dataSql === false || trim($dataSql) === '') {
            $zip->close();
            throw new RuntimeException('数据包缺少 data.sql 或内容为空。');
        }

        // ---- 3) 解析哨兵并预切分（只读语句，不执行） ----
        [$sentinel, $statements] = self::parseStatements($dataSql);
        if ($sentinel === null || $statements === []) {
            $zip->close();
            throw new RuntimeException('data.sql 中找不到语句分隔哨兵，数据包可能已损坏。');
        }

        // ---- 4) 解压到 staging（校验完整性后再动线上文件） ----
        $exportDir = self::ensureExportDir();
        $staging = $exportDir . '/staging-' . bin2hex(random_bytes(6));
        if (!$zip->extractTo($staging)) {
            $zip->close();
            throw new RuntimeException('解压数据包失败，请重试。');
        }
        $zip->close();
        foreach (['data.sql', 'manifest.json', 'uploads_public', 'uploads_storage'] as $need) {
            if (!file_exists($staging . '/' . $need)) {
                self::rrmdir($staging);
                throw new RuntimeException("数据包不完整：缺少 {$need}。");
            }
        }

        // ---- 5) 事务内清表 + 逐语句回放（FK 开关 try/finally 保证恢复） ----
        $pdo = Db::pdo();
        $statementCount = 0;
        try {
            Db::transaction(static function () use ($pdo, $statements, &$statementCount): void {
                try {
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                    foreach (self::baseTables() as $table) {
                        if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                            continue; // 表名白名单防御（数据来自 information_schema，双保险）
                        }
                        $pdo->exec('DELETE FROM `' . $table . '`');
                    }
                    foreach ($statements as $stmt) {
                        $pdo->exec($stmt);
                        if (str_starts_with(strtoupper($stmt), 'INSERT')) {
                            $statementCount++;
                        }
                    }
                } finally {
                    // 会话级 FK 开关必须恢复，避免回滚路径污染连接状态
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                }
            });
        } catch (Throwable $e) {
            self::rrmdir($staging);
            throw new RuntimeException('数据库导入失败，已回滚，站点数据未受影响：' . $e->getMessage());
        }

        // ---- 6) 文件恢复（aside 方式：旧文件先挪走，全部就位成功才清理；失败可回滚） ----
        $uploadCounts = ['public' => 0, 'storage' => 0];
        try {
            $uploadCounts = self::restoreUploads($staging);
        } catch (Throwable $e) {
            self::rrmdir($staging);
            throw new RuntimeException('数据库已恢复，但上传文件还原失败（旧文件已尽量保留）：' . $e->getMessage());
        } finally {
            self::rrmdir($staging);
        }

        return [
            'manifest' => $manifest,
            'inserts' => $statementCount,
            'tables' => count($manifest['table_rows'] ?? []),
            'uploads' => $uploadCounts,
        ];
    }

    /**
     * 解析 data.sql：首行读取哨兵 hex，兼容旧版固定哨兵；返回 [哨兵|null, 语句数组]。
     *
     * @return array{0: ?string, 1: string[]}
     */
    private static function parseStatements(string $dataSql): array
    {
        $sentinel = null;
        $lines = explode("\n", $dataSql, 2);
        if (isset($lines[0]) && preg_match('/^' . preg_quote(self::SENTINEL_HEADER, '/') . '\s*([0-9a-f]{8,64})\s*$/', trim($lines[0]), $m) === 1) {
            $sentinel = self::SENTINEL_PREFIX . $m[1];
        } elseif (str_contains($dataSql, self::LEGACY_SENTINEL)) {
            $sentinel = self::LEGACY_SENTINEL; // 旧版包兼容
        }
        if ($sentinel === null) {
            return [null, []];
        }

        $statements = [];
        foreach (explode($sentinel, $dataSql) as $chunk) {
            $stmt = trim($chunk);
            // 只保留真正的语句块；跳过文件头说明、空块
            if ($stmt === '' || !preg_match('/^(INSERT|SET)/i', $stmt)) {
                continue;
            }
            $statements[] = $stmt;
        }
        return [$sentinel, $statements];
    }

    /**
     * 生成 data.sql：首行记录哨兵 hex，每条语句后跟随机哨兵行（文件本身仍是合法 SQL，
     * 可直接 `mysql team_home < data.sql` 独立使用）。
     */
    private static function generateDataSql(string $sentinel): string
    {
        $pdo = Db::pdo();
        $out = self::SENTINEL_HEADER . ' ' . substr($sentinel, strlen(self::SENTINEL_PREFIX)) . "\n"
             . "-- TeamHub 数据包 data.sql；生成时间：" . date('c') . "；导入将整库覆盖。\n";
        $emit = static function (string $sql) use (&$out, $sentinel): void {
            $out .= rtrim($sql, ";\n\r") . ";\n" . $sentinel . "\n";
        };

        $emit('SET NAMES utf8mb4');
        $emit('SET FOREIGN_KEY_CHECKS = 0');

        foreach (self::baseTables() as $table) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                continue;
            }
            $rows = Db::fetchAll("SELECT * FROM `{$table}`");
            if ($rows === []) {
                continue;
            }
            $cols = array_keys($rows[0]);
            $colSql = implode(', ', array_map(static fn(string $c): string => '`' . $c . '`', $cols));
            foreach ($rows as $row) {
                $vals = [];
                foreach ($cols as $c) {
                    $v = $row[$c];
                    $vals[] = $v === null ? 'NULL' : $pdo->quote((string) $v);
                }
                $emit("INSERT INTO `{$table}` ({$colSql}) VALUES (" . implode(', ', $vals) . ')');
            }
        }

        $emit('SET FOREIGN_KEY_CHECKS = 1');
        return $out;
    }

    /**
     * 文件恢复：旧内容挪入卷内 aside 目录 → 新内容就位 → 成功清理 aside / 失败回滚。
     * aside 建在卷内部（同文件系统，rename 原子），不触碰卷挂载点本身。
     */
    private static function restoreUploads(string $staging): array
    {
        $counts = ['public' => 0, 'storage' => 0];
        $asides = [];
        try {
            foreach ([
                ['uploads_public', APP_ROOT . '/public/static/uploads'],
                ['uploads_storage', APP_ROOT . '/storage/uploads'],
            ] as [$zipDir, $target]) {
                // aside 统一放 storage/（docroot 之外，Apache 已拒绝直读），
                // 跨设备场景由 moveTree 的 copy 回退兜底
                $aside = APP_ROOT . '/storage/.dp-aside-' . $zipDir . '-' . bin2hex(random_bytes(4));
                if (!is_dir($target)) {
                    mkdir($target, 0775, true);
                }
                // 1) 先注册 aside（即使"旧内容挪入"中途失败，回滚也知道要去哪找回）
                $asides[$target] = $aside;
                // 2) 旧内容挪入 aside（保留回滚能力）
                self::moveTree($target, $aside);
                // 3) 新内容就位
                $counts[$zipDir === 'uploads_public' ? 'public' : 'storage'] =
                    self::moveTree($staging . '/' . $zipDir, $target);
            }
            // 4) 全部成功 → 清理 aside
            foreach ($asides as $aside) {
                self::rrmdir($aside);
            }
        } catch (Throwable $e) {
            // 回滚：把 aside 里的旧内容**合并搬回**目标（不清空目标——
            // 半就位的新文件与旧文件共存时，以找回旧数据为优先）
            foreach ($asides as $target => $aside) {
                if (is_dir($aside)) {
                    self::moveTree($aside, $target);
                    self::rrmdir($aside);
                }
            }
            self::ensureUploadHtaccess();
            throw $e;
        }
        self::ensureUploadHtaccess();
        return $counts;
    }

    /**
     * 上传目录 .htaccess 兜底：包内缺失时（旧包）就地补写禁执行规则，
     * 不必等到容器重启由 entrypoint 修复。
     */
    private static function ensureUploadHtaccess(): void
    {
        $path = APP_ROOT . '/public/static/uploads/.htaccess';
        if (is_file($path)) {
            return;
        }
        $rules = "Options -Indexes\n\n"
            . "<FilesMatch \"\\.(?i:php|phtml|php[0-9]|phar|pl|py|cgi|sh)$\">\n    Require all denied\n</FilesMatch>\n\n"
            . "RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar\n"
            . "RemoveType .php .phtml .php3 .php4 .php5 .php7 .php8 .phar\n";
        @file_put_contents($path, $rules);
    }

    /**
     * 确保导出目录存在（容器内 www-data 可写；bind mount 下由 entrypoint 预建并 chown）。
     */
    private static function ensureExportDir(): string
    {
        $dir = APP_ROOT . '/storage/exports';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('无法创建导出目录 storage/exports，请检查目录写权限。');
        }
        return $dir;
    }

    /**
     * 递归把目录内容加入 ZIP（$zipPrefix 为包内目录名）；addFile 失败即抛出，杜绝产出截断包。
     */
    private static function addTreeToZip(ZipArchive $zip, string $dir, string $zipPrefix): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            $rel = ltrim(substr($file->getPathname(), strlen($dir)), '/\\');
            if ($zip->addFile($file->getPathname(), $zipPrefix . '/' . $rel) !== true) {
                $zip->close();
                if (is_file($path)) {
                    @unlink($path);
                }
                throw new RuntimeException('打包上传文件失败：' . $rel);
            }
        }
    }

    /**
     * 递归删除目录内容（不删目录本身）；符号链接只删链接本身。
     */
    private static function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isLink() || $item->isFile() ? @unlink($item->getPathname()) : @rmdir($item->getPathname());
        }
    }

    /**
     * 递归删除整个目录（含目录本身）。
     */
    private static function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isLink() || $item->isFile() ? @unlink($item->getPathname()) : @rmdir($item->getPathname());
        }
        @rmdir($dir);
    }

    /**
     * 把来源目录整体搬移到目标目录（同文件系统 rename），返回搬移文件数；
     * rename 失败立即抛出（不再静默吞掉）。
     */
    private static function moveTree(string $src, string $dst): int
    {
        if (!is_dir($src)) {
            return 0;
        }
        if (!is_dir($dst) && !mkdir($dst, 0775, true) && !is_dir($dst)) {
            throw new RuntimeException("目标目录不可写：{$dst}");
        }
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }
            $rel = ltrim(substr($file->getPathname(), strlen($src)), '/\\');
            $target = $dst . '/' . $rel;
            $targetDir = dirname($target);
            if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new RuntimeException("目标子目录不可写：{$targetDir}");
            }
            if (!@rename($file->getPathname(), $target)) {
                // 跨文件系统（EXDEV）时退化为 copy+unlink
                if (!@copy($file->getPathname(), $target) || !@unlink($file->getPathname())) {
                    throw new RuntimeException("文件搬移失败：{$rel}");
                }
            }
            $count++;
        }
        return $count;
    }
}
