<?php
/**
 * 文件名：AdminController.class.php
 * 功能：管理后台控制器；当前使用演示数据，后端接口接入后替换
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MemberModel.class.php';
require_once APP_ROOT . '/app/model/AnnouncementModel.class.php';

final class AdminController
{
    public function dashboard(): void
    {
        $members = MemberModel::allVisible();
        $announcements = AnnouncementModel::latest(5);

        render('admin/dashboard', [
            'pageTitle' => '数据看板 · 管理后台',
            'adminNav' => 'dashboard',
            'kpi' => [
                'members' => count($members),
                'projects' => 3,
                'pending' => 7,
                'views' => 1286,
            ],
            'trend' => [
                ['date' => '10-23', 'views' => 42],
                ['date' => '10-24', 'views' => 58],
                ['date' => '10-25', 'views' => 36],
                ['date' => '10-26', 'views' => 64],
                ['date' => '10-27', 'views' => 51],
                ['date' => '10-28', 'views' => 73],
                ['date' => '10-29', 'views' => 68],
                ['date' => '10-30', 'views' => 82],
                ['date' => '10-31', 'views' => 61],
                ['date' => '11-01', 'views' => 90],
                ['date' => '11-02', 'views' => 76],
                ['date' => '11-03', 'views' => 88],
                ['date' => '11-04', 'views' => 95],
                ['date' => '11-05', 'views' => 102],
            ],
            'skills' => [
                ['name' => 'MySQL', 'count' => 3],
                ['name' => 'PHP', 'count' => 3],
                ['name' => 'JavaScript', 'count' => 2],
                ['name' => 'UI Design', 'count' => 2],
                ['name' => 'Git', 'count' => 2],
            ],
            'messageStatus' => [
                ['label' => '待审核', 'count' => 7, 'class' => 'is-warn'],
                ['label' => '已通过', 'count' => 12, 'class' => 'is-ok'],
                ['label' => '已拒绝', 'count' => 3, 'class' => 'is-muted'],
                ['label' => '垃圾', 'count' => 2, 'class' => 'is-danger'],
            ],
            'projectStatus' => [
                ['label' => '规划中', 'count' => 1],
                ['label' => '进行中', 'count' => 1],
                ['label' => '已完成', 'count' => 1],
                ['label' => '已归档', 'count' => 0],
            ],
            'pendingMessages' => self::pendingMessages(),
            'announcements' => $announcements,
        ]);
    }

    public function members(): void
    {
        render('admin/member_list', [
            'pageTitle' => '成员管理 · 管理后台',
            'adminNav' => 'members',
            'members' => MemberModel::allVisible(),
        ]);
    }

    public function announcements(): void
    {
        render('admin/announcement_list', [
            'pageTitle' => '公告管理 · 管理后台',
            'adminNav' => 'announcements',
            'announcements' => AnnouncementModel::all(),
        ]);
    }

    public function messages(): void
    {
        render('admin/message_list', [
            'pageTitle' => '留言管理 · 管理后台',
            'adminNav' => 'messages',
            'messages' => self::pendingMessages(),
        ]);
    }

    public function projects(): void
    {
        render('admin/project_list', [
            'pageTitle' => '项目管理 · 管理后台',
            'adminNav' => 'projects',
            'projects' => MemberModel::allProjects(),
        ]);
    }

    /**
     * 留言审核队列演示数据。
     */
    private static function pendingMessages(): array
    {
        return [
            ['author' => '访客A', 'excerpt' => '首页的成员速览可以再加一个技能标签。', 'member' => '林知远', 'created_at' => '2026-11-05 09:12', 'status' => '待审核'],
            ['author' => '访客B', 'excerpt' => '请问团队文档什么时候可以下载？', 'member' => '周予安', 'created_at' => '2026-11-05 08:40', 'status' => '待审核'],
            ['author' => '访客C', 'excerpt' => '成员主页的项目卡片排版很清晰。', 'member' => '苏晚晴', 'created_at' => '2026-11-04 21:05', 'status' => '待审核'],
        ];
    }
}
