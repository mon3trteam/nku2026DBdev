<?php
/**
 * 文件名：me_header.php
 * 功能：成员中心头部布局（侧栏、顶栏、面包屑）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心布局
 */

$pageTitle = $pageTitle ?? '成员中心';
$metaDescription = $metaDescription ?? '数聚 TeamHub 成员中心。';
$meNav = $meNav ?? 'overview';
$member = $member ?? [];
$toastMessage = $toastMessage ?? '';
$toastType = $toastType ?? 'success';

$meItems = [
    'overview' => '概览',
    'profile' => '个人资料',
    'projects' => '我的项目',
    'security' => '账号安全',
];
$meRoutes = [
    'overview' => 'me/index',
    'profile' => 'me/profile',
    'projects' => 'me/projects',
    'security' => 'me/security',
];
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
    <link rel="stylesheet" href="<?= e(asset('css/enhancements.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/me.css')) ?>">
</head>
<body class="me-body"<?= $toastMessage !== '' ? ' data-toast="' . e($toastMessage) . '" data-toast-type="' . e($toastType) . '"' : '' ?>>
<div class="me-shell">
    <aside class="me-sidebar" aria-label="成员中心导航">
        <a class="me-brand" href="<?= e(url('home/index')) ?>">
            <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="32" height="32">
            <span>数聚 TeamHub<small>MEMBER</small></span>
        </a>

        <nav class="me-nav">
            <?php foreach ($meItems as $key => $label): ?>
                <a class="me-nav__item <?= $key === $meNav ? 'is-active' : '' ?>"
                   href="<?= e(url($meRoutes[$key])) ?>"
                   <?= $key === $meNav ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="me-sidebar__foot">
            <a href="<?= e(url('member/show', ['slug' => $member['slug'] ?? ''])) ?>">查看公开主页 ↗</a>
            <a href="<?= e(url('auth/logout')) ?>">退出登录</a>
        </div>
    </aside>

    <div class="me-main">
        <header class="me-topbar">
            <div>
                <p class="me-breadcrumb">成员中心 / <?= e($meItems[$meNav] ?? '概览') ?></p>
                <h1 class="me-page-title"><?= e($meItems[$meNav] ?? '概览') ?></h1>
            </div>
            <div class="me-user">
                <img src="<?= e(asset($member['avatar'] ?? 'img/placeholder-avatar.svg')) ?>" alt="" width="36" height="36">
                <div>
                    <strong><?= e($member['name'] ?? '成员') ?></strong>
                    <small><?= e($member['role_title'] ?? '') ?></small>
                </div>
            </div>
        </header>
        <div class="me-content">
