<?php
/**
 * 文件名：message_index.php
 * 功能：留言板（已通过留言列表、提交表单、审核说明、分页）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 留言
 */

require APP_ROOT . '/app/view/layout/public_header.php';
?>
<section class="page-hero page-hero--compact">
    <div class="container">
        <p class="eyebrow">Messages</p>
        <h1 class="page-hero__title">留言板</h1>
        <p class="page-hero__lead">留下建议或合作意向。留言需要审核后才会公开，邮箱不会公开。</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Approved</p>
                        <h2 class="section-title">最近留言</h2>
                    </div>
                    <span class="section-note">共 <?= e($pagination['total']) ?> 条已通过</span>
                </div>

                <?php if ($messages === []): ?>
                    <?php
                    $emptyTitle = '还没有公开留言';
                    $emptyMessage = '成为第一个留言的人吧。';
                    $emptyActionUrl = '';
                    $emptyActionText = '';
                    require APP_ROOT . '/app/view/partial/state_empty.php';
                    ?>
                <?php else: ?>
                    <div class="message-board">
                        <?php foreach ($messages as $message): ?>
                            <article class="message-card">
                                <div class="message-card__head">
                                    <div>
                                        <strong><?= e($message['author_name']) ?></strong>
                                        <?php if ($message['member_name'] !== ''): ?>
                                            <span class="message-card__target">
                                                给 <a href="<?= e(url('member/show', ['slug' => $message['member_slug']])) ?>"><?= e($message['member_name']) ?></a>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <time datetime="<?= e($message['created_at']) ?>"><?= e($message['created_at']) ?></time>
                                </div>
                                <p class="message-card__content"><?= e($message['content']) ?></p>
                                <?php if ($message['reply'] !== null): ?>
                                    <div class="message-card__reply">
                                        <span class="message-card__reply-label">团队回复</span>
                                        <p><?= e($message['reply']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($pagination['last_page'] > 1): ?>
                        <nav class="pagination-bar" aria-label="留言分页">
                            <a class="page-link <?= $pagination['page'] <= 1 ? 'is-disabled' : '' ?>"
                               href="<?= e(url('message/index', ['page' => max(1, $pagination['page'] - 1)])) ?>"
                               <?= $pagination['page'] <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>上一页</a>
                            <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                                <a class="page-link <?= $i === $pagination['page'] ? 'is-active' : '' ?>"
                                   href="<?= e(url('message/index', ['page' => $i])) ?>"
                                   <?= $i === $pagination['page'] ? 'aria-current="page"' : '' ?>><?= e($i) ?></a>
                            <?php endfor; ?>
                            <a class="page-link <?= $pagination['page'] >= $pagination['last_page'] ? 'is-disabled' : '' ?>"
                               href="<?= e(url('message/index', ['page' => min($pagination['last_page'], $pagination['page'] + 1)])) ?>"
                               <?= $pagination['page'] >= $pagination['last_page'] ? 'aria-disabled="true" tabindex="-1"' : '' ?>>下一页</a>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <aside class="col-lg-5">
                <form class="message-form message-form--sticky" action="<?= e(url('message/store')) ?>" method="post" novalidate>
                    <h2 class="form-title">写留言</h2>
                    <p class="form-hint">留言提交后进入待审核队列，审核通过后才会公开；邮箱不会公开。</p>

                    <?php if (($formNotice ?? '') !== ''): ?>
                        <div class="alert alert-warning" role="alert"><?= e($formNotice) ?></div>
                    <?php endif; ?>

                    <?php if ($errors !== []): ?>
                        <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
                    <?php endif; ?>

                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label" for="author_name">昵称 <span class="required">*</span></label>
                        <input class="form-control <?= isset($errors['author_name']) ? 'is-invalid' : '' ?>"
                               id="author_name" name="author_name" type="text" required minlength="2" maxlength="30"
                               value="<?= e($old['author_name']) ?>" placeholder="怎么称呼你"
                               <?= isset($errors['author_name']) ? 'aria-describedby="author-name-error"' : '' ?>>
                        <?php if (isset($errors['author_name'])): ?>
                            <div class="invalid-feedback" id="author-name-error"><?= e($errors['author_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="author_email">邮箱（可选）</label>
                            <input class="form-control <?= isset($errors['author_email']) ? 'is-invalid' : '' ?>"
                                   id="author_email" name="author_email" type="email" maxlength="120"
                                   value="<?= e($old['author_email']) ?>" placeholder="you@example.com">
                            <?php if (isset($errors['author_email'])): ?>
                                <div class="invalid-feedback"><?= e($errors['author_email']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="author_website">个人网站（可选）</label>
                            <input class="form-control <?= isset($errors['author_website']) ? 'is-invalid' : '' ?>"
                                   id="author_website" name="author_website" type="url" maxlength="255"
                                   value="<?= e($old['author_website']) ?>" placeholder="https://">
                            <?php if (isset($errors['author_website'])): ?>
                                <div class="invalid-feedback"><?= e($errors['author_website']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="member_slug">目标成员（可选）</label>
                        <select class="form-select form-control" id="member_slug" name="member_slug">
                            <option value="">不指定成员</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= e($member['slug']) ?>" <?= $old['member_slug'] === $member['slug'] ? 'selected' : '' ?>>
                                    <?= e($member['name']) ?> · <?= e($member['role_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mt-3">
                        <label class="form-label" for="content">留言内容 <span class="required">*</span></label>
                        <textarea class="form-control <?= isset($errors['content']) ? 'is-invalid' : '' ?>"
                                  id="content" name="content" rows="5" required minlength="10" maxlength="500"
                                  placeholder="想说的话，10—500 字"
                                  <?= isset($errors['content']) ? 'aria-describedby="content-error"' : '' ?>><?= e($old['content']) ?></textarea>
                        <?php if (isset($errors['content'])): ?>
                            <div class="invalid-feedback" id="content-error"><?= e($errors['content']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="hp-field" aria-hidden="true">
                        <label for="website_hp">网站</label>
                        <input id="website_hp" name="website_hp" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <button class="btn btn-primary w-100 mt-4" type="submit">提交留言</button>
                </form>
            </aside>
        </div>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/public_footer.php'; ?>
