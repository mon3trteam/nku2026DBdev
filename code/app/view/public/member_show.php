<?php
/**
 * 文件名：member_show.php
 * 功能：成员主页（封面头像、身份、技能矩阵、代表项目、留言区）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 成员
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="member-hero">
    <div class="member-hero__cover">
        <img src="<?= e(asset($member['cover'])) ?>" alt="" width="1600" height="900">
    </div>
    <div class="container">
        <div class="member-hero__body">
            <div class="member-hero__avatar">
                <img src="<?= e(asset($member['avatar'])) ?>" alt="<?= e($member['name']) ?> 的头像" width="160" height="160">
            </div>
            <div class="member-hero__info">
                <p class="eyebrow"><?= e($member['role_title']) ?></p>
                <h1 class="member-hero__name"><?= e($member['name']) ?>
                    <span class="member-hero__en"><?= e($member['english_name']) ?></span>
                </h1>
                <p class="member-hero__tagline"><?= e($member['tagline']) ?></p>
                <div class="member-hero__meta">
                    <span><?= e($member['location']) ?></span>
                    <span>加入于 <?= e(format_date($member['join_date'])) ?></span>
                </div>
            </div>
            <div class="member-hero__links">
                <?php if ($member['github'] !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e($member['github']) ?>" rel="noopener noreferrer" target="_blank">GitHub</a>
                <?php endif; ?>
                <?php if ($member['website'] !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e($member['website']) ?>" rel="noopener noreferrer" target="_blank">个人网站</a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary btn-sm" href="mailto:<?= e($member['email']) ?>">邮箱</a>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="content-block">
                    <p class="eyebrow">About</p>
                    <h2 class="section-title">关于我</h2>
                    <p class="story-text"><?= e($member['bio']) ?></p>
                </div>

                <div class="content-block">
                    <p class="eyebrow">Skills</p>
                    <h2 class="section-title">技能矩阵</h2>
                    <?php foreach ($member['skills'] as $skill): ?>
                        <?php require APP_ROOT . '/app/view/partial/skill_bar.php'; ?>
                    <?php endforeach; ?>
                </div>

                <div class="content-block">
                    <p class="eyebrow">Messages</p>
                    <h2 class="section-title">留言</h2>
                    <div class="message-list">
                        <?php foreach ($messages as $message): ?>
                            <article class="message-item">
                                <div class="message-item__head">
                                    <strong><?= e($message['author_name']) ?></strong>
                                    <time><?= e(format_date($message['created_at'])) ?></time>
                                </div>
                                <p><?= e($message['content']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <form class="message-form" action="<?= e(url('message/store')) ?>" method="post" novalidate>
                        <h3 class="form-title">给 <?= e($member['name']) ?> 留言</h3>
                        <p class="form-hint">留言需要审核后才会公开，邮箱不会公开。当前为演示表单，提交不会入库。</p>
                        <input type="hidden" name="_token" value="">
                        <input type="hidden" name="member_slug" value="<?= e($member['slug']) ?>">
                        <input type="hidden" name="member_id" value="<?= e($member['id']) ?>">
                        <div class="hp-field" aria-hidden="true">
                            <label for="website_hp">网站</label>
                            <input id="website_hp" name="website_hp" type="text" tabindex="-1" autocomplete="off">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="author_name">昵称 <span class="required">*</span></label>
                                <input class="form-control" id="author_name" name="author_name" type="text" required minlength="2" maxlength="30" placeholder="怎么称呼你">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="author_email">邮箱（可选，不公开）</label>
                                <input class="form-control" id="author_email" name="author_email" type="email" maxlength="120" placeholder="you@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="content">留言内容 <span class="required">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="4" required minlength="10" maxlength="500" placeholder="想说的话，10—500 字"></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">提交留言</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="content-block">
                    <p class="eyebrow">Projects</p>
                    <h2 class="section-title">代表项目</h2>
                    <?php if ($projects === []): ?>
                        <div class="empty-state empty-state--small">
                            <p>暂时没有公开项目。</p>
                        </div>
                    <?php else: ?>
                        <div class="stack">
                            <?php foreach ($projects as $project): ?>
                                <?php require APP_ROOT . '/app/view/partial/project_card.php'; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
