<?php
/**
 * 文件名：user_list.php
 * 功能：后台用户与角色列表（新建、编辑、重置密码、启用/禁用）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 用户与角色
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$currentUserId = Auth::id();
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($users)) ?></strong> 个账号；密码只保存 bcrypt 散列，临时密码只显示一次。</p>
    <a class="btn btn-sm btn-primary" href="<?= e(url('admin/userCreate')) ?>">新建账号</a>
</div>

<?php if (($tempPassword ?? null) !== null): ?>
    <div class="alert alert-warning" role="alert">
        <strong>临时密码只显示一次。</strong>
        账号 <code><?= e($tempPassword['username']) ?></code> 的临时密码：
        <code class="fs-6"><?= e($tempPassword['password']) ?></code>
        <div class="small mt-1">请立即通过安全渠道交给本人；对方首次登录会被要求修改密码。</div>
    </div>
<?php endif; ?>

<div class="admin-role-legend">
    <?php foreach ($roles as $role): ?>
        <span class="badge badge--muted"><?= e($role['name']) ?>（<?= e($role['code']) ?>）</span>
    <?php endforeach; ?>
</div>

<div class="table-responsive">
    <table class="table admin-table align-middle">
        <caption class="visually-hidden">用户与角色列表</caption>
        <thead>
        <tr>
            <th scope="col">账号</th>
            <th scope="col">显示名</th>
            <th scope="col">邮箱</th>
            <th scope="col">角色</th>
            <th scope="col">成员档案</th>
            <th scope="col">状态</th>
            <th scope="col">最近登录</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <?php $isSelf = (int) $user['id'] === $currentUserId; ?>
            <tr>
                <td>
                    <code><?= e($user['username']) ?></code>
                    <?php if ($isSelf): ?><span class="badge badge--accent ms-1">当前账号</span><?php endif; ?>
                    <?php if ((int) $user['must_change_password'] === 1): ?>
                        <span class="badge badge--gold ms-1">待改密</span>
                    <?php endif; ?>
                </td>
                <td><?= e($user['display_name']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td><span class="badge badge--accent"><?= e($user['role_name']) ?></span></td>
                <td><?= e((string) ($user['member_name'] ?? '—')) ?></td>
                <td>
                    <?php if ((int) $user['status'] === 1): ?>
                        <span class="badge badge--success"><?= e($user['status_label']) ?></span>
                    <?php elseif ((int) $user['status'] === 2): ?>
                        <span class="badge badge--gold"><?= e($user['status_label']) ?></span>
                    <?php else: ?>
                        <span class="badge badge--muted"><?= e($user['status_label']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= e($user['last_login_at']) ?></td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/userEdit', ['id' => $user['id']])) ?>">编辑</a>
                    <?php if ($isSelf): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/security')) ?>">账号安全</a>
                    <?php else: ?>
                        <form class="d-inline" action="<?= e(url('admin/resetUserPassword')) ?>" method="post"
                              onsubmit="return confirm('重置后该账号所有旧会话立即失效，并收到一次性临时密码。确定继续吗？')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($user['id']) ?>">
                            <button class="btn btn-sm btn-outline-secondary" type="submit">重置密码</button>
                        </form>
                        <form class="d-inline" action="<?= e(url('admin/setUserStatus')) ?>" method="post"
                              <?= (int) $user['status'] === 1 ? 'onsubmit="return confirm(\'禁用后该账号立即被注销，确定继续吗？\')"' : '' ?>>
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($user['id']) ?>">
                            <input type="hidden" name="status" value="<?= (int) $user['status'] === 1 ? '0' : '1' ?>">
                            <button class="btn btn-sm <?= (int) $user['status'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                                <?= (int) $user['status'] === 1 ? '禁用' : '启用' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">新建账号默认要求首次登录修改密码；角色调整会在对方下一次请求时生效。系统至少保留一名启用中的超级管理员。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
