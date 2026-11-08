<?php
/**
 * 文件名：Auth.class.php
 * 功能：会话启动、登录态、RBAC 权限判定、CSRF 防护与访问守卫
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：公共框架 / 认证
 *
 * 说明：
 *   1) 会话 Cookie 使用 HttpOnly + SameSite=Lax，HTTPS 下自动加 Secure。
 *   2) 登录成功后 session_regenerate_id(true)，避免会话固定攻击。
 *   3) 权限以 roles -> role_permissions -> permissions 为准，超管拥有全部权限。
 *   4) CSRF token 存于 Session，表单通过 csrf_field() 输出隐藏域。
 */

declare(strict_types=1);

final class Auth
{
    private const ADMIN_ROLES = ['super_admin', 'editor'];

    private static ?array $config = null;
    private static ?array $permissions = null;

    /**
     * 启动会话、处理空闲超时、初始化 CSRF token。
     * 必须在任何页面输出之前调用一次。
     */
    public static function boot(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = self::config();
            session_name($config['session_name']);
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => self::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        self::validateSession();
        self::expireIdleSession();

        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || $_SESSION['_csrf'] === '') {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']) && (int) $_SESSION['user']['id'] > 0;
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION['user'] : null;
    }

    public static function id(): int
    {
        return self::check() ? (int) $_SESSION['user']['id'] : 0;
    }

    public static function role(): string
    {
        return self::check() ? (string) ($_SESSION['user']['role_code'] ?? '') : '';
    }

    public static function isAdmin(): bool
    {
        return in_array(self::role(), self::ADMIN_ROLES, true);
    }

