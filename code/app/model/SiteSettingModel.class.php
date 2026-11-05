<?php
/**
 * 文件名：SiteSettingModel.class.php
 * 功能：站点设置的演示数据（键值、分组、类型）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 站点设置
 */

declare(strict_types=1);

final class SiteSettingModel
{
    private static array $groups = [
        'general' => '常规',
        'seo' => 'SEO',
        'footer' => '页脚',
        'contact' => '联系',
    ];

    private static array $settings = [
        ['key' => 'site.name', 'label' => '站点名称', 'value' => '数聚 TeamHub', 'type' => 'string', 'group' => 'general'],
        ['key' => 'site.english_name', 'label' => '英文名', 'value' => 'DataNova', 'type' => 'string', 'group' => 'general'],
        ['key' => 'general.maintenance', 'label' => '维护模式', 'value' => '0', 'type' => 'bool', 'group' => 'general'],
        ['key' => 'site.description', 'label' => 'SEO 描述', 'value' => '数聚 TeamHub：团队展示、成员主页、公告与课程作业下载。', 'type' => 'string', 'group' => 'seo'],
        ['key' => 'site.keywords', 'label' => 'SEO 关键词', 'value' => '南开大学, 数据库, 团队主页, 课程设计', 'type' => 'string', 'group' => 'seo'],
        ['key' => 'footer.copyright', 'label' => '版权文案', 'value' => '© 2026 数聚 TeamHub · 课程设计演示', 'type' => 'string', 'group' => 'footer'],
        ['key' => 'footer.icp', 'label' => '备案信息', 'value' => '', 'type' => 'string', 'group' => 'footer'],
        ['key' => 'contact.email', 'label' => '联系邮箱', 'value' => 'team@example.com', 'type' => 'string', 'group' => 'contact'],
        ['key' => 'contact.location', 'label' => '所在地', 'value' => '天津', 'type' => 'string', 'group' => 'contact'],
        ['key' => 'general.values', 'label' => '价值观（JSON）', 'value' => '[{"title":"共同目标","description":"先把主流程做通"}]', 'type' => 'json', 'group' => 'general'],
    ];

    public static function all(): array
    {
        return self::$settings;
    }

    public static function groups(): array
    {
        return self::$groups;
    }

    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::$settings as $setting) {
            $grouped[$setting['group']][] = $setting;
        }

        return $grouped;
    }
}
