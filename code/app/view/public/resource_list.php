<?php
/**
 * 文件名：resource_list.php
 * 功能：资源下载页（分类筛选、文件信息、下载说明）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 资源
 */

require APP_ROOT . '/app/view/layout/public_header.php';

$renderGroup = static function (string $label, array $items): void {
    ?>
    <div class="resource-group">
        <h2 class="resource-group__title"><?= e($label) ?></h2>
        <div class="resource-list">
            <?php foreach ($items as $resource): ?>
                <article class="resource-item">
                    <div class="resource-item__type" aria-hidden="true"><?= e($resource['file_type']) ?></div>
                    <div class="resource-item__body">
                        <h3 class="resource-item__title"><?= e($resource['title']) ?></h3>
                        <p class="resource-item__desc"><?= e($resource['description']) ?></p>
                        <p class="resource-item__meta">
                            <?= e($resource['file_name']) ?> · <?= e($resource['size_text']) ?>
                            · 更新于 <?= e(format_date($resource['updated_at'])) ?>
                            · 下载 <?= e($resource['download_count']) ?> 次
                        </p>
                    </div>
                    <div class="resource-item__action">
                        <a class="btn btn-sm btn-primary"
                           href="<?= e(url('resource/download', ['id' => $resource['id']])) ?>">下载</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
};
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <p class="eyebrow">Resources</p>
        <h1 class="page-hero__title">资源下载</h1>
        <p class="page-hero__lead">团队文档、个人作业、设计资料与部署资料。公开资源可直接下载并记录次数。</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="alert alert-info" role="note">
            文件由下载控制器校验后输出，并记录下载次数。若提示“文件尚未上传”，请联系管理员补充文件。
        </div>

        <div class="filter-bar" role="group" aria-label="按分类筛选">
            <a class="filter-pill <?= $activeCategory === '' ? 'is-active' : '' ?>"
               href="<?= e(url('resource/index')) ?>">全部</a>
            <?php foreach ($categories as $key => $label): ?>
                <a class="filter-pill <?= $activeCategory === $key ? 'is-active' : '' ?>"
                   href="<?= e(url('resource/index', ['category' => $key])) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>

        <p class="result-count">共 <strong><?= e($total) ?></strong> 个资源<?= $activeCategory !== '' ? ' · ' . e($categories[$activeCategory]) : '' ?></p>

        <?php if ($activeCategory !== ''): ?>
            <?php $renderGroup($categories[$activeCategory], $resources); ?>
        <?php else: ?>
            <?php foreach ($categories as $key => $label): ?>
                <?php if (isset($grouped[$key])): ?>
                    <?php $renderGroup($label, $grouped[$key]); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
