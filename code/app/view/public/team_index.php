<?php
/**
 * 文件名：team_index.php
 * 功能：团队介绍页（故事、价值观、时间线、能力矩阵、成员网格）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 团队介绍
 */

require APP_ROOT . '/app/view/layout/public_header.php';

$teamHeroSlogan = trim((string) ($team['slogan'] ?? '')) !== ''
    ? (string) $team['slogan']
    : '把团队的每一次协作，变成看得见的作品。';
$teamMeta = [];
if (trim((string) ($team['founded_on'] ?? '')) !== '') {
    $teamMeta[] = '成立于 ' . $team['founded_on'];
}
if (trim((string) ($team['location'] ?? '')) !== '') {
    $teamMeta[] = (string) $team['location'];
}
if (trim((string) ($team['email'] ?? '')) !== '') {
    $teamMeta[] = (string) $team['email'];
}
$teamMetaText = implode(' · ', $teamMeta);
?>
<section class="page-hero">
    <div class="container">
        <p class="eyebrow">About us</p>
        <h1 class="page-hero__title"><?= e($team['name']) ?></h1>
        <p class="page-hero__lead"><?= e($teamHeroSlogan) ?></p>
        <?php if ($teamMetaText !== ''): ?>
            <p class="text-muted mt-2 mb-0"><?= e($teamMetaText) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5">
                <div class="story-visual">
                    <img class="story-visual__cover" src="<?= e(asset($team['cover'])) ?>" alt="">
                    <div class="story-visual__body">
                        <img class="story-visual__logo" src="<?= e(asset($team['logo'])) ?>" alt="团队 Logo" width="88" height="88">
                        <span class="story-visual__label"><?= e(((string) ($team['english_name'] ?? '')) !== '' ? $team['english_name'] : 'TeamHub') ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <p class="eyebrow">Our story</p>
                <h2 class="section-title">我们相信，好的团队项目不是功能的堆叠</h2>
                <p class="story-text"><?= e($team['story']) ?></p>
                <div class="row g-3 mt-4">
                    <?php foreach ($values as $value): ?>
                        <div class="col-sm-6">
                            <div class="value-card">
                                <h3><?= e($value['title']) ?></h3>
                                <p><?= e($value['description']) ?></p>
                            </div>
                        </div>
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
                <p class="eyebrow">Milestones</p>
                <h2 class="section-title">成长时间线</h2>
            </div>
        </div>
        <ol class="timeline">
            <?php foreach ($milestones as $item): ?>
                <li class="timeline__item">
                    <span class="timeline__dot" aria-hidden="true"></span>
                    <span class="timeline__year"><?= e($item['year']) ?></span>
                    <h3 class="timeline__title"><?= e($item['title']) ?></h3>
                    <p class="timeline__desc"><?= e($item['description']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6">
                <p class="eyebrow">Capability</p>
                <h2 class="section-title">能力矩阵</h2>
                <p class="text-muted">只展示技能覆盖与平均熟练度，不公开成员个人评分。</p>
                <?php foreach ($skillGroups as $group): ?>
                    <div class="skill-group">
                        <h3 class="skill-group__title"><?= e($group['category']) ?></h3>
                        <?php foreach ($group['items'] as $skill): ?>
                            <?php require APP_ROOT . '/app/view/partial/skill_bar.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-6">
                <p class="eyebrow">Team</p>
                <h2 class="section-title">成员网格</h2>
                <div class="row g-3">
                    <?php foreach ($members as $member): ?>
                        <div class="col-sm-6">
                            <?php require APP_ROOT . '/app/view/partial/member_card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section--tint">
    <div class="container">
        <div class="cta-panel">
            <div>
                <p class="eyebrow">Resources</p>
                <h2 class="section-title">团队文档与作业下载</h2>
                <p class="text-muted mb-0">下载中心提供团队文档与个人作业的分类下载，文件经下载控制器校验后输出。</p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= e(url('resource/index')) ?>">前往下载中心</a>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
