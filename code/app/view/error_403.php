<?php
/**
 * 文件名：error_403.php
 * 功能：403 无权限页面
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：错误页
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="section">
    <div class="container">
        <div class="empty-state empty-state--large">
            <p class="eyebrow">403 Forbidden</p>
            <h1>没有访问权限</h1>
            <p>该页面需要成员或管理员角色。请确认账号权限，或切换到有权限的账号后重试。</p>
            <div class="d-flex justify-content-center flex-wrap gap-3">
                <a class="btn btn-primary" href="<?= e(url('home/index')) ?>">返回首页</a>
                <a class="btn btn-outline-secondary" href="<?= e(url('auth/login')) ?>">切换账号</a>
            </div>
        </div>
    </div>
</section>
<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
