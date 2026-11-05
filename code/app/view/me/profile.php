<?php
/**
 * 文件名：profile.php
 * 功能：成员中心 / 个人资料编辑（基本资料、头像封面、技能标签）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心 / 个人资料
 */

require APP_ROOT . '/app/view/layout/me_header.php';

$fieldError = static function (string $key, array $errors): string {
    return isset($errors[$key]) ? 'is-invalid' : '';
};
$errorText = static function (string $key, array $errors): string {
    return isset($errors[$key]) ? (string) $errors[$key] : '';
};
?>
<section class="me-profile-head">
    <div>
        <p class="eyebrow">Profile</p>
        <h2 class="section-title">编辑个人资料</h2>
        <p class="text-muted mb-0">修改会同步到公开成员主页。当前为演示表单，提交不会入库。</p>
    </div>
    <div class="me-profile-head__actions">
        <span class="completeness__badge">完整度 <?= e($completeness) ?>%</span>
        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('member/show', ['slug' => $member['slug']])) ?>">查看公开主页 ↗</a>
    </div>
</section>

<?php if ($conflict): ?>
    <div class="alert alert-warning" role="alert">
        资料已被他人修改，请刷新后重试。当前展示的是你的输入，刷新后会回到最新版本。
    </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="me-form" action="<?= e(url('me/updateProfile')) ?>" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_token" value="">
    <input type="hidden" name="updated_at" value="<?= e($member['updated_at'] ?? '') ?>">

    <section class="me-card">
        <div class="me-card__head">
            <div>
                <h2>基本资料</h2>
                <p>姓名、slug 与公开介绍。</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">姓名 / 昵称 <span class="required">*</span></label>
                <input class="form-control <?= e($fieldError('name', $errors)) ?>" id="name" name="name" type="text"
                       value="<?= e($old['name']) ?>" required minlength="2" maxlength="30">
                <?php if ($errorText('name', $errors) !== ''): ?><div class="invalid-feedback"><?= e($errorText('name', $errors)) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="english_name">英文名</label>
                <input class="form-control" id="english_name" name="english_name" type="text"
                       value="<?= e($old['english_name']) ?>" maxlength="60">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="slug">公开 slug <span class="required">*</span></label>
                <input class="form-control <?= e($fieldError('slug', $errors)) ?>" id="slug" name="slug" type="text"
                       value="<?= e($old['slug']) ?>" required pattern="[a-z0-9-]{3,60}">
                <p class="form-hint">只允许小写字母、数字和连字符，公开链接为 /members/{slug}。</p>
                <?php if ($errorText('slug', $errors) !== ''): ?><div class="invalid-feedback"><?= e($errorText('slug', $errors)) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="role_title">团队角色 <span class="required">*</span></label>
                <input class="form-control <?= e($fieldError('role_title', $errors)) ?>" id="role_title" name="role_title" type="text"
                       value="<?= e($old['role_title']) ?>" required maxlength="60">
                <?php if ($errorText('role_title', $errors) !== ''): ?><div class="invalid-feedback"><?= e($errorText('role_title', $errors)) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="tagline">一句话介绍</label>
                <input class="form-control <?= e($fieldError('tagline', $errors)) ?>" id="tagline" name="tagline" type="text"
                       value="<?= e($old['tagline']) ?>" maxlength="60">
                <?php if ($errorText('tagline', $errors) !== ''): ?><div class="invalid-feedback"><?= e($errorText('tagline', $errors)) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="bio">个人简介</label>
                <textarea class="form-control <?= e($fieldError('bio', $errors)) ?>" id="bio" name="bio" rows="4"
                          maxlength="500" placeholder="200—500 字，介绍你的方向、角色与代表作品"><?= e($old['bio']) ?></textarea>
                <?php if ($errorText('bio', $errors) !== ''): ?><div class="invalid-feedback"><?= e($errorText('bio', $errors)) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="location">所在地</label>
                <input class="form-control" id="location" name="location" type="text"
                       value="<?= e($old['location']) ?>" maxlength="100">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="join_date">加入日期</label>
                <input class="form-control" id="join_date" type="text" value="<?= e($member['join_date']) ?>" readonly>
                <p class="form-hint">加入日期由管理员维护，成员不可修改。</p>
            </div>
        </div>
    </section>

    <section class="me-card">
        <div class="me-card__head">
            <div>
                <h2>联系方式</h2>
                <p>邮箱默认不公开，社交链接由你选择是否填写。</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="email">邮箱</label>
                <input class="form-control <?= e($fieldError('email', $errors)) ?>" id="email" name="email" type="email"
                       value="<?= e($old['email']) ?>" maxlength="120">
                <?php if ($errorText('email', $errors) !== ''): ?><div class="invalid-feedback"><?= e($errorText('email', $errors)) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="github">GitHub</label>
                <input class="form-control <?= e($fieldError('github', $errors)) ?>" id="github" name="github" type="url"
                       value="<?= e($old['github']) ?>" placeholder="https://github.com/...">
                <?php if ($errorText('github', $errors) !== ''): ?><div class="invalid-feedback"><?= e($errorText('github', $errors)) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="website">个人网站</label>
                <input class="form-control <?= e($fieldError('website', $errors)) ?>" id="website" name="website" type="url"
                       value="<?= e($old['website']) ?>" placeholder="https://...">
                <?php if ($errorText('website', $errors) !== ''): ?><div class="invalid-feedback"><?= e($errorText('website', $errors)) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="me-card">
        <div class="me-card__head">
            <div>
                <h2>头像与封面</h2>
                <p>头像 1:1，封面 16:9；支持 JPG/PNG/WebP。</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="avatar-upload">
                    <img id="avatarPreview" src="<?= e(asset($member['avatar'])) ?>" alt="头像预览" width="120" height="120">
                    <label class="btn btn-outline-secondary btn-sm" for="avatar">更换头像</label>
                    <input class="visually-hidden" id="avatar" name="avatar" type="file" accept="image/*"
                           data-preview="avatarPreview" data-max-mb="2">
                    <p class="form-hint">建议 400×400，单文件 ≤ 2 MB。</p>
                </div>
            </div>
            <div class="col-md-8">
                <div class="cover-upload">
                    <img id="coverPreview" src="<?= e(asset($member['cover'])) ?>" alt="封面预览" width="640" height="360">
                    <label class="btn btn-outline-secondary btn-sm" for="cover">更换封面</label>
                    <input class="visually-hidden" id="cover" name="cover" type="file" accept="image/*"
                           data-preview="coverPreview" data-max-mb="5">
                    <p class="form-hint">建议 1600×900，单文件 ≤ 5 MB。上传后由后端生成缩略图。</p>
                </div>
            </div>
        </div>
    </section>

    <section class="me-card">
        <div class="me-card__head">
            <div>
                <h2>技能标签</h2>
                <p>最多 12 项、至少 3 项；熟练度 1—5。</p>
            </div>
        </div>
        <?php if ($errorText('skills', $errors) !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= e($errorText('skills', $errors)) ?></div>
        <?php endif; ?>
        <div class="skill-picker">
            <?php foreach ($skillCatalog as $skill): ?>
                <?php $isSelected = array_key_exists($skill['name'], $selectedSkills); ?>
                <div class="skill-picker__row">
                    <label class="skill-picker__check">
                        <input type="checkbox" name="skills[]" value="<?= e($skill['name']) ?>" <?= $isSelected ? 'checked' : '' ?>>
                        <span><?= e($skill['name']) ?></span>
                    </label>
                    <span class="tag"><?= e($skill['category']) ?></span>
                    <select class="form-select form-select-sm" name="proficiency[<?= e($skill['name']) ?>]" aria-label="<?= e($skill['name']) ?> 熟练度">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= e($i) ?>" <?= (int) ($selectedSkills[$skill['name']] ?? 3) === $i ? 'selected' : '' ?>><?= e($i) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="me-savebar">
        <span class="text-muted">最后保存：<?= e($member['updated_at'] ?? '—') ?></span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('me/profile')) ?>">取消</a>
            <button class="btn btn-primary" type="submit">保存修改</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/me_footer.php'; ?>
