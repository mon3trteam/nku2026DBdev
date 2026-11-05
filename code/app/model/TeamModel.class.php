<?php
/**
 * 文件名：TeamModel.class.php
 * 功能：团队资料、里程碑、价值观与能力矩阵的演示数据
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 团队介绍
 */

declare(strict_types=1);

final class TeamModel
{
    public static function profile(): array
    {
        return [
            'name' => '数聚 TeamHub',
            'english_name' => 'DataNova',
            'slogan' => '把团队的每一次协作，变成看得见的作品。',
            'short_intro' => '数聚团队主页 · 项目、成员与课程作业的一站式展示空间。',
            'story' => '我们来自计算机学院的不同方向，因为一门数据库课程走到一起。'
                . '有人负责数据建模，有人写后端接口，有人把信息整理成清晰的界面。'
                . '我们相信，好的团队项目不是功能的堆叠，而是让每个人都能被看见、被记录。'
                . '这个主页既是课程作业，也是我们协作方式的说明书。',
            'founded_on' => '2026-09-01',
            'location' => '天津',
            'email' => 'team@example.com',
            'phone' => '',
            'updated_at' => '2026-11-05 10:00:00',
        ];
    }

    public static function milestones(): array
    {
        return [
            ['year' => '2026.09', 'title' => '组队与选题', 'description' => '确定团队主页方向，完成需求分析与角色分工。'],
            ['year' => '2026.10', 'title' => '需求与设计定稿', 'description' => '完成 32 项功能清单、21 张表的数据字典与页面线框。'],
            ['year' => '2026.11', 'title' => '前后台开发', 'description' => '完成公共门户、成员中心与管理后台的界面与联调。'],
            ['year' => '2026.12', 'title' => '演示与交付', 'description' => '整理文档、PPT 与部署说明，完成课程演示。'],
        ];
    }

    public static function values(): array
    {
        return [
            ['title' => '共同目标', 'description' => '先把主流程做通，再谈锦上添花。'],
            ['title' => '透明协作', 'description' => '接口、分工与进度都写在文档里，不靠口头同步。'],
            ['title' => '可被验证', 'description' => '每个功能都能在页面上找到证据。'],
            ['title' => '留有余地', 'description' => '给后来的人留下能读懂的代码与注释。'],
        ];
    }

    public static function skillGroups(): array
    {
        return [
            ['category' => '技术开发', 'items' => [
                ['name' => 'PHP', 'count' => 3, 'avg' => 4.0],
                ['name' => 'JavaScript', 'count' => 2, 'avg' => 4.0],
                ['name' => 'MySQL', 'count' => 3, 'avg' => 4.3],
                ['name' => 'Docker', 'count' => 1, 'avg' => 3.0],
            ]],
            ['category' => '设计表达', 'items' => [
                ['name' => 'UI Design', 'count' => 2, 'avg' => 4.5],
                ['name' => '写作', 'count' => 1, 'avg' => 5.0],
            ]],
            ['category' => '协作工具', 'items' => [
                ['name' => 'Git', 'count' => 2, 'avg' => 3.5],
            ]],
        ];
    }
}
