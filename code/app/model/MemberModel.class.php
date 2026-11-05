<?php
/**
 * 文件名：MemberModel.class.php
 * 功能：成员与项目的演示数据模型；后端接入数据库后替换方法内部实现即可
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 成员
 */

declare(strict_types=1);

final class MemberModel
{
    /**
     * 演示数据（非真实成员）。字段与设计文档 members / projects 数据字典保持一致。
     */
    private static array $members = [
        [
            'id' => 1,
            'slug' => 'lin-zhiyuan',
            'name' => '林知远',
            'english_name' => 'Lin Zhiyuan',
            'role_title' => '前端开发 / 数据库设计',
            'tagline' => '用数据与界面讲好团队故事。',
            'bio' => '负责团队主页的前台与后台界面，关注信息架构、交互细节与可访问性。喜欢把复杂的数据整理成一眼能看懂的页面。',
            'avatar' => 'img/placeholder-avatar.svg',
            'cover' => 'img/placeholder-cover.svg',
            'email' => 'lin@example.com',
            'github' => 'https://github.com/example',
            'website' => '',
            'location' => '天津',
            'join_date' => '2026-09-01',
            'updated_at' => '2026-11-05 10:00:00',
            'is_featured' => 1,
            'skills' => [
                ['name' => 'JavaScript', 'category' => 'tech', 'proficiency' => 5],
                ['name' => 'PHP', 'category' => 'tech', 'proficiency' => 4],
                ['name' => 'MySQL', 'category' => 'data', 'proficiency' => 4],
                ['name' => 'UI Design', 'category' => 'design', 'proficiency' => 4],
            ],
        ],
        [
            'id' => 2,
            'slug' => 'su-wanqing',
            'name' => '苏晚晴',
            'english_name' => 'Su Wanqing',
            'role_title' => '后端开发 / 接口设计',
            'tagline' => '让每一条请求都有清晰的去向。',
            'bio' => '负责认证、会话与后台服务，关注接口边界、事务一致性与安全默认值。',
            'avatar' => 'img/placeholder-avatar.svg',
            'cover' => 'img/placeholder-cover.svg',
            'email' => 'su@example.com',
            'github' => 'https://github.com/example',
            'website' => '',
            'location' => '天津',
            'join_date' => '2026-09-01',
            'is_featured' => 1,
            'skills' => [
                ['name' => 'PHP', 'category' => 'tech', 'proficiency' => 5],
                ['name' => 'MySQL', 'category' => 'data', 'proficiency' => 5],
                ['name' => 'Docker', 'category' => 'tech', 'proficiency' => 3],
            ],
        ],
        [
            'id' => 3,
            'slug' => 'chen-yiming',
            'name' => '陈一鸣',
            'english_name' => 'Chen Yiming',
            'role_title' => '数据建模 / SQL',
            'tagline' => '先想清楚关系，再写第一条 SQL。',
            'bio' => '负责数据库设计、install.sql 与查询优化，喜欢用 EXPLAIN 找问题。',
            'avatar' => 'img/placeholder-avatar.svg',
            'cover' => 'img/placeholder-cover.svg',
            'email' => 'chen@example.com',
            'github' => 'https://github.com/example',
            'website' => '',
            'location' => '天津',
            'join_date' => '2026-09-01',
            'is_featured' => 1,
            'skills' => [
                ['name' => 'MySQL', 'category' => 'data', 'proficiency' => 5],
                ['name' => 'PHP', 'category' => 'tech', 'proficiency' => 3],
                ['name' => 'Git', 'category' => 'tool', 'proficiency' => 4],
            ],
        ],
        [
            'id' => 4,
            'slug' => 'zhou-yuan',
            'name' => '周予安',
            'english_name' => 'Zhou Yuan',
            'role_title' => '内容运营 / 视觉',
            'tagline' => '把团队的故事讲得清楚、好看。',
            'bio' => '负责团队介绍文案、视觉素材与内容发布，关注品牌一致性与阅读体验。',
            'avatar' => 'img/placeholder-avatar.svg',
            'cover' => 'img/placeholder-cover.svg',
            'email' => 'zhou@example.com',
            'github' => '',
            'website' => 'https://example.com',
            'location' => '天津',
            'join_date' => '2026-09-15',
            'is_featured' => 1,
            'skills' => [
                ['name' => 'UI Design', 'category' => 'design', 'proficiency' => 5],
                ['name' => '写作', 'category' => 'soft', 'proficiency' => 5],
                ['name' => 'Git', 'category' => 'tool', 'proficiency' => 3],
            ],
        ],
    ];

