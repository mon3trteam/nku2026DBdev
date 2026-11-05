<?php
/**
 * 文件名：error_404.php
 * 功能：404 页面
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：错误页
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="section">
    <div class="container">
        <div class="empty-state empty-state--large">
            <p class="eyebrow">404</p>
            <h1>页面不存在</h1>
            <p>链接可能已经失效，或者内容尚未开放。你可以返回首页，或查看团队成员。</p>
            <div class="d-flex justify-content-center flex-wrap gap-3">
                <a class="btn btn-primary" href="<?= e(url('home/index')) ?>">返回首页</a>
                <a class="btn btn-outline-secondary" href="<?= e(url('member/index')) ?>">查看成员</a>
            </div>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
