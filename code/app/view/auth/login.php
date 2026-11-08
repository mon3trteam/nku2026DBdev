<?php
/**
 * 文件名：login.php
 * 功能：成员登录页（居中卡片、显示密码、错误/锁定/限流状态、Toast）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：认证
 */

$next = $next ?? '';
$state = $state ?? '';
$stateType = $stateType ?? '';
$stateMessage = $stateMessage ?? '';
$disabled = $disabled ?? false;
$oldLogin = $oldLogin ?? '';
$siteIdentity = site_identity();
$siteName = $siteIdentity['name'];
$siteLogo = $siteIdentity['logo'];
$pageTitle = $pageTitle ?? ('成员登录 · ' . $siteName);
$metaDescription = $metaDescription ?? ($siteName . ' 成员登录入口。');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/5.3.8/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/enhancements.css')) ?>">
</head>
<body class="auth-body"<?= $state === 'logout' ? ' data-toast="已安全退出" data-toast-type="success"' : '' ?>>
<main class="auth-shell">
    <a class="auth-brand" href="<?= e(url('home/index')) ?>">
        <img src="<?= e(asset($siteLogo)) ?>" alt="" width="40" height="40">
        <span><?= e($siteName) ?></span>
    </a>

    <section class="auth-card" aria-labelledby="login-title">
        <p class="eyebrow">Member access</p>
        <h1 id="login-title" class="auth-card__title">成员登录</h1>
        <p class="auth-card__lead">使用管理员创建的账号登录。拿到临时密码后请尽快修改。</p>

        <?php if ($stateMessage !== ''): ?>
            <div class="alert alert-<?= e($stateType) ?>" role="alert"><?= e($stateMessage) ?></div>
        <?php else: ?>
            <div class="alert alert-info" role="note">
                请使用管理员创建的账号登录；连续 5 次密码错误将锁定 15 分钟。
            </div>
        <?php endif; ?>

        <form action="<?= e(url('auth/login')) ?>" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">

            <div class="mb-3">
                <label class="form-label" for="login">用户名或邮箱</label>
                <input class="form-control <?= $state === 'error' ? 'is-invalid' : '' ?>"
                       id="login" name="login" type="text" autocomplete="username"
                       required maxlength="50" placeholder="请输入用户名或邮箱" autofocus
                       value="<?= e($oldLogin) ?>"
                       <?= $state === 'error' ? 'aria-describedby="login-error"' : '' ?>>
                <?php if ($state === 'error'): ?>
                    <div class="invalid-feedback" id="login-error">用户名或密码不正确。</div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">密码</label>
                <div class="password-field">
                    <input class="form-control <?= $state === 'error' ? 'is-invalid' : '' ?>"
                           id="password" name="password" type="password"
                           autocomplete="current-password" required minlength="10" placeholder="请输入密码"
                           <?= $state === 'error' ? 'aria-describedby="password-error"' : '' ?>>
                    <button class="password-toggle" type="button" data-toggle-password="#password"
                            aria-label="显示密码">显示</button>
                </div>
                <?php if ($state === 'error'): ?>
                    <div class="invalid-feedback d-block" id="password-error">请检查密码后重试。</div>
                <?php endif; ?>
            </div>

            <button class="btn btn-primary w-100" type="submit" <?= $disabled ? 'disabled' : '' ?>>
                <?= $disabled ? '暂时无法登录' : '登录' ?>
            </button>
        </form>

        <p class="auth-help">忘记密码请联系管理员重置临时密码。本系统不开放公开注册，登录状态 30 分钟无操作后自动失效。</p>
    </section>

    <p class="auth-back"><a href="<?= e(url('home/index')) ?>">← 返回首页</a></p>
</main>

<?php require APP_ROOT . '/app/view/partial/toast.php'; ?>
<script src="<?= e(asset('js/common.js')) ?>" defer></script>
</body>
</html>
