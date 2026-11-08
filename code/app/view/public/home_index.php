<?php
/**
 * 文件名：home_index.php
 * 功能：首页视图（首屏、数据条、精选项目、最新公告、成员速览）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 首页
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="hero">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-7">
                <p class="eyebrow">
                    <?= e($team['name']) ?><?php if (trim((string) ($team['english_name'] ?? '')) !== ''): ?> · <?= e($team['english_name']) ?><?php endif; ?>
                </p>
                <h1 class="hero__title"><?= e(trim((string) ($team['slogan'] ?? '')) !== ''
                    ? $team['slogan']
                    : '把团队的每一次协作，变成看得见的作品。') ?></h1>
                <p class="hero__lead"><?= e($team['short_intro']) ?></p>
                <div class="hero__actions">
                    <a class="btn btn-primary btn-lg" href="<?= e(url('team/index')) ?>">认识我们 <span aria-hidden="true">→</span></a>
                    <a class="btn btn-outline-secondary btn-lg" href="<?= e(url('project/index')) ?>">查看项目</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero__visual" aria-hidden="true">
                    <div class="hero__visual-card hero__visual-card--back"></div>
                    <div class="hero__visual-card hero__visual-card--front">
                        <span class="hero__visual-label"><?= e(trim((string) ($team['english_name'] ?? '')) !== '' ? $team['english_name'] : $siteName) ?></span>
                        <span class="hero__visual-dots"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="stats-bar" aria-label="团队数据">
    <div class="container">
        <div class="row text-center gy-3">
            <div class="col-6 col-lg-3">
                <div class="stat"><span class="stat__num"><?= e($stats['members']) ?></span><span class="stat__label">团队成员</span></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat"><span class="stat__num"><?= e($stats['projects']) ?></span><span class="stat__label">课程项目</span></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat"><span class="stat__num"><?= e($stats['tables']) ?></span><span class="stat__label">数据表</span></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat"><span class="stat__num"><?= e($stats['dimensions']) ?></span><span class="stat__label">展示维度</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Selected work</p>
                        <h2 class="section-title">精选项目</h2>
                    </div>
                    <span class="section-note">用项目说明我们做过什么</span>
                </div>
                <div class="row g-4">
                    <?php foreach ($projects as $project): ?>
                        <div class="col-md-6">
                            <?php require APP_ROOT . '/app/view/partial/project_card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Latest</p>
                        <h2 class="section-title">最新动态</h2>
                    </div>
                    <a class="section-link" href="<?= e(url('announcement/index')) ?>">全部公告 →</a>
                </div>
                <div class="announcement-list">
                    <?php foreach ($announcements as $announcement): ?>
                        <?php require APP_ROOT . '/app/view/partial/announcement_item.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--tint">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">People</p>
                <h2 class="section-title">成员速览</h2>
            </div>
            <a class="section-link" href="<?= e(url('member/index')) ?>">全部成员 →</a>
        </div>
        <div class="row g-4">
            <?php foreach ($members as $member): ?>
                <div class="col-sm-6 col-lg-3">
                    <?php require APP_ROOT . '/app/view/partial/member_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta-panel">
            <div>
                <p class="eyebrow">Say hello</p>
                <h2 class="section-title">想进一步了解我们？</h2>
                <p class="text-muted mb-0">欢迎在留言板留下建议，或直接查看团队介绍与项目。</p>
            </div>
            <div class="d-flex flex-wrap gap-3">
                <a class="btn btn-primary" href="<?= e(url('message/index')) ?>">去留言板</a>
                <a class="btn btn-outline-secondary" href="<?= e(url('team/index')) ?>">了解团队</a>
            </div>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
