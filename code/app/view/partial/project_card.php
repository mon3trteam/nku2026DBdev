<?php
/**
 * 文件名：project_card.php
 * 功能：项目卡片组件
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共组件
 */
$statusClass = match ($project['status']) {
    'ongoing' => 'badge--accent',
    'completed' => 'badge--success',
    'planning' => 'badge--gold',
    default => 'badge--muted',
};
?>
<a class="project-card" href="<?= e(url('project/show', ['slug' => $project['slug']])) ?>">
    <div class="project-card__cover">
        <img src="<?= e(asset($project['cover'])) ?>" alt="" width="640" height="360" loading="lazy">
        <span class="badge <?= e($statusClass) ?>"><?= e($project['status_label']) ?></span>
    </div>
    <div class="project-card__body">
        <h3 class="project-card__title"><?= e($project['title']) ?></h3>
        <p class="project-card__summary"><?= e($project['summary']) ?></p>
        <div class="tag-list">
            <?php foreach (array_map('trim', explode(',', $project['tech_stack'])) as $tech): ?>
                <span class="tag"><?= e($tech) ?></span>
            <?php endforeach; ?>
        </div>
        <div class="project-card__meta">
            <?php foreach ($project['members'] as $member): ?>
                <span><?= e($member['name']) ?> · <?= e($member['role_name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</a>
