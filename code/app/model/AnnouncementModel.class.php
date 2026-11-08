<?php
/**
 * 文件名：AnnouncementModel.class.php
 * 功能：公告的数据访问；公开页只取已发布视图，后台可取全量
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 公告
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MediaModel.class.php';

final class AnnouncementModel
{
    private const CATEGORIES = [
        'news'     => '团队动态',
        'notice'   => '项目通知',
        'activity' => '功能预告',
        'other'    => '其他',
    ];

    /**
     * 公开公告列表：只返回已发布且未过期的公告。
     */
    public static function all(string $category = ''): array
    {
        $sql = 'SELECT a.*, u.display_name AS author_name
                FROM v_published_announcements a
                JOIN users u ON u.id = a.author_id';
        $params = [];

        if ($category !== '' && array_key_exists($category, self::CATEGORIES)) {
            $sql .= ' WHERE a.category = :category';
            $params['category'] = $category;
        }

        $sql .= ' ORDER BY a.is_pinned DESC, a.published_at DESC, a.id DESC';

        return array_map([self::class, 'hydrate'], Db::fetchAll($sql, $params));
    }

    /**
     * 后台公告列表：包含草稿与已下线。
     */
    public static function allForAdmin(): array
    {
        $rows = Db::fetchAll(
            'SELECT a.*, u.display_name AS author_name
             FROM announcements a
             JOIN users u ON u.id = a.author_id
             ORDER BY a.is_pinned DESC, a.published_at DESC, a.id DESC'
        );

        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * 首页最新公告。
     */
    public static function latest(int $limit = 3): array
    {
        $limit = max(1, min(20, $limit));

        return array_slice(self::all(), 0, $limit);
    }

    /**
     * 按 slug 查找公开公告。
     */
    public static function findBySlug(string $slug): ?array
    {
        $row = Db::fetchOne(
            'SELECT a.*, u.display_name AS author_name
             FROM v_published_announcements a
             JOIN users u ON u.id = a.author_id
             WHERE a.slug = :slug
             LIMIT 1',
            ['slug' => $slug]
        );

        return $row === null ? null : self::hydrate($row);
    }

    /**
     * 按 ID 查找公告（后台编辑用，包含草稿与已下线）。
     */
    public static function findById(int $id): ?array
    {
        $row = Db::fetchOne(
            'SELECT a.*, u.display_name AS author_name
             FROM announcements a
             JOIN users u ON u.id = a.author_id
             WHERE a.id = :id
             LIMIT 1',
            ['id' => $id]
        );

        return $row === null ? null : self::hydrate($row);
    }

    /**
     * slug 是否已被其他公告占用。
     */
    public static function slugExists(string $slug, int $exceptId = 0): bool
    {
        $id = Db::fetchValue(
            'SELECT id FROM announcements WHERE slug = :slug AND id <> :id LIMIT 1',
            ['slug' => $slug, 'id' => $exceptId]
        );

        return $id !== null;
    }

    /**
     * 当前置顶公告数量；编辑时排除自身。
     */
    public static function pinnedCount(int $exceptId = 0): int
    {
        $count = Db::fetchValue(
            'SELECT COUNT(*) FROM announcements WHERE is_pinned = 1 AND id <> :id',
            ['id' => $exceptId]
        );

        return (int) ($count ?? 0);
    }

    /**
     * 新建公告，返回自增 ID。时间字段由控制器标准化后传入。
     *
     * @param array{title: string, slug: string, summary: string, content: string,
     *              category: string, status: string, is_pinned: int,
     *              published_at: ?string, expires_at: ?string} $data
     */
    public static function create(array $data, int $authorId): int
    {
        Db::execute(
            'INSERT INTO announcements
                (title, slug, summary, content, category, status, is_pinned,
                 published_at, expires_at, cover_media_id, author_id)
             VALUES
                (:title, :slug, :summary, :content, :category, :status, :is_pinned,
                 :published_at, :expires_at, :cover_media_id, :author_id)',
            [
                'title'        => $data['title'],
                'slug'         => $data['slug'],
                'summary'      => $data['summary'] === '' ? null : $data['summary'],
                'content'      => $data['content'],
                'category'     => $data['category'],
                'status'       => $data['status'],
                'is_pinned'    => $data['is_pinned'],
                'published_at' => $data['published_at'],
                'expires_at'   => $data['expires_at'],
                'cover_media_id' => !empty($data['cover_media_id']) ? (int) $data['cover_media_id'] : null,
                'author_id'    => $authorId,
            ]
        );

        return Db::lastInsertId();
    }

    /**
     * 保存公告编辑结果。时间字段由控制器标准化后传入。
     *
     * @param array{title: string, slug: string, summary: string, content: string,
     *              category: string, status: string, is_pinned: int,
     *              published_at: ?string, expires_at: ?string} $data
     */
    public static function update(int $id, array $data): void
    {
        Db::execute(
            'UPDATE announcements
             SET title = :title,
                 slug = :slug,
                 summary = :summary,
                 content = :content,
                 category = :category,
                 status = :status,
                 is_pinned = :is_pinned,
                 published_at = :published_at,
                 expires_at = :expires_at,
                 cover_media_id = :cover_media_id
             WHERE id = :id',
            [
                'title'        => $data['title'],
                'slug'         => $data['slug'],
                'summary'      => $data['summary'] === '' ? null : $data['summary'],
                'content'      => $data['content'],
                'category'     => $data['category'],
                'status'       => $data['status'],
                'is_pinned'    => $data['is_pinned'],
                'published_at' => $data['published_at'],
                'expires_at'   => $data['expires_at'],
                'cover_media_id' => !empty($data['cover_media_id']) ? (int) $data['cover_media_id'] : null,
                'id'           => $id,
            ]
        );
    }

    /**
     * 仅更新公告状态；首次发布时补发布时间。
     */
    public static function setStatus(int $id, string $status): void
    {
        Db::execute(
            'UPDATE announcements
             SET status = :status,
                 published_at = COALESCE(published_at, :published_at)
             WHERE id = :id',
            [
                'status'       => $status,
                'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
                'id'           => $id,
            ]
        );
    }

    /**
     * 公告分类字典。
     */
    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * 统一字段映射，保持视图现有字段名不变。
     */
    private static function hydrate(array $row): array
    {
        $row['content'] = (string) ($row['content'] ?? '');
        $row['content_html'] = markdown_lite($row['content']);
        $row['category_label'] = self::CATEGORIES[(string) $row['category']] ?? (string) $row['category'];
        $row['is_pinned'] = (int) $row['is_pinned'];
        $row['published_at'] = format_datetime((string) ($row['published_at'] ?? ''), '');
        $row['expires_at'] = $row['expires_at'] === null
            ? null
            : format_datetime((string) $row['expires_at'], '');
        $row['author'] = (string) ($row['author_name'] ?? '');
        $row['cover_media_id'] = (int) ($row['cover_media_id'] ?? 0);
        $row['cover'] = MediaModel::pathForId($row['cover_media_id']);
        $row['status_label'] = match ((string) $row['status']) {
            'draft'     => '草稿',
            'published' => '已发布',
            'offline'   => '已下线',
            default     => (string) $row['status'],
        };

        return $row;
    }
}
