<?php
/**
 * 文件名：resource_edit.php
 * 功能：后台编辑下载资源（标题、分类、说明、可见性、可选替换文件）
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 资源管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url('admin/updateResource')) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($old['id']) ?>">

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>资源信息</h2>
                <p>保存后立即更新下载列表；隐藏后前台不可下载。</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="title">资源标题 <span class="required">*</span></label>
                <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                       id="title" name="title" type="text" maxlength="150" required
                       value="<?= e($old['title']) ?>">
                <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= e($errors['title']) ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="category">分类</label>
                <select class="form-select form-control <?= isset($errors['category']) ? 'is-invalid' : '' ?>"
                        id="category" name="category">
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= $old['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category'])): ?><div class="invalid-feedback"><?= e($errors['category']) ?></div><?php endif; ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="description">说明</label>
                <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                          id="description" name="description" rows="3" maxlength="500"><?= e($old['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>文件</h2>
                <p>不选择新文件时保留现有文件。</p>
            </div>
        </div>

        <dl class="message-admin-meta mb-4">
            <div>
                <dt>当前文件</dt>
                <dd><code><?= e($resource['file_name']) ?></code></dd>
            </div>
            <div>
                <dt>类型 / 大小</dt>
                <dd><?= e($resource['file_type']) ?> · <?= e($resource['size_text']) ?></dd>
            </div>
            <div>
                <dt>下载次数</dt>
                <dd><?= e($resource['download_count']) ?></dd>
            </div>
            <div>
                <dt>存储路径</dt>
                <dd><code><?= e($resource['storage_path']) ?></code></dd>
            </div>
        </dl>

        <div class="mb-3">
            <label class="form-label" for="file">替换文件（可选）</label>
            <input class="form-control <?= isset($errors['file']) ? 'is-invalid' : '' ?>"
                   id="file" name="file" type="file"
                   accept=".pdf,.zip,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.png,.jpg,.jpeg">
            <?php if (isset($errors['file'])): ?><div class="invalid-feedback"><?= e($errors['file']) ?></div><?php endif; ?>
            <p class="form-hint">最大 10 MB；替换成功后旧文件会从磁盘删除（仍被其他内容引用时保留）。</p>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                   <?= (int) $old['is_public'] === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_public">公开资源（访客可下载）</label>
        </div>
    </section>

    <div class="admin-savebar">
        <span class="text-muted">保存后会写入审计日志。</span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/resources')) ?>">取消</a>
            <button class="btn btn-primary" type="submit">保存资源</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
