<?php
/**
 * 文件名：AuthModel.class.php
 * 功能：登录日志、失败计数与账号锁定、密码更新、操作审计的数据访问
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：认证 / 日志
 */

declare(strict_types=1);

final class AuthModel
{
    public const MAX_FAILURES = 5;
    public const LOCK_MINUTES = 15;
    public const IP_WINDOW_MINUTES = 10;
    public const IP_MAX_FAILURES = 15;

    /**
     * 写入一条登录尝试记录（成功与失败都记录）。
     */
    public static function logLogin(?int $userId, string $usernameAttempted, bool $success, ?string $reason = null): void
    {
        Db::execute(
            'INSERT INTO login_logs
                (user_id, username_attempted, success, failure_reason, ip_hash, user_agent)
             VALUES
                (:user_id, :username, :success, :reason, :ip_hash, :user_agent)',
            [
                'user_id'  => $userId,
                'username' => mb_substr($usernameAttempted, 0, 50, 'UTF-8'),
                'success'  => $success ? 1 : 0,
                'reason'   => $reason === null ? null : mb_substr($reason, 0, 50, 'UTF-8'),
                'ip_hash'  => Auth::ipHash(),
                'user_agent' => Auth::userAgent(),
            ]
        );
    }

    /**
     * 同一来源 IP 在窗口期内的失败次数，用于限流。
     */
    public static function recentFailuresByIp(int $minutes = self::IP_WINDOW_MINUTES): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - max(1, $minutes) * 60);
        $count = Db::fetchValue(
            'SELECT COUNT(*) FROM login_logs
             WHERE success = 0 AND ip_hash = :ip_hash AND created_at >= :cutoff',
            ['ip_hash' => Auth::ipHash(), 'cutoff' => $cutoff]
        );

        return (int) ($count ?? 0);
    }

    /**
     * 密码错误：累加失败次数，达到阈值时写入锁定截止时间。
     */
    public static function registerFailure(int $userId, int $newCount, ?string $lockedUntil): void
    {
        Db::execute(
            'UPDATE users
             SET failed_login_count = :count, locked_until = :locked_until
             WHERE id = :id',
            [
                'count'        => $newCount,
                'locked_until' => $lockedUntil,
                'id'           => $userId,
            ]
        );
    }

    /**
     * 登录成功：清零失败计数、解除锁定、记录最后登录时间与来源哈希。
     */
    public static function touchSuccess(int $userId): void
    {
        Db::execute(
            'UPDATE users
             SET failed_login_count = 0, locked_until = NULL,
                 last_login_at = NOW(), last_login_ip_hash = :ip_hash
             WHERE id = :id',
            ['ip_hash' => Auth::ipHash(), 'id' => $userId]
        );
    }

    public static function passwordHash(int $userId): ?string
    {
        $hash = Db::fetchValue(
            'SELECT password_hash FROM users WHERE id = :id LIMIT 1',
            ['id' => $userId]
        );

        return $hash === null ? null : (string) $hash;
    }

    /**
     * 修改密码并清除“必须修改临时密码”标记。
     * 返回新的 password_changed_at，供当前会话同步；旧会话据此失效。
     */
    public static function updatePassword(int $userId, string $passwordHash): string
    {
        Db::execute(
            'UPDATE users
             SET password_hash = :password_hash,
                 must_change_password = 0,
                 failed_login_count = 0,
                 locked_until = NULL,
                 password_changed_at = NOW()
             WHERE id = :id',
            ['password_hash' => $passwordHash, 'id' => $userId]
        );

        return (string) Db::fetchValue(
            'SELECT password_changed_at FROM users WHERE id = :id LIMIT 1',
            ['id' => $userId]
        );
    }

    /**
     * 密码字符类别数：大写、小写、数字、符号。
     */
    public static function passwordClassCount(string $password): int
    {
        $count = 0;
        $count += preg_match('/[A-Z]/', $password) === 1 ? 1 : 0;
        $count += preg_match('/[a-z]/', $password) === 1 ? 1 : 0;
        $count += preg_match('/\d/', $password) === 1 ? 1 : 0;
        $count += preg_match('/[^A-Za-z0-9]/', $password) === 1 ? 1 : 0;

        return $count;
    }

    /**
     * 写操作审计。actor_id 取当前登录用户，未登录写 NULL。
     */
    public static function audit(string $action, string $entityType, ?int $entityId = null, string $summary = ''): void
    {
        $actorId = Auth::check() ? Auth::id() : null;

        $payload = json_encode(
            ['summary' => mb_substr($summary, 0, 255, 'UTF-8')],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        Db::execute(
            'INSERT INTO audit_logs
                (actor_id, action, entity_type, entity_id, after_json, ip_hash)
             VALUES
                (:actor_id, :action, :entity_type, :entity_id, :after_json, :ip_hash)',
            [
                'actor_id'    => $actorId,
                'action'      => mb_substr($action, 0, 50, 'UTF-8'),
                'entity_type' => mb_substr($entityType, 0, 50, 'UTF-8'),
                'entity_id'   => $entityId,
                'after_json'  => $payload === false ? null : $payload,
                'ip_hash'     => Auth::ipHash(),
            ]
        );
    }

    private function __construct()
    {
    }
}
