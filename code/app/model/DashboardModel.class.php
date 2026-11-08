<?php
/**
 * 文件名：DashboardModel.class.php
 * 功能：管理后台数据看板的统计查询（KPI、访问趋势、技能与状态分布）
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：管理后台 / 数据看板
 *
 * 说明：
 *   1) 看板只读取数据库，不再使用演示数组；
 *   2) 趋势查询基于视图 v_dashboard_daily_views，空白天在模型层补 0；
 *   3) 所有数量均返回 int，视图可直接输出。
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MessageModel.class.php';

final class DashboardModel
{
    private const PROJECT_STATUS_LABELS = [
        'planning'  => '规划中',
        'ongoing'   => '进行中',
        'completed' => '已完成',
        'archived'  => '已归档',
    ];

    /**
     * 顶部四个 KPI。
     *
     * @return array{members: int, projects: int, pending: int, views: int}
     */
    public static function kpi(): array
    {
        $weekStart = date('Y-m-d', strtotime('-6 days'));

        return [
            'members'  => (int) Db::fetchValue(
                'SELECT COUNT(*) FROM members WHERE is_visible = 1'
            ),
            'projects' => (int) Db::fetchValue(
                'SELECT COUNT(*) FROM projects WHERE is_visible = 1'
            ),
            'pending'  => (int) Db::fetchValue(
                "SELECT COUNT(*) FROM messages WHERE status = 'pending'"
            ),
            'views'    => (int) Db::fetchValue(
                'SELECT COALESCE(SUM(views), 0)
                 FROM v_dashboard_daily_views
                 WHERE view_date >= :start',
                ['start' => $weekStart]
            ),
        ];
    }

    /**
     * 近 N 天访问趋势；没有记录的日期补 0，保证图表横轴完整。
     *
     * @return list<array{date: string, full_date: string, views: int, visitors: int}>
     */
    public static function dailyViews(int $days = 14): array
    {
        $days = max(1, min(90, $days));
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $rows = Db::fetchAll(
            'SELECT view_date, views, visitors
             FROM v_dashboard_daily_views
             WHERE view_date >= :start
             ORDER BY view_date ASC',
            ['start' => $start]
        );

        $byDate = [];
        foreach ($rows as $row) {
            $byDate[(string) $row['view_date']] = [
                'views'    => (int) $row['views'],
                'visitors' => (int) $row['visitors'],
            ];
        }

        $trend = [];
        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $day = date('Y-m-d', strtotime('-' . $offset . ' days'));
            $trend[] = [
                'date'      => substr($day, 5),
                'full_date' => $day,
                'views'     => $byDate[$day]['views'] ?? 0,
                'visitors'  => $byDate[$day]['visitors'] ?? 0,
            ];
        }

        return $trend;
    }

    /**
     * 成员技能分布：每个技能对应的公开成员数，按人数倒序取前 N 个。
     *
     * @return list<array{name: string, count: int}>
     */
    public static function skillDistribution(int $limit = 6): array
    {
        $limit = max(1, min(20, $limit));

        $rows = Db::fetchAll(
            "SELECT s.name, COUNT(DISTINCT ms.member_id) AS total
             FROM skills s
             JOIN member_skills ms ON ms.skill_id = s.id
             JOIN members m ON m.id = ms.member_id AND m.is_visible = 1
             GROUP BY s.id, s.name, s.sort_order
             ORDER BY total DESC, s.sort_order ASC, s.name ASC
             LIMIT {$limit}"
        );

        return array_map(
            static fn (array $row): array => [
                'name'  => (string) $row['name'],
                'count' => (int) $row['total'],
            ],
            $rows
        );
    }

    /**
     * 留言状态分布，保持“待审核/已通过/已拒绝/垃圾”的固定顺序。
     *
     * @return list<array{label: string, count: int, class: string}>
     */
    public static function messageStatus(): array
    {
        $counts = MessageModel::countByStatus();
        $classes = [
            'pending'  => 'is-warn',
            'approved' => 'is-ok',
            'rejected' => 'is-muted',
            'spam'     => 'is-danger',
        ];

        $status = [];
        foreach (MessageModel::STATUS_LABELS as $code => $label) {
            $status[] = [
                'label' => $label,
                'count' => (int) ($counts[$code] ?? 0),
                'class' => $classes[$code] ?? 'is-muted',
            ];
        }

        return $status;
    }

    /**
     * 项目状态分布，包含数量为 0 的状态。
     *
     * @return list<array{label: string, count: int}>
     */
    public static function projectStatus(): array
    {
        $counts = array_fill_keys(array_keys(self::PROJECT_STATUS_LABELS), 0);
        $rows = Db::fetchAll('SELECT status, COUNT(*) AS total FROM projects GROUP BY status');

        foreach ($rows as $row) {
            $code = (string) $row['status'];
            if (array_key_exists($code, $counts)) {
                $counts[$code] = (int) $row['total'];
            }
        }

        $status = [];
        foreach (self::PROJECT_STATUS_LABELS as $code => $label) {
            $status[] = [
                'label' => $label,
                'count' => $counts[$code],
            ];
        }

        return $status;
    }
}
