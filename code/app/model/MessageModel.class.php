<?php
/**
 * 文件名：MessageModel.class.php
 * 功能：留言的数据访问；公开查询只返回 approved，后台可查看全部状态
 * 作者：杨斯源（学号待填）
 * 修改：后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 留言
 */

declare(strict_types=1);

final class MessageModel
{
    private const STATUS_LABELS = [
        'pending'  => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
        'spam'     => '垃圾',
    ];

    /**
     * 公开留言分页。只返回 approved，并带出最新一条可见回复。
     *
     * @return array{items: array, total: int, page: int, per_page: int, last_page: int}
     */
    public static function approved(int $page = 1, int $perPage = 5): array
    {
        $total = (int) Db::fetchValue("SELECT COUNT(*) FROM messages WHERE status = 'approved'");
        $perPage = max(1, min(50, $perPage));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $rows = Db::fetchAll(
            "SELECT m.id, m.author_name, m.content, m.created_at,
                    mem.slug AS member_slug, mem.name AS member_name,
                    (SELECT r.content
                       FROM message_replies r
                      WHERE r.message_id = m.id AND r.status = 'visible'
                      ORDER BY r.created_at DESC, r.id DESC
                      LIMIT 1) AS reply,
                    (SELECT r.created_at
                       FROM message_replies r
                      WHERE r.message_id = m.id AND r.status = 'visible'
                      ORDER BY r.created_at DESC, r.id DESC
                      LIMIT 1) AS reply_at
             FROM messages m
             LEFT JOIN members mem ON mem.id = m.member_id
             WHERE m.status = 'approved'
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );

        $items = array_map(
            static function (array $row): array {
                $row['member_slug'] = (string) ($row['member_slug'] ?? '');
                $row['member_name'] = (string) ($row['member_name'] ?? '');
                $row['created_at'] = format_datetime((string) $row['created_at']);
                $row['reply_at'] = $row['reply_at'] === null
                    ? null
                    : format_datetime((string) $row['reply_at']);
                $row['status'] = 'approved';

                return $row;
            },
            $rows
        );

        return [
            'items'     => $items,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
        ];
    }

    /**
     * 后台留言列表：按待审核优先、时间倒序排列。
     */
    public static function forAdmin(): array
    {
        $rows = Db::fetchAll(
            'SELECT m.id, m.author_name, m.content, m.created_at, m.status, mem.name AS member_name
             FROM messages m
             LEFT JOIN members mem ON mem.id = m.member_id
             ORDER BY FIELD(m.status, \'pending\', \'approved\', \'rejected\', \'spam\'),
                      m.created_at DESC, m.id DESC'
        );

        return array_map(
            static fn (array $row): array => [
                'id'         => (int) $row['id'],
                'author'     => (string) $row['author_name'],
                'excerpt'    => str_limit((string) $row['content'], 60),
                'member'     => (string) ($row['member_name'] ?? '—'),
                'created_at' => format_datetime((string) $row['created_at']),
                'status'     => self::STATUS_LABELS[(string) $row['status']] ?? (string) $row['status'],
                'status_code'=> (string) $row['status'],
            ],
            $rows
        );
    }

    /**
     * 各状态数量，供看板使用。
     *
     * @return array<string, int>
     */
    public static function countByStatus(): array
    {
        $rows = Db::fetchAll('SELECT status, COUNT(*) AS total FROM messages GROUP BY status');
        $counts = array_fill_keys(array_keys(self::STATUS_LABELS), 0);

        foreach ($rows as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
