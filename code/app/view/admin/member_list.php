<?php
/**
 * 文件名：member_list.php
 * 功能：后台成员管理列表
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 成员管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($members)) ?></strong> 位成员；列表支持搜索、筛选与批量操作（接口接入后启用）。</p>
    <button class="btn btn-sm btn-primary" type="button" disabled>新建成员</button>
</div>

<div class="table-responsive">
    <table class="table admin-table align-middle">
        <caption class="visually-hidden">成员管理列表</caption>
        <thead>
        <tr>
            <th scope="col">成员</th>
            <th scope="col">团队角色</th>
            <th scope="col">可见</th>
            <th scope="col">精选</th>
            <th scope="col">资料完整度</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($members as $member): ?>
            <?php $completeness = min(100, 82 + ((int) $member['is_featured'] === 1 ? 8 : 0)); ?>
            <tr>
                <td>
                    <div class="admin-person">
                        <img src="<?= e(asset($member['avatar'])) ?>" alt="" width="32" height="32">
                        <div>
                            <strong><?= e($member['name']) ?></strong>
                            <small><?= e($member['english_name']) ?></small>
                        </div>
                    </div>
                </td>
                <td><?= e($member['role_title']) ?></td>
                <td><span class="badge badge--success">可见</span></td>
                <td>
                    <?php if ((int) $member['is_featured'] === 1): ?>
                        <span class="badge badge--gold">精选</span>
                    <?php else: ?>
                        <span class="badge badge--muted">普通</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="admin-progress">
                        <span class="admin-progress__track"><span class="admin-progress__fill" style="width: <?= e($completeness) ?>%"></span></span>
                        <span><?= e($completeness) ?>%</span>
                    </div>
                </td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-secondary" type="button" disabled>编辑</button>
                    <button class="btn btn-sm btn-outline-danger" type="button" disabled>隐藏</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">按钮暂为占位，成员管理接口接入后启用。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
