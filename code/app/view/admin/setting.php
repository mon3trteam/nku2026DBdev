<?php
/**
 * 文件名：setting.php
 * 功能：后台站点设置（按分组渲染键值表单）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 站点设置
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url('admin/updateSettings')) ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <?php foreach ($groups as $groupKey => $groupLabel): ?>
        <?php if (!isset($grouped[$groupKey])): continue; endif; ?>
        <section class="admin-card">
            <div class="admin-card__head">
                <div>
                    <h2><?= e($groupLabel) ?></h2>
                    <p>键值配置；公开项会用于前台导航品牌、页脚与 SEO，保存后刷新前台生效。</p>
                </div>
            </div>
            <div class="row g-3">
                <?php foreach ($grouped[$groupKey] as $setting): ?>
                    <?php
                    $key = $setting['key'];
                    $value = $old[$key] ?? $setting['value'];
                    ?>
                    <div class="col-12">
                        <label class="form-label" for="setting-<?= e($key) ?>"><?= e($setting['label']) ?></label>
                        <?php if ($setting['type'] === 'bool'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="setting-<?= e($key) ?>"
                                       name="settings[<?= e($key) ?>]" value="1" <?= (string) $value === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="setting-<?= e($key) ?>">启用</label>
                            </div>
                        <?php elseif ($setting['type'] === 'json'): ?>
                            <textarea class="form-control <?= isset($errors[$key]) ? 'is-invalid' : '' ?>"
                                      id="setting-<?= e($key) ?>" name="settings[<?= e($key) ?>]" rows="3"><?= e($value) ?></textarea>
                            <?php if (isset($errors[$key])): ?><div class="invalid-feedback"><?= e($errors[$key]) ?></div><?php endif; ?>
                        <?php else: ?>
                            <input class="form-control <?= isset($errors[$key]) ? 'is-invalid' : '' ?>"
                                   id="setting-<?= e($key) ?>" name="settings[<?= e($key) ?>]" type="text"
                                   value="<?= e($value) ?>">
                            <?php if (isset($errors[$key])): ?><div class="invalid-feedback"><?= e($errors[$key]) ?></div><?php endif; ?>
                        <?php endif; ?>
                        <p class="form-hint">键名：<code><?= e($key) ?></code> · 类型：<?= e($setting['type']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="admin-savebar">
        <span class="text-muted">保存后立即写入数据库；前台公开配置随页面读取。</span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/settings')) ?>">取消修改</a>
            <button class="btn btn-primary" type="submit">保存设置</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
