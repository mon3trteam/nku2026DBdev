<?php
/**
 * 文件名：LogModel.class.php
 * 功能：登录日志与操作审计的演示数据
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 日志审计
 */

declare(strict_types=1);

final class LogModel
{
    public static function loginLogs(): array
    {
        return [
            ['time' => '2026-11-05 09:12', 'username' => 'admin', 'success' => 1, 'reason' => '', 'ip_hash' => 'a1b2c3d4', 'ua' => 'Edge / Windows 11'],
            ['time' => '2026-11-04 21:40', 'username' => 'lin-zhiyuan', 'success' => 1, 'reason' => '', 'ip_hash' => 'a1b2c3d4', 'ua' => 'Edge / Windows 11'],
            ['time' => '2026-11-04 21:39', 'username' => 'lin-zhiyuan', 'success' => 0, 'reason' => 'wrong_password', 'ip_hash' => 'a1b2c3d4', 'ua' => 'Edge / Windows 11'],
            ['time' => '2026-11-03 18:05', 'username' => 'su-wanqing', 'success' => 1, 'reason' => '', 'ip_hash' => '9f8e7d6c', 'ua' => 'Firefox / Windows 11'],
            ['time' => '2026-11-02 10:22', 'username' => 'unknown', 'success' => 0, 'reason' => 'not_found', 'ip_hash' => '9f8e7d6c', 'ua' => 'Firefox / Windows 11'],
        ];
    }

    public static function auditLogs(): array
    {
        return [
            ['time' => '2026-11-05 10:20', 'actor' => 'admin', 'action' => 'publish', 'entity' => 'announcement:1', 'summary' => '发布公告「团队主页初版界面开放预览」'],
            ['time' => '2026-11-05 09:45', 'actor' => 'editor', 'action' => 'update', 'entity' => 'member:2', 'summary' => '更新成员资料（技能标签）'],
            ['time' => '2026-11-04 20:10', 'actor' => 'admin', 'action' => 'moderate', 'entity' => 'message:3', 'summary' => '通过留言并回复'],
            ['time' => '2026-11-04 16:32', 'actor' => 'editor', 'action' => 'create', 'entity' => 'announcement:4', 'summary' => '新建公告草稿'],
            ['time' => '2026-11-03 11:08', 'actor' => 'admin', 'action' => 'setting', 'entity' => 'site_settings:seo', 'summary' => '更新站点 SEO 描述'],
        ];
    }
}
