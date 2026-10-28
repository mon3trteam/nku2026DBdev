<?php
/**
 * 文件名：index.php
 * 功能：项目统一入口，接收请求并路由分发到对应控制器（本文件同时作为注释头范例）
 * 作者：黄浩天（2413421）
 * 创建日期：2026-10-29
 * 所属模块：公共框架
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));   // code/
define('APP_DEBUG', true);              // 上线前改为 false

// ---- 简易路由：/?r=控制器/动作 （后续骨架细化时可替换为 PATH_INFO 形式）----
$route  = $_GET['r'] ?? 'index/index';          // 默认前台首页
$parts  = explode('/', $route);
$ctrl   = ucfirst(strtolower($parts[0] ?? 'index'));
$action = $parts[1] ?? 'index';

$controllerClass = $ctrl . 'Controller';
$controllerFile  = APP_ROOT . '/app/controller/' . $controllerClass . '.class.php';

if (!preg_match('/^[A-Za-z]+$/', $ctrl) || !is_file($controllerFile)) {
    http_response_code(404);
    exit('404 Not Found');
}

require_once $controllerFile;
if (!class_exists($controllerClass)) {
    http_response_code(500);
    exit('Controller missing: ' . htmlspecialchars($controllerClass));
}

(new $controllerClass())->$action();
