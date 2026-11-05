<?php
/**
 * 文件名：ResourceModel.class.php
 * 功能：资源下载演示数据；后端接入 resources/media 表后替换方法实现
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 资源
 */

declare(strict_types=1);

final class ResourceModel
{
    private static array $categories = [
        'team' => '团队文档',
        'personal' => '个人作业',
        'design' => '设计资料',
        'doc' => '部署资料',
        'other' => '其他',
    ];

    private static array $resources = [
        [
            'id' => 1,
            'title' => '团队需求文档',
            'category' => 'team',
            'description' => '功能需求、用例、业务规则与验收标准。',
            'file_name' => 'M3_需求文档.pdf',
            'file_type' => 'PDF',
            'size_text' => '1.8 MB',
            'updated_at' => '2026-10-28',
            'download_count' => 36,
            'is_public' => 1,
        ],
        [
            'id' => 2,
            'title' => '团队设计文档',
            'category' => 'team',
            'description' => '架构、21 张表的数据字典、接口与页面设计。',
            'file_name' => 'M3_设计文档.pdf',
            'file_type' => 'PDF',
            'size_text' => '3.2 MB',
            'updated_at' => '2026-10-28',
            'download_count' => 41,
            'is_public' => 1,
        ],
        [
            'id' => 3,
            'title' => '部署文档',
            'category' => 'doc',
            'description' => 'Docker Compose 一键部署、备份与还原流程。',
            'file_name' => 'M3_部署文档.pdf',
            'file_type' => 'PDF',
            'size_text' => '980 KB',
            'updated_at' => '2026-11-02',
            'download_count' => 18,
            'is_public' => 1,
        ],
        [
            'id' => 4,
            'title' => '个人作业 1（学号_姓名）',
            'category' => 'personal',
            'description' => 'Web 前端初探：抓包、jQuery 事件与浏览器插件。',
            'file_name' => '作业1(学号_姓名).zip',
            'file_type' => 'ZIP',
            'size_text' => '4.6 MB',
            'updated_at' => '2026-10-20',
            'download_count' => 12,
            'is_public' => 1,
        ],
        [
            'id' => 5,
            'title' => '个人作业 2（学号_姓名）',
            'category' => 'personal',
            'description' => '页面设计实践与响应式布局。',
            'file_name' => '作业2(学号_姓名).zip',
            'file_type' => 'ZIP',
            'size_text' => '3.1 MB',
            'updated_at' => '2026-10-27',
            'download_count' => 9,
            'is_public' => 1,
        ],
        [
            'id' => 6,
            'title' => '个人作业 3（学号_姓名）',
            'category' => 'personal',
            'description' => 'WordPress 建站实践与内容运营。',
            'file_name' => '作业3(学号_姓名).zip',
            'file_type' => 'ZIP',
            'size_text' => '5.4 MB',
            'updated_at' => '2026-11-01',
            'download_count' => 7,
            'is_public' => 1,
        ],
        [
            'id' => 7,
            'title' => 'E-R 图',
            'category' => 'design',
            'description' => '团队主页核心实体关系图。',
            'file_name' => 'M3_ER图.pdf',
            'file_type' => 'PDF',
            'size_text' => '620 KB',
            'updated_at' => '2026-10-28',
            'download_count' => 15,
            'is_public' => 1,
        ],
        [
            'id' => 8,
            'title' => '数据字典',
            'category' => 'design',
            'description' => '21 张表的字段、类型、索引与约束。',
            'file_name' => 'M3_数据字典.pdf',
            'file_type' => 'PDF',
            'size_text' => '1.1 MB',
            'updated_at' => '2026-10-28',
            'download_count' => 22,
            'is_public' => 1,
        ],
        [
            'id' => 9,
            'title' => '演示环境说明',
            'category' => 'other',
            'description' => '本地 Docker 环境、初始账号与演示路径。',
            'file_name' => 'M3_演示说明.pdf',
            'file_type' => 'PDF',
            'size_text' => '320 KB',
            'updated_at' => '2026-11-03',
            'download_count' => 5,
            'is_public' => 1,
        ],
    ];

    public static function categories(): array
    {
        return self::$categories;
    }

    public static function all(string $category = ''): array
    {
        if ($category === '' || !array_key_exists($category, self::$categories)) {
            return self::$resources;
        }

        return array_values(array_filter(
            self::$resources,
            static fn (array $resource): bool => $resource['category'] === $category
        ));
    }

    public static function grouped(): array
    {
        $groups = [];
        foreach (self::$resources as $resource) {
            $groups[$resource['category']][] = $resource;
        }

        return $groups;
    }
}
