<?php
/**
 * 文件名：team.php
 * 功能：后台团队资料（单例表单、品牌图、价值观展示）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 团队资料
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url('admin/updateTeam')) ?>" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_token" value="">

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>基本信息</h2>
                <p>团队资料为单例（id=1），保存后同步到前台。</p>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">团队名称 <span class="required">*</span></label>
                <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" type="text"
                       value="<?= e($old['name']) ?>" required maxlength="80">
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="english_name">英文名</label>
                <input class="form-control" id="english_name" name="english_name" type="text"
                       value="<?= e($old['english_name']) ?>" maxlength="80">
            </div>
            <div class="col-12">
                <label class="form-label" for="slogan">口号</label>
                <input class="form-control" id="slogan" name="slogan" type="text"
                       value="<?= e($old['slogan']) ?>" maxlength="120">
            </div>
            <div class="col-12">
                <label class="form-label" for="short_intro">一句话介绍</label>
                <input class="form-control" id="short_intro" name="short_intro" type="text"
                       value="<?= e($old['short_intro']) ?>" maxlength="500">
            </div>
            <div class="col-12">
                <label class="form-label" for="story">团队故事</label>
                <textarea class="form-control <?= isset($errors['story']) ? 'is-invalid' : '' ?>" id="story" name="story" rows="5"
                          maxlength="800" placeholder="300—800 字"><?= e($old['story']) ?></textarea>
                <?php if (isset($errors['story'])): ?><div class="invalid-feedback"><?= e($errors['story']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="founded_on">成立日期</label>
                <input class="form-control" id="founded_on" name="founded_on" type="date" value="<?= e($old['founded_on']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="location">所在地</label>
                <input class="form-control" id="location" name="location" type="text" value="<?= e($old['location']) ?>" maxlength="100">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">联系电话</label>
                <input class="form-control" id="phone" name="phone" type="text" value="<?= e($old['phone']) ?>" maxlength="30">
                <p class="form-hint">默认不公开。</p>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">联系邮箱</label>
                <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email"
                       value="<?= e($old['email']) ?>" maxlength="120">
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>品牌图</h2>
                <p>Logo 与封面；上传接口接入后生效。</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="admin-preview">
                    <img id="logoPreview" src="<?= e(asset('img/logo.svg')) ?>" alt="团队 Logo 预览" width="88" height="88">
                    <label class="btn btn-outline-secondary btn-sm" for="logo">更换 Logo</label>
                    <input class="visually-hidden" id="logo" name="logo" type="file" accept="image/*"
                           data-preview="logoPreview" data-max-mb="2">
                </div>
            </div>
            <div class="col-md-8">
                <div class="admin-preview">
                    <img id="coverPreview" class="admin-preview__cover" src="<?= e(asset('img/placeholder-cover.svg')) ?>" alt="团队封面预览">
                    <label class="btn btn-outline-secondary btn-sm" for="cover">更换封面</label>
                    <input class="visually-hidden" id="cover" name="cover" type="file" accept="image/*"
                           data-preview="coverPreview" data-max-mb="5">
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>价值观</h2>
                <p>来自 values_json；编辑接口接入后开放排序与增删。</p>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($values as $value): ?>
                <div class="col-sm-6">
                    <div class="admin-value">
                        <strong><?= e($value['title']) ?></strong>
                        <span><?= e($value['description']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="admin-savebar">
        <span class="text-muted">最后保存：<?= e($team['updated_at'] ?? '—') ?></span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/team')) ?>">取消</a>
            <button class="btn btn-primary" type="submit">保存团队资料</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
