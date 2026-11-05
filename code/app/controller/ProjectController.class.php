<?php
/**
 * 文件名：ProjectController.class.php
 * 功能：项目列表与项目详情控制器
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 项目
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MemberModel.class.php';

final class ProjectController
{
    public function index(): void
    {
        $projects = MemberModel::allProjects();

        render('public/project_list', [
            'pageTitle' => '项目作品 · 数聚 TeamHub',
            'metaDescription' => '数聚 TeamHub 的项目作品：团队主页、校园二手书平台与课程作业下载中心。',
            'activeNav' => 'project',
            'projects' => $projects,
        ]);
    }

    public function show(): void
    {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        $project = MemberModel::findProjectBySlug($slug);

        if ($project === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '项目不存在']);
            return;
        }

        $related = array_values(array_filter(
            MemberModel::allProjects(),
            static fn (array $item): bool => $item['slug'] !== $project['slug']
        ));

        render('public/project_show', [
            'pageTitle' => $project['title'] . ' · 项目',
            'metaDescription' => $project['summary'],
            'ogType' => 'article',
            'activeNav' => 'project',
            'project' => $project,
            'related' => array_slice($related, 0, 2),
        ]);
    }
}
