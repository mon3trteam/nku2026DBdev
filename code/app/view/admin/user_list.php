<?php
/**
 * 文件名：user_list.php
 * 功能：后台用户与角色列表
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 用户与角色
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($users)) ?></strong> 个账号；创建账号、重置临时密码与禁用操作在接口接入后启用。</p>
    <button class="btn btn-sm btn-primary" type="button" disabled>新建账号</button>
</div>

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
            <th scope="col">状态</th>
            <th scope="col">最近登录</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><code><?= e($user['username']) ?></code></td>
                <td><?= e($user['display_name']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td><span class="badge badge--accent"><?= e($user['role_name']) ?></span></td>
                <td>
                    <?php if ((int) $user['status'] === 1): ?>
                        <span class="badge badge--success"><?= e($user['status_label']) ?></span>
                    <?php else: ?>
                        <span class="badge badge--muted"><?= e($user['status_label']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= e($user['last_login_at']) ?></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" type="button" disabled>重置密码</button>
                    <button class="btn btn-sm btn-outline-danger" type="button" disabled>禁用</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">密码只保存 bcrypt 散列；临时密码只显示一次，并强制首次登录修改。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
