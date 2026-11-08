<?php
/**
 * 文件名：announcement_edit.php
 * 功能：后台公告编辑（正文、分类、发布状态、置顶与时间）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 公告管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$publishedValue = (string) ($old['published_at'] ?? '');
$publishedValue = $publishedValue !== '' ? str_replace(' ', 'T', $publishedValue) : '';
$expiresValue = (string) ($old['expires_at'] ?? '');
$expiresValue = $expiresValue !== '' ? str_replace(' ', 'T', $expiresValue) : '';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url((int) $old['id'] > 0 ? 'admin/updateAnnouncement' : 'admin/storeAnnouncement')) ?>" method="post" enctype="multipart/form-data" novalidate>
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
                        <select class="form-select form-control <?= isset($errors['category']) ? 'is-invalid' : '' ?>" id="category" name="category">
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $old['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category'])): ?><div class="invalid-feedback"><?= e($errors['category']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="summary">摘要</label>
                        <input class="form-control <?= isset($errors['summary']) ? 'is-invalid' : '' ?>"
                               id="summary" name="summary" type="text"
                               value="<?= e($old['summary']) ?>" maxlength="300">
                        <?php if (isset($errors['summary'])): ?><div class="invalid-feedback"><?= e($errors['summary']) ?></div><?php endif; ?>
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
                        <select class="form-select form-control <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status">
                            <option value="draft" <?= $old['status'] === 'draft' ? 'selected' : '' ?>>草稿</option>
                            <option value="published" <?= $old['status'] === 'published' ? 'selected' : '' ?>>已发布</option>
                            <option value="offline" <?= $old['status'] === 'offline' ? 'selected' : '' ?>>已下线</option>
                        </select>
                        <?php if (isset($errors['status'])): ?><div class="invalid-feedback"><?= e($errors['status']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned" value="1"
                                   <?= (int) $old['is_pinned'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_pinned">置顶（最多 2 条）</label>
                        </div>
                        <?php if (isset($errors['is_pinned'])): ?>
                            <div class="text-danger small mt-1"><?= e($errors['is_pinned']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="published_at">发布时间</label>
                        <input class="form-control <?= isset($errors['published_at']) ? 'is-invalid' : '' ?>"
                               id="published_at" name="published_at" type="datetime-local" value="<?= e($publishedValue) ?>">
                        <?php if (isset($errors['published_at'])): ?><div class="invalid-feedback"><?= e($errors['published_at']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="expires_at">过期时间</label>
                        <input class="form-control <?= isset($errors['expires_at']) ? 'is-invalid' : '' ?>"
                               id="expires_at" name="expires_at" type="datetime-local" value="<?= e($expiresValue) ?>">
                        <p class="form-hint">留空表示长期有效；已发布公告留空发布时间时自动记为当前时间。</p>
                        <?php if (isset($errors['expires_at'])): ?><div class="invalid-feedback"><?= e($errors['expires_at']) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>封面</h2>
                        <p>16:9 图片；不选择文件则保留原封面。</p>
                    </div>
                </div>
                <div class="admin-preview">
                    <img id="coverPreview" class="admin-preview__cover"
                         src="<?= e(asset((string) ($old['cover'] ?? 'img/placeholder-cover.svg'))) ?>" alt="公告封面预览">
                    <label class="btn btn-outline-secondary btn-sm" for="cover">更换封面</label>
                    <input class="visually-hidden" id="cover" name="cover" type="file"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           data-preview="coverPreview" data-max-mb="5">
                    <p class="form-hint mt-2 mb-0">支持 JPG/PNG/WebP/GIF，单文件 ≤ 5 MB。</p>
                    <?php if (isset($errors['cover'])): ?><div class="invalid-feedback d-block"><?= e($errors['cover']) ?></div><?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="admin-savebar">
        <span class="text-muted">保存后立即更新公告；标记已发布后前台可见。</span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/announcements')) ?>">取消</a>
            <?php if ((int) $old['id'] > 0 && $old['status'] === 'published'): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('announcement/show', ['slug' => $old['slug']])) ?>" target="_blank" rel="noopener">预览</a>
            <?php endif; ?>
            <button class="btn btn-primary" type="submit">保存公告</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
