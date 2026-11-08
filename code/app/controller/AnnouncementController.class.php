<?php
/**
 * 文件名：AnnouncementController.class.php
 * 功能：公告列表与公告详情控制器
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 公告
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/AnnouncementModel.class.php';

final class AnnouncementController
{
    public function index(): void
    {
        $categories = AnnouncementModel::categories();
        $category = (string) ($_GET['category'] ?? '');
        $category = array_key_exists($category, $categories) ? $category : '';
        $announcements = AnnouncementModel::all($category);

        render('public/announcement_list', [
            'pageTitle' => '公告中心 · ' . site_name(),
            'metaDescription' => site_name() . ' 的团队动态、项目通知与功能预告。置顶公告优先展示。',
            'activeNav' => 'announcement',
            'announcements' => $announcements,
            'categories' => $categories,
            'activeCategory' => $category,
        ]);
    }

    public function show(): void
    {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        $announcement = AnnouncementModel::findBySlug($slug);

        if ($announcement === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '公告不存在']);
            return;
        }

        render('public/announcement_show', [
            'pageTitle' => $announcement['title'] . ' · 公告',
            'metaDescription' => $announcement['summary'],
            'ogType' => 'article',
            'activeNav' => 'announcement',
            'announcement' => $announcement,
            'related' => array_values(array_filter(
                AnnouncementModel::all(),
                static fn (array $item): bool => $item['slug'] !== $announcement['slug']
            )),
        ]);
    }
}
