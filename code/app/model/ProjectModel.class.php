<?php
/**
 * 文件名：ProjectModel.class.php
 * 功能：项目管理数据访问（列表、详情、新建、编辑、归档）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-08
 * 所属模块：内容管理 / 项目
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MediaModel.class.php';

final class ProjectModel
{
    public const STATUS_LABELS = [
        'planning'  => '规划中',
        'ongoing'   => '进行中',
        'completed' => '已完成',
        'archived'  => '已归档',
    ];

    /**
     * 后台项目列表：包含已隐藏和已归档项目。
     */
    public static function allForAdmin(): array
    {
        $rows = Db::fetchAll(
            "SELECT p.*,
                    COUNT(pm.member_id) AS member_count,
                    GROUP_CONCAT(m.name ORDER BY pm.sort_order ASC, m.id ASC SEPARATOR '、') AS member_names
             FROM projects p
             LEFT JOIN project_members pm ON pm.project_id = p.id
             LEFT JOIN members m ON m.id = pm.member_id
             GROUP BY p.id
             ORDER BY p.sort_order ASC, p.id ASC"
        );

        return array_map(
            static function (array $row): array {
                $row['member_count'] = (int) $row['member_count'];
                $row['member_names'] = (string) ($row['member_names'] ?? '');
                $row['status_label'] = self::STATUS_LABELS[(string) $row['status']] ?? (string) $row['status'];
                $row['is_visible'] = (int) $row['is_visible'];
                $row['is_featured'] = (int) $row['is_featured'];
                return $row;
            },
            $rows
        );
    }

    /**
     * 后台编辑用的项目详情，附带成员分工。
     */
    public static function findForAdmin(int $id): ?array
    {
        $row = Db::fetchOne('SELECT * FROM projects WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($row === null) {
            return null;
        }

        $row['members'] = Db::fetchAll(
            'SELECT pm.member_id, pm.role_name, pm.contribution, pm.sort_order
             FROM project_members pm
             WHERE pm.project_id = :id
             ORDER BY pm.sort_order ASC, pm.member_id ASC',
            ['id' => $id]
        );
        $row['status_label'] = self::STATUS_LABELS[(string) $row['status']] ?? (string) $row['status'];
        $row['cover_media_id'] = (int) ($row['cover_media_id'] ?? 0);
        $row['cover'] = MediaModel::pathForId($row['cover_media_id']) ?? 'img/placeholder-cover.svg';

        return $row;
    }

    /**
     * 表单可选的成员（含已隐藏成员，便于保留历史分工）。
     */
    public static function availableMembers(): array
    {
        return Db::fetchAll(
            'SELECT id, name, role_title, is_visible
             FROM members
             ORDER BY sort_order ASC, id ASC'
        );
    }

    public static function slugExists(string $slug, int $excludeId = 0): bool
    {
        $count = Db::fetchValue(
            'SELECT COUNT(*) FROM projects WHERE slug = :slug AND id <> :exclude_id',
            ['slug' => $slug, 'exclude_id' => $excludeId]
        );

        return (int) $count > 0;
    }

    /**
     * 新建项目并在同一事务内写入成员分工。
     *
     * @param array<string, mixed> $data
     * @param array<int, array{role_name: string, contribution: string}> $members member_id => 分工
     */
    public static function create(array $data, array $members): int
    {
        return (int) Db::transaction(static function () use ($data, $members): int {
            Db::execute(
                'INSERT INTO projects
                    (slug, title, summary, description, tech_stack, status,
                     start_date, end_date, repo_url, demo_url, cover_media_id,
                     is_featured, is_visible, sort_order, created_by)
                 VALUES
                    (:slug, :title, :summary, :description, :tech_stack, :status,
                     :start_date, :end_date, :repo_url, :demo_url, :cover_media_id,
                     :is_featured, :is_visible, :sort_order, :created_by)',
                [
                    'slug' => $data['slug'],
                    'title' => $data['title'],
                    'summary' => $data['summary'],
                    'description' => $data['description'],
                    'tech_stack' => $data['tech_stack'],
                    'status' => $data['status'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'repo_url' => $data['repo_url'],
                    'demo_url' => $data['demo_url'],
                    'cover_media_id' => !empty($data['cover_media_id']) ? (int) $data['cover_media_id'] : null,
                    'is_featured' => $data['is_featured'],
                    'is_visible' => $data['is_visible'],
                    'sort_order' => $data['sort_order'],
                    'created_by' => $data['created_by'],
                ]
            );

            $id = Db::lastInsertId();
            self::replaceMembers($id, $members);

            return $id;
        });
    }

    /**
     * 更新项目并整体替换成员分工。
     *
     * @param array<string, mixed> $data
     * @param array<int, array{role_name: string, contribution: string}> $members
     */
    public static function update(int $id, array $data, array $members): void
    {
        Db::transaction(static function () use ($id, $data, $members): void {
            Db::execute(
                'UPDATE projects SET
                    slug = :slug,
                    title = :title,
                    summary = :summary,
                    description = :description,
                    tech_stack = :tech_stack,
                    status = :status,
                    start_date = :start_date,
                    end_date = :end_date,
                    repo_url = :repo_url,
                    demo_url = :demo_url,
                    cover_media_id = :cover_media_id,
                    is_featured = :is_featured,
                    is_visible = :is_visible,
                    sort_order = :sort_order
                 WHERE id = :id',
                [
                    'slug' => $data['slug'],
                    'title' => $data['title'],
                    'summary' => $data['summary'],
                    'description' => $data['description'],
                    'tech_stack' => $data['tech_stack'],
                    'status' => $data['status'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'repo_url' => $data['repo_url'],
                    'demo_url' => $data['demo_url'],
                    'cover_media_id' => !empty($data['cover_media_id']) ? (int) $data['cover_media_id'] : null,
                    'is_featured' => $data['is_featured'],
                    'is_visible' => $data['is_visible'],
                    'sort_order' => $data['sort_order'],
                    'id' => $id,
                ]
            );

            self::replaceMembers($id, $members);
        });
    }

    /**
     * 归档：状态改为 archived 并从前台隐藏。
     */
    public static function archive(int $id): void
    {
        Db::execute(
            "UPDATE projects SET status = 'archived', is_visible = 0 WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * 快捷显示 / 隐藏。
     */
    public static function setVisibility(int $id, bool $visible): void
    {
        Db::execute(
            'UPDATE projects SET is_visible = :visible WHERE id = :id',
            ['visible' => $visible ? 1 : 0, 'id' => $id]
        );
    }

    /**
     * @param array<int, array{role_name: string, contribution: string}> $members
     */
    private static function replaceMembers(int $projectId, array $members): void
    {
        Db::execute('DELETE FROM project_members WHERE project_id = :id', ['id' => $projectId]);

        $sort = 0;
        foreach ($members as $memberId => $assignment) {
            Db::execute(
                'INSERT INTO project_members (project_id, member_id, role_name, contribution, sort_order)
                 VALUES (:project_id, :member_id, :role_name, :contribution, :sort_order)',
                [
                    'project_id' => $projectId,
                    'member_id' => $memberId,
                    'role_name' => $assignment['role_name'],
                    'contribution' => $assignment['contribution'],
                    'sort_order' => $sort++,
                ]
            );
        }
    }
}
