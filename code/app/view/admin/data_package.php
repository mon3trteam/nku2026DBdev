<?php
/**
 * 文件名：data_package.php
 * 功能：后台「数据包」页——全站数据导出/导入（FR-DB-04 备份恢复）
 * 作者：黄浩天（2413421）
 * 创建日期：2026-11-09
 * 所属模块：管理后台 / 数据与运维
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$contentTables = ['users', 'roles', 'permissions', 'role_permissions', 'team_profile', 'milestones',
    'members', 'skills', 'member_skills', 'projects', 'project_members', 'announcements',
    'messages', 'message_replies', 'media', 'resources', 'site_settings'];
$opsTables = ['page_views', 'login_logs', 'audit_logs', 'schema_migrations'];
?>
<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>什么是数据包？</h2>
            <p>数据包（.zip）是整站运行数据的快照：全部数据表（含访问/登录/审计日志）+ 上传文件 + 版本清单。它是站点内容的唯一携带方式——代码仓库只是模板，真实数据不进仓库。</p>
        </div>
    </div>
    <ul class="admin-list">
        <li><span class="admin-list__title">导出</span><span class="admin-list__meta">下载当前全站数据的 ZIP，作为备份或迁移到云端/新机器的载体。</span></li>
        <li><span class="admin-list__title">导入</span><span class="admin-list__meta">上传任一历史数据包，整库覆盖恢复到那个时点（可多次导出留档，实现"回到某个版本"）。</span></li>
        <li><span class="admin-list__title">与 install.sql 的分工</span><span class="admin-list__meta">install.sql 只承载表结构与演示种子；真实内容只随数据包走，不回填仓库。</span></li>
        <li><span class="admin-list__title">安全边界</span><span class="admin-list__meta">仅超级管理员可操作；导入校验包内 schema 版本，包版本新于当前代码时拒绝导入；不含 .env 等密钥。</span></li>
    </ul>
    <p class="admin-hint">当前代码结构版本：schema v<?= e((string) $schemaVersion) ?>（导入的包不得高于此版本）。</p>
</section>

<?php if (is_array($importSummary)): ?>
<section class="admin-card">
    <div class="admin-card__head"><div><h2>导入成功</h2><p>站点已恢复到该数据包的快照时点。</p></div></div>
    <ul class="admin-list">
        <li><span class="admin-list__title">快照时间</span><span class="admin-list__meta"><?= e((string) ($importSummary['manifest']['exported_at'] ?? '—')) ?></span></li>
        <li><span class="admin-list__title">数据表</span><span class="admin-list__meta"><?= e((string) $importSummary['tables']) ?> 张表已覆盖</span></li>
        <li><span class="admin-list__title">写入记录</span><span class="admin-list__meta"><?= e((string) $importSummary['inserts']) ?> 条 INSERT</span></li>
        <li><span class="admin-list__title">上传文件</span><span class="admin-list__meta">
            公开媒体 <?= e((string) ($importSummary['uploads']['public'] ?? '0')) ?> 个 ·
            资源文件 <?= e((string) ($importSummary['uploads']['storage'] ?? '0')) ?> 个
        </span></li>
    </ul>
    <p class="admin-hint">本次导入已写入审计日志；建议刷新前台核对内容后，再导出一份新的数据包留档。</p>
</section>
<?php endif; ?>

<?php if ($importError !== ''): ?>
<div class="alert alert-danger" role="alert"><?= e($importError) ?></div>
<?php endif; ?>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>导出数据包</h2>
            <p>打包当前全部数据表与上传文件，下载到本地留档。</p>
        </div>
    </div>
    <form action="<?= e(url('datapackage/export')) ?>" method="post">
        <?= csrf_field() ?>
        <button class="btn btn-primary" type="submit">导出并下载数据包（.zip）</button>
    </form>
    <p class="admin-hint">建议在每次大改动（批量录入、上线迁移）后导出一份，文件名自带时间戳；请勿将数据包提交进 Git 或公开分享（含真实成员与留言数据）。</p>
</section>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>导入数据包</h2>
            <p>上传历史数据包，将整站恢复到该快照时点。</p>
        </div>
    </div>
    <form class="admin-form" action="<?= e(url('datapackage/import')) ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="package">数据包文件（.zip）</label>
            <input class="form-control" type="file" id="package" name="package" accept=".zip" required>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" id="ack" name="ack" required>
            <label class="form-check-label" for="ack">
                我已了解：导入将<b>整库覆盖</b>当前站点的全部数据与上传文件，此操作会写入审计日志。
            </label>
        </div>
        <button class="btn btn-danger" type="submit">导入并整库覆盖</button>
        <p class="admin-hint">包体积上限与上传配置一致（<?= e((string) ($_ENV['UPLOAD_MAX_MB'] ?? '12')) ?>MB 级别）；导入成功后建议立即重新导出一份留档。</p>
    </form>
</section>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>当前数据规模</h2>
            <p>导出前可据此核对；导入后数字应与所选数据包的 manifest 一致。</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead><tr><th>表</th><th>行数</th><th>说明</th></tr></thead>
            <tbody>
            <?php foreach ($contentTables as $t): if (!isset($tableRows[$t])) continue; ?>
                <tr><td><code><?= e($t) ?></code></td><td><?= e((string) $tableRows[$t]) ?></td><td class="text-muted">内容与权限</td></tr>
            <?php endforeach; ?>
            <?php foreach ($opsTables as $t): if (!isset($tableRows[$t])) continue; ?>
                <tr><td><code><?= e($t) ?></code></td><td><?= e((string) $tableRows[$t]) ?></td><td class="text-muted">运维日志</td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
