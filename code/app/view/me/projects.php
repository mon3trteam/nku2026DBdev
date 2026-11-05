<?php
/**
 * 文件名：projects.php
 * 功能：成员中心 / 我的项目
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心 / 我的项目
 */

require APP_ROOT . '/app/view/layout/me_header.php';
?>
<section class="me-profile-head">
    <div>
        <p class="eyebrow">Projects</p>
        <h2 class="section-title">我的项目</h2>
        <p class="text-muted mb-0">项目主体由管理员维护；成员可维护自己在项目中的角色与贡献说明。</p>
    </div>
    <span class="badge badge--muted">共 <?= e(count($projects)) ?> 个项目</span>
</section>

<?php if ($projects === []): ?>
    <?php
    $emptyTitle = '还没有关联项目';
    $emptyMessage = '项目关联由管理员维护，接口接入后可在这里查看。';
    $emptyActionUrl = '';
    $emptyActionText = '';
    require APP_ROOT . '/app/view/partial/state_empty.php';
    ?>
<?php else: ?>
    <div class="me-project-list me-project-list--detailed">
        <?php foreach ($projects as $project): ?>
            <?php
            $roles = array_column($project['members'], 'role_name', 'name');
            $myRole = $roles[$member['name']] ?? '成员';
            ?>
            <article class="me-project-card">
                <div class="me-project-card__head">
                    <div>
                        <h3><?= e($project['title']) ?></h3>
                        <p><?= e($project['summary']) ?></p>
                    </div>
                    <span class="badge <?= $project['status'] === 'completed' ? 'badge--success' : ($project['status'] === 'planning' ? 'badge--gold' : 'badge--accent') ?>">
                        <?= e($project['status_label']) ?>
                    </span>
                </div>
                <dl class="me-project-card__meta">
                    <div><dt>我的角色</dt><dd><?= e($myRole) ?></dd></div>
                    <div><dt>技术栈</dt><dd><?= e($project['tech_stack']) ?></dd></div>
                    <div><dt>参与成员</dt><dd><?= e(implode('、', array_column($project['members'], 'name'))) ?></dd></div>
                </dl>
                <div class="me-project-card__actions">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('project/show', ['slug' => $project['slug']])) ?>">查看公开页</a>
                    <button class="btn btn-sm btn-primary" type="button" disabled>编辑我的贡献（接口接入后启用）</button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require APP_ROOT . '/app/view/layout/me_footer.php'; ?>
