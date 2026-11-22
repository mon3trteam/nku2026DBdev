<?php
/**
 * 文件名：health.php
 * 功能：后台「数据健康」页视图——数据库状态、数据规模、运行环境、最近活动四张信息卡
 * 作者：钱泊瑜 2411282
 * 创建日期：2026-11-22
 * 所属模块：管理后台 / 数据健康
 *
 * 说明：纯展示页，无表单、无写操作；所有动态输出均经 e() 转义。
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

// 数据规模卡的合计行数（仅做展示聚合，不涉及 SQL）
$totalRows = array_sum($tableRows);

// 登录日志的失败原因码 -> 中文（与 login_logs.failure_reason 的取值一致）
$failureReasons = [
    'not_found' => '账号不存在',
    'wrong_password' => '密码错误',
    'locked' => '账号锁定',
    '' => '—',
];
?>
<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>数据库状态</h2>
            <p>当前连接的 MySQL 版本与本库对象数量。</p>
        </div>
    </div>
    <ul class="admin-list">
        <li>
            <span class="admin-list__title">MySQL 版本</span>
            <span class="admin-list__meta"><?= e($dbVersion) ?></span>
        </li>
        <li>
            <span class="admin-list__title">数据表总数</span>
            <span class="admin-list__meta"><?= e((string) $tableTotal) ?> 张（不含视图）</span>
        </li>
        <li>
            <span class="admin-list__title">视图数量</span>
            <span class="admin-list__meta"><?= e((string) $viewCount) ?> 个</span>
        </li>
    </ul>
</section>

<section class="admin-card">
    <div class="admin-card__head">
        <div>
            <h2>数据规模</h2>
            <p>全部业务表按行数降序排列，共 <?= e((string) count($tableRows)) ?> 张表、合计 <?= e((string) $totalRows) ?> 行。</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
            <tr><th>数据表</th><th>行数</th></tr>
            </thead>
            <tbody>
            <?php foreach ($tableRows as $table => $rows): ?>
                <tr>
                    <td><code><?= e($table) ?></code></td>
                    <td><?= e((string) $rows) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th>合计</th>
                <th><?= e((string) $totalRows) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>
    <p class="admin-hint">行数由 <code>SELECT COUNT(*)</code> 实时统计，口径与「数据包」页的数据规模一致。</p>
</section>

<div class="admin-grid">
    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>运行环境</h2>
                <p>PHP 运行时与服务器当前状态。</p>
            </div>
        </div>
        <ul class="admin-list">
            <li>
                <span class="admin-list__title">PHP 版本</span>
                <span class="admin-list__meta"><?= e($phpVersion) ?></span>
            </li>
            <li>
                <span class="admin-list__title">服务器软件</span>
                <span class="admin-list__meta"><?= e($serverSoftware) ?></span>
            </li>
            <li>
                <span class="admin-list__title">磁盘剩余空间</span>
                <span class="admin-list__meta"><?= e($diskFree) ?></span>
            </li>
            <li>
                <span class="admin-list__title">storage 目录</span>
                <span class="admin-list__meta">
                    <?php if ($storageWritable): ?>
                        <span class="text-success">可写</span>，上传与数据包导出正常
                    <?php else: ?>
                        <span class="text-danger">不可写</span>，上传与数据包导出会失败，请检查目录权限
                    <?php endif; ?>
                </span>
            </li>
        </ul>
    </section>

    <section class="admin-card admin-card--wide">
        <div class="admin-card__head">
            <div>
                <h2>最近活动</h2>
                <p>最近 5 条登录记录，按时间倒序。</p>
            </div>
        </div>
        <?php if ($recentLogins === []): ?>
            <p class="admin-hint">暂无登录记录。</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                    <tr><th>时间</th><th>账号</th><th>结果</th><th>失败原因</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentLogins as $log): ?>
                        <?php $ok = (int) ($log['success'] ?? 0) === 1; ?>
                        <tr>
                            <td><?= e(format_datetime((string) ($log['created_at'] ?? ''))) ?></td>
                            <td><code><?= e((string) ($log['username_attempted'] ?? '')) ?></code></td>
                            <td class="<?= $ok ? 'text-success' : 'text-danger' ?>"><?= $ok ? '成功' : '失败' ?></td>
                            <td class="text-muted">
                                <?php if ($ok): ?>—<?php else: ?>
                                    <?= e($failureReasons[(string) ($log['failure_reason'] ?? '')] ?? '未知原因') ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="admin-hint">最近一次审计日志：<?= e(format_datetime($lastAuditTime, '暂无审计记录')) ?></p>
    </section>
</div>

<?php require APP_ROOT . '/app/view/layout/admin_footer.php'; ?>
