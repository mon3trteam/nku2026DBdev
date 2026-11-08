<?php
/**
 * 文件名：projects.php
 * 功能：成员中心 / 我的项目
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心 / 我的项目
 */

require APP_ROOT . '/app/view/layout/me_header.php';

$invalid = ($_GET['state'] ?? '') === 'invalid';
?>
<section class="me-profile-head">
    <div>
        <p class="eyebrow">Projects</p>
        <h2 class="section-title">我的项目</h2>
        <p class="text-muted mb-0">项目主体由管理员维护；你可以维护自己在项目中的角色与贡献说明。</p>
    </div>
    <span class="badge badge--muted">共 <?= e(count($projects)) ?> 个项目</span>
</section>

<?php if ($invalid): ?>
    <div class="alert alert-danger" role="alert">保存失败：项目角色需要 1—50 字，贡献说明不超过 255 字。</div>
<?php endif; ?>

<?php if ($projects === []): ?>
    <?php
    $emptyTitle = '还没有关联项目';
    $emptyMessage = '项目关联由管理员维护，关联后会在这里显示。';
    $emptyActionUrl = '';
    $emptyActionText = '';
    require APP_ROOT . '/app/view/partial/state_empty.php';
    ?>
<?php else: ?>
    <div class="me-project-list me-project-list--detailed">
        <?php foreach ($projects as $project): ?>
            <?php
            $myAssignment = ['role_name' => '', 'contribution' => ''];
            foreach ($project['members'] as $projectMember) {
                if ((string) $projectMember['name'] === (string) $member['name']) {
                    $myAssignment = $projectMember;
                    break;
                }
            }
            $myRole = (string) ($myAssignment['role_name'] ?? '') !== '' ? $myAssignment['role_name'] : '成员';
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
                <?php if ((string) ($myAssignment['contribution'] ?? '') !== ''): ?>
                    <p class="me-project-card__contribution">我的贡献：<?= e($myAssignment['contribution']) ?></p>
                <?php endif; ?>

                <details class="me-project-card__editor">
                    <summary>编辑我的分工</summary>
                    <form action="<?= e(url('me/updateContribution')) ?>" method="post" class="row g-2 mt-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="project_id" value="<?= e($project['id']) ?>">
                        <div class="col-md-4">
                            <label class="form-label" for="role-<?= e($project['id']) ?>">项目角色</label>
                            <input class="form-control form-control-sm" id="role-<?= e($project['id']) ?>"
                                   name="role_name" type="text" maxlength="50" required
                                   value="<?= e((string) ($myAssignment['role_name'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contribution-<?= e($project['id']) ?>">贡献说明</label>
                            <input class="form-control form-control-sm" id="contribution-<?= e($project['id']) ?>"
                                   name="contribution" type="text" maxlength="255"
                                   value="<?= e((string) ($myAssignment['contribution'] ?? '')) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-sm btn-primary w-100" type="submit">保存</button>
                        </div>
                    </form>
                </details>

                <div class="me-project-card__actions">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('project/show', ['slug' => $project['slug']])) ?>">查看公开页</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require APP_ROOT . '/app/view/layout/me_footer.php'; ?>
