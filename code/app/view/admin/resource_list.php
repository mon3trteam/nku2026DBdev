<?php
/**
 * 文件名：resource_list.php
 * 功能：后台资源管理列表
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 资源管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($resources)) ?></strong> 个资源；上传、公开/隐藏与替换文件在接口接入后启用。</p>
    <button class="btn btn-sm btn-primary" type="button" disabled>上传资源</button>
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
                <td><span class="badge badge--success">公开</span></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" type="button" disabled>编辑</button>
                    <button class="btn btn-sm btn-outline-danger" type="button" disabled>隐藏</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">文件存放于 Web 根之外，由下载控制器校验后输出；当前为演示数据。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
