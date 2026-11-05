<?php
/**
 * 文件名：security.php
 * 功能：成员中心 / 账号安全（修改密码、最近登录记录）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心 / 账号安全
 */

require APP_ROOT . '/app/view/layout/me_header.php';
?>
<section class="me-profile-head">
    <div>
        <p class="eyebrow">Security</p>
        <h2 class="section-title">账号安全</h2>
        <p class="text-muted mb-0">修改密码并查看最近登录记录。修改密码后旧会话会失效。</p>
    </div>
</section>

<div class="row g-4">
    <div class="col-lg-6">
        <section class="me-card">
            <div class="me-card__head">
                <div>
                    <h2>修改密码</h2>
                    <p>当前为演示表单，提交不会入库。</p>
                </div>
            </div>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
            <?php endif; ?>

            <form action="<?= e(url('me/updatePassword')) ?>" method="post" novalidate>
                <input type="hidden" name="_token" value="">

                <div class="mb-3">
                    <label class="form-label" for="current_password">当前密码 <span class="required">*</span></label>
                    <div class="password-field">
                        <input class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                               id="current_password" name="current_password" type="password"
                               autocomplete="current-password" required>
                        <button class="password-toggle" type="button" data-toggle-password="#current_password" aria-label="显示密码">显示</button>
                    </div>
                    <?php if (isset($errors['current_password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['current_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="new_password">新密码 <span class="required">*</span></label>
                    <div class="password-field">
                        <input class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                               id="new_password" name="new_password" type="password"
                               autocomplete="new-password" required minlength="10">
                        <button class="password-toggle" type="button" data-toggle-password="#new_password" aria-label="显示密码">显示</button>
                    </div>
                    <?php if (isset($errors['new_password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['new_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="confirm_password">确认新密码 <span class="required">*</span></label>
                    <div class="password-field">
                        <input class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                               id="confirm_password" name="confirm_password" type="password"
                               autocomplete="new-password" required>
                        <button class="password-toggle" type="button" data-toggle-password="#confirm_password" aria-label="显示密码">显示</button>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary" type="submit">保存新密码</button>
            </form>
        </section>

        <section class="me-card">
            <div class="me-card__head">
                <div>
                    <h2>密码规则</h2>
                    <p>与设计文档一致。</p>
                </div>
            </div>
            <ul class="password-rules">
                <li>至少 10 位。</li>
                <li>包含大写、小写、数字、符号中的至少三类。</li>
                <li>不得与用户名相同。</li>
                <li>修改成功后旧会话失效，需要重新登录。</li>
            </ul>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="me-card">
            <div class="me-card__head">
                <div>
                    <h2>最近登录</h2>
                    <p>IP 只保存哈希前 8 位。</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <caption class="visually-hidden">最近登录记录</caption>
                    <thead>
                    <tr><th scope="col">时间</th><th scope="col">IP 哈希</th><th scope="col">设备</th><th scope="col">结果</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($loginLogs as $log): ?>
                        <tr>
                            <td><?= e($log['time']) ?></td>
                            <td><code><?= e($log['ip_hash']) ?></code></td>
                            <td><?= e($log['ua']) ?></td>
                            <td>
                                <?php if ($log['success']): ?>
                                    <span class="badge badge--success">成功</span>
                                <?php else: ?>
                                    <span class="badge badge--gold">失败</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="btn btn-outline-danger btn-sm mt-2" type="button" disabled>退出所有设备（接口接入后启用）</button>
        </section>
    </div>
</div>

<?php require APP_ROOT . '/app/view/layout/me_footer.php'; ?>
