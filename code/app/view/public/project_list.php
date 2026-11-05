<?php
/**
 * 文件名：project_list.php
 * 功能：项目作品列表页
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 项目
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <p class="eyebrow">Projects</p>
        <h1 class="page-hero__title">项目作品</h1>
        <p class="page-hero__lead">课程作业、团队项目与功能原型。每个项目都标注状态、技术栈与参与成员。</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Selected work</p>
                <h2 class="section-title">全部项目</h2>
            </div>
            <span class="section-note">共 <?= e(count($projects)) ?> 个项目</span>
        </div>
        <div class="row g-4">
            <?php foreach ($projects as $project): ?>
                <div class="col-md-6 col-lg-4">
                    <?php require APP_ROOT . '/app/view/partial/project_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
