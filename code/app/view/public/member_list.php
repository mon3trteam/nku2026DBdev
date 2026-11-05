<?php
/**
 * 文件名：member_list.php
 * 功能：成员列表页（搜索、技能筛选、排序、分页、空状态）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 成员
 */

require APP_ROOT . '/app/view/layout/public_header.php';

$baseParams = array_filter(
    ['q' => $keyword, 'skill' => $activeSkill],
    static fn (string $value): bool => $value !== ''
);
$pagerParams = $baseParams;
if ($sort !== '') {
    $pagerParams['sort'] = $sort;
}
$sortParams = $baseParams;
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <p class="eyebrow">Members</p>
        <h1 class="page-hero__title">认识我们的成员</h1>
        <p class="page-hero__lead">按技能筛选、按姓名搜索，进入成员主页查看项目、技能与留言。</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <form class="member-search" action="<?= e(APP_BASE . '/index.php') ?>" method="get" role="search">
            <input type="hidden" name="r" value="member/index">
            <?php if ($activeSkill !== ''): ?>
                <input type="hidden" name="skill" value="<?= e($activeSkill) ?>">
            <?php endif; ?>
            <?php if ($sort !== ''): ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <?php endif; ?>
            <label class="visually-hidden" for="member-q">搜索成员</label>
            <input class="form-control" id="member-q" name="q" type="search"
                   value="<?= e($keyword) ?>" placeholder="搜索姓名、角色或一句话介绍">
            <button class="btn btn-primary" type="submit">搜索</button>
            <?php if ($keyword !== '' || $activeSkill !== ''): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('member/index')) ?>">清除</a>
            <?php endif; ?>
        </form>

        <div class="filter-bar" role="group" aria-label="按技能筛选">
            <a class="filter-pill <?= $activeSkill === '' ? 'is-active' : '' ?>"
               href="<?= e(url('member/index', $sortParams)) ?>">全部</a>
            <?php foreach ($skills as $skill): ?>
                <a class="filter-pill <?= $activeSkill === $skill ? 'is-active' : '' ?>"
                   href="<?= e(url('member/index', $sortParams + ['skill' => $skill])) ?>"><?= e($skill) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="list-toolbar">
            <p class="result-count">共 <strong><?= e($pagination['total']) ?></strong> 位成员
                · 第 <?= e($pagination['page']) ?>/<?= e($pagination['last_page']) ?> 页
                <?= $activeSkill !== '' ? ' · 技能：' . e($activeSkill) : '' ?>
                <?= $keyword !== '' ? ' · 关键词：' . e($keyword) : '' ?>
            </p>
            <nav class="sort-bar" aria-label="排序方式">
                <span>排序</span>
                <a class="sort-link <?= $sort === '' ? 'is-active' : '' ?>"
                   href="<?= e(url('member/index', $sortParams)) ?>">默认</a>
                <a class="sort-link <?= $sort === 'name' ? 'is-active' : '' ?>"
                   href="<?= e(url('member/index', $sortParams + ['sort' => 'name'])) ?>">姓名</a>
                <a class="sort-link <?= $sort === 'join_date' ? 'is-active' : '' ?>"
                   href="<?= e(url('member/index', $sortParams + ['sort' => 'join_date'])) ?>">加入时间</a>
            </nav>
        </div>

        <?php if ($members === []): ?>
            <?php
            $emptyTitle = '没有匹配的成员';
            $emptyMessage = '换一个关键词或技能筛选，或者查看全部成员。';
            $emptyActionUrl = url('member/index');
            $emptyActionText = '清除筛选';
            require APP_ROOT . '/app/view/partial/state_empty.php';
            ?>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($members as $member): ?>
                    <div class="col-sm-6 col-lg-3">
                        <?php require APP_ROOT . '/app/view/partial/member_card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($pagination['last_page'] > 1): ?>
                <nav class="pagination-bar" aria-label="成员列表分页">
                    <a class="page-link <?= $pagination['page'] <= 1 ? 'is-disabled' : '' ?>"
                       href="<?= e(url('member/index', $pagerParams + ['page' => max(1, $pagination['page'] - 1)])) ?>"
                       <?= $pagination['page'] <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>上一页</a>
                    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                        <a class="page-link <?= $i === $pagination['page'] ? 'is-active' : '' ?>"
                           href="<?= e(url('member/index', $pagerParams + ['page' => $i])) ?>"
                           <?= $i === $pagination['page'] ? 'aria-current="page"' : '' ?>><?= e($i) ?></a>
                    <?php endfor; ?>
                    <a class="page-link <?= $pagination['page'] >= $pagination['last_page'] ? 'is-disabled' : '' ?>"
                       href="<?= e(url('member/index', $pagerParams + ['page' => min($pagination['last_page'], $pagination['page'] + 1)])) ?>"
                       <?= $pagination['page'] >= $pagination['last_page'] ? 'aria-disabled="true" tabindex="-1"' : '' ?>>下一页</a>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
