<?php
/**
 * 文件名：skill_bar.php
 * 功能：技能熟练度进度条组件
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共组件
 */
$proficiency = max(1, min(5, (int) ($skill['proficiency'] ?? $skill['avg'] ?? 3)));
?>
<div class="skill-bar">
    <div class="skill-bar__head">
        <span class="skill-bar__name"><?= e($skill['name']) ?></span>
        <span class="skill-bar__value"><?= e($proficiency) ?>/5</span>
    </div>
    <div class="progress" role="progressbar" aria-label="<?= e($skill['name']) ?> 熟练度"
         aria-valuenow="<?= e($proficiency) ?>" aria-valuemin="1" aria-valuemax="5">
        <div class="progress-bar" style="width: <?= e($proficiency * 20) ?>%"></div>
    </div>
</div>
