<?php
/**
 * 文件名：message_edit.php
 * 功能：后台留言审核页（全文、审核状态、备注、团队回复）
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 留言管理
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$statusBadgeClass = match ($message['status']) {
    'approved' => 'badge--success',
    'pending' => 'badge--gold',
    'spam' => 'badge--danger',
    default => 'badge--muted',
};
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="admin-card">
            <div class="admin-card__head">
                <div>
                    <h2>留言全文</h2>
                    <p>来自 <?= e($message['author_name']) ?> · <?= e($message['created_at']) ?></p>
                </div>
                <span class="badge <?= e($statusBadgeClass) ?>"><?= e($statuses[$message['status']] ?? $message['status']) ?></span>
            </div>

            <p class="message-admin-content"><?= nl2br(e($message['content'])) ?></p>

            <dl class="message-admin-meta">
                <div>
                    <dt>邮箱</dt>
                    <dd><?= $message['author_email'] !== '' ? e($message['author_email']) : '未填写' ?></dd>
                </div>
                <div>
                    <dt>个人网站</dt>
                    <dd>
                        <?php if ($message['author_website'] !== ''): ?>
                            <a href="<?= e($message['author_website']) ?>" target="_blank" rel="noopener noreferrer"><?= e($message['author_website']) ?></a>
                        <?php else: ?>
                            未填写
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt>目标成员</dt>
                    <dd>
                        <?php if ($message['member_slug'] !== ''): ?>
                            <a href="<?= e(url('member/show', ['slug' => $message['member_slug']])) ?>" target="_blank" rel="noopener"><?= e($message['member_name']) ?></a>
                        <?php else: ?>
                            未指定
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt>来源 IP 哈希</dt>
                    <dd><code><?= e(str_limit($message['ip_hash'], 16)) ?></code></dd>
                </div>
                <div>
                    <dt>浏览器 UA</dt>
                    <dd><code><?= e($message['user_agent'] !== '' ? $message['user_agent'] : '—') ?></code></dd>
                </div>
                <div>
                    <dt>最后更新</dt>
                    <dd><?= e($message['updated_at']) ?></dd>
                </div>
            </dl>
        </section>

        <section class="admin-card">
            <div class="admin-card__head">
                <div>
                    <h2>团队回复</h2>
                    <p>公开页只展示最新一条 <code>visible</code> 状态的回复。</p>
                </div>
            </div>

            <?php if ($message['reply'] !== null): ?>
                <div class="message-admin-reply">
                    <p><?= nl2br(e($message['reply']['content'])) ?></p>
                    <p class="form-hint mb-0">
                        状态：<?= $message['reply']['status'] === 'visible' ? '公开' : '隐藏' ?>
                        · 更新于 <?= e($message['reply']['updated_at']) ?>
                    </p>
                </div>
            <?php else: ?>
                <p class="form-hint mb-0">还没有团队回复。保存时填写回复内容即可新增。</p>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-5">
        <form class="admin-form" action="<?= e(url('admin/updateMessage')) ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($old['id']) ?>">

            <section class="admin-card">
                <div class="admin-card__head">
                    <div>
                        <h2>审核设置</h2>
                        <p>状态变更和回复会在同一事务中保存。</p>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="status">审核状态</label>
                    <select class="form-select form-control <?= isset($errors['status']) ? 'is-invalid' : '' ?>" id="status" name="status">
                        <?php foreach ($statuses as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= $old['status'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['status'])): ?><div class="invalid-feedback"><?= e($errors['status']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="admin_note">审核备注</label>
                    <input class="form-control <?= isset($errors['admin_note']) ? 'is-invalid' : '' ?>"
                           id="admin_note" name="admin_note" type="text" maxlength="255"
                           value="<?= e($old['admin_note']) ?>" placeholder="例如：已核对内容，予以通过">
                    <?php if (isset($errors['admin_note'])): ?><div class="invalid-feedback"><?= e($errors['admin_note']) ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="reply">团队回复</label>
                    <textarea class="form-control <?= isset($errors['reply']) ? 'is-invalid' : '' ?>"
                              id="reply" name="reply" rows="6" maxlength="1000"
                              placeholder="留空表示暂不回复；已有回复会被覆盖"><?= e($old['reply']) ?></textarea>
                    <?php if (isset($errors['reply'])): ?><div class="invalid-feedback"><?= e($errors['reply']) ?></div><?php endif; ?>
                    <p class="form-hint">回复最多 1000 字；公开页只显示最新一条可见回复。</p>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="reply_visible" name="reply_visible" value="1"
                           <?= $old['reply_visible'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="reply_visible">回复在公开页显示</label>
                </div>
            </section>

            <div class="admin-savebar">
                <a class="btn btn-outline-secondary" href="<?= e(url('admin/messages')) ?>">取消</a>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-danger" type="submit" name="decision" value="rejected"
                            onclick="return confirm('确认拒绝这条留言？记录会保留，并写入审计日志。')">拒绝</button>
                    <button class="btn btn-outline-danger" type="submit" name="decision" value="spam"
                            onclick="return confirm('确认标记为垃圾留言？记录会保留，并写入审计日志。')">标记垃圾</button>
                    <button class="btn btn-primary" type="submit">保存审核</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
