<?php
/**
 * 文件名：MemberModel.class.php
 * 功能：成员、技能、项目与成员留言的数据访问；对外字段保持前端契约不变
 * 作者：杨斯源（学号待填）
 * 修改：后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 成员
 */

declare(strict_types=1);

final class MemberModel
{
    private const AVATAR_FALLBACK = 'img/placeholder-avatar.svg';
    private const COVER_FALLBACK  = 'img/placeholder-cover.svg';

    /**
     * 公开成员列表（is_visible=1），按 sort_order 排序。
     */
    public static function allVisible(): array
    {
        $rows = Db::fetchAll(
            'SELECT * FROM members WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC'
        );

        return self::hydrateMembers($rows);
    }

    /**
     * 后台成员列表：包含已隐藏档案。
     */
    public static function allForAdmin(): array
    {
        $rows = Db::fetchAll('SELECT * FROM members ORDER BY sort_order ASC, id ASC');

        return self::hydrateMembers($rows);
    }

    /**
     * 首页精选成员。
     */
    public static function featured(int $limit = 4): array
    {
        $limit = max(1, min(20, $limit));
        $rows = Db::fetchAll(
            "SELECT * FROM members
             WHERE is_visible = 1 AND is_featured = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT {$limit}"
        );

        return self::hydrateMembers($rows);
    }

    /**
     * 按 slug 查找公开成员；不存在或已隐藏返回 null。
     */
    public static function findBySlug(string $slug): ?array
    {
        $row = Db::fetchOne(
            'SELECT * FROM members WHERE slug = :slug AND is_visible = 1 LIMIT 1',
            ['slug' => $slug]
        );

        if ($row === null) {
            return null;
        }

        $members = self::hydrateMembers([$row]);

        return $members[0];
    }

