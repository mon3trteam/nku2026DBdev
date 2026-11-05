<?php
/**
 * 文件名：announcement_show.php
 * 功能：公告详情页
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 公告
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="section">
    <div class="container">
        <div class="row g-5">
            <article class="col-lg-8">
                <nav class="breadcrumb-line" aria-label="面包屑">
                    <a href="<?= e(url('announcement/index')) ?>">公告中心</a>
                    <span aria-hidden="true">/</span>
                    <span><?= e($announcement['category_label']) ?></span>
                </nav>

                <header class="article-head">
                    <div class="article-head__meta">
                        <?php if ((int) $announcement['is_pinned'] === 1): ?>
                            <span class="badge badge--gold">置顶</span>
                        <?php endif; ?>
                        <span class="badge badge--muted"><?= e($announcement['category_label']) ?></span>
                        <time datetime="<?= e($announcement['published_at']) ?>"><?= e($announcement['published_at']) ?></time>
                    </div>
                    <h1 class="article-title"><?= e($announcement['title']) ?></h1>
                    <p class="article-summary"><?= e($announcement['summary']) ?></p>
                    <p class="article-author">作者：<?= e($announcement['author']) ?></p>
                </header>

                <div class="article-body">
                    <?= $announcement['content_html'] ?>
                </div>

                <div class="article-footer">
                    <a class="btn btn-outline-secondary" href="<?= e(url('announcement/index')) ?>">← 返回公告中心</a>
                </div>
            </article>

            <aside class="col-lg-4">
                <div class="side-panel">
                    <h2 class="side-panel__title">相关公告</h2>
                    <div class="announcement-list announcement-list--compact">
                        <?php foreach (array_slice($related, 0, 3) as $item): ?>
                            <?php $announcement = $item; ?>
                            <?php require APP_ROOT . '/app/view/partial/announcement_item.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