    private static array $projects = [
        [
            'slug' => 'teamhub',
            'title' => '数聚 TeamHub 团队主页',
            'summary' => '团队展示、成员主页、公告与作业下载一体化的课程设计项目。',
            'description_html' => '<p>数聚 TeamHub 是本课程的团队主页项目，目标是做出一套可演示、可还原、可讲清楚的 PHP + MySQL 应用。</p>'
                . '<p>前端包含公共门户、成员中心与管理后台三套布局；后端按 MVC 分层，数据库设计 21 张表。当前版本先完成公共门户的展示页面，后续接入认证、留言审核与内容发布。</p>',
            'start_date' => '2026-10-28',
            'end_date' => '',
            'repo_url' => 'https://github.com/mon3trteam/nku2026DBdev',
            'demo_url' => '',
            'tech_stack' => 'PHP, MySQL, Bootstrap, ECharts',
            'status' => 'ongoing',
            'status_label' => '进行中',
            'cover' => 'img/placeholder-cover.svg',
            'featured' => 1,
            'members' => [
                ['name' => '林知远', 'role_name' => '前端 / 数据库设计'],
                ['name' => '苏晚晴', 'role_name' => '后端 / 接口'],
            ],
        ],
        [
            'slug' => 'book-market',
            'title' => '校园二手书交易平台',
            'summary' => '面向校园的二手书发布、检索与留言系统，练习 CRUD 与权限控制。',
            'description_html' => '<p>一个面向校园的二手书交易练习项目：用户可以发布书籍、按关键词检索，并在详情页留言。</p>'
                . '<p>项目重点练习表单校验、权限边界与列表分页，为团队主页的成员与资源模块提供参考。</p>',
            'start_date' => '2026-09-10',
            'end_date' => '2026-10-20',
            'repo_url' => '',
            'demo_url' => '',
            'tech_stack' => 'PHP, MySQL, JavaScript',
            'status' => 'completed',
            'status_label' => '已完成',
            'cover' => 'img/placeholder-cover.svg',
            'featured' => 1,
            'members' => [
                ['name' => '陈一鸣', 'role_name' => '数据建模'],
                ['name' => '苏晚晴', 'role_name' => '后端'],
            ],
        ],
        [
            'slug' => 'download-center',
            'title' => '课程作业下载中心',
            'summary' => '按团队文档与个人作业分类的下载中心，包含权限校验与下载计数。',
            'description_html' => '<p>下载中心读取 <code>data/team</code> 与 <code>data/personal</code> 目录，按团队文档与个人作业分类展示。</p>'
                . '<p>公开资源直接下载并计数，私有资源需要登录；下载路径经过控制器校验，不暴露磁盘路径。</p>',
            'start_date' => '2026-11-01',
            'end_date' => '',
            'repo_url' => '',
            'demo_url' => '',
            'tech_stack' => 'PHP, MySQL, Bootstrap',
            'status' => 'planning',
            'status_label' => '规划中',
            'cover' => 'img/placeholder-cover.svg',
            'featured' => 1,
            'members' => [
                ['name' => '周予安', 'role_name' => '内容 / 视觉'],
            ],
        ],
    ];

