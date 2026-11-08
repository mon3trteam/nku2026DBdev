<?php
/**
 * 文件名：announcement_list.php
 * 功能：后台公告管理列表（新建、编辑、快捷发布/下线）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 修改：金暄琛（2411672）启用新建与发布/下线（2026-11-08）
 * 所属模块：管理后台 / 公告管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($announcements)) ?></strong> 条公告；发布后同步到首页与公告中心。</p>
    <a class="btn btn-sm btn-primary" href="<?= e(url('admin/announcementCreate')) ?>">新建公告</a>
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
            <?php
            $statusClass = match ($announcement['status']) {
                'published' => 'badge--success',
                'offline' => 'badge--gold',
                default => 'badge--muted',
            };
            ?>
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
                <td><span class="badge <?= e($statusClass) ?>"><?= e($announcement['status_label']) ?></span></td>
                <td><?= e($announcement['published_at']) ?></td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/announcementEdit', ['id' => $announcement['id']])) ?>">编辑</a>
                    <?php if ($announcement['status'] === 'published'): ?>
                        <form class="d-inline" action="<?= e(url('admin/setAnnouncementStatus')) ?>" method="post"
                              onsubmit="return confirm('下线后前台立即不可见，确认继续？')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($announcement['id']) ?>">
                            <input type="hidden" name="status" value="offline">
                            <button class="btn btn-sm btn-outline-danger" type="submit">下线</button>
                        </form>
                    <?php else: ?>
                        <form class="d-inline" action="<?= e(url('admin/setAnnouncementStatus')) ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($announcement['id']) ?>">
                            <input type="hidden" name="status" value="published">
                            <button class="btn btn-sm btn-success" type="submit">发布</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">发布与下线立即生效并写入审计日志；编辑页可设置置顶、发布时间与过期时间。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
