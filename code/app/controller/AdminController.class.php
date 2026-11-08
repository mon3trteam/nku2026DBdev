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
require_once APP_ROOT . '/app/model/MessageModel.class.php';
require_once APP_ROOT . '/app/model/TeamModel.class.php';
require_once APP_ROOT . '/app/model/ResourceModel.class.php';
require_once APP_ROOT . '/app/model/UserModel.class.php';
require_once APP_ROOT . '/app/model/SiteSettingModel.class.php';
require_once APP_ROOT . '/app/model/LogModel.class.php';

final class AdminController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    public function dashboard(): void
    {
        Auth::requirePermission('dashboard.view');
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
        Auth::requirePermission('member.manage');
        render('admin/member_list', [
            'pageTitle' => '成员管理 · 管理后台',
            'adminNav' => 'members',
            'members' => MemberModel::allForAdmin(),
        ]);
    }

    public function announcements(): void
    {
        Auth::requirePermission('content.manage');
        render('admin/announcement_list', [
            'pageTitle' => '公告管理 · 管理后台',
            'adminNav' => 'announcements',
            'announcements' => AnnouncementModel::allForAdmin(),
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '公告已保存（演示）。' : '',
            'toastType' => 'success',
        ]);
    }

    public function team(): void
    {
        Auth::requirePermission('team.edit');
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
        Auth::requirePermission('team.edit');
        $this->requirePost();

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
        Auth::requirePermission('resource.manage');
        render('admin/resource_list', [
            'pageTitle' => '资源管理 · 管理后台',
            'adminNav' => 'resources',
            'resources' => ResourceModel::allForAdmin(),
            'categories' => ResourceModel::categories(),
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    public function users(): void
    {
        Auth::requirePermission('user.manage');
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
        Auth::requirePermission('setting.manage');
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
        Auth::requirePermission('setting.manage');
        $this->requirePost();

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
        Auth::requirePermission('log.view');
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
        Auth::requirePermission('content.manage');
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
        Auth::requirePermission('content.manage');
        $this->requirePost();

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
        Auth::requirePermission('message.moderate');
        render('admin/message_list', [
            'pageTitle' => '留言管理 · 管理后台',
            'adminNav' => 'messages',
            'messages' => MessageModel::forAdmin(),
        ]);
    }

    public function projects(): void
    {
        Auth::requirePermission('content.manage');
        render('admin/project_list', [
            'pageTitle' => '项目管理 · 管理后台',
            'adminNav' => 'projects',
            'projects' => MemberModel::allProjectsForAdmin(),
        ]);
    }

    /**
     * 看板与留言管理共用的审核队列。
     */
    private static function pendingMessages(): array
    {
        return MessageModel::forAdmin();
    }

    /**
     * 写操作统一入口检查：只接受 POST，且必须携带有效 CSRF token。
     */
    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            $this->abort(405, '请求方式不正确', '该地址只接受表单 POST 提交，请从后台页面操作。');
        }

        if (!Auth::verifyCsrf($_POST['_token'] ?? null)) {
            // 419 不是 Apache 认识的标准状态码，会被改写成 500，这里使用 400
            $this->abort(400, '页面已过期', '安全令牌已失效，请返回上一页刷新后重新提交。');
        }
    }

    private function abort(int $code, string $title, string $message): never
    {
        http_response_code($code);
        render('error_request', [
            'pageTitle' => $title . ' · 数聚 TeamHub',
            'metaDescription' => $message,
            'activeNav' => '',
            'errorCode' => $code,
            'errorTitle' => $title,
            'errorMessage' => $message,
        ]);
        exit;
    }
}
