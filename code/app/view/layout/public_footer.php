<?php
/**
 * 文件名：public_footer.php
 * 功能：公共门户页脚与脚本
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共布局
 */
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-5">
                <div class="footer-brand">数聚 TeamHub</div>
                <p class="footer-desc">把团队的每一次协作，变成看得见的作品。</p>
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
                    <li><a href="mailto:team@example.com">team@example.com</a></li>
                    <li>南开大学 · 互联网数据库开发</li>
                    <li>天津</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© 2026 数聚 TeamHub · 课程设计演示</span>
            <a href="<?= e(url('auth/login')) ?>">管理入口</a>
        </div>
    </div>
</footer>

<?php require APP_ROOT . '/app/view/partial/toast.php'; ?>
<script src="<?= e(asset('vendor/bootstrap/5.3.8/bootstrap.bundle.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/common.js')) ?>" defer></script>
</body>
</html>
