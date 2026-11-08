<?php
/**
 * 文件名：index.php
 * 功能：成员中心概览（资料完整度、项目、留言、快捷入口）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心 / 概览
 */

require APP_ROOT . '/app/view/layout/me_header.php';
?>
<section class="me-summary">
    <div class="me-summary__avatar">
        <img src="<?= e(asset($member['avatar'])) ?>" alt="<?= e($member['name']) ?> 的头像" width="104" height="104">
    </div>
    <div class="me-summary__body">
        <p class="eyebrow">My space</p>
        <h2 class="me-summary__name"><?= e($member['name']) ?> <span><?= e($member['english_name']) ?></span></h2>
        <p class="me-summary__role"><?= e($member['role_title']) ?> · <?= e($member['tagline']) ?></p>
        <div class="completeness">
            <div class="completeness__head">
                <span>资料完整度</span>
                <strong><?= e($completeness) ?>%</strong>
            </div>
            <div class="completeness__track" role="progressbar" aria-label="资料完整度"
                 aria-valuenow="<?= e($completeness) ?>" aria-valuemin="0" aria-valuemax="100">
                <span class="completeness__fill" style="width: <?= e($completeness) ?>%"></span>
            </div>
            <p class="completeness__hint">补全简介、社交链接与技能后，公开主页会更完整。</p>
        </div>
    </div>
    <div class="me-summary__actions">
        <a class="btn btn-primary" href="<?= e(url('me/profile')) ?>">编辑资料</a>
        <a class="btn btn-outline-secondary" href="<?= e(url('member/show', ['slug' => $member['slug']])) ?>">查看公开主页</a>
    </div>
</section>

<section class="me-stats" aria-label="个人数据">
    <div class="me-stat">
        <span class="me-stat__num"><?= e(count($member['skills'])) ?></span>
        <span class="me-stat__label">技能标签</span>
    </div>
    <div class="me-stat">
        <span class="me-stat__num"><?= e(count($projects)) ?></span>
        <span class="me-stat__label">参与项目</span>
    </div>
    <div class="me-stat">
        <span class="me-stat__num"><?= e(count($messages)) ?></span>
        <span class="me-stat__label">收到留言</span>
    </div>
    <div class="me-stat">
        <span class="me-stat__num"><?= e($member['join_date']) ?></span>
        <span class="me-stat__label">加入日期</span>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="me-card">
            <div class="me-card__head">
                <div>
                    <h2>最近项目</h2>
                    <p>你参与的项目与角色。</p>
                </div>
                <a class="section-link" href="<?= e(url('me/projects')) ?>">全部项目 →</a>
            </div>

            <?php if ($projects === []): ?>
                <?php
                $emptyTitle = '还没有关联项目';
                $emptyMessage = '项目关联由管理员维护，关联后会在这里显示。';
                $emptyActionUrl = '';
                $emptyActionText = '';
                require APP_ROOT . '/app/view/partial/state_empty.php';
                ?>
            <?php else: ?>
                <div class="me-project-list">
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $roles = array_column($project['members'], 'role_name', 'name');
                        $myRole = $roles[$member['name']] ?? '成员';
                        ?>
                        <a class="me-project-row" href="<?= e(url('project/show', ['slug' => $project['slug']])) ?>">
                            <div>
                                <strong><?= e($project['title']) ?></strong>
                                <span><?= e($myRole) ?> · <?= e($project['tech_stack']) ?></span>
                            </div>
                            <span class="badge badge--accent"><?= e($project['status_label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="me-card">
            <div class="me-card__head">
                <div>
                    <h2>快捷入口</h2>
                    <p>常用的个人维护操作。</p>
                </div>
            </div>
            <div class="me-quick">
                <a href="<?= e(url('me/profile')) ?>">个人资料 <span>编辑基本资料、头像与技能</span></a>
                <a href="<?= e(url('me/projects')) ?>">我的项目 <span>查看项目角色与贡献</span></a>
                <a href="<?= e(url('me/security')) ?>">账号安全 <span>修改密码、查看登录记录</span></a>
                <a href="<?= e(url('message/index')) ?>">留言板 <span>查看公开留言与提交建议</span></a>
            </div>
        </section>
    </div>
</div>

<?php require APP_ROOT . '/app/view/layout/me_footer.php'; ?>
