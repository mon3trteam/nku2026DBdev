<?php
/**
 * 文件名：project_edit.php
 * 功能：后台项目新建与编辑（基本信息、时间、链接、成员分工、公开设置）
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 项目管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$isEdit = (int) $old['id'] > 0;
$memberValues = $old['members'] ?? [];
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url($isEdit ? 'admin/updateProject' : 'admin/storeProject')) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($old['id']) ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>项目信息</h2>
                        <p>详情支持受限 Markdown，原始 HTML 会被转义。</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="title">项目名称 <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                               id="title" name="title" type="text" value="<?= e($old['title']) ?>" required maxlength="120">
                        <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= e($errors['title']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="slug">slug <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>"
                               id="slug" name="slug" type="text" value="<?= e($old['slug']) ?>"
                               required pattern="[a-z0-9-]{3,80}">
                        <p class="form-hint">公开链接：project/show?slug=...</p>
                        <?php if (isset($errors['slug'])): ?><div class="invalid-feedback"><?= e($errors['slug']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="status">状态</label>
                        <select class="form-select form-control <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $old['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['status'])): ?><div class="invalid-feedback"><?= e($errors['status']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="summary">摘要</label>
                        <input class="form-control <?= isset($errors['summary']) ? 'is-invalid' : '' ?>"
                               id="summary" name="summary" type="text" value="<?= e((string) ($old['summary'] ?? '')) ?>" maxlength="300">
                        <?php if (isset($errors['summary'])): ?><div class="invalid-feedback"><?= e($errors['summary']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="description">项目详情</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  id="description" name="description" rows="10"><?= e((string) ($old['description'] ?? '')) ?></textarea>
                        <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="tech_stack">技术栈</label>
                        <input class="form-control <?= isset($errors['tech_stack']) ? 'is-invalid' : '' ?>"
                               id="tech_stack" name="tech_stack" type="text"
                               value="<?= e((string) ($old['tech_stack'] ?? '')) ?>" maxlength="255"
                               placeholder="PHP 8.2, MySQL 8.0, Docker">
                        <p class="form-hint">使用英文逗号分隔，展示时按逗号拆成标签。</p>
                        <?php if (isset($errors['tech_stack'])): ?><div class="invalid-feedback"><?= e($errors['tech_stack']) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>成员分工 <span class="required">*</span></h2>
                        <p>至少勾选 1 名成员；分工与贡献会显示在项目详情页。</p>
                    </div>
                </div>
                <?php if (isset($errors['members'])): ?>
                    <div class="alert alert-danger py-2" role="alert"><?= e($errors['members']) ?></div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table admin-table align-middle">
                        <caption class="visually-hidden">项目成员分工</caption>
                        <thead>
                        <tr>
                            <th scope="col" style="width: 4rem;">参与</th>
                            <th scope="col">成员</th>
                            <th scope="col">项目角色</th>
                            <th scope="col">贡献说明</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($availableMembers as $member): ?>
                            <?php
                            $memberId = (int) $member['id'];
                            $assignment = $memberValues[$memberId] ?? null;
                            $checked = $assignment !== null;
                            ?>
                            <tr>
                                <td>
                                    <input class="form-check-input" type="checkbox" id="member_<?= e($memberId) ?>"
                                           name="members[]" value="<?= e($memberId) ?>" <?= $checked ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <label class="form-check-label" for="member_<?= e($memberId) ?>">
                                        <?= e($member['name']) ?>
                                    </label>
                                    <?php if ((int) $member['is_visible'] === 0): ?>
                                        <span class="badge badge--muted ms-1">已隐藏</span>
                                    <?php endif; ?>
                                    <div class="text-muted small"><?= e((string) ($member['role_title'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" type="text"
                                           name="member_roles[<?= e($memberId) ?>]"
                                           value="<?= e((string) ($assignment['role_name'] ?? '')) ?>"
                                           maxlength="50" placeholder="如：后端">
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" type="text"
                                           name="member_contributions[<?= e($memberId) ?>]"
                                           value="<?= e((string) ($assignment['contribution'] ?? '')) ?>"
                                           maxlength="255" placeholder="负责的模块">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>项目封面</h2>
                        <p>显示在项目列表与详情页；不选择文件则保留原封面。</p>
                    </div>
                </div>
                <div class="admin-preview">
                    <img id="coverPreview" class="admin-preview__cover"
                         src="<?= e(asset((string) ($old['cover'] ?? 'img/placeholder-cover.svg'))) ?>" alt="项目封面预览">
                </div>
                <div class="mt-2">
                    <label class="btn btn-outline-secondary btn-sm" for="cover">选择封面</label>
                    <input class="visually-hidden" id="cover" name="cover" type="file"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           data-preview="coverPreview" data-max-mb="5">
                    <p class="form-hint mb-0">建议 16:9，单文件 ≤ 5 MB。</p>
                    <?php if (isset($errors['cover'])): ?><div class="invalid-feedback d-block"><?= e($errors['cover']) ?></div><?php endif; ?>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>时间与链接</h2>
                        <p>均可留空。</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="start_date">开始日期</label>
                        <input class="form-control <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>"
                               id="start_date" name="start_date" type="date" value="<?= e((string) ($old['start_date'] ?? '')) ?>">
                        <?php if (isset($errors['start_date'])): ?><div class="invalid-feedback"><?= e($errors['start_date']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="end_date">结束日期</label>
                        <input class="form-control <?= isset($errors['end_date']) ? 'is-invalid' : '' ?>"
                               id="end_date" name="end_date" type="date" value="<?= e((string) ($old['end_date'] ?? '')) ?>">
                        <?php if (isset($errors['end_date'])): ?><div class="invalid-feedback"><?= e($errors['end_date']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="repo_url">仓库地址</label>
                        <input class="form-control <?= isset($errors['repo_url']) ? 'is-invalid' : '' ?>"
                               id="repo_url" name="repo_url" type="url" value="<?= e((string) ($old['repo_url'] ?? '')) ?>"
                               placeholder="https://github.com/...">
                        <?php if (isset($errors['repo_url'])): ?><div class="invalid-feedback"><?= e($errors['repo_url']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="demo_url">演示地址</label>
                        <input class="form-control <?= isset($errors['demo_url']) ? 'is-invalid' : '' ?>"
                               id="demo_url" name="demo_url" type="url" value="<?= e((string) ($old['demo_url'] ?? '')) ?>"
                               placeholder="https://...">
                        <?php if (isset($errors['demo_url'])): ?><div class="invalid-feedback"><?= e($errors['demo_url']) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>公开设置</h2>
                        <p>决定前台是否展示与排序。</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_visible" name="is_visible" value="1"
                                   <?= (int) $old['is_visible'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_visible">前台公开</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                                   <?= (int) $old['is_featured'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_featured">首页精选</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="sort_order">排序</label>
                        <input class="form-control <?= isset($errors['sort_order']) ? 'is-invalid' : '' ?>"
                               id="sort_order" name="sort_order" type="number" min="-9999" max="9999"
                               value="<?= e((string) $old['sort_order']) ?>">
                        <p class="form-hint">数字越小越靠前，可为负数。</p>
                        <?php if (isset($errors['sort_order'])): ?><div class="invalid-feedback"><?= e($errors['sort_order']) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="admin-savebar">
        <span class="text-muted">保存后立即更新前台项目列表与成员主页。</span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/projects')) ?>">取消</a>
            <?php if ($isEdit && (int) $old['is_visible'] === 1): ?>
                <a class="btn btn-outline-secondary" href="<?= e(url('project/show', ['slug' => $old['slug']])) ?>"
                   target="_blank" rel="noopener">预览</a>
            <?php endif; ?>
            <button class="btn btn-primary" type="submit">保存项目</button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
