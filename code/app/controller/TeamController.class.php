<?php
/**
 * 文件名：TeamController.class.php
 * 功能：团队介绍控制器，渲染团队故事、时间线、价值观、能力矩阵与成员网格
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 团队介绍
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/TeamModel.class.php';
require_once APP_ROOT . '/app/model/MemberModel.class.php';

final class TeamController
{
    public function index(): void
    {
        $team = TeamModel::profile();

        render('public/team_index', [
            'pageTitle' => '团队介绍 · ' . $team['name'],
            'metaDescription' => '了解' . $team['name'] . '的团队故事、成长时间线、价值观、能力矩阵与成员。',
            'activeNav' => 'team',
            'team' => $team,
            'milestones' => TeamModel::milestones(),
            'values' => TeamModel::values(),
            'skillGroups' => TeamModel::skillGroups(),
            'members' => MemberModel::allVisible(),
        ]);
    }
}
