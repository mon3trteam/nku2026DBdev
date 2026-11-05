<?php
/**
 * 文件名：message_list.php
 * 功能：后台留言管理列表（默认待审核、拒绝需二次确认）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 留言管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($messages)) ?></strong> 条待审核留言；审核路径不超过两次点击。</p>
    <button class="btn btn-sm btn-outline-secondary" type="button" disabled>批量通过</button>
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
            <tr>
                <td><?= e($message['author']) ?></td>
                <td><?= e(str_limit($message['excerpt'], 36)) ?></td>
                <td><?= e($message['member']) ?></td>
                <td><?= e($message['created_at']) ?></td>
                <td><span class="badge badge--gold"><?= e($message['status']) ?></span></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-success" type="button" disabled>通过</button>
                    <button class="btn btn-sm btn-outline-danger" type="button"
                            data-bs-toggle="modal" data-bs-target="#rejectModal">拒绝</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">通过按钮暂为占位；拒绝按钮会打开二次确认对话框。</p>

<?php
$modalId = 'rejectModal';
$modalTitle = '拒绝留言';
$modalBody = '拒绝后留言不会公开，但仍保留记录以便复盘。该操作会写入审计日志。';
$modalConfirmText = '确认拒绝';
$modalDanger = true;
require APP_ROOT . '/app/view/partial/modal_confirm.php';
require APP_ROOT . '/app/view/layout/admin_footer.php';
?>