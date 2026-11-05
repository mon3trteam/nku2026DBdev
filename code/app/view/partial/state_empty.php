<?php
/**
 * 文件名：state_empty.php
 * 功能：空状态组件（图标 + 解释 + 主行动）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共组件
 */
$emptyTitle = $emptyTitle ?? '暂无内容';
$emptyMessage = $emptyMessage ?? '换个筛选条件，或稍后再来看看。';
$emptyActionUrl = $emptyActionUrl ?? '';
$emptyActionText = $emptyActionText ?? '';
?>
<div class="empty-state">
    <div class="empty-state__icon" aria-hidden="true">◌</div>
    <h2><?= e($emptyTitle) ?></h2>
    <p><?= e($emptyMessage) ?></p>
    <?php if ($emptyActionUrl !== '' && $emptyActionText !== ''): ?>
        <a class="btn btn-primary" href="<?= e($emptyActionUrl) ?>"><?= e($emptyActionText) ?></a>
    <?php endif; ?>
</div>
