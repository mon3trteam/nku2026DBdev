<?php
/**
 * 文件名：public_footer.php
 * 功能：公共门户页脚与脚本
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共布局
 */
?>
<?php
$footerEmail = trim((string) ($siteSettings['contact.email'] ?? ''));
if ($footerEmail === '') {
    $footerEmail = trim((string) ($siteTeam['email'] ?? ''));
}
$footerLocation = trim((string) ($siteSettings['contact.location'] ?? ''));
if ($footerLocation === '') {
    $footerLocation = trim((string) ($siteTeam['location'] ?? ''));
}
$footerSlogan = trim((string) ($siteTeam['slogan'] ?? ''));
if ($footerSlogan === '') {
    $footerSlogan = $siteDescription !== '' ? $siteDescription : '把团队的每一次协作，变成看得见的作品。';
}
$footerCopyright = trim((string) ($siteSettings['footer.copyright'] ?? ''));
if ($footerCopyright === '') {
    $footerCopyright = '© ' . date('Y') . ' ' . $siteName . ' · 课程设计演示';
}
$footerIcp = trim((string) ($siteSettings['footer.icp'] ?? ''));
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-5">
                <div class="footer-brand"><?= e($siteName) ?></div>
                <p class="footer-desc"><?= e($footerSlogan) ?></p>
            </div>
            <div class="col-6 col-lg-3">
                <div class="footer-title">浏览</div>
                <ul class="footer-links">
                    <li><a href="<?= e(url('home/index')) ?>">首页</a></li>
                    <li><a href="<?= e(url('team/index')) ?>">团队介绍</a></li>
                    <li><a href="<?= e(url('member/index')) ?>">成员</a></li>
                    <li><a href="<?= e(url('announcement/index')) ?>">公告</a></li>
                    <li><a href="<?= e(url('resource/index')) ?>">资源</a></li>
                    <li><a href="<?= e(url('message/index')) ?>">留言板</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-4">
                <div class="footer-title">联系</div>
                <ul class="footer-links">
                    <?php if ($footerEmail !== ''): ?>
                        <li><a href="mailto:<?= e($footerEmail) ?>"><?= e($footerEmail) ?></a></li>
                    <?php endif; ?>
                    <li>南开大学 · 互联网数据库开发</li>
                    <?php if ($footerLocation !== ''): ?><li><?= e($footerLocation) ?></li><?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span><?= e($footerCopyright) ?></span>
            <?php if ($footerIcp !== ''): ?><span><?= e($footerIcp) ?></span><?php endif; ?>
            <span class="d-flex gap-3">
                <a href="<?= e(url('me/index')) ?>">成员中心</a>
                <a href="<?= e(url('auth/login')) ?>">管理入口</a>
            </span>
        </div>
    </div>
</footer>

<?php require APP_ROOT . '/app/view/partial/toast.php'; ?>
<script src="<?= e(asset('vendor/bootstrap/5.3.8/bootstrap.bundle.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/common.js')) ?>" defer></script>
</body>
</html>
