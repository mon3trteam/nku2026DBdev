<?php
/**
 * 文件名：resource_upload.php
 * 功能：后台上传下载资源（标题、分类、说明、可见性、文件）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 资源管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url('admin/storeResource')) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>资源信息</h2>
                <p>保存后立即出现在资源列表；公开资源可被访客下载。</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="title">资源标题 <span class="required">*</span></label>
                <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                       id="title" name="title" type="text" maxlength="150" required
                       value="<?= e($old['title']) ?>" placeholder="例如：M3 需求文档">
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
                          id="description" name="description" rows="3" maxlength="500"
                          placeholder="这份文件包含什么、适用哪次作业"><?= e($old['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>文件</h2>
                <p>最大 10 MB；团队文档与个人作业分别存入 code/data/team、code/data/personal。</p>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="file">选择文件 <span class="required">*</span></label>
            <input class="form-control <?= isset($errors['file']) ? 'is-invalid' : '' ?>"
                   id="file" name="file" type="file" required
                   accept=".pdf,.zip,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.png,.jpg,.jpeg">
            <?php if (isset($errors['file'])): ?><div class="invalid-feedback"><?= e($errors['file']) ?></div><?php endif; ?>
            <p class="form-hint">允许 PDF、ZIP、Office 文档、TXT/Markdown、PNG/JPG；文件名会保留中文展示名。</p>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                   <?= (int) $old['is_public'] === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_public">公开资源（访客可下载）</label>
        </div>
    </section>

    <div class="admin-savebar">
        <span class="text-muted">上传后会写入 media 与 resources 两张表，并记录审计日志。</span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/resources')) ?>">取消</a>
            <button class="btn btn-primary" type="submit">上传资源</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
