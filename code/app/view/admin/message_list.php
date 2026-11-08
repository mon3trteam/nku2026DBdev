<?php
/**
 * 文件名：message_list.php
 * 功能：后台留言管理列表（状态统计、快捷通过、进入审核/回复页）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 修改：金暄琛（2411672）接入留言审核状态与快捷通过（2026-11-08）
 * 所属模块：管理后台 / 留言管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$counts = $counts ?? [];
$pendingCount = (int) ($counts['pending'] ?? 0);
$approvedCount = (int) ($counts['approved'] ?? 0);
$rejectedCount = (int) ($counts['rejected'] ?? 0);
$spamCount = (int) ($counts['spam'] ?? 0);
?>
<div class="admin-toolbar">
    <p>
        待审核 <strong><?= e($pendingCount) ?></strong> 条 ·
        已通过 <strong><?= e($approvedCount) ?></strong> 条 ·
        已拒绝 <strong><?= e($rejectedCount) ?></strong> 条 ·
        垃圾 <strong><?= e($spamCount) ?></strong> 条
    </p>
</div>

<div class="table-responsive">
    <table class="table admin-table align-middle">
        <caption class="visually-hidden">留言管理列表</caption>
        <thead>
        <tr>
            <th scope="col">访客</th>
            <th scope="col">内容摘要</th>
            <th scope="col">目标成员</th>
            <th scope="col">提交时间</th>
            <th scope="col">状态</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($messages as $message): ?>
            <?php
            $badgeClass = match ($message['status_code']) {
                'approved' => 'badge--success',
                'pending' => 'badge--gold',
                'spam' => 'badge--danger',
                default => 'badge--muted',
            };
            ?>
            <tr>
                <td><?= e($message['author']) ?></td>
                <td><?= e(str_limit($message['excerpt'], 36)) ?></td>
                <td><?= e($message['member']) ?></td>
                <td><?= e($message['created_at']) ?></td>
                <td><span class="badge <?= e($badgeClass) ?>"><?= e($message['status']) ?></span></td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary"
                       href="<?= e(url('admin/messageEdit', ['id' => $message['id']])) ?>">审核/回复</a>
                    <?php if ($message['status_code'] === 'pending'): ?>
                        <form class="d-inline" action="<?= e(url('admin/updateMessage')) ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($message['id']) ?>">
                            <input type="hidden" name="status" value="approved">
                            <input type="hidden" name="return_to" value="list">
                            <button class="btn btn-sm btn-success" type="submit">通过</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">点“审核/回复”可查看全文、填写审核备注和团队回复；拒绝或标记垃圾会保留记录并写入审计日志。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
