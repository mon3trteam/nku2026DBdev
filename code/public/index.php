<?php
/**
 * 文件名：index.php
 * 功能：项目统一入口，接收请求并路由分发到对应控制器（本文件同时作为注释头范例）
 * 作者：黄浩天（2413421）
 * 修改：金暄琛（2411672）错误页标题接入站点品牌（2026-11-08）
 * 创建日期：2026-10-29
 * 所属模块：公共框架
 */

declare(strict_types=1);

// 与 MySQL 会话时区保持一致，避免锁定时间、日志时间相差 8 小时
date_default_timezone_set('Asia/Shanghai');

define('APP_ROOT', dirname(__DIR__));   // code/
// 生产环境默认关闭调试；本地排障可设 APP_DEBUG=true
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN));
// 站点根可能指向 public/（SCRIPT_NAME=/index.php），也可能仍指向 code/
//（SCRIPT_NAME=/public/index.php）。未显式设置 APP_BASE 时按入口路径自动判断，
// 兼容新旧两种 Apache 配置；部署在子目录时用 APP_BASE=/teamhub 覆盖。
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$detectedBase = '';
if (preg_match('#^(.*)/public/index\.php$#', $scriptName, $matches) === 1) {
    $detectedBase = $matches[1];
}
define('APP_BASE', rtrim((string) (getenv('APP_BASE') ?: $detectedBase), '/'));
define('ASSET_VERSION', '20261108');

require_once APP_ROOT . '/app/helpers.php';
require_once APP_ROOT . '/app/core/Db.class.php';
require_once APP_ROOT . '/app/core/Auth.class.php';
require_once APP_ROOT . '/app/model/AnalyticsModel.class.php';

// 会话必须在任何输出之前启动；随后初始化 CSRF token 并检查空闲超时
Auth::boot();

// ---- 简易路由：index.php?r=控制器/动作&参数=值 ----
// 约定：控制器文件 app/controller/{Xxx}Controller.class.php，动作即方法名。
$route  = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'home/index';   // 默认前台首页
$parts  = explode('/', $route);
$ctrl   = ucfirst(strtolower($parts[0] ?? 'home'));
$action = $parts[1] ?? 'index';

$controllerClass = $ctrl . 'Controller';
$controllerFile  = APP_ROOT . '/app/controller/' . $controllerClass . '.class.php';

$validCtrl   = preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $ctrl) === 1;
$validAction = preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $action) === 1;

if (!$validCtrl || !$validAction || !is_file($controllerFile)) {
    http_response_code(404);
    render('error_404', ['pageTitle' => '页面不存在']);
    exit;
}

require_once $controllerFile;
if (!class_exists($controllerClass) || !method_exists($controllerClass, $action)) {
    http_response_code(404);
    render('error_404', ['pageTitle' => '页面不存在']);
    exit;
}

// 前台页面匿名访问统计；写入失败只记日志，不影响页面响应。
try {
    AnalyticsModel::track(strtolower((string) ($parts[0] ?? 'home')), $action);
} catch (Throwable $e) {
    error_log('[analytics] ' . $e->getMessage());
}

ob_start();
try {
    (new $controllerClass())->$action();
    ob_end_flush();
} catch (Throwable $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);
    error_log(sprintf(
        '[%s] %s in %s:%d',
        $e->getMessage(),
        get_class($e),
        $e->getFile(),
        $e->getLine()
    ));

    render('error_500', [
        'pageTitle' => '服务暂时不可用 · ' . site_name(),
        'metaDescription' => '服务暂时不可用，请稍后重试。',
    ]);
}
