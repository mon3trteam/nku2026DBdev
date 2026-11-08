<?php
/**
 * 文件名：SiteSettingModel.class.php
 * 功能：站点键值配置的数据访问（键名、分组、类型）
 * 作者：杨斯源（学号待填）
 * 修改：后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 站点设置
 */

declare(strict_types=1);

final class SiteSettingModel
{
    private const GROUPS = [
        'general' => '常规',
        'seo'     => 'SEO',
        'footer'  => '页脚',
        'contact' => '联系',
    ];

    /**
     * 全部配置，按分组顺序排列。
     */
    public static function all(): array
    {
        $rows = Db::fetchAll(
            "SELECT setting_key, setting_value, value_type, group_name, label, is_public
             FROM site_settings
             ORDER BY FIELD(group_name, 'general', 'seo', 'footer', 'contact'), id ASC"
        );

        return array_map(
            static fn (array $row): array => [
                'key'       => (string) $row['setting_key'],
                'label'     => (string) ($row['label'] ?? $row['setting_key']),
                'value'     => (string) ($row['setting_value'] ?? ''),
                'type'      => (string) $row['value_type'],
                'group'     => (string) $row['group_name'],
                'is_public' => (int) $row['is_public'],
            ],
            $rows
        );
    }

    public static function groups(): array
    {
        return self::GROUPS;
    }

    /**
     * 按分组返回，供后台表单分块渲染。
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $setting) {
            $grouped[$setting['group']][] = $setting;
        }

        return $grouped;
    }

    /**
     * 读取单个公开配置，前台页脚/SEO 使用。
     */
    public static function get(string $key, string $default = ''): string
    {
        $value = Db::fetchValue(
            'SELECT setting_value FROM site_settings WHERE setting_key = :key AND is_public = 1 LIMIT 1',
            ['key' => $key]
        );

        return $value === null ? $default : (string) $value;
    }
}
