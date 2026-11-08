<?php
/**
 * 文件名：TeamModel.class.php
 * 功能：团队资料、里程碑、价值观与能力矩阵的数据访问
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 团队介绍
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MediaModel.class.php';

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
                'logo_media_id' => 0,
                'cover_media_id' => 0,
                'logo' => 'img/logo.svg',
                'cover' => 'img/placeholder-cover.svg',
            ];
        }

        $row['phone'] = (string) ($row['phone'] ?? '');
        $row['updated_at'] = format_datetime((string) ($row['updated_at'] ?? ''));
        $row['logo_media_id'] = isset($row['logo_media_id']) ? (int) $row['logo_media_id'] : 0;
        $row['cover_media_id'] = isset($row['cover_media_id']) ? (int) $row['cover_media_id'] : 0;
        $row['logo'] = MediaModel::pathForId($row['logo_media_id']) ?? 'img/logo.svg';
        $row['cover'] = MediaModel::pathForId($row['cover_media_id']) ?? 'img/placeholder-cover.svg';

        return $row;
    }

    /**
     * 保存团队资料单例。空字符串统一写 NULL，日期由控制器先校验。
     *
     * @param array{name: string, english_name: string, slogan: string, short_intro: string,
     *              story: string, founded_on: ?string, location: string, email: string, phone: string,
     *              values?: array<int, array{title: string, description: string}>} $data
     */
    public static function updateProfile(array $data, int $actorId): void
    {
        $nullable = static fn (string $value): ?string => $value === '' ? null : $value;
        $values = $data['values'] ?? [];
        $valuesJson = $values === []
            ? null
            : json_encode(array_values($values), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Db::execute(
            'UPDATE team_profile
             SET name = :name,
                 english_name = :english_name,
                 slogan = :slogan,
                 short_intro = :short_intro,
                 story = :story,
                 founded_on = :founded_on,
                 location = :location,
                 email = :email,
                 phone = :phone,
                 values_json = :values_json,
                 logo_media_id = :logo_media_id,
                 cover_media_id = :cover_media_id,
                 updated_by = :updated_by
             WHERE id = 1',
            [
                'name'         => $data['name'],
                'english_name' => $nullable($data['english_name']),
                'slogan'       => $nullable($data['slogan']),
                'short_intro'  => $nullable($data['short_intro']),
                'story'        => $nullable($data['story']),
                'founded_on'   => $data['founded_on'],
                'location'     => $nullable($data['location']),
                'email'        => $nullable($data['email']),
                'phone'        => $nullable($data['phone']),
                'values_json'  => $valuesJson,
                'logo_media_id' => !empty($data['logo_media_id']) ? (int) $data['logo_media_id'] : null,
                'cover_media_id' => !empty($data['cover_media_id']) ? (int) $data['cover_media_id'] : null,
                'updated_by'   => $actorId > 0 ? $actorId : null,
            ]
        );
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
