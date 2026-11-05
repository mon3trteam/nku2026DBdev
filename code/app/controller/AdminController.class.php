<?php
/**
 * 文件名：AdminController.class.php
 * 功能：管理后台控制器；当前使用演示数据，后端接口接入后替换
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MemberModel.class.php';
require_once APP_ROOT . '/app/model/AnnouncementModel.class.php';
require_once APP_ROOT . '/app/model/TeamModel.class.php';
require_once APP_ROOT . '/app/model/ResourceModel.class.php';
require_once APP_ROOT . '/app/model/UserModel.class.php';
require_once APP_ROOT . '/app/model/SiteSettingModel.class.php';
require_once APP_ROOT . '/app/model/LogModel.class.php';

final class AdminController
{
    public function dashboard(): void
    {
        $members = MemberModel::allVisible();
        $announcements = AnnouncementModel::latest(5);

        render('admin/dashboard', [
            'pageTitle' => '数据看板 · 管理后台',
            'adminNav' => 'dashboard',
            'kpi' => [
                'members' => count($members),
                'projects' => 3,
                'pending' => 7,
                'views' => 1286,
            ],
            'trend' => [
                ['date' => '10-23', 'views' => 42],
                ['date' => '10-24', 'views' => 58],
                ['date' => '10-25', 'views' => 36],
                ['date' => '10-26', 'views' => 64],
                ['date' => '10-27', 'views' => 51],
                ['date' => '10-28', 'views' => 73],
                ['date' => '10-29', 'views' => 68],
                ['date' => '10-30', 'views' => 82],
                ['date' => '10-31', 'views' => 61],
                ['date' => '11-01', 'views' => 90],
                ['date' => '11-02', 'views' => 76],
                ['date' => '11-03', 'views' => 88],
                ['date' => '11-04', 'views' => 95],
                ['date' => '11-05', 'views' => 102],
            ],
            'skills' => [
                ['name' => 'MySQL', 'count' => 3],
                ['name' => 'PHP', 'count' => 3],
                ['name' => 'JavaScript', 'count' => 2],
                ['name' => 'UI Design', 'count' => 2],
                ['name' => 'Git', 'count' => 2],
            ],
            'messageStatus' => [
                ['label' => '待审核', 'count' => 7, 'class' => 'is-warn'],
                ['label' => '已通过', 'count' => 12, 'class' => 'is-ok'],
                ['label' => '已拒绝', 'count' => 3, 'class' => 'is-muted'],
                ['label' => '垃圾', 'count' => 2, 'class' => 'is-danger'],
            ],
            'projectStatus' => [
                ['label' => '规划中', 'count' => 1],
                ['label' => '进行中', 'count' => 1],
                ['label' => '已完成', 'count' => 1],
                ['label' => '已归档', 'count' => 0],
            ],
            'pendingMessages' => self::pendingMessages(),
            'announcements' => $announcements,
        ]);
    }

    public function members(): void
    {
        render('admin/member_list', [
            'pageTitle' => '成员管理 · 管理后台',
            'adminNav' => 'members',
            'members' => MemberModel::allVisible(),
        ]);
    }

    public function announcements(): void
    {
        render('admin/announcement_list', [
            'pageTitle' => '公告管理 · 管理后台',
            'adminNav' => 'announcements',
            'announcements' => AnnouncementModel::all(),
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '公告已保存（演示）。' : '',
            'toastType' => 'success',
        ]);
    }

    public function team(): void
    {
        $team = TeamModel::profile();

        render('admin/team', [
            'pageTitle' => '团队资料 · 管理后台',
            'adminNav' => 'team',
            'team' => $team,
            'values' => TeamModel::values(),
            'errors' => [],
            'old' => $team,
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '团队资料已保存（演示）。' : '',
            'toastType' => 'success',
        ]);
    }

    public function updateTeam(): void
    {
        $team = TeamModel::profile();
        $input = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'english_name' => trim((string) ($_POST['english_name'] ?? '')),
            'slogan' => trim((string) ($_POST['slogan'] ?? '')),
            'short_intro' => trim((string) ($_POST['short_intro'] ?? '')),
            'story' => trim((string) ($_POST['story'] ?? '')),
            'founded_on' => trim((string) ($_POST['founded_on'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
        ];

        $errors = [];
        if ($input['name'] === '' || mb_strlen($input['name'], 'UTF-8') > 80) {
            $errors['name'] = '团队名称必填，且不超过 80 字。';
        }
        if ($input['email'] !== '' && filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = '邮箱格式不正确。';
        }
        if (mb_strlen($input['story'], 'UTF-8') > 800) {
            $errors['story'] = '团队故事不超过 800 字。';
        }

        if ($errors !== []) {
            http_response_code(422);
            render('admin/team', [
                'pageTitle' => '团队资料 · 管理后台',
                'adminNav' => 'team',
                'team' => $team,
                'values' => TeamModel::values(),
                'errors' => $errors,
                'old' => $input,
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        redirect(url('admin/team', ['saved' => '1']));
    }

    public function resources(): void
    {
        render('admin/resource_list', [
            'pageTitle' => '资源管理 · 管理后台',
            'adminNav' => 'resources',
            'resources' => ResourceModel::all(),
            'categories' => ResourceModel::categories(),
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    public function users(): void
    {
        render('admin/user_list', [
            'pageTitle' => '用户与角色 · 管理后台',
            'adminNav' => 'users',
            'users' => UserModel::all(),
            'roles' => UserModel::roles(),
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    public function settings(): void
    {
        render('admin/setting', [
            'pageTitle' => '站点设置 · 管理后台',
            'adminNav' => 'settings',
            'grouped' => SiteSettingModel::grouped(),
            'groups' => SiteSettingModel::groups(),
            'errors' => [],
            'old' => [],
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '站点设置已保存（演示）。' : '',
            'toastType' => 'success',
        ]);
    }

    public function updateSettings(): void
    {
        $posted = $_POST['settings'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }

        $input = [];
        $errors = [];
        foreach (SiteSettingModel::all() as $setting) {
            $key = $setting['key'];
            if ($setting['type'] === 'bool') {
                $input[$key] = isset($posted[$key]) ? '1' : '0';
                continue;
            }

            $value = trim((string) ($posted[$key] ?? ''));
            $input[$key] = $value;

            if ($setting['type'] === 'json' && $value !== '') {
                json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[$key] = 'JSON 格式不正确。';
                }
            }
        }

        if ($errors !== []) {
            http_response_code(422);
            render('admin/setting', [
                'pageTitle' => '站点设置 · 管理后台',
                'adminNav' => 'settings',
                'grouped' => SiteSettingModel::grouped(),
                'groups' => SiteSettingModel::groups(),
                'errors' => $errors,
                'old' => $input,
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        redirect(url('admin/settings', ['saved' => '1']));
    }

    public function logs(): void
    {
        render('admin/log_list', [
            'pageTitle' => '日志审计 · 管理后台',
            'adminNav' => 'logs',
            'loginLogs' => LogModel::loginLogs(),
            'auditLogs' => LogModel::auditLogs(),
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    public function announcementEdit(): void
    {
        $id = (int) ($_GET['id'] ?? 1);
        $announcement = AnnouncementModel::findById($id);
        if ($announcement === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '公告不存在']);
            return;
        }

        if (!isset($announcement['content'])) {
            $announcement['content'] = strip_tags((string) ($announcement['content_html'] ?? ''));
        }

        render('admin/announcement_edit', [
            'pageTitle' => '编辑公告 · 管理后台',
            'adminNav' => 'announcements',
            'announcement' => $announcement,
            'old' => $announcement,
            'categories' => AnnouncementModel::categories(),
            'errors' => [],
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    public function updateAnnouncement(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $announcement = AnnouncementModel::findById($id);
        if ($announcement === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '公告不存在']);
            return;
        }

        $input = [
            'id' => $id,
            'title' => trim((string) ($_POST['title'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? 'news')),
            'summary' => trim((string) ($_POST['summary'] ?? '')),
            'content' => trim((string) ($_POST['content'] ?? '')),
            'status' => trim((string) ($_POST['status'] ?? 'draft')),
            'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
            'published_at' => trim((string) ($_POST['published_at'] ?? '')),
            'expires_at' => trim((string) ($_POST['expires_at'] ?? '')),
        ];

        $errors = [];
        if ($input['title'] === '' || mb_strlen($input['title'], 'UTF-8') > 150) {
            $errors['title'] = '标题必填，且不超过 150 字。';
        }
        if (preg_match('/^[a-z0-9-]{3,100}$/', $input['slug']) !== 1) {
            $errors['slug'] = 'slug 只允许小写字母、数字和连字符，长度 3—100。';
        }
        if (!array_key_exists($input['category'], AnnouncementModel::categories())) {
            $errors['category'] = '分类不存在。';
        }
        if ($input['content'] === '') {
            $errors['content'] = '正文不能为空。';
        }
        if (!in_array($input['status'], ['draft', 'published', 'offline'], true)) {
            $errors['status'] = '状态不合法。';
        }

        if ($errors !== []) {
            http_response_code(422);
            render('admin/announcement_edit', [
                'pageTitle' => '编辑公告 · 管理后台',
                'adminNav' => 'announcements',
                'announcement' => $announcement,
                'old' => $input,
                'categories' => AnnouncementModel::categories(),
                'errors' => $errors,
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        redirect(url('admin/announcements', ['saved' => '1']));
    }

    public function messages(): void
    {
        render('admin/message_list', [
            'pageTitle' => '留言管理 · 管理后台',
            'adminNav' => 'messages',
            'messages' => self::pendingMessages(),
        ]);
    }

    public function projects(): void
    {
        render('admin/project_list', [
            'pageTitle' => '项目管理 · 管理后台',
            'adminNav' => 'projects',
            'projects' => MemberModel::allProjects(),
        ]);
    }

    /**
     * 留言审核队列演示数据。
     */
    private static function pendingMessages(): array
    {
        return [
            ['author' => '访客A', 'excerpt' => '首页的成员速览可以再加一个技能标签。', 'member' => '林知远', 'created_at' => '2026-11-05 09:12', 'status' => '待审核'],
            ['author' => '访客B', 'excerpt' => '请问团队文档什么时候可以下载？', 'member' => '周予安', 'created_at' => '2026-11-05 08:40', 'status' => '待审核'],
            ['author' => '访客C', 'excerpt' => '成员主页的项目卡片排版很清晰。', 'member' => '苏晚晴', 'created_at' => '2026-11-04 21:05', 'status' => '待审核'],
        ];
    }
}