    /**
     * 按登录账号查绑定成员档案，供成员中心使用（不限制 is_visible）。
     */
    public static function findByUserId(int $userId): ?array
    {
        $row = Db::fetchOne(
            'SELECT * FROM members WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );

        if ($row === null) {
            return null;
        }

        $members = self::hydrateMembers([$row]);

        return $members[0];
    }

    /**
     * slug 是否已被其他成员占用（包含未公开成员，避免唯一键冲突）。
     */
    public static function slugExists(string $slug, int $excludeId = 0): bool
    {
        $count = Db::fetchValue(
            'SELECT COUNT(*) FROM members WHERE slug = :slug AND id <> :exclude_id',
            ['slug' => $slug, 'exclude_id' => $excludeId]
        );

        return (int) ($count ?? 0) > 0;
    }

    /**
     * 更新本人档案与技能关联。
     *
     * 返回 ok / conflict / missing：
     *   - conflict 表示 updated_at 与页面打开时不一致（乐观锁，HTTP 409）；
     *   - missing 表示档案已被删除。
     *
     * @param array<string, string> $data
     * @param array<string, int>    $skills 技能名 => 熟练度
     */
    public static function updateProfile(int $memberId, array $data, array $skills, string $expectedUpdatedAt): string
    {
        return Db::transaction(static function () use ($memberId, $data, $skills, $expectedUpdatedAt): string {
            $current = Db::fetchOne(
                'SELECT updated_at FROM members WHERE id = :id FOR UPDATE',
                ['id' => $memberId]
            );

            if ($current === null) {
                return 'missing';
            }

            if ($expectedUpdatedAt !== '' && (string) $current['updated_at'] !== $expectedUpdatedAt) {
                return 'conflict';
            }

            $nullable = static fn (string $value): ?string => $value === '' ? null : $value;

            Db::execute(
                'UPDATE members
                 SET name = :name,
                     english_name = :english_name,
                     slug = :slug,
                     role_title = :role_title,
                     tagline = :tagline,
                     bio = :bio,
                     email = :email,
                     github_url = :github_url,
                     website_url = :website_url,
                     location = :location,
                     profile_completeness = :profile_completeness,
                     updated_at = NOW()
                 WHERE id = :id',
                [
                    'name'          => $data['name'],
                    'english_name'  => $nullable($data['english_name']),
                    'slug'          => $data['slug'],
                    'role_title'    => $data['role_title'],
                    'tagline'       => $nullable($data['tagline']),
                    'bio'           => $nullable($data['bio']),
                    'email'         => $nullable($data['email']),
                    'github_url'    => $nullable($data['github']),
                    'website_url'   => $nullable($data['website']),
                    'location'      => $nullable($data['location']),
                    'profile_completeness' => max(0, min(100, (int) ($data['profile_completeness'] ?? 0))),
                    'id'            => $memberId,
                ]
            );

            // 先清空再写入，保证取消勾选的技能会真正删除
            Db::execute('DELETE FROM member_skills WHERE member_id = :member_id', ['member_id' => $memberId]);

            if ($skills !== []) {
                $names = array_keys($skills);
                $placeholders = implode(',', array_fill(0, count($names), '?'));
                $skillRows = Db::fetchAll(
                    "SELECT id, name FROM skills WHERE name IN ({$placeholders})",
                    $names
                );

                foreach ($skillRows as $skillRow) {
                    $proficiency = max(1, min(5, (int) ($skills[(string) $skillRow['name']] ?? 3)));
                    Db::execute(
                        'INSERT INTO member_skills (member_id, skill_id, proficiency)
                         VALUES (:member_id, :skill_id, :proficiency)',
                        [
                            'member_id'   => $memberId,
                            'skill_id'    => (int) $skillRow['id'],
                            'proficiency' => $proficiency,
                        ]
                    );
                }
            }

            return 'ok';
        });
    }

    /**
     * 技能筛选下拉选项。
     */
    public static function skillOptions(): array
    {
        $rows = Db::fetchAll('SELECT name FROM skills ORDER BY sort_order ASC, name ASC');

        return array_column($rows, 'name');
    }

    /**
     * 全局技能库（名称 + 分类），用于成员资料页的技能选择。
     */
    public static function skillCatalog(): array
    {
        $rows = Db::fetchAll(
            'SELECT name, category FROM skills ORDER BY category ASC, sort_order ASC, name ASC'
        );

        return array_map(
            static fn (array $row): array => [
                'name'     => (string) $row['name'],
                'category' => (string) $row['category'],
            ],
            $rows
        );
    }

    /**
     * 按技能名筛选公开成员。
     */
    public static function filterBySkill(string $skill): array
    {
        if ($skill === '') {
            return self::allVisible();
        }

        $rows = Db::fetchAll(
            'SELECT m.*
             FROM members m
             JOIN member_skills ms ON ms.member_id = m.id
             JOIN skills s ON s.id = ms.skill_id
             WHERE m.is_visible = 1 AND s.name = :skill
             ORDER BY m.sort_order ASC, m.id ASC',
            ['skill' => $skill]
        );

        return self::hydrateMembers($rows);
    }

    /**
     * 成员列表的搜索、筛选、排序与分页。
     *
     * @return array{items: array, total: int, page: int, per_page: int, last_page: int}
     */
    public static function search(string $keyword, string $skill, string $sort, int $page, int $perPage): array
    {
        $where  = ['m.is_visible = 1'];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(m.name LIKE ? OR m.english_name LIKE ? OR m.role_title LIKE ? OR m.tagline LIKE ?)';
            $like = '%' . $keyword . '%';
            array_push($params, $like, $like, $like, $like);
        }

        if ($skill !== '') {
            $where[] = 'EXISTS (
                SELECT 1
                FROM member_skills ms
                JOIN skills s ON s.id = ms.skill_id
                WHERE ms.member_id = m.id AND s.name = ?
            )';
            $params[] = $skill;
        }

        $sort = in_array($sort, ['name', 'join_date'], true) ? $sort : '';
        $orderBy = match ($sort) {
            'name'      => 'm.name ASC',
            'join_date' => 'm.join_date DESC',
            default     => 'm.sort_order ASC, m.id ASC',
        };

        $whereSql = implode(' AND ', $where);
        $total = (int) Db::fetchValue("SELECT COUNT(*) FROM members m WHERE {$whereSql}", $params);

