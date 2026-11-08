<?php
/**
 * 文件名：ResourceController.class.php
 * 功能：资源下载列表与文件输出（路径校验、下载计数）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 修改：接入安全下载（2026-11-08）
 * 所属模块：公共门户 / 资源
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/ResourceModel.class.php';

final class ResourceController
{
    public function index(): void
    {
        $categories = ResourceModel::categories();
        $category = (string) ($_GET['category'] ?? '');
        $category = array_key_exists($category, $categories) ? $category : '';
        $resources = ResourceModel::all($category);

        render('public/resource_list', [
            'pageTitle' => '资源下载 · ' . site_name(),
            'metaDescription' => '下载' . site_name() . '的团队文档、个人作业、设计资料与部署资料。',
            'activeNav' => 'resource',
            'categories' => $categories,
            'activeCategory' => $category,
            'resources' => $resources,
            'grouped' => ResourceModel::grouped(),
            'total' => count($resources),
        ]);
    }

    /**
     * 输出公开资源文件。数据库只存相对路径，输出前用 realpath + 根目录前缀双重校验。
     */
    public function download(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $resource = $id > 0 ? ResourceModel::findPublic($id) : null;
        if ($resource === null) {
            $this->abort(404, '资源不存在', '该资源不存在或未公开。');
        }

        $file = $this->resolveFile((string) $resource['storage_path']);
        if ($file === null) {
            $this->abort(404, '文件尚未上传', '文件尚未放入 data/team、data/personal 或 storage/uploads，请联系管理员上传。');
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            $this->abort(500, '文件读取失败', '服务器暂时无法读取该文件，请稍后重试。');
        }

        $name = str_replace(["\r", "\n"], '', (string) $resource['file_name']);
        $name = $name === '' ? 'download' : $name;
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf'  => 'application/pdf',
            'zip'  => 'application/zip',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'txt'  => 'text/plain; charset=UTF-8',
            'md'   => 'text/markdown; charset=UTF-8',
        ];
        $mime = $mimeTypes[$extension] ?? 'application/octet-stream';

        $fallback = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) ?: 'download';
        $fallback = str_replace(['"', '\\'], '_', $fallback);
        $disposition = sprintf(
            'attachment; filename="%s"; filename*=UTF-8\'\'%s',
            $fallback,
            rawurlencode($name)
        );

        // index.php 开了输出缓冲；输出文件前清理，避免文件内容被缓冲或混入页面。
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        ResourceModel::incrementDownload($id);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($file));
        header('Content-Disposition: ' . $disposition);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        fpassthru($handle);
        fclose($handle);
        exit;
    }

    /**
     * 只允许下载 code/data/team、code/data/personal、code/storage/uploads 下的真实文件。
     */
    private function resolveFile(string $storagePath): ?string
    {
        $relative = str_replace('\\', '/', trim($storagePath));
        if (
            $relative === ''
            || str_contains($relative, "\0")
            || str_starts_with($relative, '/')
            || preg_match('#(^|/)\.\.(/|$)#', $relative) === 1
        ) {
            return null;
        }

        $full = realpath(APP_ROOT . '/' . $relative);
        if ($full === false || !is_file($full)) {
            return null;
        }

        $roots = [];
        foreach (['data/team', 'data/personal', 'storage/uploads'] as $allowed) {
            $root = realpath(APP_ROOT . '/' . $allowed);
            if ($root !== false) {
                $roots[] = rtrim($root, DIRECTORY_SEPARATOR);
            }
        }

        foreach ($roots as $root) {
            if (str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
                return $full;
            }
        }

        return null;
    }

    private function abort(int $code, string $title, string $message): never
    {
        http_response_code($code);
        render('error_request', [
            'pageTitle' => $title . ' · ' . site_name(),
            'metaDescription' => $message,
            'activeNav' => 'resource',
            'errorCode' => $code,
            'errorTitle' => $title,
            'errorMessage' => $message,
        ]);
        exit;
    }
}
