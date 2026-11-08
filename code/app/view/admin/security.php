<?php
/**
 * 文件名：security.php
 * 功能：后台账号安全（修改本人密码、查看最近登录）
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 账号安全
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$loginLogs = $loginLogs ?? [];
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger" role="alert">请检查标红字段后重试。</div>
<?php endif; ?>

<div class="admin-grid">
    <section class="admin-card admin-card--wide">
        <div class="admin-card__head">
            <div>
                <h2>修改密码</h2>
                <p>需要验证当前密码；修改成功后其他设备上的旧会话立即失效。</p>
            </div>
        </div>

        <form class="admin-form" action="<?= e(url('admin/updatePassword')) ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
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

                <div class="col-md-4">
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

                <div class="col-md-4">
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
            </div>

            <div class="admin-savebar">
                <span class="text-muted">新密码至少 10 位，且包含大写、小写、数字、符号中的至少三类。</span>
                <button class="btn btn-primary" type="submit">保存新密码</button>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>密码规则</h2>
                <p>与成员中心保持一致。</p>
            </div>
        </div>
        <ul class="password-rules">
            <li>至少 10 位。</li>
            <li>包含大写、小写、数字、符号中的至少三类。</li>
            <li>不得与用户名相同。</li>
            <li>修改成功后旧会话失效，需要重新登录。</li>
        </ul>
    </section>

    <section class="admin-card admin-card--wide">
        <div class="admin-card__head">
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
                <?php if ($loginLogs === []): ?>
                    <tr><td colspan="4" class="text-muted">暂无登录记录。</td></tr>
                <?php else: ?>
                    <?php foreach ($loginLogs as $log): ?>
                        <tr>
                            <td><?= e($log['time']) ?></td>
                            <td><code><?= e($log['ip_hash']) ?></code></td>
                            <td><?= e($log['ua']) ?></td>
                            <td>
                                <span class="badge <?= $log['success'] ? 'badge--success' : 'badge--gold' ?>">
                                    <?= $log['success'] ? '成功' : '失败' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="form-hint mt-2 mb-0">IP 仅保存 HMAC 哈希，无法还原为明文地址。</p>
    </section>
</div>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
