<?php
/**
 * 文件名：admin_footer.php
 * 功能：管理后台页脚与脚本
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：后台布局
 */
?>
        </div>
        <footer class="admin-footer"><?= e(site_name()) ?> 管理后台 · 课程设计演示</footer>
    </div>
</div>
<?php require APP_ROOT . '/app/view/partial/toast.php'; ?>
<script src="<?= e(asset('vendor/bootstrap/5.3.8/bootstrap.bundle.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/common.js')) ?>" defer></script>
<script src="<?= e(asset('js/media-preview.js')) ?>" defer></script>
</body>
</html>
