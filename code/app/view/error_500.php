<?php
/**
 * 文件名：error_500.php
 * 功能：500 服务错误页面（不暴露堆栈）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：错误页
 */

require APP_ROOT . '/app/view/layout/public_header.php';
$requestId = 'REQ-DEMO-' . strtoupper(substr(md5((string) microtime(true)), 0, 6));
?>
<section class="section">
    <div class="container">
        <div class="empty-state empty-state--large empty-state--error">
            <p class="eyebrow">500 Server Error</p>
            <h1>服务暂时不可用</h1>
            <p>请求没有完成，请稍后重试。如果问题持续出现，请把下面的请求编号反馈给管理员。</p>
            <p class="request-id">请求编号：<?= e($requestId) ?></p>
            <div class="d-flex justify-content-center flex-wrap gap-3">
                <a class="btn btn-primary" href="<?= e(url('home/index')) ?>">重试</a>
                <a class="btn btn-outline-secondary" href="mailto:team@example.com">联系管理员</a>
            </div>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
