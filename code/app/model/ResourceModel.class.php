<?php
/**
 * 文件名：ResourceModel.class.php
 * 功能：下载资源的数据访问；关联 media 表输出文件信息
 * 作者：杨斯源（学号待填）
 * 修改：后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 资源
 */

declare(strict_types=1);

final class ResourceModel
{
    private const CATEGORIES = [
        'team'     => '团队文档',
        'personal' => '个人作业',
        'design'   => '设计资料',
        'doc'      => '部署资料',
        'other'    => '其他',
    ];

    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * 公开资源列表，可按分类筛选。
     */
    public static function all(string $category = ''): array
    {
        $sql = 'SELECT r.*, m.original_name, m.extension, m.size_bytes, m.storage_path
                FROM resources r
                JOIN media m ON m.id = r.media_id
                WHERE r.is_public = 1';
        $params = [];

        if ($category !== '' && array_key_exists($category, self::CATEGORIES)) {
            $sql .= ' AND r.category = :category';
            $params['category'] = $category;
        }

        $sql .= ' ORDER BY r.sort_order ASC, r.id ASC';

        return array_map([self::class, 'hydrate'], Db::fetchAll($sql, $params));
    }

    /**
     * 后台资源列表：包含未公开资源。
     */
    public static function allForAdmin(): array
    {
        $rows = Db::fetchAll(
            'SELECT r.*, m.original_name, m.extension, m.size_bytes, m.storage_path
             FROM resources r
             JOIN media m ON m.id = r.media_id
             ORDER BY r.sort_order ASC, r.id ASC'
        );

        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * 按 ID 查找资源（后台编辑用，包含隐藏资源）。
     */
    public static function findForAdmin(int $id): ?array
    {
        $row = Db::fetchOne(
            'SELECT r.*, m.original_name, m.extension, m.size_bytes, m.storage_path, m.mime_type
             FROM resources r
             JOIN media m ON m.id = r.media_id
             WHERE r.id = :id
             LIMIT 1',
            ['id' => $id]
        );

        return $row === null ? null : self::hydrate($row);
    }

    /**
     * 按分类分组，供下载页一次渲染。
     */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::all() as $resource) {
            $groups[$resource['category']][] = $resource;
        }

        return $groups;
    }

    /**
     * 按 ID 查找公开资源，供下载控制器使用。
     */
    public static function findPublic(int $id): ?array
    {
        $row = Db::fetchOne(
            'SELECT r.*, m.original_name, m.extension, m.size_bytes, m.storage_path, m.mime_type
             FROM resources r
             JOIN media m ON m.id = r.media_id
             WHERE r.id = :id AND r.is_public = 1
             LIMIT 1',
            ['id' => $id]
        );

        return $row === null ? null : self::hydrate($row);
    }

    /**
     * 下载计数 +1。
     */
    public static function incrementDownload(int $id): void
    {
        Db::execute('UPDATE resources SET download_count = download_count + 1 WHERE id = :id', ['id' => $id]);
    }

    /**
     * 新资源默认排到列表末尾。
     */
    public static function nextSortOrder(): int
    {
        $max = Db::fetchValue('SELECT COALESCE(MAX(sort_order), 0) FROM resources');

        return (int) ($max ?? 0) + 10;
    }

    /**
     * 媒体与资源记录同事务写入；文件由控制器先移动到磁盘。
     *
     * @param array{disk_name: string, original_name: string, mime_type: string, extension: string,
     *              size_bytes: int, sha256: string, storage_path: string, visibility: string,
     *              uploaded_by: int} $media
     * @param array{title: string, category: string, description: string, is_public: int,
     *              sort_order: int, uploaded_by: int} $resource
     */
    public static function createWithMedia(array $media, array $resource): int
    {
        return Db::transaction(static function () use ($media, $resource): int {
            Db::execute(
                'INSERT INTO media
                    (disk_name, original_name, mime_type, extension, size_bytes, sha256,
                     storage_path, visibility, uploaded_by)
                 VALUES
                    (:disk_name, :original_name, :mime_type, :extension, :size_bytes, :sha256,
                     :storage_path, :visibility, :uploaded_by)',
                $media
            );
            $mediaId = Db::lastInsertId();

            Db::execute(
                'INSERT INTO resources
                    (title, category, description, media_id, is_public, sort_order, uploaded_by)
                 VALUES
                    (:title, :category, :description, :media_id, :is_public, :sort_order, :uploaded_by)',
                [
                    'title'       => $resource['title'],
                    'category'    => $resource['category'],
                    'description' => $resource['description'] === '' ? null : $resource['description'],
                    'media_id'    => $mediaId,
                    'is_public'   => $resource['is_public'],
                    'sort_order'  => $resource['sort_order'],
                    'uploaded_by' => $resource['uploaded_by'],
                ]
            );

            return Db::lastInsertId();
        });
    }

    /**
     * 更新资源信息；传入新媒体数组时同事务插入并替换 media_id。
     *
     * @param array{title: string, category: string, description: string, is_public: int} $resource
     * @param array{disk_name: string, original_name: string, mime_type: string, extension: string,
     *              size_bytes: int, sha256: string, storage_path: string, visibility: string,
     *              uploaded_by: int}|null $media
     * @return int|null 新 media ID；未替换文件时为 null
     */
    public static function update(int $id, array $resource, ?array $media = null): ?int
    {
        return Db::transaction(static function () use ($id, $resource, $media): ?int {
            $mediaId = null;
            if ($media !== null) {
                Db::execute(
                    'INSERT INTO media
                        (disk_name, original_name, mime_type, extension, size_bytes, sha256,
                         storage_path, visibility, uploaded_by)
                     VALUES
                        (:disk_name, :original_name, :mime_type, :extension, :size_bytes, :sha256,
                         :storage_path, :visibility, :uploaded_by)',
                    $media
                );
                $mediaId = Db::lastInsertId();
            }

            $sql = 'UPDATE resources
                    SET title = :title,
                        category = :category,
                        description = :description,
                        is_public = :is_public';
            $params = [
                'title'       => $resource['title'],
                'category'    => $resource['category'],
                'description' => $resource['description'] === '' ? null : $resource['description'],
                'is_public'   => $resource['is_public'],
            ];

            if ($mediaId !== null) {
                $sql .= ', media_id = :media_id';
                $params['media_id'] = $mediaId;
            }

            $sql .= ' WHERE id = :id';
            $params['id'] = $id;

            Db::execute($sql, $params);

            return $mediaId;
        });
    }

    /**
     * 删除不再被引用的媒体记录；仍被引用时返回 false，文件保持不变。
     */
    public static function deleteMedia(int $mediaId): bool
    {
        try {
            return Db::execute('DELETE FROM media WHERE id = :id', ['id' => $mediaId]) > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    private static function hydrate(array $row): array
    {
        $row['file_name'] = (string) ($row['original_name'] ?? '');
        $row['file_type'] = strtoupper((string) ($row['extension'] ?? 'FILE'));
        $row['size_text'] = bytes_text((int) ($row['size_bytes'] ?? 0));
        $row['updated_at'] = (string) ($row['updated_at'] ?? '');
        $row['download_count'] = (int) ($row['download_count'] ?? 0);
        $row['is_public'] = (int) ($row['is_public'] ?? 0);

        return $row;
    }
}
