<?php
/**
 * 文件名：member_list.php
 * 功能：后台成员管理列表（编辑、公开状态、首页精选）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 成员管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($members)) ?></strong> 位成员；公开档案由成员中心维护，后台可编辑公开信息与展示状态。</p>
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('member/index')) ?>" target="_blank" rel="noopener">查看前台</a>
</div>

<div class="table-responsive">
    <table class="table admin-table align-middle">
        <caption class="visually-hidden">成员管理列表</caption>
        <thead>
        <tr>
            <th scope="col">成员</th>
            <th scope="col">团队角色</th>
            <th scope="col">技能</th>
            <th scope="col">可见</th>
            <th scope="col">精选</th>
            <th scope="col">资料完整度</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($members === []): ?>
            <tr><td colspan="7" class="text-muted">还没有成员档案。</td></tr>
        <?php else: ?>
            <?php foreach ($members as $member): ?>
                <?php $completeness = max(0, min(100, (int) $member['profile_completeness'])); ?>
                <tr>
                    <td>
                        <div class="admin-person">
                            <img src="<?= e(asset($member['avatar'])) ?>" alt="" width="32" height="32">
                            <div>
                                <strong><?= e($member['name']) ?></strong>
                                <small><?= e((string) ($member['english_name'] ?? '')) ?></small>
                                <div class="text-muted small">/<?= e($member['slug']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($member['role_title']) ?></td>
                    <td><?= e(count($member['skills'] ?? [])) ?> 项</td>
                    <td>
                        <?php if ((int) $member['is_visible'] === 1): ?>
                            <span class="badge badge--success">公开</span>
                        <?php else: ?>
                            <span class="badge badge--muted">隐藏</span>
                        <?php endif; ?>
                    </td>
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
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/memberEdit', ['id' => $member['id']])) ?>">编辑</a>
                        <?php if ((int) $member['is_visible'] === 1): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('member/show', ['slug' => $member['slug']])) ?>"
                               target="_blank" rel="noopener">查看</a>
                        <?php endif; ?>
                        <form class="d-inline" action="<?= e(url('admin/setMemberVisibility')) ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($member['id']) ?>">
                            <input type="hidden" name="visible" value="<?= (int) $member['is_visible'] === 1 ? '0' : '1' ?>">
                            <button class="btn btn-sm <?= (int) $member['is_visible'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                                <?= (int) $member['is_visible'] === 1 ? '隐藏' : '公开' ?>
                            </button>
                        </form>
                        <form class="d-inline" action="<?= e(url('admin/setMemberFeatured')) ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($member['id']) ?>">
                            <input type="hidden" name="featured" value="<?= (int) $member['is_featured'] === 1 ? '0' : '1' ?>">
                            <button class="btn btn-sm btn-outline-secondary" type="submit">
                                <?= (int) $member['is_featured'] === 1 ? '取消精选' : '设为精选' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">隐藏成员后，其公开主页与项目成员卡不再出现在前台；技能标签在成员中心维护。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
