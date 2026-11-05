<?php
/**
 * 文件名：AnnouncementModel.class.php
 * 功能：公告的演示数据模型；后端接入数据库后替换方法内部实现即可
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 公告
 */

declare(strict_types=1);

final class AnnouncementModel
{
    private static array $announcements = [
        [
            'id' => 1,
            'slug' => 'site-preview',
            'title' => '团队主页初版界面开放预览',
            'summary' => '首页、团队介绍、成员列表与成员主页已经完成第一版，欢迎提出意见。',
            'content_html' => '<p>团队主页的第一版界面已经完成，包含首页、团队介绍、成员列表与成员主页。</p>'
                . '<p>当前使用演示数据，后端接口接入后会替换为真实内容。欢迎在留言板留下你的建议。</p>',
            'content' => "团队主页的第一版界面已经完成，包含首页、团队介绍、成员列表与成员主页。\n\n当前使用演示数据，后端接口接入后会替换为真实内容。欢迎在留言板留下你的建议。",
            'category' => 'news',
            'category_label' => '团队动态',
            'is_pinned' => 1,
            'published_at' => '2026-11-05 10:00',
            'expires_at' => null,
            'author' => '周予安',
            'status' => 'published',
            'status_label' => '已发布',
        ],
        [
            'id' => 2,
            'slug' => 'design-review',
            'title' => '需求与设计文档完成评审',
            'summary' => '32 项功能、21 张表与页面线框已定稿，进入开发阶段。',
            'content_html' => '<p>需求文档与设计文档已完成内部评审。</p>'
                . '<p>下一步按 P0 优先级推进：公共门户、成员中心与管理后台。</p>',
            'content' => "需求文档与设计文档已完成内部评审。\n\n下一步按 P0 优先级推进：公共门户、成员中心与管理后台。",
            'category' => 'notice',
            'category_label' => '项目通知',
            'is_pinned' => 0,
            'published_at' => '2026-10-28 18:30',
            'expires_at' => null,
            'author' => '林知远',
            'status' => 'published',
            'status_label' => '已发布',
        ],
        [
            'id' => 3,
            'slug' => 'download-center',
            'title' => '课程作业下载中心即将上线',
            'summary' => '团队文档与个人作业将按分类提供下载，并记录下载次数。',
            'content_html' => '<p>下载中心将读取 <code>data/team</code> 与 <code>data/personal</code> 目录，'
                . '按团队文档与个人作业分类展示。</p><p>资源管理页面完成后即可在后台上传与公开。</p>',
            'content' => "下载中心将读取 data/team 与 data/personal 目录，按团队文档与个人作业分类展示。\n\n资源管理页面完成后即可在后台上传与公开。",
            'category' => 'activity',
            'category_label' => '功能预告',
            'is_pinned' => 0,
            'published_at' => '2026-10-30 14:20',
            'expires_at' => null,
            'author' => '陈一鸣',
            'status' => 'published',
            'status_label' => '已发布',
        ],
    ];

    public static function all(string $category = ''): array
    {
        $list = self::$announcements;

        if ($category !== '') {
            $list = array_values(array_filter(
                $list,
                static fn (array $item): bool => $item['category'] === $category
            ));
        }

        usort($list, static function (array $a, array $b): int {
            if ($a['is_pinned'] !== $b['is_pinned']) {
                return $b['is_pinned'] <=> $a['is_pinned'];
            }

            return strcmp($b['published_at'], $a['published_at']);
        });

        return $list;
    }

    public static function latest(int $limit = 3): array
    {
        return array_slice(self::all(), 0, $limit);
    }

    public static function findBySlug(string $slug): ?array
    {
        foreach (self::$announcements as $announcement) {
            if ($announcement['slug'] === $slug) {
                return $announcement;
            }
        }

        return null;
    }

    public static function findById(int $id): ?array
    {
        foreach (self::$announcements as $announcement) {
            if ((int) $announcement['id'] === $id) {
                return $announcement;
            }
        }

        return null;
    }

    public static function categories(): array
    {
        $categories = [];
        foreach (self::$announcements as $announcement) {
            $categories[$announcement['category']] = $announcement['category_label'];
        }

        return $categories;
    }
}
