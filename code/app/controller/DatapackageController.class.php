<?php
/**
 * 文件名：DatapackageController.class.php
 * 功能：数据包控制器——全站数据导出（FR-DB-04）与整库恢复，仅 data.manage（超级管理员）
 * 作者：黄浩天（2413421）
 * 创建日期：2026-11-09
 * 所属模块：数据与运维 / 数据包
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/DataPackageModel.class.php';
require_once APP_ROOT . '/app/model/AuthModel.class.php';

final class DatapackageController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    /**
     * 数据包管理页：导出入口、导入表单与当前数据规模概览。
     */
    public function index(): void
    {
        Auth::requirePermission('data.manage');

        render('admin/data_package', [
            'pageTitle' => '数据包 · 管理后台',
            'adminNav' => 'datamanager',
            'tableRows' => DataPackageModel::tableRowCounts(),
            'schemaVersion' => DataPackageModel::schemaVersion(),
            'importSummary' => null,
            'importError' => '',
        ]);
    }

    /**
     * 流式下载全站数据包 ZIP（导出后删除服务器临时文件，版本由管理员本地留档）。
     */
    public function export(): void
    {
        Auth::requirePermission('data.manage');
        $this->requirePost(); // 导出会写审计与临时文件，按写操作处理（POST+CSRF）

        $result = DataPackageModel::export(Auth::id());
        AuthModel::audit('export', 'data_package', 0, '导出全站数据包：' . $result['filename']);

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . (string) filesize($result['path']));
        header('Cache-Control: no-store');
        readfile($result['path']);
        unlink($result['path']);
        exit;
    }

    /**
     * 上传数据包并整库覆盖恢复（二次校验在模型层：manifest + schema 版本）。
     */
    public function import(): void
    {
        Auth::requirePermission('data.manage');
        $this->requirePost();

        // post_max_size 超限时 PHP 会清空 $_POST/$_FILES，提前给出明确的 413 提示
        $limit = self::postMaxSizeBytes();
        if ($limit > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $limit) {
            $mb = (int) round($limit / 1048576);
            $this->renderImportError("数据包超过服务器单次上传上限（约 {$mb}MB）。"
                . '可调大 PHP 的 post_max_size / upload_max_filesize 后重试，或分批导出。');
            return;
        }

        if (($_POST['ack'] ?? '') !== '1') {
            $this->renderImportError('请先勾选"我已了解导入将整库覆盖"后再提交。');
            return;
        }

        $file = $_FILES['package'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->renderImportError('请选择要导入的数据包文件（.zip）。');
            return;
        }
        if (($file['size'] ?? 0) <= 0) {
            $this->renderImportError('数据包文件为空。');
            return;
        }
        if (strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'zip') {
            $this->renderImportError('只接受 .zip 格式的数据包。');
            return;
        }

        $exportDir = APP_ROOT . '/storage/exports';
        if (!is_dir($exportDir) && !mkdir($exportDir, 0775, true) && !is_dir($exportDir)) {
            $this->renderImportError('服务器临时目录不可写（storage/exports），请联系管理员。');
            return;
        }
        $tmpPath = $exportDir . '/import-' . bin2hex(random_bytes(6)) . '.zip';
        if (!move_uploaded_file((string) $file['tmp_name'], $tmpPath)) {
            $this->renderImportError('数据包保存失败，请重试。');
            return;
        }

        try {
            $summary = DataPackageModel::import($tmpPath, Auth::id());
        } catch (RuntimeException $e) {
            @unlink($tmpPath);
            $this->renderImportError($e->getMessage());
            return;
        }
        @unlink($tmpPath);

        AuthModel::audit('import', 'data_package', 0,
            '导入数据包并整库覆盖：' . $summary['inserts'] . ' 条记录，来自快照 '
            . (string) ($summary['manifest']['exported_at'] ?? '未知时间'));

        render('admin/data_package', [
            'pageTitle' => '数据包 · 管理后台',
            'adminNav' => 'datamanager',
            'tableRows' => DataPackageModel::tableRowCounts(),
            'schemaVersion' => DataPackageModel::schemaVersion(),
            'importSummary' => $summary,
            'importError' => '',
        ]);
    }

    /**
     * 导入失败：422 回数据包页并展示原因。
     */
    private function renderImportError(string $message): void
    {
        http_response_code(422);
        render('admin/data_package', [
            'pageTitle' => '数据包 · 管理后台',
            'adminNav' => 'datamanager',
            'tableRows' => DataPackageModel::tableRowCounts(),
            'schemaVersion' => DataPackageModel::schemaVersion(),
            'importSummary' => null,
            'importError' => $message,
        ]);
    }

    /**
     * 解析 post_max_size 为字节数（支持 K/M/G 简写）；解析失败返回 0（不预检）。
     */
    private static function postMaxSizeBytes(): int
    {
        $v = trim((string) ini_get('post_max_size'));
        if ($v === '' || $v === '-1') {
            return 0;
        }
        if (preg_match('/^(\d+)\s*([KMG])?$/i', $v, $m) !== 1) {
            return 0;
        }
        $n = (int) $m[1];
        return match (strtoupper($m[2] ?? '')) {
            'K' => $n * 1024,
            'M' => $n * 1048576,
            'G' => $n * 1073741824,
            default => $n,
        };
    }

    /**
     * POST 校验 + CSRF（与 AdminController::requirePost 同一约定）。
     */
    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            render('error_request', ['pageTitle' => '请求方式不正确']);
            exit;
        }
        if (!Auth::verifyCsrf($_POST['_token'] ?? null)) {
            http_response_code(400);
            render('error_request', ['pageTitle' => '页面已过期']);
            exit;
        }
    }
}
