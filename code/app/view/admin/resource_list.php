<?php
/**
 * 文件名：resource_list.php
 * 功能：后台资源管理列表（下载、编辑、可见性）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 修改：金暄琛（2411672）启用资源编辑入口（2026-11-08）
 * 所属模块：管理后台 / 资源管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($resources)) ?></strong> 个资源；上传后立即进入公开下载列表（或按可见性隐藏）。</p>
    <a class="btn btn-sm btn-primary" href="<?= e(url('admin/resourceUpload')) ?>">上传资源</a>
</div>

<div class="table-responsive">
    <table class="table admin-table align-middle">
        <caption class="visually-hidden">资源管理列表</caption>
        <thead>
        <tr>
            <th scope="col">资源</th>
            <th scope="col">分类</th>
            <th scope="col">文件</th>
            <th scope="col">大小</th>
            <th scope="col">下载</th>
            <th scope="col">可见性</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($resources as $resource): ?>
            <tr>
                <td><?= e($resource['title']) ?></td>
                <td><?= e($categories[$resource['category']] ?? $resource['category']) ?></td>
                <td><code><?= e($resource['file_name']) ?></code></td>
                <td><?= e($resource['size_text']) ?></td>
                <td><?= e($resource['download_count']) ?></td>
                <td>
                    <span class="badge <?= $resource['is_public'] === 1 ? 'badge--success' : 'badge--muted' ?>">
                        <?= $resource['is_public'] === 1 ? '公开' : '隐藏' ?>
                    </span>
                </td>
                <td class="text-nowrap">
                    <?php if ($resource['is_public'] === 1): ?>
                        <a class="btn btn-sm btn-outline-secondary"
                           href="<?= e(url('resource/download', ['id' => $resource['id']])) ?>">下载</a>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-outline-secondary"
                       href="<?= e(url('admin/resourceEdit', ['id' => $resource['id']])) ?>">编辑</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">文件存放在 data/ 或 storage/ 下，由下载控制器校验路径后输出；直接访问这些目录会被 Apache 拒绝。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
