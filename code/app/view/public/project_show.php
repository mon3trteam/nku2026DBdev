<?php
/**
 * 文件名：project_show.php
 * 功能：项目详情页（封面、状态、正文、技术栈、成员、相关项目）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 项目
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="project-hero">
    <div class="container">
        <nav class="breadcrumb-line" aria-label="面包屑">
            <a href="<?= e(url('project/index')) ?>">项目作品</a>
            <span aria-hidden="true">/</span>
            <span><?= e($project['title']) ?></span>
        </nav>
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="project-hero__cover">
                    <img src="<?= e(asset($project['cover'])) ?>" alt="<?= e($project['title']) ?> 封面" width="800" height="450">
                </div>
            </div>
            <div class="col-lg-6">
                <span class="badge <?= $project['status'] === 'completed' ? 'badge--success' : ($project['status'] === 'planning' ? 'badge--gold' : 'badge--accent') ?>">
                    <?= e($project['status_label']) ?>
                </span>
                <h1 class="project-hero__title"><?= e($project['title']) ?></h1>
                <p class="project-hero__summary"><?= e($project['summary']) ?></p>
                <div class="tag-list">
                    <?php foreach (array_map('trim', explode(',', $project['tech_stack'])) as $tech): ?>
                        <span class="tag"><?= e($tech) ?></span>
                    <?php endforeach; ?>
                </div>
                <dl class="project-meta">
                    <div><dt>开始时间</dt><dd><?= e(format_date($project['start_date'], '—')) ?></dd></div>
                    <div><dt>结束时间</dt><dd><?= e(format_date($project['end_date'], '进行中')) ?></dd></div>
                    <div><dt>参与成员</dt><dd><?= e(implode('、', array_column($project['members'], 'name'))) ?></dd></div>
                </dl>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <?php if ($project['repo_url'] !== ''): ?>
                        <a class="btn btn-primary" href="<?= e($project['repo_url']) ?>" target="_blank" rel="noopener noreferrer">查看仓库</a>
                    <?php endif; ?>
                    <?php if ($project['demo_url'] !== ''): ?>
                        <a class="btn btn-outline-secondary" href="<?= e($project['demo_url']) ?>" target="_blank" rel="noopener noreferrer">在线演示</a>
                    <?php endif; ?>
                    <a class="btn btn-outline-secondary" href="<?= e(url('project/index')) ?>">返回项目列表</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5">
            <article class="col-lg-8">
                <p class="eyebrow">Overview</p>
                <h2 class="section-title">项目说明</h2>
                <div class="article-body">
                    <?= $project['description_html'] ?>
                </div>

                <h2 class="section-title">参与成员</h2>
                <div class="row g-3">
                    <?php foreach ($project['members'] as $member): ?>
                        <div class="col-sm-6">
                            <div class="member-role-card">
                                <strong><?= e($member['name']) ?></strong>
                                <span><?= e($member['role_name']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <aside class="col-lg-4">
                <div class="side-panel">
                    <h2 class="side-panel__title">相关项目</h2>
                    <div class="stack">
                        <?php foreach ($related as $project): ?>
                            <?php require APP_ROOT . '/app/view/partial/project_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
