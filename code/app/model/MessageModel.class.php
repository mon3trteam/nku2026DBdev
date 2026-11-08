<?php
/**
 * 文件名：MessageModel.class.php
 * 功能：留言的数据访问；公开查询只返回 approved，后台可查看全部状态
 * 作者：杨斯源（学号待填）
 * 修改：金暄琛（2411672）后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 留言
 */

declare(strict_types=1);

final class MessageModel
{
    public const STATUS_LABELS = [
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

        return self::hydrateAdminList($rows);
    }

    /**
     * 看板待审核队列：只取 pending，按提交时间正序（先到先审）。
     */
    public static function pending(int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));

        $rows = Db::fetchAll(
            "SELECT m.id, m.author_name, m.content, m.created_at, m.status, mem.name AS member_name
             FROM messages m
             LEFT JOIN members mem ON mem.id = m.member_id
             WHERE m.status = 'pending'
             ORDER BY m.created_at ASC, m.id ASC
             LIMIT {$limit}"
        );

        return self::hydrateAdminList($rows);
    }

    /**
     * 留言列表（后台 / 看板）共用的字段映射。
     */
    private static function hydrateAdminList(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [
                'id'          => (int) $row['id'],
                'author'      => (string) $row['author_name'],
                'excerpt'     => str_limit((string) $row['content'], 60),
                'member'      => (string) ($row['member_name'] ?? '—'),
                'created_at'  => format_datetime((string) $row['created_at']),
                'status'      => self::STATUS_LABELS[(string) $row['status']] ?? (string) $row['status'],
                'status_code' => (string) $row['status'],
            ],
            $rows
        );
    }

    /**
     * 公开留言提交：默认进入待审核队列，邮箱/网站为空时写 NULL。
     */
    public static function create(array $data): int
    {
        $email = trim((string) ($data['author_email'] ?? ''));
        $website = trim((string) ($data['author_website'] ?? ''));

        Db::execute(
            'INSERT INTO messages
                (member_id, author_name, author_email, author_website, content, status, ip_hash, user_agent)
             VALUES
                (:member_id, :author_name, :author_email, :author_website, :content, :status, :ip_hash, :user_agent)',
            [
                'member_id'      => $data['member_id'] ?? null,
                'author_name'    => (string) $data['author_name'],
                'author_email'   => $email === '' ? null : $email,
                'author_website' => $website === '' ? null : $website,
                'content'        => (string) $data['content'],
                'status'         => (string) ($data['status'] ?? 'pending'),
                'ip_hash'        => (string) $data['ip_hash'],
                'user_agent'     => (string) ($data['user_agent'] ?? ''),
            ]
        );

        return Db::lastInsertId();
    }

    /**
     * 同一 IP 在窗口期内的留言数，用于公开表单限流。
     */
    public static function recentCountByIp(string $ipHash, int $minutes = 10): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - max(1, $minutes) * 60);
        $count = Db::fetchValue(
            'SELECT COUNT(*) FROM messages
             WHERE ip_hash = :ip_hash AND created_at >= :cutoff',
            ['ip_hash' => $ipHash, 'cutoff' => $cutoff]
        );

        return (int) ($count ?? 0);
    }

    /**
     * 后台审核页：完整留言 + 最新一条回复。
     */
    public static function findForAdmin(int $id): ?array
    {
        $row = Db::fetchOne(
            'SELECT m.*, mem.name AS member_name, mem.slug AS member_slug
             FROM messages m
             LEFT JOIN members mem ON mem.id = m.member_id
             WHERE m.id = :id
             LIMIT 1',
            ['id' => $id]
        );

        if ($row === null) {
            return null;
        }

        $reply = Db::fetchOne(
            'SELECT id, content, status, created_at, updated_at
             FROM message_replies
             WHERE message_id = :message_id
             ORDER BY id DESC
             LIMIT 1',
            ['message_id' => $id]
        );

        $row['id'] = (int) $row['id'];
        $row['member_id'] = $row['member_id'] === null ? null : (int) $row['member_id'];
        $row['author_name'] = (string) $row['author_name'];
        $row['author_email'] = (string) ($row['author_email'] ?? '');
        $row['author_website'] = (string) ($row['author_website'] ?? '');
        $row['content'] = (string) $row['content'];
        $row['status'] = (string) $row['status'];
        $row['admin_note'] = (string) ($row['admin_note'] ?? '');
        $row['ip_hash'] = (string) ($row['ip_hash'] ?? '');
        $row['user_agent'] = (string) ($row['user_agent'] ?? '');
        $row['member_name'] = (string) ($row['member_name'] ?? '');
        $row['member_slug'] = (string) ($row['member_slug'] ?? '');
        $row['created_at'] = format_datetime((string) $row['created_at']);
        $row['updated_at'] = format_datetime((string) $row['updated_at']);

        $row['reply'] = $reply === null ? null : [
            'id'         => (int) $reply['id'],
            'content'    => (string) $reply['content'],
            'status'     => (string) $reply['status'],
            'created_at' => format_datetime((string) $reply['created_at']),
            'updated_at' => format_datetime((string) $reply['updated_at']),
        ];

        return $row;
    }

    /**
     * 审核留言并保存一条官方回复。状态变更与回复写入放在同一事务里。
     */
    public static function moderate(
        int $id,
        string $status,
        ?string $adminNote,
        ?string $replyContent,
        bool $replyVisible,
        int $actorId,
        string $actorName
    ): void {
        Db::transaction(static function () use ($id, $status, $adminNote, $replyContent, $replyVisible, $actorId, $actorName): void {
            $exists = Db::fetchValue(
                'SELECT id FROM messages WHERE id = :id FOR UPDATE',
                ['id' => $id]
            );

            if ($exists === null) {
                throw new RuntimeException('message_missing');
            }

            Db::execute(
                'UPDATE messages
                 SET status = :status, admin_note = :admin_note
                 WHERE id = :id',
                [
                    'status'     => $status,
                    'admin_note' => $adminNote === null || $adminNote === '' ? null : $adminNote,
                    'id'         => $id,
                ]
            );

            $replyContent = $replyContent === null ? '' : trim($replyContent);
            if ($replyContent === '') {
                return;
            }

            $replyId = Db::fetchValue(
                'SELECT id FROM message_replies
                 WHERE message_id = :message_id
                 ORDER BY id DESC
                 LIMIT 1
                 FOR UPDATE',
                ['message_id' => $id]
            );

            $replyStatus = $replyVisible ? 'visible' : 'hidden';
            $replierName = mb_substr($actorName, 0, 50, 'UTF-8');

            if ($replyId === null) {
                Db::execute(
                    'INSERT INTO message_replies
                        (message_id, user_id, replier_name, content, is_admin, status)
                     VALUES
                        (:message_id, :user_id, :replier_name, :content, 1, :status)',
                    [
                        'message_id'   => $id,
                        'user_id'      => $actorId,
                        'replier_name' => $replierName,
                        'content'      => $replyContent,
                        'status'       => $replyStatus,
                    ]
                );

                return;
            }

            Db::execute(
                'UPDATE message_replies
                 SET user_id = :user_id,
                     replier_name = :replier_name,
                     content = :content,
                     is_admin = 1,
                     status = :status
                 WHERE id = :id',
                [
                    'user_id'      => $actorId,
                    'replier_name' => $replierName,
                    'content'      => $replyContent,
                    'status'       => $replyStatus,
                    'id'           => (int) $replyId,
                ]
            );
        });
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
