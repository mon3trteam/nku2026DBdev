<?php
/**
 * 文件名：error_request.php
 * 功能：通用请求错误页（405 方法不允许、419 CSRF 过期等）
 * 作者：（后端负责人 学号 姓名）
 * 创建日期：2026-11-08
 * 所属模块：错误页
 */

$errorCode = $errorCode ?? 400;
$errorTitle = $errorTitle ?? '请求无法处理';
$errorMessage = $errorMessage ?? '请检查操作后重试。';

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="section">
    <div class="container">
        <div class="empty-state empty-state--large">
            <p class="eyebrow"><?= e($errorCode) ?> Request error</p>
            <h1><?= e($errorTitle) ?></h1>
            <p><?= e($errorMessage) ?></p>
            <div class="d-flex justify-content-center flex-wrap gap-3">
                <a class="btn btn-primary" href="<?= e(url('home/index')) ?>">返回首页</a>
                <a class="btn btn-outline-secondary" href="javascript:history.back()">返回上一页</a>
            </div>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
