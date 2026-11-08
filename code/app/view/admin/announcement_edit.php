<?php
/**
 * 文件名：announcement_edit.php
 * 功能：后台公告编辑（正文、分类、发布状态、置顶与时间）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 公告管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$publishedValue = $old['published_at'] !== '' ? str_replace(' ', 'T', (string) $old['published_at']) : '';
$expiresValue = $old['expires_at'] !== '' ? str_replace(' ', 'T', (string) $old['expires_at']) : '';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url('admin/updateAnnouncement')) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($old['id']) ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>正文</h2>
                        <p>正文使用受限 Markdown；发布前请预览。</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="title">标题 <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title" name="title" type="text"
                               value="<?= e($old['title']) ?>" required maxlength="150">
                        <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= e($errors['title']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="slug">slug <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" id="slug" name="slug" type="text"
                               value="<?= e($old['slug']) ?>" required pattern="[a-z0-9-]{3,100}">
                        <?php if (isset($errors['slug'])): ?><div class="invalid-feedback"><?= e($errors['slug']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category">分类</label>
                        <select class="form-select form-control" id="category" name="category">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $old['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="summary">摘要</label>
                        <input class="form-control" id="summary" name="summary" type="text"
                               value="<?= e($old['summary']) ?>" maxlength="300">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="content">正文 <span class="required">*</span></label>
                        <textarea class="form-control <?= isset($errors['content']) ? 'is-invalid' : '' ?>" id="content" name="content"
                                  rows="12" required><?= e($old['content']) ?></textarea>
                        <?php if (isset($errors['content'])): ?><div class="invalid-feedback"><?= e($errors['content']) ?></div><?php endif; ?>
                        <p class="form-hint">支持标题、粗体、链接、列表、引用与代码块；原始 HTML 会被转义。</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>发布设置</h2>
                        <p>状态与时间。</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="status">状态</label>
                        <select class="form-select form-control" id="status" name="status">
                            <option value="draft" <?= $old['status'] === 'draft' ? 'selected' : '' ?>>草稿</option>
                            <option value="published" <?= $old['status'] === 'published' ? 'selected' : '' ?>>已发布</option>
                            <option value="offline" <?= $old['status'] === 'offline' ? 'selected' : '' ?>>已下线</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned" value="1"
                                   <?= (int) $old['is_pinned'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_pinned">置顶（最多 2 条）</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="published_at">发布时间</label>
                        <input class="form-control" id="published_at" name="published_at" type="datetime-local" value="<?= e($publishedValue) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="expires_at">过期时间</label>
                        <input class="form-control" id="expires_at" name="expires_at" type="datetime-local" value="<?= e($expiresValue) ?>">
                        <p class="form-hint">留空表示长期有效。</p>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>封面</h2>
                        <p>16:9 图片。</p>
                    </div>
                </div>
                <div class="admin-preview">
                    <img id="coverPreview" class="admin-preview__cover" src="<?= e(asset('img/placeholder-cover.svg')) ?>" alt="公告封面预览">
                    <label class="btn btn-outline-secondary btn-sm" for="cover">更换封面</label>
                    <input class="visually-hidden" id="cover" name="cover" type="file" accept="image/*"
                           data-preview="coverPreview" data-max-mb="5">
                </div>
            </section>
        </div>
    </div>

    <div class="admin-savebar">
        <span class="text-muted">当前为演示表单，保存不会入库。</span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/announcements')) ?>">取消</a>
            <?php if ($old['status'] === 'published'): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('announcement/show', ['slug' => $old['slug']])) ?>" target="_blank" rel="noopener">预览</a>
            <?php endif; ?>
            <button class="btn btn-primary" type="submit">保存公告</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
