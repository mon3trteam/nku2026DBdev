<?php
/**
 * 文件名：state_loading.php
 * 功能：加载态骨架屏组件
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共组件
 */
$loadingText = $loadingText ?? '仍在加载…';
?>
<div class="state-loading" role="status" aria-live="polite">
    <span class="visually-hidden"><?= e($loadingText) ?></span>
    <div class="state-loading__row">
        <span class="skeleton skeleton--card"></span>
        <span class="skeleton skeleton--card"></span>
        <span class="skeleton skeleton--card"></span>
    </div>
    <p class="state-loading__text"><?= e($loadingText) ?></p>
</div>
