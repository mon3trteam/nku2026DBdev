<?php
/**
 * 文件名：MediaModel.class.php
 * 功能：图片上传、媒体路径与引用清理
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：公共组件 / 媒体
 */

declare(strict_types=1);

final class MediaModel
{
    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * 校验并保存一张图片，返回媒体 ID、公开路径与尺寸。
     *
     * @param array{name?: string, tmp_name?: string, error?: int, size?: int} $file
     * @return array{id: int, path: string, storage_path: string, width: int, height: int}
     */
    public static function storeImage(array $file, int $uploaderId, int $maxBytes): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('没有选择图片文件。');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '图片超过服务器允许的大小。',
                UPLOAD_ERR_PARTIAL => '图片上传不完整，请重试。',
                default => '图片上传失败，错误码 ' . $error . '。',
            });
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            $maxMb = max(1, (int) floor($maxBytes / 1024 / 1024));
            throw new RuntimeException('图片大小需要在 1 字节到 ' . $maxMb . ' MB 之间。');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('上传文件来源不合法。');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpPath);
        if (!isset(self::IMAGE_TYPES[$mime])) {
            throw new RuntimeException('只支持 JPG、PNG、WebP、GIF 图片。');
        }

        $info = @getimagesize($tmpPath);
        if ($info === false || (int) $info[0] <= 0 || (int) $info[1] <= 0) {
            throw new RuntimeException('无法识别图片内容，请更换文件。');
        }
        $width = (int) $info[0];
        $height = (int) $info[1];
        if ($width > 8000 || $height > 8000) {
            throw new RuntimeException('图片尺寸不能超过 8000×8000。');
        }

        $extension = self::IMAGE_TYPES[$mime];
        $relativeDir = 'public/static/uploads/media/' . date('Y/m');
        $absoluteDir = APP_ROOT . '/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('无法创建图片存储目录，请检查目录权限。');
        }

        $diskName = bin2hex(random_bytes(16)) . '.' . $extension;
        $relativePath = $relativeDir . '/' . $diskName;
        $absolutePath = APP_ROOT . '/' . $relativePath;
        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            throw new RuntimeException('保存图片失败，请检查目录权限。');
        }
        @chmod($absolutePath, 0644);

        $originalName = mb_substr(basename((string) ($file['name'] ?? '')), 0, 255, 'UTF-8');
        if ($originalName === '') {
            $originalName = 'image.' . $extension;
        }

        try {
            Db::execute(
                'INSERT INTO media
                    (disk_name, original_name, mime_type, extension, size_bytes,
                     width, height, sha256, storage_path, visibility, uploaded_by)
                 VALUES
                    (:disk_name, :original_name, :mime_type, :extension, :size_bytes,
                     :width, :height, :sha256, :storage_path, :visibility, :uploaded_by)',
                [
                    'disk_name' => $diskName,
                    'original_name' => $originalName,
                    'mime_type' => $mime,
                    'extension' => $extension,
                    'size_bytes' => $size,
                    'width' => $width,
                    'height' => $height,
                    'sha256' => hash_file('sha256', $absolutePath) ?: '',
                    'storage_path' => $relativePath,
                    'visibility' => 'public',
                    'uploaded_by' => $uploaderId > 0 ? $uploaderId : null,
                ]
            );
        } catch (Throwable $e) {
            @unlink($absolutePath);
            error_log('[media] ' . $e->getMessage());
            throw new RuntimeException('保存图片记录失败，请稍后重试。');
        }

        return [
            'id' => Db::lastInsertId(),
            'path' => self::assetPath($relativePath),
            'storage_path' => $relativePath,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * 把 storage_path 转换为 asset() 可用的相对路径。
     */
    public static function assetPath(string $storagePath): string
    {
        $prefix = 'public/static/';

        return str_starts_with($storagePath, $prefix) ? substr($storagePath, strlen($prefix)) : $storagePath;
    }

    /**
     * 按媒体 ID 取公开路径；不存在返回 null。
     */
    public static function pathForId(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }

        $path = Db::fetchValue('SELECT storage_path FROM media WHERE id = :id LIMIT 1', ['id' => $id]);

        return $path === null ? null : self::assetPath((string) $path);
    }

    /**
     * 删除没有任何引用的媒体记录与文件；仍被引用时返回 false。
     */
    public static function deleteIfUnused(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $references = [
            ['SELECT COUNT(*) FROM team_profile WHERE logo_media_id = :id1 OR cover_media_id = :id2', ['id1' => $id, 'id2' => $id]],
            ['SELECT COUNT(*) FROM members WHERE avatar_media_id = :id1 OR cover_media_id = :id2', ['id1' => $id, 'id2' => $id]],
            ['SELECT COUNT(*) FROM projects WHERE cover_media_id = :id', ['id' => $id]],
            ['SELECT COUNT(*) FROM announcements WHERE cover_media_id = :id', ['id' => $id]],
            ['SELECT COUNT(*) FROM resources WHERE media_id = :id', ['id' => $id]],
            ['SELECT COUNT(*) FROM users WHERE avatar_media_id = :id', ['id' => $id]],
        ];
        foreach ($references as [$sql, $params]) {
            if ((int) Db::fetchValue($sql, $params) > 0) {
                return false;
            }
        }

        $row = Db::fetchOne('SELECT storage_path FROM media WHERE id = :id LIMIT 1', ['id' => $id]);
        if ($row === null) {
            return false;
        }

        Db::execute('DELETE FROM media WHERE id = :id', ['id' => $id]);

        $storagePath = (string) $row['storage_path'];
        $prefix = 'public/static/uploads/media/';
        if (str_starts_with($storagePath, $prefix) && !str_contains($storagePath, '..')) {
            $absolutePath = APP_ROOT . '/' . $storagePath;
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        return true;
    }
}
