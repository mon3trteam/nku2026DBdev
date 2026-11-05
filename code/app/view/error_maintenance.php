<?php
/**
 * 文件名：error_maintenance.php
 * 功能：503 维护页面
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：错误页
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="section">
    <div class="container">
        <div class="empty-state empty-state--large">
            <p class="eyebrow">503 Maintenance</p>
            <h1>站点正在维护</h1>
            <p>我们正在更新数据与部署配置，预计很快恢复。你可以稍后刷新页面，或先查看团队公告。</p>
            <div class="d-flex justify-content-center flex-wrap gap-3">
                <a class="btn btn-primary" href="<?= e(url('home/index')) ?>">稍后重试</a>
                <a class="btn btn-outline-secondary" href="<?= e(url('announcement/index')) ?>">查看公告</a>
            </div>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
