<?php
/**
 * 文件名：TeamModel.class.php
 * 功能：团队资料、里程碑、价值观与能力矩阵的数据访问
 * 作者：杨斯源（学号待填）
 * 修改：后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 团队介绍
 */

declare(strict_types=1);

final class TeamModel
{
    /**
     * 团队资料单例（id=1）。
     */
    public static function profile(): array
    {
        $row = Db::fetchOne('SELECT * FROM team_profile WHERE id = 1 LIMIT 1');

        if ($row === null) {
            return [
                'name' => '团队主页',
                'english_name' => '',
                'slogan' => '',
                'short_intro' => '',
                'story' => '',
                'founded_on' => '',
                'location' => '',
                'email' => '',
                'phone' => '',
                'updated_at' => '',
            ];
        }

        $row['phone'] = (string) ($row['phone'] ?? '');
        $row['updated_at'] = format_datetime((string) ($row['updated_at'] ?? ''));

        return $row;
    }

    /**
     * 公开时间线，按年份与排序号升序。
     */
    public static function milestones(): array
    {
        $rows = Db::fetchAll(
            'SELECT year, title, description
             FROM milestones
             WHERE is_visible = 1
             ORDER BY year ASC, sort_order ASC, id ASC'
        );

        return array_map(
            static fn (array $row): array => [
                'year'        => (string) $row['year'],
                'title'       => (string) $row['title'],
                'description' => (string) ($row['description'] ?? ''),
            ],
            $rows
        );
    }

    /**
     * 团队价值观（team_profile.values_json）。
     */
    public static function values(): array
    {
        $raw = Db::fetchValue('SELECT values_json FROM team_profile WHERE id = 1 LIMIT 1');
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $values = [];
        foreach ($decoded as $item) {
            if (!is_array($item) || !isset($item['title'])) {
                continue;
            }
            $values[] = [
                'title' => (string) $item['title'],
                'description' => (string) ($item['description'] ?? ''),
            ];
        }

        return $values;
    }

    /**
     * 能力矩阵：按技能分类聚合成员数量与平均熟练度。
     */
    public static function skillGroups(): array
    {
        $rows = Db::fetchAll(
            'SELECT s.name, s.category,
                    COUNT(ms.member_id) AS member_count,
                    ROUND(AVG(ms.proficiency), 1) AS avg_proficiency
             FROM skills s
             LEFT JOIN member_skills ms ON ms.skill_id = s.id
             GROUP BY s.id, s.name, s.category
             ORDER BY s.sort_order ASC, s.name ASC'
        );

        $groups = [
            '技术开发' => [],
            '设计表达' => [],
            '协作工具' => [],
        ];

        foreach ($rows as $row) {
            $group = match ((string) $row['category']) {
                'design', 'soft' => '设计表达',
                'tool'          => '协作工具',
                default         => '技术开发',
            };

            $groups[$group][] = [
                'name'  => (string) $row['name'],
                'count' => (int) $row['member_count'],
                'avg'   => (float) $row['avg_proficiency'],
            ];
        }

        $result = [];
        foreach ($groups as $label => $items) {
            if ($items !== []) {
                $result[] = ['category' => $label, 'items' => $items];
            }
        }

        return $result;
    }
}
