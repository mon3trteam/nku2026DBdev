<?php
/**
 * 文件名：LogModel.class.php
 * 功能：登录日志与操作审计的数据访问
 * 作者：杨斯源（学号待填）
 * 修改：后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 日志审计
 */

declare(strict_types=1);

final class LogModel
{
    private const ACTION_LABELS = [
        'create'   => '新建',
        'update'   => '更新',
        'publish'  => '发布',
        'archive'  => '归档',
        'moderate' => '审核',
        'setting'  => '设置',
        'delete'   => '删除',
    ];

    private const ENTITY_LABELS = [
        'announcement'  => '公告',
        'member'        => '成员',
        'message'       => '留言',
        'site_settings' => '站点设置',
        'project'       => '项目',
        'resource'      => '资源',
        'user'          => '账号',
    ];

    public static function loginLogs(): array
    {
        $rows = Db::fetchAll(
            'SELECT l.username_attempted, l.success, l.failure_reason, l.ip_hash, l.user_agent, l.created_at
             FROM login_logs l
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT 100'
        );

        return array_map(
            static fn (array $row): array => [
                'time'     => format_datetime((string) $row['created_at']),
                'username' => (string) $row['username_attempted'],
                'success'  => (int) $row['success'],
                'reason'   => (string) ($row['failure_reason'] ?? ''),
                'ip_hash'  => substr((string) ($row['ip_hash'] ?? ''), 0, 8),
                'ua'       => (string) ($row['user_agent'] ?? ''),
            ],
            $rows
        );
    }

    /**
     * 指定账号的最近登录记录，供成员中心“账号安全”页使用。
     */
    public static function loginLogsForUser(int $userId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));

        $rows = Db::fetchAll(
            "SELECT success, failure_reason, ip_hash, user_agent, created_at
             FROM login_logs
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit}",
            ['user_id' => $userId]
        );

        return array_map(
            static fn (array $row): array => [
                'time'    => format_datetime((string) $row['created_at']),
                'ip_hash' => substr((string) ($row['ip_hash'] ?? ''), 0, 8),
                'ua'      => (string) ($row['user_agent'] ?? ''),
                'success' => (int) $row['success'] === 1,
            ],
            $rows
        );
    }

    public static function auditLogs(): array
    {
        $rows = Db::fetchAll(
            'SELECT a.action, a.entity_type, a.entity_id, a.created_at, COALESCE(u.username, \'system\') AS actor
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.actor_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT 100'
        );

        return array_map(
            static function (array $row): array {
                $entityType = (string) $row['entity_type'];
                $entityId = $row['entity_id'] === null ? '' : ':' . $row['entity_id'];
                $action = (string) $row['action'];
                $entityLabel = self::ENTITY_LABELS[$entityType] ?? $entityType;
                $actionLabel = self::ACTION_LABELS[$action] ?? $action;

                return [
                    'time'    => format_datetime((string) $row['created_at']),
                    'actor'   => (string) $row['actor'],
                    'action'  => $action,
                    'entity'  => $entityType . $entityId,
                    'summary' => $actionLabel . $entityLabel . $entityId,
                ];
            },
            $rows
        );
    }
}
