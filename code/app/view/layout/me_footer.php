<?php
/**
 * 文件名：me_footer.php
 * 功能：成员中心页脚与脚本
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心布局
 */
?>
        </div>
        <footer class="me-footer"><?= e(site_name()) ?> 成员中心 · 课程设计演示</footer>
    </div>
</div>
<?php require APP_ROOT . '/app/view/partial/toast.php'; ?>
<script src="<?= e(asset('vendor/bootstrap/5.3.8/bootstrap.bundle.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/common.js')) ?>" defer></script>
<script src="<?= e(asset('js/media-preview.js')) ?>" defer></script>
</body>
</html>
