<?php
/**
 * 文件名：project_list.php
 * 功能：后台项目管理列表
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 项目管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$statusBadges = [
    'planning' => 'badge--gold',
    'ongoing' => 'badge--accent',
    'completed' => 'badge--success',
    'archived' => 'badge--muted',
];
?>
<div class="admin-toolbar">
    <p>共 <strong><?= e(count($projects)) ?></strong> 个项目；项目至少关联 1 名成员，归档后从前台隐藏。</p>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('project/index')) ?>" target="_blank" rel="noopener">查看前台</a>
        <a class="btn btn-sm btn-primary" href="<?= e(url('admin/projectCreate')) ?>">新建项目</a>
    </div>
</div>

<div class="table-responsive">
    <table class="table admin-table align-middle">
        <caption class="visually-hidden">项目管理列表</caption>
        <thead>
        <tr>
            <th scope="col">项目</th>
            <th scope="col">状态</th>
            <th scope="col">技术栈</th>
            <th scope="col">参与成员</th>
            <th scope="col">前台</th>
            <th scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($projects === []): ?>
            <tr><td colspan="6" class="text-muted">还没有项目，点击“新建项目”开始。</td></tr>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td>
                        <strong><?= e($project['title']) ?></strong>
                        <?php if ((int) $project['is_featured'] === 1): ?>
                            <span class="badge badge--gold ms-1">首页精选</span>
                        <?php endif; ?>
                        <div class="text-muted small">/<?= e($project['slug']) ?></div>
                    </td>
                    <td>
                        <span class="badge <?= e($statusBadges[$project['status']] ?? 'badge--muted') ?>">
                            <?= e($project['status_label']) ?>
                        </span>
                    </td>
                    <td><?= e($project['tech_stack'] !== null && $project['tech_stack'] !== '' ? $project['tech_stack'] : '—') ?></td>
                    <td>
                        <?= e($project['member_names'] !== '' ? $project['member_names'] : '—') ?>
                        <span class="text-muted small">（<?= e($project['member_count']) ?> 人）</span>
                    </td>
                    <td>
                        <?php if ((int) $project['is_visible'] === 1): ?>
                            <span class="badge badge--success">公开</span>
                        <?php else: ?>
                            <span class="badge badge--muted">隐藏</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/projectEdit', ['id' => $project['id']])) ?>">编辑</a>
                        <?php if (Auth::can('content.publish')): ?>
                            <?php if ((int) $project['is_visible'] === 0): ?>
                                <form class="d-inline" action="<?= e(url('admin/setProjectVisibility')) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($project['id']) ?>">
                                    <input type="hidden" name="visible" value="1">
                                    <button class="btn btn-sm btn-outline-success" type="submit">恢复公开</button>
                                </form>
                            <?php else: ?>
                                <form class="d-inline" action="<?= e(url('admin/setProjectVisibility')) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($project['id']) ?>">
                                    <input type="hidden" name="visible" value="0">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">隐藏</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($project['status'] !== 'archived'): ?>
                                <form class="d-inline" action="<?= e(url('admin/archiveProject')) ?>" method="post"
                                      onsubmit="return confirm('归档后项目从前台隐藏，确定继续吗？')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e($project['id']) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">归档</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<p class="admin-hint">“首页精选”与排序在编辑页设置；隐藏项目仍保留成员分工与访问链接。</p>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
