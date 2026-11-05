<?php
/**
 * 文件名：announcement_list.php
 * 功能：后台公告管理列表
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 公告管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($announcements)) ?></strong> 条公告；发布后同步到首页与公告中心。</p>
    <button class="btn btn-sm btn-primary" type="button" disabled>新建公告</button>
</div>

<div class="table-responsive">
    <table class="table admin-table align-middle">
        <caption class="visually-hidden">公告管理列表</caption>
        <thead>
        <tr>
            <th scope="col">标题</th>
            <th scope="col">分类</th>
            <th scope="col">置顶</th>
            <th scope="col">状态</th>
            <th scope="col">发布时间</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($announcements as $announcement): ?>
            <tr>
                <td><?= e($announcement['title']) ?></td>
                <td><?= e($announcement['category_label']) ?></td>
                <td>
                    <?php if ((int) $announcement['is_pinned'] === 1): ?>
                        <span class="badge badge--gold">置顶</span>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge--success"><?= e($announcement['status_label']) ?></span></td>
                <td><?= e($announcement['published_at']) ?></td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/announcementEdit', ['id' => $announcement['id']])) ?>">编辑</a>
                    <button class="btn btn-sm btn-outline-danger" type="button" disabled>下线</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">按钮暂为占位，公告发布接口接入后启用。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
