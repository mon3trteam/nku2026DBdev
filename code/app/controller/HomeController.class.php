<?php
/**
 * 文件名：HomeController.class.php
 * 功能：首页控制器，聚合团队、精选项目、最新公告与成员速览
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 首页
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/TeamModel.class.php';
require_once APP_ROOT . '/app/model/MemberModel.class.php';
require_once APP_ROOT . '/app/model/AnnouncementModel.class.php';

final class HomeController
{
    public function index(): void
    {
        $team = TeamModel::profile();
        $members = MemberModel::featured(4);
        $projects = MemberModel::featuredProjects(2);
        $announcements = AnnouncementModel::latest(3);

        $pageTitle = (string) $team['name'];
        if (trim((string) ($team['slogan'] ?? '')) !== '') {
            $pageTitle .= ' · ' . $team['slogan'];
        }
        $metaDescription = trim((string) ($team['short_intro'] ?? '')) !== ''
            ? $team['short_intro'] . ' 查看团队成员、精选项目、最新公告与课程作业下载。'
            : $team['name'] . '：查看团队成员、精选项目、最新公告与课程作业下载。';

        render('public/home_index', [
            'pageTitle' => $pageTitle,
            'metaDescription' => $metaDescription,
            'activeNav' => 'home',
            'team' => $team,
            'stats' => [
                'members' => count(MemberModel::allVisible()),
                'projects' => MemberModel::countVisibleProjects(),
                'tables' => 21,
                'dimensions' => 3,
            ],
            'projects' => $projects,
            'announcements' => $announcements,
            'members' => $members,
        ]);
    }
}
