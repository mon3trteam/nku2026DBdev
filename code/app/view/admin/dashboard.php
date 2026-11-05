<?php
/**
 * 文件名：dashboard.php
 * 功能：管理后台数据看板（KPI、访问趋势、技能分布、待审核留言）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 数据看板
 */

require APP_ROOT . '/app/view/layout/admin_header.php';

$maxViews = max(array_column($trend, 'views'));
$maxSkill = max(array_column($skills, 'count'));
?>
<section class="kpi-grid" aria-label="关键指标">
    <a class="kpi-card" href="<?= e(url('admin/members')) ?>">
        <span class="kpi-card__label">成员总数</span>
        <strong class="kpi-card__value"><?= e($kpi['members']) ?></strong>
        <span class="kpi-card__hint">可见成员</span>
    </a>
    <a class="kpi-card" href="<?= e(url('admin/projects')) ?>">
        <span class="kpi-card__label">已发布项目</span>
        <strong class="kpi-card__value"><?= e($kpi['projects']) ?></strong>
        <span class="kpi-card__hint">进行中 + 已完成</span>
    </a>
    <a class="kpi-card kpi-card--warn" href="<?= e(url('admin/messages')) ?>">
        <span class="kpi-card__label">待审核留言</span>
        <strong class="kpi-card__value"><?= e($kpi['pending']) ?></strong>
        <span class="kpi-card__hint">需要处理</span>
    </a>
    <a class="kpi-card" href="#trend">
        <span class="kpi-card__label">近 7 日访问</span>
        <strong class="kpi-card__value"><?= e($kpi['views']) ?></strong>
        <span class="kpi-card__hint">按访客去重</span>
    </a>
</section>

<div class="admin-grid">
    <section class="admin-card admin-card--wide" id="trend">
        <div class="admin-card__head">
            <div>
                <h2>近 14 天访问趋势</h2>
                <p>按日统计访问量；图表接入 ECharts 前先用柱状占位。</p>
            </div>
            <span class="badge badge--muted">演示数据</span>
        </div>
        <div class="bar-chart" role="img" aria-label="近 14 天访问趋势柱状图">
            <?php foreach ($trend as $point): ?>
                <div class="bar-chart__item">
                    <span class="bar-chart__value"><?= e($point['views']) ?></span>
                    <span class="bar-chart__bar" style="height: <?= e((int) round($point['views'] / $maxViews * 100)) ?>%"></span>
                    <span class="bar-chart__label"><?= e($point['date']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <table class="visually-hidden">
            <caption>近 14 天访问趋势数据</caption>
            <thead><tr><th>日期</th><th>访问量</th></tr></thead>
            <tbody>
            <?php foreach ($trend as $point): ?>
                <tr><td><?= e($point['date']) ?></td><td><?= e($point['views']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>成员技能分布</h2>
                <p>按技能统计成员数。</p>
            </div>
        </div>
        <div class="skill-dist">
            <?php foreach ($skills as $skill): ?>
                <div class="skill-dist__row">
                    <span class="skill-dist__name"><?= e($skill['name']) ?></span>
                    <span class="skill-dist__track"><span class="skill-dist__fill" style="width: <?= e((int) round($skill['count'] / $maxSkill * 100)) ?>%"></span></span>
                    <span class="skill-dist__num"><?= e($skill['count']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-card admin-card--wide">
        <div class="admin-card__head">
            <div>
                <h2>待审核留言</h2>
                <p>默认进入待审核队列，审核路径不超过两次点击。</p>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="#">查看全部</a>
        </div>
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                <tr><th>访客</th><th>内容摘要</th><th>目标成员</th><th>提交时间</th><th>操作</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pendingMessages as $message): ?>
                    <tr>
                        <td><?= e($message['author']) ?></td>
                        <td><?= e(str_limit($message['excerpt'], 30)) ?></td>
                        <td><?= e($message['member']) ?></td>
                        <td><?= e($message['created_at']) ?></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-success" type="button" disabled>通过</button>
                            <button class="btn btn-sm btn-outline-danger" type="button"
                                    data-bs-toggle="modal" data-bs-target="#rejectModal">拒绝</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="admin-hint">按钮暂为占位，留言审核接口接入后启用。</p>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>最近公告</h2>
                <p>发布后同步到首页与公告中心。</p>
            </div>
        </div>
        <ul class="admin-list">
            <?php foreach ($announcements as $announcement): ?>
                <li>
                    <span class="admin-list__title"><?= e($announcement['title']) ?></span>
                    <span class="admin-list__meta"><?= e(format_date($announcement['published_at'])) ?> · <?= e($announcement['category_label']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>留言状态分布</h2>
                <p>待审核 / 已通过 / 已拒绝 / 垃圾。</p>
            </div>
        </div>
        <div class="status-bars">
            <?php $maxMessage = max(array_column($messageStatus, 'count')); ?>
            <?php foreach ($messageStatus as $item): ?>
                <div class="status-bar">
                    <span class="status-bar__label"><?= e($item['label']) ?></span>
                    <span class="status-bar__track">
                        <span class="status-bar__fill" style="width: <?= e((int) round($item['count'] / $maxMessage * 100)) ?>%"></span>
                    </span>
                    <span class="status-bar__num"><?= e($item['count']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>项目状态分布</h2>
                <p>规划中 / 进行中 / 已完成 / 已归档。</p>
            </div>
        </div>
        <div class="status-bars">
            <?php $maxProject = max(1, max(array_column($projectStatus, 'count'))); ?>
            <?php foreach ($projectStatus as $item): ?>
                <div class="status-bar">
                    <span class="status-bar__label"><?= e($item['label']) ?></span>
                    <span class="status-bar__track">
                        <span class="status-bar__fill status-bar__fill--gold" style="width: <?= e((int) round($item['count'] / $maxProject * 100)) ?>%"></span>
                    </span>
                    <span class="status-bar__num"><?= e($item['count']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card__head">
            <div>
                <h2>待办</h2>
                <p>高频操作入口。</p>
            </div>
        </div>
        <ul class="admin-todo">
            <li><a href="<?= e(url('admin/messages')) ?>">处理 <?= e($kpi['pending']) ?> 条待审核留言</a></li>
            <li><a href="<?= e(url('admin/members')) ?>">检查成员资料完整度</a></li>
            <li><a href="<?= e(url('admin/announcements')) ?>">发布一条团队公告</a></li>
        </ul>
    </section>
</div>

<?php
$modalId = 'rejectModal';
$modalTitle = '拒绝留言';
$modalBody = '拒绝后留言不会公开，但仍保留记录以便复盘。该操作会写入审计日志。';
$modalConfirmText = '确认拒绝';
$modalDanger = true;
require APP_ROOT . '/app/view/partial/modal_confirm.php';
require APP_ROOT . '/app/view/layout/admin_footer.php';
?>
