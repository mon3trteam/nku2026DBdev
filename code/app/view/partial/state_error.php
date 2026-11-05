<?php
/**
 * 文件名：state_error.php
 * 功能：错误态组件（人话说明 + 重试）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共组件
 */
$errorTitle = $errorTitle ?? '加载失败';
$errorMessage = $errorMessage ?? '服务暂时不可用，请稍后重试。';
$retryUrl = $retryUrl ?? url('home/index');
?>
<div class="empty-state empty-state--error" role="alert">
    <div class="empty-state__icon" aria-hidden="true">!</div>
    <h2><?= e($errorTitle) ?></h2>
    <p><?= e($errorMessage) ?></p>
    <a class="btn btn-primary" href="<?= e($retryUrl) ?>">重试</a>
</div>
