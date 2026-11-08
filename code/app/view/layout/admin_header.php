<?php
/**
 * 文件名：admin_header.php
 * 功能：管理后台头部布局（侧栏、顶栏、面包屑）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：后台布局
 */

$pageTitle = $pageTitle ?? '管理后台';
$adminNav = $adminNav ?? 'dashboard';
$adminPageLabel = $adminPageLabel ?? null;
$toastMessage = $toastMessage ?? '';
$toastType = $toastType ?? 'success';
$siteIdentity = site_identity();
$siteName = $siteIdentity['name'];
$siteLogo = $siteIdentity['logo'];
$adminItems = [
    'dashboard' => '数据看板',
    'team' => '团队资料',
    'members' => '成员管理',
    'projects' => '项目管理',
    'announcements' => '公告管理',
    'messages' => '留言管理',
    'resources' => '资源管理',
    'users' => '用户与角色',
    'settings' => '站点设置',
    'logs' => '日志审计',
];
$adminRoutes = [
    'dashboard' => 'admin/dashboard',
    'team' => 'admin/team',
    'members' => 'admin/members',
    'projects' => 'admin/projects',
    'announcements' => 'admin/announcements',
    'messages' => 'admin/messages',
    'resources' => 'admin/resources',
    'users' => 'admin/users',
    'settings' => 'admin/settings',
    'logs' => 'admin/logs',
];
$adminPermissions = [
    'dashboard' => 'dashboard.view',
    'team' => 'team.edit',
    'members' => 'member.manage',
    'projects' => 'content.manage',
    'announcements' => 'content.manage',
    'messages' => 'message.moderate',
    'resources' => 'resource.manage',
    'users' => 'user.manage',
    'settings' => 'setting.manage',
    'logs' => 'log.view',
];
// 无权限的菜单不渲染，避免成员误点后进入 403
$adminItems = array_filter(
    $adminItems,
    static fn (string $key): bool => Auth::can($adminPermissions[$key] ?? ''),
    ARRAY_FILTER_USE_KEY
);
$currentUser = Auth::user() ?? [];
$displayName = (string) ($currentUser['display_name'] ?? '访客');
$adminPageLabel = $adminPageLabel ?? ($adminItems[$adminNav] ?? '数据看板');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/5.3.8/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/enhancements.css')) ?>">
</head>
<body class="admin-body"<?= $toastMessage !== '' ? ' data-toast="' . e($toastMessage) . '" data-toast-type="' . e($toastType) . '"' : '' ?>>
<div class="admin-shell">
    <aside class="admin-sidebar" aria-label="后台导航">
        <a class="admin-brand" href="<?= e(url('admin/dashboard')) ?>">
            <img src="<?= e(asset($siteLogo)) ?>" alt="" width="32" height="32">
            <span><?= e($siteName) ?><small>ADMIN</small></span>
        </a>
        <nav class="admin-nav">
            <?php foreach ($adminItems as $key => $label): ?>
                <?php if (isset($adminRoutes[$key])): ?>
                    <a class="admin-nav__item <?= $key === $adminNav ? 'is-active' : '' ?>"
                       href="<?= e(url($adminRoutes[$key])) ?>"
                       <?= $key === $adminNav ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php else: ?>
                    <span class="admin-nav__item is-disabled" title="模块接入后开放"><?= e($label) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <a class="admin-back" href="<?= e(url('home/index')) ?>">← 返回前台</a>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div>
                <p class="admin-breadcrumb">管理后台 / <?= e($adminPageLabel) ?></p>
                <h1 class="admin-page-title"><?= e($adminPageLabel) ?></h1>
            </div>
            <div class="admin-user">
                <span class="admin-user__avatar" aria-hidden="true"><?= e(mb_substr($displayName, 0, 1, 'UTF-8')) ?></span>
                <span><?= e($displayName) ?></span>
                <a class="admin-user__logout ms-2" href="<?= e(url('admin/security')) ?>">账号安全</a>
                <a class="admin-user__logout ms-2" href="<?= e(url('auth/logout')) ?>">退出</a>
            </div>
        </header>
        <div class="admin-content">