    private static array $messages = [
        [
            'id' => 1,
            'author_name' => '一位访客',
            'content' => '首页的结构很清楚，成员主页的技能条也很好读，期待看到项目详情页。',
            'created_at' => '2026-11-02 20:15',
        ],
        [
            'id' => 2,
            'author_name' => '同班同学',
            'content' => '请问下载中心会放团队文档的 PDF 吗？想参考一下设计文档的结构。',
            'created_at' => '2026-11-03 09:40',
        ],
    ];

    public static function allVisible(): array
    {
        return self::$members;
    }

    public static function featured(int $limit = 4): array
    {
        $list = array_values(array_filter(
            self::$members,
            static fn (array $member): bool => (int) $member['is_featured'] === 1
        ));

        return array_slice($list, 0, $limit);
    }

    public static function findBySlug(string $slug): ?array
    {
        foreach (self::$members as $member) {
            if ($member['slug'] === $slug) {
                return $member;
            }
        }

        return null;
    }

    public static function skillOptions(): array
    {
        return array_column(self::skillCatalog(), 'name');
    }

    /**
     * 全局技能库（去重，按名称排序），用于成员资料页的技能选择。
     */
    public static function skillCatalog(): array
    {
        $catalog = [];
        foreach (self::$members as $member) {
            foreach ($member['skills'] as $skill) {
                $catalog[$skill['name']] = [
                    'name' => $skill['name'],
                    'category' => $skill['category'],
                ];
            }
        }

        ksort($catalog);
        return array_values($catalog);
    }

    public static function filterBySkill(string $skill): array
    {
        if ($skill === '') {
            return self::allVisible();
        }

        return array_values(array_filter(
            self::$members,
            static function (array $member) use ($skill): bool {
                foreach ($member['skills'] as $item) {
                    if ($item['name'] === $skill) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    /**
     * 成员列表的搜索、筛选、排序与分页。
     *
     * @return array{items: array, total: int, page: int, per_page: int, last_page: int}
     */
    public static function search(string $keyword, string $skill, string $sort, int $page, int $perPage): array
    {
        $list = self::filterBySkill($skill);

        if ($keyword !== '') {
            $list = array_values(array_filter(
                $list,
                static function (array $member) use ($keyword): bool {
                    $haystack = $member['name'] . ' ' . $member['english_name'] . ' '
                        . $member['role_title'] . ' ' . $member['tagline'];
                    return mb_stripos($haystack, $keyword, 0, 'UTF-8') !== false;
                }
            ));
        }

        if ($sort === 'name') {
            usort($list, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
        } elseif ($sort === 'join_date') {
            usort($list, static fn (array $a, array $b): int => strcmp($b['join_date'], $a['join_date']));
        }

        $total = count($list);
        $perPage = max(1, min(100, $perPage));
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

    public static function allProjects(): array
    {
        return self::$projects;
    }

    public static function findProjectBySlug(string $slug): ?array
    {
        foreach (self::$projects as $project) {
            if ($project['slug'] === $slug) {
                return $project;
            }
        }

        return null;
    }

    public static function projectsOf(string $slug): array
    {
        return array_values(array_filter(
            self::$projects,
            static function (array $project) use ($slug): bool {
                foreach ($project['members'] as $member) {
                    if (self::slugOfName($member['name']) === $slug) {
                        return true;
                    }
                }

                return false;
            }
        ));
    }

    public static function featuredProjects(int $limit = 3): array
    {
        $list = array_values(array_filter(
            self::$projects,
            static fn (array $project): bool => (int) $project['featured'] === 1
        ));

        return array_slice($list, 0, $limit);
    }

    public static function messagesOf(string $slug): array
    {
        return self::$messages;
    }

    private static function slugOfName(string $name): string
    {
        foreach (self::$members as $member) {
            if ($member['name'] === $name) {
                return $member['slug'];
            }
        }

        return '';
    }
}
