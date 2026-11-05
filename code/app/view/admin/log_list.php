<?php
/**
 * 文件名：log_list.php
 * 功能：后台日志审计（登录日志 + 操作审计）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 日志审计
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>日志只追加、不修改；登录日志与操作审计保留 180 天。</p>
    <div class="d-flex gap-2">
        <select class="form-select form-select-sm" aria-label="按用户筛选" disabled>
            <option>全部用户</option>
        </select>
        <select class="form-select form-select-sm" aria-label="按动作筛选" disabled>
            <option>全部动作</option>
        </select>
    </div>
</div>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>登录日志</h2>
            <p>成功/失败、原因、IP 哈希与设备。</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <caption class="visually-hidden">登录日志</caption>
            <thead>
            <tr><th scope="col">时间</th><th scope="col">账号</th><th scope="col">结果</th><th scope="col">原因</th><th scope="col">IP 哈希</th><th scope="col">设备</th></tr>
            </thead>
            <tbody>
            <?php foreach ($loginLogs as $log): ?>
                <tr>
                    <td><?= e($log['time']) ?></td>
                    <td><code><?= e($log['username']) ?></code></td>
                    <td>
                        <?php if ((int) $log['success'] === 1): ?>
                            <span class="badge badge--success">成功</span>
                        <?php else: ?>
                            <span class="badge badge--gold">失败</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($log['reason'] !== '' ? $log['reason'] : '—') ?></td>
                    <td><code><?= e($log['ip_hash']) ?></code></td>
                    <td><?= e($log['ua']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>操作审计</h2>
            <p>发布、审核、资料更新等敏感操作。</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <caption class="visually-hidden">操作审计</caption>
            <thead>
            <tr><th scope="col">时间</th><th scope="col">操作者</th><th scope="col">动作</th><th scope="col">实体</th><th scope="col">摘要</th></tr>
            </thead>
            <tbody>
            <?php foreach ($auditLogs as $log): ?>
                <tr>
                    <td><?= e($log['time']) ?></td>
                    <td><code><?= e($log['actor']) ?></code></td>
                    <td><span class="badge badge--muted"><?= e($log['action']) ?></span></td>
                    <td><code><?= e($log['entity']) ?></code></td>
                    <td><?= e($log['summary']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
