<?php
/**
 * 文件名：public_header.php
 * 功能：公共门户头部布局（导航、样式、跳转链接）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共布局
 */

$pageTitle = $pageTitle ?? '数聚 TeamHub';
$activeNav = $activeNav ?? '';
$metaDescription = $metaDescription ?? '数聚 TeamHub：团队展示、成员主页、公告与课程作业下载。';
$ogType = $ogType ?? 'website';
$ogImage = $ogImage ?? asset('img/placeholder-cover.svg');
$currentUrl = (string) ($_SERVER['REQUEST_URI'] ?? (APP_BASE . '/index.php'));
$toastMessage = $toastMessage ?? '';
$toastType = $toastType ?? 'success';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <link rel="canonical" href="<?= e(absolute_url($currentUrl)) ?>">
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:url" content="<?= e(absolute_url($currentUrl)) ?>">
    <meta property="og:image" content="<?= e(absolute_url($ogImage)) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/5.3.8/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/enhancements.css')) ?>">
</head>
<body<?= $toastMessage !== '' ? ' data-toast="' . e($toastMessage) . '" data-toast-type="' . e($toastType) . '"' : '' ?>>
<a class="skip-link" href="#main">跳到主要内容</a>

<header class="site-header">
    <nav class="navbar navbar-expand-lg" aria-label="主导航">
        <div class="container">
            <a class="navbar-brand" href="<?= e(url('home/index')) ?>">
                <img src="<?= e(asset('img/logo.svg')) ?>" alt="" width="34" height="34">
                <span>数聚 TeamHub</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#siteNav" aria-controls="siteNav"
                    aria-expanded="false" aria-label="展开导航">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="siteNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?= e(is_active('home')) ?>" href="<?= e(url('home/index')) ?>">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= e(is_active('team')) ?>" href="<?= e(url('team/index')) ?>">团队介绍</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= e(is_active('member')) ?>" href="<?= e(url('member/index')) ?>">成员</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= e(is_active('project')) ?>" href="<?= e(url('project/index')) ?>">项目</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= e(is_active('announcement')) ?>" href="<?= e(url('announcement/index')) ?>">公告</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= e(is_active('resource')) ?>" href="<?= e(url('resource/index')) ?>">资源</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-primary btn-sm" href="<?= e(url('auth/login')) ?>">登录</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main id="main">
