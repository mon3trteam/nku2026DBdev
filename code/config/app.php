<?php
/**
 * 文件名：app.php
 * 功能：应用级配置（会话、CSRF、IP 哈希密钥）
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：配置 / 应用
 *
 * 说明：
 *   1) APP_KEY 用于 HMAC-SHA256 计算 IP 哈希；生产环境必须通过环境变量覆盖。
 *   2) 默认值只用于本地课程演示，不得用于公网部署。
 */

declare(strict_types=1);

return [
    'name'             => '数聚 TeamHub',
    'key'              => getenv('APP_KEY') ?: 'teamhub-local-demo-key-change-me',
    'session_name'     => 'teamhub_session',
    'session_lifetime' => (int) (getenv('SESSION_LIFETIME') ?: 1800),
    'upload_max_mb'    => (int) (getenv('UPLOAD_MAX_MB') ?: 10),
];