        $perPage = max(1, min(100, $perPage));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $rows = Db::fetchAll(
            "SELECT m.* FROM members m
             WHERE {$whereSql}
             ORDER BY {$orderBy}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'items'     => self::hydrateMembers($rows),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
        ];
    }

    /**
     * 公开项目列表。
     */
    public static function allProjects(): array
    {
        $rows = Db::fetchAll(
            'SELECT * FROM projects WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC'
        );

        return self::hydrateProjects($rows);
    }

    /**
     * 后台项目列表：包含已隐藏项目。
     */
    public static function allProjectsForAdmin(): array
    {
        $rows = Db::fetchAll('SELECT * FROM projects ORDER BY sort_order ASC, id ASC');

        return self::hydrateProjects($rows);
    }

    /**
     * 按 slug 查找公开项目。
     */
    public static function findProjectBySlug(string $slug): ?array
    {
        $row = Db::fetchOne(
            'SELECT * FROM projects WHERE slug = :slug AND is_visible = 1 LIMIT 1',
            ['slug' => $slug]
        );

        if ($row === null) {
            return null;
        }

        $projects = self::hydrateProjects([$row]);

        return $projects[0];
    }

    /**
     * 某个成员参与的公开发项目。
     */
    public static function projectsOf(string $slug): array
    {
        $rows = Db::fetchAll(
            'SELECT p.*
             FROM projects p
             JOIN project_members pm ON pm.project_id = p.id
             JOIN members m ON m.id = pm.member_id
             WHERE m.slug = :slug AND p.is_visible = 1
             ORDER BY p.sort_order ASC, p.id ASC',
            ['slug' => $slug]
        );

        return self::hydrateProjects($rows);
    }

    /**
     * 首页精选项目。
     */
    public static function featuredProjects(int $limit = 3): array
    {
        $limit = max(1, min(20, $limit));
        $rows = Db::fetchAll(
            "SELECT * FROM projects
             WHERE is_visible = 1 AND is_featured = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT {$limit}"
        );

        return self::hydrateProjects($rows);
    }

    /**
     * 某个成员收到的公开留言（已审核通过）。
     */
    public static function messagesOf(string $slug): array
    {
        $rows = Db::fetchAll(
            'SELECT m.id, m.author_name, m.content, m.created_at
             FROM messages m
             JOIN members mem ON mem.id = m.member_id
             WHERE mem.slug = :slug AND m.status = \'approved\'
             ORDER BY m.created_at DESC
             LIMIT 50',
            ['slug' => $slug]
        );

        return array_map(
            static function (array $row): array {
                $row['created_at'] = format_datetime((string) $row['created_at']);

                return $row;
            },
            $rows
        );
    }

    /**
     * 把 members 行转换为视图需要的结构，并批量挂载技能。
     */
    private static function hydrateMembers(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $skillRows = Db::fetchAll(
            "SELECT ms.member_id, s.name, s.category, ms.proficiency
             FROM member_skills ms
             JOIN skills s ON s.id = ms.skill_id
             WHERE ms.member_id IN ({$placeholders})
             ORDER BY s.sort_order ASC, s.name ASC",
            $ids
        );

        $skillsByMember = [];
        foreach ($skillRows as $skill) {
            $skillsByMember[(int) $skill['member_id']][] = [
                'name'        => (string) $skill['name'],
                'category'    => (string) $skill['category'],
                'proficiency' => (int) $skill['proficiency'],
            ];
        }

        $members = [];
        foreach ($rows as $row) {
            $row['avatar'] = self::AVATAR_FALLBACK;
            $row['cover'] = self::COVER_FALLBACK;
            $row['email'] = (string) ($row['email'] ?? '');
            $row['github'] = (string) ($row['github_url'] ?? '');
            $row['website'] = (string) ($row['website_url'] ?? '');
            $row['tagline'] = (string) ($row['tagline'] ?? '');
            $row['is_featured'] = (int) $row['is_featured'];
            $row['skills'] = $skillsByMember[(int) $row['id']] ?? [];
            $members[] = $row;
        }

        return $members;
    }

    /**
     * 把 projects 行转换为视图需要的结构，并批量挂载参与成员。
     */
    private static function hydrateProjects(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $memberRows = Db::fetchAll(
            "SELECT pm.project_id, m.name, pm.role_name
             FROM project_members pm
             JOIN members m ON m.id = pm.member_id
             WHERE pm.project_id IN ({$placeholders})
             ORDER BY pm.sort_order ASC, m.id ASC",
            $ids
        );

        $membersByProject = [];
        foreach ($memberRows as $member) {
            $membersByProject[(int) $member['project_id']][] = [
                'name'      => (string) $member['name'],
                'role_name' => (string) ($member['role_name'] ?? ''),
            ];
        }

        $projects = [];
        foreach ($rows as $row) {
            $row['description_html'] = markdown_lite((string) ($row['description'] ?? ''));
            $row['cover'] = self::COVER_FALLBACK;
            $row['featured'] = (int) $row['is_featured'];
            $row['status_label'] = self::projectStatusLabel((string) $row['status']);
            $row['start_date'] = (string) ($row['start_date'] ?? '');
            $row['end_date'] = (string) ($row['end_date'] ?? '');
            $row['repo_url'] = (string) ($row['repo_url'] ?? '');
            $row['demo_url'] = (string) ($row['demo_url'] ?? '');
            $row['tech_stack'] = (string) ($row['tech_stack'] ?? '');
            $row['members'] = $membersByProject[(int) $row['id']] ?? [];
            $projects[] = $row;
        }

        return $projects;
    }

    private static function projectStatusLabel(string $status): string
    {
        return match ($status) {
            'planning'  => '规划中',
            'ongoing'   => '进行中',
            'completed' => '已完成',
            'archived'  => '已归档',
            default     => $status,
        };
    }
}
