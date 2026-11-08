<?php
/**
 * 文件名：database.php
 * 功能：数据库连接配置；优先读取环境变量，默认值与 docker-compose.yml 一致
 * 作者：（后端负责人 学号 姓名）
 * 创建日期：2026-11-08
 * 所属模块：配置 / 数据库
 *
 * 说明：
 *   1) 生产环境请通过环境变量或 code/.env 覆盖，禁止把真实口令提交到 Git。
 *   2) 同时兼容设计文档中的 DB_DATABASE/DB_USERNAME/DB_PASSWORD
 *      与简写 DB_NAME/DB_USER/DB_PASS，便于 phpStudy/XAMPP 手动部署。
 */

declare(strict_types=1);

return [
    'host'     => getenv('DB_HOST') ?: 'db',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'team_home'),
    'username' => getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'team'),
    'password' => getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: 'team123456'),
    'charset'  => 'utf8mb4',
    'timezone' => '+08:00',
];
