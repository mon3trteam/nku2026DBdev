<?php
/**
 * 文件名：announcement_list.php
 * 功能：公告中心列表页（分类筛选、置顶、空状态）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 公告
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <p class="eyebrow">Announcements</p>
        <h1 class="page-hero__title">公告中心</h1>
        <p class="page-hero__lead">团队动态、项目通知与功能预告。置顶公告优先展示。</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">
                <p class="result-count">共 <strong><?= e(count($announcements)) ?></strong> 条公告<?= $activeCategory !== '' ? ' · ' . e($categories[$activeCategory]) : '' ?></p>

                <?php if ($announcements === []): ?>
                    <?php
                    $emptyTitle = '这个分类还没有公告';
                    $emptyMessage = '换一个分类，或者查看全部公告。';
                    $emptyActionUrl = url('announcement/index');
                    $emptyActionText = '查看全部公告';
                    require APP_ROOT . '/app/view/partial/state_empty.php';
                    ?>
                <?php else: ?>
                    <div class="announcement-list">
                        <?php foreach ($announcements as $announcement): ?>
                            <?php require APP_ROOT . '/app/view/partial/announcement_item.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <aside class="col-lg-4">
                <div class="side-panel">
                    <h2 class="side-panel__title">分类</h2>
                    <ul class="side-list">
                        <li>
                            <a class="<?= $activeCategory === '' ? 'is-active' : '' ?>"
                               href="<?= e(url('announcement/index')) ?>">全部公告</a>
                        </li>
                        <?php foreach ($categories as $key => $label): ?>
                            <li>
                                <a class="<?= $activeCategory === $key ? 'is-active' : '' ?>"
                                   href="<?= e(url('announcement/index', ['category' => $key])) ?>"><?= e($label) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="side-panel">
                    <h2 class="side-panel__title">说明</h2>
                    <p class="text-muted mb-0">公告正文使用受限 Markdown 渲染，发布后出现在首页最新动态。当前为演示数据。</p>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
