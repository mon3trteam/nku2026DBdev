<?php
/**
 * 文件名：SystemController.class.php
 * 功能：状态页控制器（403 / 500 / 维护中），用于演示与联调
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：错误页
 */

declare(strict_types=1);

final class SystemController
{
    public function forbidden(): void
    {
        http_response_code(403);
        render('error_403', [
            'pageTitle' => '无权限 · ' . site_name(),
            'metaDescription' => '当前账号没有访问该页面的权限。',
        ]);
    }

    public function serverError(): void
    {
        http_response_code(500);
        render('error_500', [
            'pageTitle' => '服务暂时不可用 · ' . site_name(),
            'metaDescription' => '服务暂时不可用，请稍后重试。',
        ]);
    }

    public function maintenance(): void
    {
        http_response_code(503);
        render('error_maintenance', [
            'pageTitle' => '维护中 · ' . site_name(),
            'metaDescription' => '站点正在维护，请稍后访问。',
        ]);
    }

    /**
     * 健康检查：供 docker healthcheck / 反向代理探活使用。
     * 数据库不可用时返回 503，不输出连接串、账号等敏感信息。
     */
    public function healthz(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        try {
            Db::fetchValue('SELECT 1');
            http_response_code(200);
            $payload = ['status' => 'ok', 'database' => 'ok', 'time' => date('c')];
        } catch (Throwable $e) {
            error_log('[healthz] ' . $e->getMessage());
            http_response_code(503);
            $payload = ['status' => 'degraded', 'database' => 'error', 'time' => date('c')];
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
