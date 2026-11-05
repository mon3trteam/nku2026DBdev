<?php
/**
 * 文件名：MemberController.class.php
 * 功能：成员列表与成员主页控制器，支持按技能筛选和按 slug 查看详情
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 成员
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MemberModel.class.php';

final class MemberController
{
    public function index(): void
    {
        $skill = trim((string) ($_GET['skill'] ?? ''));
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $sort = (string) ($_GET['sort'] ?? '');
        $sort = in_array($sort, ['name', 'join_date'], true) ? $sort : '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 3;

        $result = MemberModel::search($keyword, $skill, $sort, $page, $perPage);

        render('public/member_list', [
            'pageTitle' => '成员列表 · 数聚 TeamHub',
            'metaDescription' => '浏览数聚 TeamHub 的成员，按技能筛选、按姓名搜索，进入成员主页查看项目与留言。',
            'activeNav' => 'member',
            'members' => $result['items'],
            'skills' => MemberModel::skillOptions(),
            'activeSkill' => $skill,
            'keyword' => $keyword,
            'sort' => $sort,
            'pagination' => $result,
        ]);
    }

    public function show(): void
    {
        $slug = trim((string) ($_GET['slug'] ?? ''));
        $member = MemberModel::findBySlug($slug);

        if ($member === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '成员不存在']);
            return;
        }

        render('public/member_show', [
            'pageTitle' => $member['name'] . ' · 成员主页',
            'metaDescription' => $member['name'] . '，' . $member['role_title'] . '。' . $member['tagline'],
            'ogType' => 'profile',
            'activeNav' => 'member',
            'member' => $member,
            'projects' => MemberModel::projectsOf($member['slug']),
            'messages' => MemberModel::messagesOf($member['slug']),
            'toastMessage' => ($_GET['sent'] ?? '') === '1' ? '留言已提交，审核通过后公开。' : '',
            'toastType' => 'success',
        ]);
    }
}