    /**
     * 登录成功：重建会话 ID、写入最小用户快照、轮换 CSRF token。
     */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user'] = [
            'id'           => (int) $user['id'],
            'username'     => (string) $user['username'],
            'display_name' => (string) ($user['display_name'] ?? $user['username']),
            'role_code'    => (string) ($user['role_code'] ?? ''),
            'role_name'    => (string) ($user['role_name'] ?? ''),
            'must_change_password' => (int) ($user['must_change_password'] ?? 0),
            'password_changed_at' => (string) ($user['password_changed_at'] ?? ''),
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));

        self::$permissions = null;
    }

    /**
     * 彻底退出：清空 Session 数据并让 Cookie 立即失效。
     */
    public static function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies') === '1') {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]);
            }

            session_destroy();
        }

        self::$permissions = null;
    }

    /**
     * 当前角色拥有的权限码列表。
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }

        $roleCode = self::role();
        if ($roleCode === '') {
            return self::$permissions = [];
        }

        $rows = Db::fetchAll(
            'SELECT p.code
             FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             JOIN roles r ON r.id = rp.role_id
             WHERE r.code = :role_code
             ORDER BY p.code ASC',
            ['role_code' => $roleCode]
        );

        return self::$permissions = array_values(array_map(
            static fn (array $row): string => (string) $row['code'],
            $rows
        ));
    }

    public static function can(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }

    /**
     * 未登录跳转到登录页，并带上登录后回跳地址。
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            $next = (string) ($_SERVER['REQUEST_URI'] ?? '');
            $params = self::safeNext($next) !== '' ? ['next' => $next] : [];
            redirect(url('auth/login', $params));
        }

        // 首次登录或管理员重置密码后，必须先改密才能访问其他页面
        if (self::mustChangePassword() && !self::isPasswordChangeRequest()) {
            redirect(self::passwordChangeUrl());
        }
    }

    public static function mustChangePassword(): bool
    {
        return self::check() && (int) ($_SESSION['user']['must_change_password'] ?? 0) === 1;
    }

    /**
     * 当前账号的改密页：后台账号进后台安全页，成员进成员中心。
     */
    public static function passwordChangeUrl(): string
    {
        return self::isAdmin() ? url('admin/security') : url('me/security');
    }

    private static function isPasswordChangeRequest(): bool
    {
        $route = is_string($_GET['r'] ?? null) ? $_GET['r'] : '';

        return in_array($route, [
            'admin/security',
            'admin/updatePassword',
            'me/security',
            'me/updatePassword',
            'auth/logout',
        ], true);
    }

    /**
     * 登录 + 权限双重要求；无权限时返回 403 页面。
     */
    public static function requirePermission(string $permission): void
    {
        self::requireLogin();

        if (self::can($permission)) {
            return;
        }

        http_response_code(403);
        render('error_403', [
            'pageTitle' => '没有访问权限 · ' . site_name(),
            'metaDescription' => '当前账号没有访问该页面的权限。',
            'activeNav' => '',
        ]);
        exit;
    }

    /**
     * 校验登录后回跳地址，只允许站内绝对路径，防开放重定向。
     */
    public static function safeNext(?string $next): string
    {
        $next = (string) $next;
        if ($next === '' || preg_match('#^/(?!/)#', $next) !== 1) {
            return '';
        }

        if (str_contains($next, "\r") || str_contains($next, "\n")) {
            return '';
        }

        return $next;
    }

    /**
     * 按角色返回登录后的默认落地页。
     */
    public static function homeForRole(string $roleCode): string
    {
        return in_array($roleCode, self::ADMIN_ROLES, true)
            ? url('admin/dashboard')
            : url('me/index');
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(mixed $token): bool
    {
        if (!is_string($token) || $token === '' || strlen($token) !== 64) {
            return false;
        }

        $expected = self::csrfToken();

        return hash_equals($expected, $token);
    }

    /**
     * 客户端 IP，仅信任 REMOTE_ADDR，不采用可伪造的 X-Forwarded-For。
     */
    public static function clientIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * IP 不落明文，使用应用密钥做 HMAC-SHA256。
     */
    public static function ipHash(): string
    {
        return hash_hmac('sha256', self::clientIp(), (string) self::config()['key']);
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    /**
     * 修改密码后刷新当前会话中的密码时间戳，并轮换会话 ID。
     * 其他设备上的旧会话会在下一次请求时因时间戳不一致被注销。
     */
    public static function refreshPasswordStamp(string $passwordChangedAt): void
    {
        if (!self::check()) {
            return;
        }

        $_SESSION['user']['password_changed_at'] = $passwordChangedAt;
        $_SESSION['user']['must_change_password'] = 0;
        $_SESSION['last_activity'] = time();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function config(): array
    {
        if (self::$config === null) {
            self::$config = require APP_ROOT . '/config/app.php';
        }

        return self::$config;
    }

    /**
     * 每次请求核对账号状态与密码时间戳：
     *   - 账号被停用或锁定 -> 立即注销当前会话；
     *   - 密码在别处被修改 -> 旧会话失效。
     */
    private static function validateSession(): void
    {
        if (!self::check()) {
            return;
        }

        $row = Db::fetchOne(
            'SELECT u.status, u.locked_until, u.password_changed_at, u.must_change_password,
                    u.display_name, r.code AS role_code, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1',
            ['id' => self::id()]
        );

        $sessionStamp = (string) ($_SESSION['user']['password_changed_at'] ?? '');
        $dbStamp = $row === null ? null : (string) ($row['password_changed_at'] ?? '');
        $locked = $row !== null
            && $row['locked_until'] !== null
            && strtotime((string) $row['locked_until']) > time();

        if ($row === null || (int) $row['status'] !== 1 || $locked || $dbStamp !== $sessionStamp) {
            unset($_SESSION['user']);
            self::$permissions = null;

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }

            return;
        }

        // 角色、显示名与强制改密标记在后台可能被管理员修改，每次请求同步到会话
        $roleChanged = (string) ($_SESSION['user']['role_code'] ?? '') !== (string) $row['role_code'];
        $_SESSION['user']['role_code'] = (string) $row['role_code'];
        $_SESSION['user']['role_name'] = (string) $row['role_name'];
        $_SESSION['user']['display_name'] = (string) $row['display_name'];
        $_SESSION['user']['must_change_password'] = (int) $row['must_change_password'];

        if ($roleChanged) {
            self::$permissions = null;
        }
    }

    private static function expireIdleSession(): void
    {
        if (!self::check()) {
            $_SESSION['last_activity'] = time();
            return;
        }

        $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);
        $lifetime = (int) self::config()['session_lifetime'];

        if ($lastActivity > 0 && time() - $lastActivity > $lifetime) {
            unset($_SESSION['user'], $_SESSION['permissions']);
            self::$permissions = null;
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
        }

        $_SESSION['last_activity'] = time();
    }

    private static function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    private function __construct()
    {
    }
}
