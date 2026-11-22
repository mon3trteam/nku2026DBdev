<?php
/**
 * 文件名：HealthModel.class.php
 * 功能：数据健康检查页的数据查询（表行数、环境信息、最近活动）
 * 作者：钱泊瑜 2411282
 * 创建日期：2026-11-22
 * 所属模块：管理后台 / 数据健康
 *
 * 说明：
 *   1) 本类只读，不写入任何表、不新增表结构；
 *   2) 不调用 helpers.php 的任何函数，保持模型层纯净；展示格式化统一在视图完成；
 *   3) 表名来自 information_schema，拼接进 SQL 前仍做白名单校验（双保险）。
 */

declare(strict_types=1);

final class HealthModel
{
    /** 最近活动卡展示的登录日志条数 */
    private const RECENT_LOGIN_LIMIT = 5;

    /**
     * 全部基础表名（information_schema，排除视图），按表名升序。
     *
     * @return string[]
     */
    public static function baseTables(): array
    {
        $rows = Db::fetchAll(
            "SELECT table_name AS name
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
             ORDER BY table_name"
        );

        return array_map(static fn (array $row): string => (string) $row['name'], $rows);
    }

    /**
     * 各业务表行数，按行数降序（行数相同时保持表名升序，输出稳定）。
     *
     * @return array<string, int>
     */
    public static function tableRowCounts(): array
    {
        $counts = [];
        foreach (self::baseTables() as $table) {
            if (preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                continue; // 表名白名单：数据来自 information_schema，仍做一次防御
            }
            $counts[$table] = (int) Db::fetchValue("SELECT COUNT(*) FROM `{$table}`");
        }

        // PHP 8 的排序是稳定的：同值元素的原有顺序（表名升序）会被保留
        arsort($counts);

        return $counts;
    }

    /**
     * MySQL 版本号。
     */
    public static function dbVersion(): string
    {
        return (string) (Db::fetchValue('SELECT VERSION()') ?? '未知');
    }

    /**
     * 视图数量（information_schema.tables 中 table_type = 'VIEW' 的条目）。
     */
    public static function viewCount(): int
    {
        return (int) Db::fetchValue(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_type = 'VIEW'"
        );
    }

    /**
     * PHP 版本号（读取运行时常量，不查询数据库）。
     */
    public static function phpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * storage 目录所在磁盘的剩余空间，格式化为 "12.34 GB"；取不到时返回「未知」。
     * 仅使用 number_format 做数值格式化，不依赖 helpers.php。
     */
    public static function diskFree(): string
    {
        $bytes = @disk_free_space(APP_ROOT);
        if ($bytes === false) {
            return '未知';
        }

        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    /**
     * storage 目录是否可写：上传、数据包导出都依赖此目录。
     */
    public static function storageWritable(): bool
    {
        return is_writable(APP_ROOT . '/storage');
    }

    /**
     * 最近登录日志（默认 5 条，按自增 id 倒序，即最新在前）。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recentLogins(int $limit = self::RECENT_LOGIN_LIMIT): array
    {
        $limit = max(1, min(20, $limit));

        return Db::fetchAll(
            "SELECT username_attempted, success, failure_reason, created_at
             FROM login_logs
             ORDER BY id DESC
             LIMIT {$limit}"
        );
    }

    /**
     * 最近一次审计日志时间；没有任何审计记录时返回 null。
     */
    public static function lastAuditTime(): ?string
    {
        $value = Db::fetchValue('SELECT MAX(created_at) FROM audit_logs');

        return $value === null ? null : (string) $value;
    }
}
