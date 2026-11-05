<?php
/**
 * 文件名：announcement_item.php
 * 功能：公告列表条目组件
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共组件
 */
?>
<a class="announcement-item" href="<?= e(url('announcement/show', ['slug' => $announcement['slug']])) ?>">
    <div class="announcement-item__main">
        <div class="announcement-item__meta">
            <?php if ((int) $announcement['is_pinned'] === 1): ?>
                <span class="badge badge--gold">置顶</span>
            <?php endif; ?>
            <span class="badge badge--muted"><?= e($announcement['category_label']) ?></span>
            <time datetime="<?= e($announcement['published_at']) ?>"><?= e(format_date($announcement['published_at'])) ?></time>
        </div>
        <h3 class="announcement-item__title"><?= e($announcement['title']) ?></h3>
        <p class="announcement-item__summary"><?= e(str_limit($announcement['summary'], 56)) ?></p>
    </div>
    <span class="announcement-item__arrow" aria-hidden="true">→</span>
</a>
