<?php
/**
 * 文件名：AuthController.class.php
 * 功能：登录/退出的完整认证流程（CSRF、口令校验、失败锁定、登录日志、角色跳转）
 * 作者：杨斯源（学号待填）
 * 修改：接入数据库认证（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：认证
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/UserModel.class.php';
require_once APP_ROOT . '/app/model/AuthModel.class.php';

final class AuthController
{
    /**
     * GET 渲染登录页；POST 执行认证。
     */
    public function login(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->attemptLogin();
            return;
        }

        if (Auth::check()) {
            redirect(Auth::mustChangePassword() ? Auth::passwordChangeUrl() : Auth::homeForRole(Auth::role()));
        }

        $state = (string) ($_GET['state'] ?? '');
        $messages = [
            'logout' => ['success', '已安全退出当前账号。'],
        ];

        $this->renderLogin(
            isset($messages[$state]) ? $state : '',
            $messages[$state][1] ?? '',
            '',
            Auth::safeNext($_GET['next'] ?? '')
        );
    }

    /**
     * 退出登录：清空会话并回到登录页。
     */
    public function logout(): void
    {
        Auth::logout();
        redirect(url('auth/login', ['state' => 'logout']));
    }

    private function attemptLogin(): void
    {
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $next = Auth::safeNext($_POST['next'] ?? null);

        // 1) CSRF：防止第三方站点伪造登录/写操作
        if (!Auth::verifyCsrf($_POST['_token'] ?? null)) {
            $this->renderLogin('expired', '页面已过期，请刷新后重新提交。', $login, $next, 400);
            return;
        }

        // 2) 来源 IP 限流：窗口期内失败过多直接拒绝
        if (AuthModel::recentFailuresByIp() >= AuthModel::IP_MAX_FAILURES) {
            $this->renderLogin(
                'rate',
                '操作过于频繁，请 ' . AuthModel::IP_WINDOW_MINUTES . ' 分钟后再试。',
                $login,
                $next,
                429,
                true
            );
            return;
        }

        if ($login === '' || $password === '') {
            $this->renderLogin('error', '请输入用户名和密码。', $login, $next, 422);
            return;
        }

        $user = UserModel::findForAuth($login);
        if ($user === null) {
            AuthModel::logLogin(null, $login, false, 'not_found');
            $this->renderLogin('error', '用户名或密码不正确。', $login, $next, 401);
            return;
        }

        $userId = (int) $user['id'];

        // 3) 账号状态：0 停用、2 锁定
        if ((int) $user['status'] === 0) {
            AuthModel::logLogin($userId, $login, false, 'disabled');
            $this->renderLogin('locked', '账号已停用，请联系管理员。', $login, $next, 403, true);
            return;
        }

        // 4) 未到期的锁定：不再校验密码，避免绕过锁定窗口
        $lockedUntil = $user['locked_until'] === null ? null : strtotime((string) $user['locked_until']);
        if ((int) $user['status'] === 2 || ($lockedUntil !== null && $lockedUntil > time())) {
            AuthModel::logLogin($userId, $login, false, 'locked');
            $minutes = $lockedUntil === null ? AuthModel::LOCK_MINUTES : max(1, (int) ceil(($lockedUntil - time()) / 60));
            $this->renderLogin('locked', '尝试次数过多，请在 ' . $minutes . ' 分钟后再试。', $login, $next, 423, true);
            return;
        }

        // 5) 口令校验：bcrypt 散列由 password_hash() 生成
        if (!password_verify($password, (string) $user['password_hash'])) {
            $newCount = (int) $user['failed_login_count'] + 1;
            $lockUntil = $newCount >= AuthModel::MAX_FAILURES
                ? date('Y-m-d H:i:s', time() + AuthModel::LOCK_MINUTES * 60)
                : null;

            AuthModel::registerFailure($userId, $newCount, $lockUntil);
            AuthModel::logLogin($userId, $login, false, 'wrong_password');

            if ($lockUntil !== null) {
                $this->renderLogin(
                    'locked',
                    '密码错误次数过多，账号已锁定 ' . AuthModel::LOCK_MINUTES . ' 分钟。',
                    $login,
                    $next,
                    423,
                    true
                );
                return;
            }

            $remaining = AuthModel::MAX_FAILURES - $newCount;
            $this->renderLogin(
                'error',
                '用户名或密码不正确。还可尝试 ' . $remaining . ' 次。',
                $login,
                $next,
                401
            );
            return;
        }

        // 6) 成功：写日志、清失败计数、重建会话
        AuthModel::touchSuccess($userId);
        AuthModel::logLogin($userId, $login, true, null);

        Auth::login($user);

        // 密码被管理员重置后，先强制改密，忽略回跳地址
        if (Auth::mustChangePassword()) {
            redirect(Auth::passwordChangeUrl());
        }

        redirect($next !== '' ? $next : Auth::homeForRole((string) $user['role_code']));
    }

    private function renderLogin(
        string $state,
        string $message = '',
        string $oldLogin = '',
        string $next = '',
        int $status = 200,
        bool $disabled = false
    ): void {
        $stateType = match ($state) {
            'error', 'expired' => 'danger',
            'locked', 'rate'   => 'warning',
            'logout'           => 'success',
            default            => '',
        };

        http_response_code($status);

        render('auth/login', [
            'pageTitle' => '成员登录 · ' . site_name(),
            'metaDescription' => site_name() . ' 成员登录入口。账号由管理员创建，不开放公开注册。',
            'activeNav' => '',
            'next' => $next,
            'state' => $state,
            'stateType' => $stateType,
            'stateMessage' => $message,
            'disabled' => $disabled,
            'oldLogin' => $oldLogin,
        ]);
    }
}
