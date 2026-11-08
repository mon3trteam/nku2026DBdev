<?php
/**
 * 文件名：team.php
 * 功能：后台团队资料（单例表单、品牌图、价值观展示）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 团队资料
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url('admin/updateTeam')) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>基本信息</h2>
                <p>团队资料为单例（id=1）。团队名称保存后会同步更新首页、团队介绍页、浏览器标题与导航栏站点名称。</p>
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
                <input class="form-control <?= isset($errors['english_name']) ? 'is-invalid' : '' ?>"
                       id="english_name" name="english_name" type="text"
                       value="<?= e($old['english_name']) ?>" maxlength="80">
                <?php if (isset($errors['english_name'])): ?><div class="invalid-feedback"><?= e($errors['english_name']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="slogan">口号</label>
                <input class="form-control <?= isset($errors['slogan']) ? 'is-invalid' : '' ?>"
                       id="slogan" name="slogan" type="text"
                       value="<?= e($old['slogan']) ?>" maxlength="120">
                <?php if (isset($errors['slogan'])): ?><div class="invalid-feedback"><?= e($errors['slogan']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="short_intro">一句话介绍</label>
                <input class="form-control <?= isset($errors['short_intro']) ? 'is-invalid' : '' ?>"
                       id="short_intro" name="short_intro" type="text"
                       value="<?= e($old['short_intro']) ?>" maxlength="500">
                <?php if (isset($errors['short_intro'])): ?><div class="invalid-feedback"><?= e($errors['short_intro']) ?></div><?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="story">团队故事</label>
                <textarea class="form-control <?= isset($errors['story']) ? 'is-invalid' : '' ?>" id="story" name="story" rows="5"
                          maxlength="800" placeholder="300—800 字"><?= e($old['story']) ?></textarea>
                <?php if (isset($errors['story'])): ?><div class="invalid-feedback"><?= e($errors['story']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="founded_on">成立日期</label>
                <input class="form-control <?= isset($errors['founded_on']) ? 'is-invalid' : '' ?>"
                       id="founded_on" name="founded_on" type="date" value="<?= e($old['founded_on']) ?>">
                <?php if (isset($errors['founded_on'])): ?><div class="invalid-feedback"><?= e($errors['founded_on']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="location">所在地</label>
                <input class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>"
                       id="location" name="location" type="text" value="<?= e($old['location']) ?>" maxlength="100">
                <?php if (isset($errors['location'])): ?><div class="invalid-feedback"><?= e($errors['location']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">联系电话</label>
                <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                       id="phone" name="phone" type="text" value="<?= e($old['phone']) ?>" maxlength="30">
                <?php if (isset($errors['phone'])): ?><div class="invalid-feedback"><?= e($errors['phone']) ?></div><?php endif; ?>
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
                <p>支持 JPG、PNG、WebP、GIF；Logo 不超过 2 MB，封面不超过 5 MB。不选择文件则保留原图。</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="admin-preview">
                    <img id="logoPreview" src="<?= e(asset((string) ($old['logo'] ?? 'img/logo.svg'))) ?>" alt="团队 Logo 预览" width="88" height="88">
                    <label class="btn btn-outline-secondary btn-sm" for="logo">更换 Logo</label>
                    <input class="visually-hidden <?= isset($errors['logo']) ? 'is-invalid' : '' ?>" id="logo" name="logo" type="file"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           data-preview="logoPreview" data-max-mb="2">
                    <?php if (isset($errors['logo'])): ?><div class="invalid-feedback d-block"><?= e($errors['logo']) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="col-md-8">
                <div class="admin-preview">
                    <img id="coverPreview" class="admin-preview__cover" src="<?= e(asset((string) ($old['cover'] ?? 'img/placeholder-cover.svg'))) ?>" alt="团队封面预览">
                    <label class="btn btn-outline-secondary btn-sm" for="cover">更换封面</label>
                    <input class="visually-hidden <?= isset($errors['cover']) ? 'is-invalid' : '' ?>" id="cover" name="cover" type="file"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           data-preview="coverPreview" data-max-mb="5">
                    <?php if (isset($errors['cover'])): ?><div class="invalid-feedback d-block"><?= e($errors['cover']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>价值观</h2>
                <p>最多 6 条，按填写顺序展示；标题留空的行不会保存。</p>
            </div>
        </div>
        <?php
        $valueRows = $values;
        while (count($valueRows) < 3) {
            $valueRows[] = ['title' => '', 'description' => ''];
        }
        if (count($valueRows) < 6) {
            $valueRows[] = ['title' => '', 'description' => ''];
        }
        ?>
        <div class="row g-2">
            <div class="col-md-4"><span class="form-hint">标题（≤40 字）</span></div>
            <div class="col-md-8"><span class="form-hint">说明（≤160 字）</span></div>
            <?php foreach ($valueRows as $index => $value): ?>
                <div class="col-md-4">
                    <input class="form-control form-control-sm" name="value_title[]" type="text"
                           maxlength="40" value="<?= e($value['title']) ?>"
                           aria-label="第 <?= e((string) ($index + 1)) ?> 条价值观标题">
                </div>
                <div class="col-md-8">
                    <input class="form-control form-control-sm" name="value_description[]" type="text"
                           maxlength="160" value="<?= e($value['description']) ?>"
                           aria-label="第 <?= e((string) ($index + 1)) ?> 条价值观说明">
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (isset($errors['values'])): ?>
            <div class="invalid-feedback d-block mt-2"><?= e($errors['values']) ?></div>
        <?php endif; ?>
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
