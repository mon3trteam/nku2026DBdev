<?php
/**
 * 文件名：SiteSettingModel.class.php
 * 功能：站点键值配置的数据访问（键名、分组、类型）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 站点设置
 */

declare(strict_types=1);

final class SiteSettingModel
{
    /** @var array<string, string>|null */
    private static ?array $publicCache = null;

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
     * 前台可公开的配置键值对，单次请求内缓存，供布局页脚与 SEO 使用。
     *
     * @return array<string, string>
     */
    public static function publicValues(): array
    {
        if (self::$publicCache !== null) {
            return self::$publicCache;
        }

        $rows = Db::fetchAll(
            'SELECT setting_key, setting_value FROM site_settings WHERE is_public = 1'
        );
        $values = [];
        foreach ($rows as $row) {
            $values[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        self::$publicCache = $values;

        return $values;
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
     * 批量保存配置值。控制器已按数据库中的键名与类型校验。
     *
     * @param array<string, string> $values setting_key => setting_value
     */
    public static function updateMany(array $values, int $actorId): void
    {
        $allowed = array_fill_keys(array_column(self::all(), 'key'), true);

        Db::transaction(static function () use ($values, $actorId, $allowed): void {
            foreach ($values as $key => $value) {
                if (!isset($allowed[$key])) {
                    continue;
                }

                Db::execute(
                    'UPDATE site_settings
                     SET setting_value = :value, updated_by = :updated_by
                     WHERE setting_key = :key',
                    [
                        'value'      => $value,
                        'updated_by' => $actorId > 0 ? $actorId : null,
                        'key'        => $key,
                    ]
                );
            }
        });

        self::$publicCache = null;
    }

    /**
     * 读取单个公开配置，前台页脚/SEO 使用。
     */
    public static function get(string $key, string $default = ''): string
    {
        $values = self::publicValues();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }
}
