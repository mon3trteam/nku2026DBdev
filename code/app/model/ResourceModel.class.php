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
