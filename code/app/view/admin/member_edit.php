<?php
/**
 * 文件名：member_edit.php
 * 功能：后台编辑成员公开档案与展示状态
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 成员管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$isEdit = (int) ($old['id'] ?? 0) > 0;
$avatar = (string) ($old['avatar'] ?? 'img/placeholder-avatar.svg');
?>
<?php if (!empty($errors['updated_at'])): ?>
    <div class="alert alert-warning" role="alert"><?= e($errors['updated_at']) ?></div>
<?php endif; ?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url('admin/updateMember')) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string) ($old['id'] ?? 0)) ?>">
<input type="hidden" name="updated_at" value="<?= e((string) ($old['updated_at'] ?? '')) ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>公开资料</h2>
                        <p>这些字段会显示在前台成员列表与个人主页。</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">姓名 <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               id="name" name="name" type="text" value="<?= e((string) ($old['name'] ?? '')) ?>" required maxlength="50">
                        <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="english_name">英文名</label>
                        <input class="form-control <?= isset($errors['english_name']) ? 'is-invalid' : '' ?>"
                               id="english_name" name="english_name" type="text"
                               value="<?= e((string) ($old['english_name'] ?? '')) ?>" maxlength="60">
                        <?php if (isset($errors['english_name'])): ?><div class="invalid-feedback"><?= e($errors['english_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="slug">slug <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>"
                               id="slug" name="slug" type="text" value="<?= e((string) ($old['slug'] ?? '')) ?>"
                               required pattern="[a-z0-9-]{3,60}">
                        <p class="form-hint">公开链接：member/show?slug=...</p>
                        <?php if (isset($errors['slug'])): ?><div class="invalid-feedback"><?= e($errors['slug']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="role_title">团队角色 <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['role_title']) ? 'is-invalid' : '' ?>"
                               id="role_title" name="role_title" type="text"
                               value="<?= e((string) ($old['role_title'] ?? '')) ?>" required maxlength="60">
                        <?php if (isset($errors['role_title'])): ?><div class="invalid-feedback"><?= e($errors['role_title']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="tagline">一句话介绍</label>
                        <input class="form-control <?= isset($errors['tagline']) ? 'is-invalid' : '' ?>"
                               id="tagline" name="tagline" type="text"
                               value="<?= e((string) ($old['tagline'] ?? '')) ?>" maxlength="150">
                        <?php if (isset($errors['tagline'])): ?><div class="invalid-feedback"><?= e($errors['tagline']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="bio">个人简介</label>
                        <textarea class="form-control <?= isset($errors['bio']) ? 'is-invalid' : '' ?>"
                                  id="bio" name="bio" rows="7" maxlength="500"><?= e((string) ($old['bio'] ?? '')) ?></textarea>
                        <?php if (isset($errors['bio'])): ?><div class="invalid-feedback"><?= e($errors['bio']) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>联系方式</h2>
                        <p>邮箱默认不在前台展示，仅团队内部可见。</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="email">邮箱</label>
                        <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               id="email" name="email" type="email" value="<?= e((string) ($old['email'] ?? '')) ?>" maxlength="120">
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="location">所在地</label>
                        <input class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>"
                               id="location" name="location" type="text" value="<?= e((string) ($old['location'] ?? '')) ?>" maxlength="100">
                        <?php if (isset($errors['location'])): ?><div class="invalid-feedback"><?= e($errors['location']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="github_url">GitHub 链接</label>
                        <input class="form-control <?= isset($errors['github_url']) ? 'is-invalid' : '' ?>"
                               id="github_url" name="github_url" type="url"
                               value="<?= e((string) ($old['github_url'] ?? '')) ?>" placeholder="https://github.com/...">
                        <?php if (isset($errors['github_url'])): ?><div class="invalid-feedback"><?= e($errors['github_url']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="website_url">个人网站</label>
                        <input class="form-control <?= isset($errors['website_url']) ? 'is-invalid' : '' ?>"
                               id="website_url" name="website_url" type="url"
                               value="<?= e((string) ($old['website_url'] ?? '')) ?>" placeholder="https://...">
                        <?php if (isset($errors['website_url'])): ?><div class="invalid-feedback"><?= e($errors['website_url']) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>技能标签</h2>
                        <p>勾选 3—12 项并设置熟练度，保存后同步到前台能力矩阵。</p>
                    </div>
                </div>
                <?php if (isset($errors['skills'])): ?>
                    <div class="alert alert-danger" role="alert"><?= e($errors['skills']) ?></div>
                <?php endif; ?>
                <div class="admin-skill-grid">
                    <?php foreach ($skillCatalog as $skill): ?>
                        <?php $isSelected = array_key_exists($skill['name'], $selectedSkills); ?>
                        <div class="admin-skill-row">
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="skills[]"
                                       value="<?= e($skill['name']) ?>" <?= $isSelected ? 'checked' : '' ?>>
                                <span class="form-check-label"><?= e($skill['name']) ?></span>
                            </label>
                            <span class="badge badge--muted"><?= e($skill['category']) ?></span>
                            <select class="form-select form-select-sm" name="proficiency[<?= e($skill['name']) ?>]"
                                    aria-label="<?= e($skill['name']) ?> 熟练度">
                                <?php for ($level = 1; $level <= 5; $level++): ?>
                                    <option value="<?= e((string) $level) ?>"
                                        <?= (int) ($selectedSkills[$skill['name']] ?? 3) === $level ? 'selected' : '' ?>>
                                        <?= e((string) $level) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>展示设置</h2>
                        <p>控制前台可见性与排序。</p>
                    </div>
                </div>
                <div class="admin-preview">
                    <img id="avatarPreview" class="admin-preview__avatar" src="<?= e(asset($avatar)) ?>" alt="成员头像">
                </div>
                <div class="mt-2">
                    <label class="btn btn-outline-secondary btn-sm" for="avatar">更换头像</label>
                    <input class="visually-hidden" id="avatar" name="avatar" type="file"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           data-preview="avatarPreview" data-max-mb="2">
                    <p class="form-hint mb-0">头像使用圆形裁切，建议 400×400，单文件 ≤ 2 MB。</p>
                    <?php if (isset($errors['avatar'])): ?><div class="invalid-feedback d-block"><?= e($errors['avatar']) ?></div><?php endif; ?>
                </div>
                <div class="admin-preview mt-3">
                    <img id="coverPreview" class="admin-preview__cover" src="<?= e(asset((string) ($old['cover'] ?? 'img/placeholder-cover.svg'))) ?>" alt="成员封面">
                </div>
                <div class="mt-2">
                    <label class="btn btn-outline-secondary btn-sm" for="cover">更换封面</label>
                    <input class="visually-hidden" id="cover" name="cover" type="file"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           data-preview="coverPreview" data-max-mb="5">
                    <p class="form-hint mb-0">封面建议 1600×900，单文件 ≤ 5 MB。</p>
                    <?php if (isset($errors['cover'])): ?><div class="invalid-feedback d-block"><?= e($errors['cover']) ?></div><?php endif; ?>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" value="1"
                                   <?= (int) ($old['is_visible'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_visible">前台公开</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                                   <?= (int) ($old['is_featured'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_featured">首页精选</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="join_date">加入日期</label>
                        <input class="form-control <?= isset($errors['join_date']) ? 'is-invalid' : '' ?>"
                               id="join_date" name="join_date" type="date" value="<?= e((string) ($old['join_date'] ?? '')) ?>">
                        <?php if (isset($errors['join_date'])): ?><div class="invalid-feedback"><?= e($errors['join_date']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="sort_order">排序</label>
                        <input class="form-control <?= isset($errors['sort_order']) ? 'is-invalid' : '' ?>"
                               id="sort_order" name="sort_order" type="number" min="-9999" max="9999"
                               value="<?= e((string) ($old['sort_order'] ?? 0)) ?>">
                        <p class="form-hint">数字越小越靠前，可为负数。</p>
                        <?php if (isset($errors['sort_order'])): ?><div class="invalid-feedback"><?= e($errors['sort_order']) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>资料状态</h2>
                    </div>
                </div>
                <p class="mb-2">当前完整度：<strong><?= e((int) ($old['profile_completeness'] ?? 0)) ?>%</strong></p>
                <p class="form-hint mb-0">保存时按姓名、角色、简介、联系方式、所在地与技能数量重新计算。</p>
                <?php if ($isEdit): ?>
                    <p class="form-hint mb-0">技能项：<?= e((string) count($selectedSkills)) ?> 项（在上方技能卡中调整）。</p>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <div class="admin-savebar">
        <span class="text-muted">保存后立即更新前台成员列表与个人主页。</span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/members')) ?>">取消</a>
            <?php if ($isEdit && (int) ($old['is_visible'] ?? 0) === 1): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('member/show', ['slug' => $old['slug']])) ?>"
                   target="_blank" rel="noopener">预览</a>
            <?php endif; ?>
            <button class="btn btn-primary" type="submit">保存成员</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
