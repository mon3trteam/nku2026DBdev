<?php
/**
 * 文件名：AnalyticsModel.class.php
 * 功能：前台匿名访问统计，写入 page_views 供数据看板聚合
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 数据看板
 *
 * 说明：
 *   1) IP 与会话 ID 不落明文，均使用 APP_KEY 做 HMAC-SHA256；
 *   2) 只统计前台 GET 页面，后台、成员中心、登录页和文件下载不计入；
 *   3) 统计失败不能影响正常页面响应，调用方需要捕获异常。
 */

declare(strict_types=1);

final class AnalyticsModel
{
    private const TRACKED_CONTROLLERS = [
        'home',
        'member',
        'project',
        'announcement',
        'resource',
        'team',
        'message',
    ];

    /**
     * 记录一次前台页面访问。
     */
    public static function track(string $controller, string $action): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        $controller = strtolower($controller);
        $action = strtolower($action);

        if (!in_array($controller, self::TRACKED_CONTROLLERS, true)) {
            return;
        }

        // 下载由 resource 控制器单独处理，不计入页面访问
        if ($controller === 'resource' && $action === 'download') {
            return;
        }

        $config = Auth::config();
        $key = (string) $config['key'];
        $visitorHash = hash_hmac('sha256', Auth::ipHash() . '|' . Auth::userAgent(), $key);
        $sessionId = session_id();
        $sessionHash = $sessionId === ''
            ? null
            : hash_hmac('sha256', $sessionId, $key);

        $referrer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
        $referrer = $referrer === '' ? null : mb_substr($referrer, 0, 255, 'UTF-8');

        Db::execute(
            'INSERT INTO page_views
                (path, route_name, visitor_hash, session_hash, referrer, user_agent)
             VALUES
                (:path, :route_name, :visitor_hash, :session_hash, :referrer, :user_agent)',
            [
                'path'         => mb_substr('/' . $controller . '/' . $action, 0, 255, 'UTF-8'),
                'route_name'   => mb_substr($controller . '/' . $action, 0, 80, 'UTF-8'),
                'visitor_hash' => $visitorHash,
                'session_hash' => $sessionHash,
                'referrer'     => $referrer,
                'user_agent'   => Auth::userAgent(),
            ]
        );
    }
}
