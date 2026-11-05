<?php
/**
 * 文件名：project_list.php
 * 功能：后台项目管理列表
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 项目管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($projects)) ?></strong> 个项目；项目至少关联 1 名成员。</p>
    <button class="btn btn-sm btn-primary" type="button" disabled>新建项目</button>
</div>

<div class="table-responsive">
    <table class="table admin-table align-middle">
        <caption class="visually-hidden">项目管理列表</caption>
        <thead>
        <tr>
            <th scope="col">项目</th>
            <th scope="col">状态</th>
            <th scope="col">技术栈</th>
            <th scope="col">参与成员</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($projects as $project): ?>
            <tr>
                <td><?= e($project['title']) ?></td>
                <td><span class="badge badge--accent"><?= e($project['status_label']) ?></span></td>
                <td><?= e($project['tech_stack']) ?></td>
                <td><?= e(implode('、', array_column($project['members'], 'name'))) ?></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" type="button" disabled>编辑</button>
                    <button class="btn btn-sm btn-outline-danger" type="button" disabled>归档</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">按钮暂为占位，项目管理接口接入后启用。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
