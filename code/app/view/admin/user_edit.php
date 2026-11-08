<?php
/**
 * 文件名：user_edit.php
 * 功能：后台新建 / 编辑账号（用户名、邮箱、显示名、角色、成员档案绑定）
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 用户与角色
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$isEdit = (int) ($old['id'] ?? 0) > 0;
$editingSelf = $isEdit && (int) $old['id'] === Auth::id();
$statusLabels = [1 => '正常', 0 => '已禁用', 2 => '已锁定'];
$statusLabel = $statusLabels[(int) ($old['status'] ?? 1)] ?? '未知';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<form class="admin-form" action="<?= e(url($isEdit ? 'admin/updateUser' : 'admin/storeUser')) ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e((string) ($old['id'] ?? 0)) ?>">

    <div class="row g-4">
        <div class="col-lg-8">
            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>账号资料</h2>
                        <p>用户名用于登录，可与成员姓名不同。</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username">用户名 <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                               id="username" name="username" type="text"
                               value="<?= e((string) ($old['username'] ?? '')) ?>" required
                               pattern="[A-Za-z0-9_-]{3,50}" autocomplete="off">
                        <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="display_name">显示名 <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['display_name']) ? 'is-invalid' : '' ?>"
                               id="display_name" name="display_name" type="text"
                               value="<?= e((string) ($old['display_name'] ?? '')) ?>" required maxlength="50">
                        <?php if (isset($errors['display_name'])): ?><div class="invalid-feedback"><?= e($errors['display_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">邮箱 <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                               id="email" name="email" type="email"
                               value="<?= e((string) ($old['email'] ?? '')) ?>" required maxlength="120">
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="role_id">角色 <span class="required">*</span></label>
                        <select class="form-select form-control <?= isset($errors['role_id']) ? 'is-invalid' : '' ?>"
                                id="role_id" name="role_id" <?= $editingSelf ? 'disabled' : '' ?>>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= e($role['id']) ?>"
                                        <?= (int) $old['role_id'] === (int) $role['id'] ? 'selected' : '' ?>>
                                    <?= e($role['name']) ?>（<?= e($role['code']) ?>）
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($editingSelf): ?>
                            <input type="hidden" name="role_id" value="<?= e((string) $old['role_id']) ?>">
                            <p class="form-hint">不能修改自己的角色。</p>
                        <?php endif; ?>
                        <?php if (isset($errors['role_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['role_id']) ?></div><?php endif; ?>
                    </div>
                </div>
            </section>

            <?php if (!$isEdit): ?>
                <section class="admin-card">
                    <div class="admin-card__head">
                        <div>
                            <h2>成员档案</h2>
                            <p>可选：把账号绑定到一个尚未绑定账号的成员公开档案。</p>
                        </div>
                    </div>
                    <?php if ($unboundMembers === []): ?>
                        <p class="text-muted mb-0">当前没有未绑定的成员档案；创建后也可由成员中心维护自己的资料。</p>
                    <?php else: ?>
                        <label class="form-label" for="member_id">绑定成员档案</label>
                        <select class="form-select form-control <?= isset($errors['member_id']) ? 'is-invalid' : '' ?>"
                                id="member_id" name="member_id">
                            <option value="0">不绑定</option>
                            <?php foreach ($unboundMembers as $member): ?>
                                <option value="<?= e($member['id']) ?>" <?= (int) ($old['member_id'] ?? 0) === (int) $member['id'] ? 'selected' : '' ?>>
                                    <?= e($member['name']) ?>（<?= e($member['role_title']) ?>）
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['member_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['member_id']) ?></div><?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2><?= $isEdit ? '账号状态' : '初始密码' ?></h2>
                    </div>
                </div>
                <?php if ($isEdit): ?>
                    <p class="mb-2">状态：<span class="badge <?= (int) $old['status'] === 1 ? 'badge--success' : 'badge--muted' ?>"><?= e($statusLabel) ?></span></p>
                    <?php if ((int) ($old['must_change_password'] ?? 0) === 1): ?>
                        <p class="mb-2"><span class="badge badge--gold">待首次改密</span></p>
                    <?php endif; ?>
                    <p class="form-hint mb-0">最近登录：<?= e(format_datetime($old['last_login_at'] ?? null, '—')) ?></p>
                    <p class="form-hint mb-0">成员档案：<?= e((string) ($old['member_name'] ?? '未绑定')) ?></p>
                    <?php if ((int) $old['id'] !== Auth::id()): ?>
                        <hr>
                        <p class="form-hint">需要重置密码？请回到列表使用“重置密码”，临时密码只显示一次。</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="mb-2">系统会生成一次性临时密码，保存后只显示一次。</p>
                    <p class="form-hint mb-0">新账号首次登录会被强制修改密码；请通过安全渠道把临时密码交给本人。</p>
                <?php endif; ?>
            </section>

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>安全说明</h2>
                    </div>
                </div>
                <ul class="password-rules">
                    <li>密码只保存 bcrypt 散列，不保存明文。</li>
                    <li>新建与重置的临时密码只显示一次。</li>
                    <li>角色调整在下一次请求时生效。</li>
                    <li>系统至少保留一名启用中的超级管理员。</li>
                </ul>
            </section>
        </div>
    </div>

    <div class="admin-savebar">
        <span class="text-muted"><?= $isEdit ? '保存账号资料；密码请使用列表中的重置操作。' : '创建账号并生成一次性临时密码。' ?></span>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(url('admin/users')) ?>">取消</a>
            <button class="btn btn-primary" type="submit"><?= $isEdit ? '保存账号' : '创建账号' ?></button>
        </div>
    </div>
</form>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
