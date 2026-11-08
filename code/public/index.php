<?php
/**
 * 文件名：index.php
 * 功能：项目统一入口，接收请求并路由分发到对应控制器（本文件同时作为注释头范例）
 * 作者：黄浩天（2413421）
 * 创建日期：2026-10-29
 * 所属模块：公共框架
 */

declare(strict_types=1);

// 与 MySQL 会话时区保持一致，避免锁定时间、日志时间相差 8 小时
date_default_timezone_set('Asia/Shanghai');

define('APP_ROOT', dirname(__DIR__));   // code/
define('APP_DEBUG', true);              // 上线前改为 false
define('APP_BASE', '/public');          // 站点根未指向 public/ 前的访问前缀；vhost 修好后改为 ''
define('ASSET_VERSION', '20261105');

require_once APP_ROOT . '/app/helpers.php';
require_once APP_ROOT . '/app/core/Db.class.php';
require_once APP_ROOT . '/app/core/Auth.class.php';

// 会话必须在任何输出之前启动；随后初始化 CSRF token 并检查空闲超时
Auth::boot();

// ---- 简易路由：index.php?r=控制器/动作&参数=值 ----
// 约定：控制器文件 app/controller/{Xxx}Controller.class.php，动作即方法名。
$route  = $_GET['r'] ?? 'home/index';   // 默认前台首页
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
        'pageTitle' => '服务暂时不可用 · 数聚 TeamHub',
        'metaDescription' => '服务暂时不可用，请稍后重试。',
    ]);
}
