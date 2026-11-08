<?php
/**
 * 文件名：log_list.php
 * 功能：后台日志审计（登录日志 + 操作审计）
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入与品牌同步（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 日志审计
 */

require APP_ROOT . '/app/view/layout/admin_header.php';
?>
<div class="admin-toolbar">
    <p>日志只追加、不修改；登录日志与操作审计保留 180 天。</p>
    <span class="badge badge--muted">登录 <?= e((string) count($loginLogs)) ?> 条 · 审计 <?= e((string) count($auditLogs)) ?> 条</span>
</div>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>筛选</h2>
            <p>账号与操作者支持模糊匹配；日期范围同时作用于两张表。</p>
        </div>
    </div>
    <form action="<?= e(APP_BASE . '/index.php') ?>" method="get" class="row g-2 align-items-end">
        <input type="hidden" name="r" value="admin/logs">
        <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="login_user">登录账号</label>
            <input class="form-control form-control-sm" id="login_user" name="login_user" type="search"
                   maxlength="50" value="<?= e($filters['login_user']) ?>">
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="login_result">登录结果</label>
            <select class="form-select form-select-sm" id="login_result" name="login_result">
                <option value="">全部结果</option>
                <option value="1" <?= $filters['login_result'] === '1' ? 'selected' : '' ?>>成功</option>
                <option value="0" <?= $filters['login_result'] === '0' ? 'selected' : '' ?>>失败</option>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="audit_actor">操作者</label>
            <input class="form-control form-control-sm" id="audit_actor" name="audit_actor" type="search"
                   maxlength="50" value="<?= e($filters['audit_actor']) ?>">
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="audit_action">审计动作</label>
            <select class="form-select form-select-sm" id="audit_action" name="audit_action">
                <option value="">全部动作</option>
                <?php foreach ($actionOptions as $code => $label): ?>
                    <option value="<?= e($code) ?>" <?= $filters['audit_action'] === $code ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="from">开始日期</label>
            <input class="form-control form-control-sm" id="from" name="from" type="date" value="<?= e($filters['from']) ?>">
        </div>
        <div class="col-sm-6 col-lg-2">
            <label class="form-label" for="to">结束日期</label>
            <input class="form-control form-control-sm" id="to" name="to" type="date" value="<?= e($filters['to']) ?>">
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-sm btn-primary" type="submit">筛选</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/logs')) ?>">重置</a>
        </div>
    </form>
</section>

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
