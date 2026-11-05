<?php
/**
 * 文件名：helpers.php
 * 功能：视图与控制器共用的基础助手（转义、URL、静态资源、渲染、日期）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共框架
 */

declare(strict_types=1);

/**
 * HTML 转义。所有输出到页面的动态内容都必须经过它。
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * 生成站内路由 URL。以后换成路径路由时只改这里。
 */
function url(string $route, array $params = []): string
{
    $query = array_merge(['r' => $route], $params);
    return APP_BASE . '/index.php?' . http_build_query($query);
}

/**
 * 生成静态资源 URL（带版本号，避免浏览器缓存旧文件）。
 */
function asset(string $path): string
{
    return APP_BASE . '/static/' . ltrim($path, '/') . '?v=' . ASSET_VERSION;
}

/**
 * 生成绝对 URL，供 Open Graph / canonical 使用。
 */
function absolute_url(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return $scheme . '://' . $host . $path;
}

/**
 * 当前导航是否命中，返回 Bootstrap 的 active 类名。
 */
function is_active(string $prefix): string
{
    $current = (string) ($_GET['r'] ?? 'home/index');
    return str_starts_with($current, $prefix) ? 'active' : '';
}

/**
 * 渲染视图。视图文件位于 app/view/{$view}.php。
 */
function render(string $view, array $data = []): void
{
    $viewFile = APP_ROOT . '/app/view/' . $view . '.php';
    if (!is_file($viewFile)) {
        http_response_code(500);
        exit('View missing: ' . e($view));
    }

    extract($data, EXTR_SKIP);
    require $viewFile;
}

/**
 * 302 跳转并结束请求。
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * 表单回填（已转义）。
 */
function old(string $key, string $default = ''): string
{
    return e($_GET[$key] ?? $default);
}

/**
 * 日期格式化：2026-11-05 -> 2026.11.05。
 */
function format_date(?string $date, string $fallback = '—'): string
{
    if ($date === null || $date === '') {
        return $fallback;
    }

    $timestamp = strtotime($date);
    return $timestamp === false ? $fallback : date('Y.m.d', $timestamp);
}

/**
 * 截取纯文本摘要，避免卡片高度失控。
 */
function str_limit(string $text, int $limit = 60, string $end = '…'): string
{
    if (mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }

    return mb_substr($text, 0, $limit, 'UTF-8') . $end;
}
