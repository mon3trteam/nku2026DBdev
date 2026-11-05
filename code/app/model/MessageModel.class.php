<?php
/**
 * 文件名：MessageModel.class.php
 * 功能：留言板演示数据；公开查询只返回 approved，后端接入数据库后替换方法实现
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 留言
 */

declare(strict_types=1);

final class MessageModel
{
    /**
     * 演示数据。status 为 approved 的才会出现在公开列表。
     */
    private static array $messages = [
        [
            'id' => 1,
            'author_name' => '一位访客',
            'content' => '首页的结构很清楚，成员主页的技能条也很好读，期待看到项目详情页。',
            'member_slug' => 'lin-zhiyuan',
            'member_name' => '林知远',
            'created_at' => '2026-11-02 20:15',
            'reply' => '谢谢关注，项目详情页已经补上了。',
            'reply_at' => '2026-11-03 09:10',
            'status' => 'approved',
        ],
        [
            'id' => 2,
            'author_name' => '同班同学',
            'content' => '请问下载中心会放团队文档的 PDF 吗？想参考一下设计文档的结构。',
            'member_slug' => 'zhou-yuan',
            'member_name' => '周予安',
            'created_at' => '2026-11-03 09:40',
            'reply' => '会放需求、设计、部署三份文档，资源页正在做。',
            'reply_at' => '2026-11-03 12:20',
            'status' => 'approved',
        ],
        [
            'id' => 3,
            'author_name' => '访客C',
            'content' => '成员主页的项目卡片排版很清晰，建议在列表页也加上项目入口。',
            'member_slug' => 'su-wanqing',
            'member_name' => '苏晚晴',
            'created_at' => '2026-11-04 21:05',
            'reply' => null,
            'reply_at' => null,
            'status' => 'approved',
        ],
        [
            'id' => 4,
            'author_name' => '学弟',
            'content' => '想了解团队招新和课程项目的方向，有没有公开的介绍材料？',
            'member_slug' => '',
            'member_name' => '',
            'created_at' => '2026-11-04 18:30',
            'reply' => '团队介绍页有方向和分工，后续会补招新说明。',
            'reply_at' => '2026-11-04 19:00',
            'status' => 'approved',
        ],
        [
            'id' => 5,
            'author_name' => '合作方',
            'content' => '项目状态和参与成员的展示很直观，方便快速判断团队分工。',
            'member_slug' => 'chen-yiming',
            'member_name' => '陈一鸣',
            'created_at' => '2026-11-03 16:20',
            'reply' => null,
            'reply_at' => null,
            'status' => 'approved',
        ],
        [
            'id' => 6,
            'author_name' => '访客D',
            'content' => '留言板会支持管理员回复吗？希望能看到官方的补充说明。',
            'member_slug' => '',
            'member_name' => '',
            'created_at' => '2026-11-02 11:05',
            'reply' => '支持，回复会显示在留言下方。',
            'reply_at' => '2026-11-02 13:40',
            'status' => 'approved',
        ],
        [
            'id' => 7,
            'author_name' => '路过的同学',
            'content' => '移动端的导航抽屉很好用，360 宽度下也没有横向滚动。',
            'member_slug' => 'lin-zhiyuan',
            'member_name' => '林知远',
            'created_at' => '2026-11-01 10:12',
            'reply' => null,
            'reply_at' => null,
            'status' => 'approved',
        ],
    ];

    /**
     * 公开留言分页。只返回 approved。
     *
     * @return array{items: array, total: int, page: int, per_page: int, last_page: int}
     */
    public static function approved(int $page = 1, int $perPage = 5): array
    {
        $list = array_values(array_filter(
            self::$messages,
            static fn (array $message): bool => $message['status'] === 'approved'
        ));

        usort($list, static fn (array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));

        $total = count($list);
        $perPage = max(1, min(50, $perPage));
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($list, $offset, $perPage),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }
}
