<?php
/**
 * 文件名：MeController.class.php
 * 功能：成员中心控制器（概览、个人资料、我的项目、账号安全）
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：成员中心
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MemberModel.class.php';
require_once APP_ROOT . '/app/model/LogModel.class.php';
require_once APP_ROOT . '/app/model/AuthModel.class.php';

final class MeController
{
    public function __construct()
    {
        Auth::requireLogin();
        Auth::requirePermission('member.edit');
    }

    public function index(): void
    {
        $member = $this->currentMember();
        $projects = MemberModel::projectsOf($member['slug']);
        $messages = MemberModel::messagesOf($member['slug']);

        render('me/index', [
            'pageTitle' => '成员概览 · 成员中心',
            'metaDescription' => '查看个人资料完整度、最近项目与快捷入口。',
            'meNav' => 'overview',
            'member' => $member,
            'projects' => $projects,
            'messages' => $messages,
            'completeness' => $this->completeness($member),
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    public function profile(): void
    {
        $member = $this->currentMember();
        $conflict = ($_GET['state'] ?? '') === 'conflict';

        render('me/profile', [
            'pageTitle' => '个人资料 · 成员中心',
            'metaDescription' => '编辑个人资料、头像与封面、技能标签。',
            'meNav' => 'profile',
            'member' => $member,
            'skillCatalog' => MemberModel::skillCatalog(),
            'selectedSkills' => $this->skillMap($member),
            'errors' => [],
            'old' => $this->memberToOld($member),
            'conflict' => $conflict,
            'completeness' => $this->completeness($member),
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '资料已保存。' : '',
            'toastType' => 'success',
        ]);
    }

    public function updateProfile(): void
    {
        $this->requirePost();
        $member = $this->currentMember();

        $input = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'english_name' => trim((string) ($_POST['english_name'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'role_title' => trim((string) ($_POST['role_title'] ?? '')),
            'tagline' => trim((string) ($_POST['tagline'] ?? '')),
            'bio' => trim((string) ($_POST['bio'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'github' => trim((string) ($_POST['github'] ?? '')),
            'website' => trim((string) ($_POST['website'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
        ];

        $rawSkills = $_POST['skills'] ?? [];
        $rawProficiencies = $_POST['proficiency'] ?? [];
        $postedSkills = is_array($rawSkills) ? array_values(array_filter($rawSkills, 'is_string')) : [];
        $postedProfs = is_array($rawProficiencies) ? $rawProficiencies : [];
        $selectedSkills = [];
        foreach ($postedSkills as $name) {
            $value = (int) ($postedProfs[$name] ?? 3);
            $selectedSkills[$name] = max(1, min(5, $value));
        }

        $errors = [];
        $nameLength = mb_strlen($input['name'], 'UTF-8');
        if ($nameLength < 2 || $nameLength > 30) {
            $errors['name'] = '姓名需要 2—30 个字符。';
        }
        if (preg_match('/^[a-z0-9-]{3,60}$/', $input['slug']) !== 1) {
            $errors['slug'] = 'slug 只允许小写字母、数字和连字符，长度 3—60。';
        } elseif (MemberModel::slugExists($input['slug'], (int) $member['id'])) {
            $errors['slug'] = '该 slug 已被其他成员使用。';
        }
        if ($input['role_title'] === '' || mb_strlen($input['role_title'], 'UTF-8') > 60) {
            $errors['role_title'] = '团队角色必填，且不超过 60 字。';
        }
        if (mb_strlen($input['tagline'], 'UTF-8') > 60) {
            $errors['tagline'] = '一句话介绍不超过 60 字。';
        }
        if (mb_strlen($input['bio'], 'UTF-8') > 500) {
            $errors['bio'] = '个人简介不超过 500 字。';
        }
        if ($input['email'] !== '' && filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = '邮箱格式不正确。';
        }
        foreach (['github', 'website'] as $urlField) {
            if ($input[$urlField] !== '' && preg_match('#^https?://#i', $input[$urlField]) !== 1) {
                $errors[$urlField] = '网址只允许 http/https。';
            }
        }
        if (count($selectedSkills) < 3 || count($selectedSkills) > 12) {
            $errors['skills'] = '技能需要 3—12 项。';
        } else {
            $catalogNames = array_column(MemberModel::skillCatalog(), 'name');
            if (array_diff(array_keys($selectedSkills), $catalogNames) !== []) {
                $errors['skills'] = '技能中包含不在技能库中的项，请刷新后重选。';
            }
        }

        if ($errors !== []) {
            $this->renderProfile($member, $selectedSkills, $errors, $input, false, 422);
            return;
        }

        $avatarMediaId = (int) ($member['avatar_media_id'] ?? 0);
        $coverMediaId = (int) ($member['cover_media_id'] ?? 0);
        $oldAvatarId = $avatarMediaId;
        $oldCoverId = $coverMediaId;
        $uploadedIds = [];

        foreach ([['avatar', 2, '头像'], ['cover', 5, '封面']] as [$field, $maxMb, $label]) {
            $file = $_FILES[$field] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $media = MediaModel::storeImage($file, Auth::id(), $maxMb * 1024 * 1024);
                $uploadedIds[] = $media['id'];
                if ($field === 'avatar') {
                    $avatarMediaId = $media['id'];
                } else {
                    $coverMediaId = $media['id'];
                }
            } catch (RuntimeException $e) {
                $errors[$field] = $label . '上传失败：' . $e->getMessage();
            }
        }

        if ($errors !== []) {
            foreach ($uploadedIds as $mediaId) {
                MediaModel::deleteIfUnused($mediaId);
            }
            $this->renderProfile($member, $selectedSkills, $errors, $input, false, 422);
            return;
        }

        $input['avatar_media_id'] = $avatarMediaId;
        $input['cover_media_id'] = $coverMediaId;

        // 完整度与详情页展示口径一致：9 个字段 + 技能项
        $filled = 0;
        foreach (['name', 'role_title', 'tagline', 'bio', 'email', 'github', 'website', 'location'] as $field) {
            $filled += $input[$field] !== '' ? 1 : 0;
        }
        if ($avatarMediaId > 0) {
            $filled++;
        }
        $input['profile_completeness'] = (int) round(
            ($filled / 9) * 70 + min(1.0, count($selectedSkills) / 5) * 30
        );

        $result = MemberModel::updateProfile(
            (int) $member['id'],
            $input,
            $selectedSkills,
            (string) ($_POST['updated_at'] ?? '')
        );

        if ($result === 'conflict' || $result === 'missing') {
            foreach ($uploadedIds as $mediaId) {
                MediaModel::deleteIfUnused($mediaId);
            }
        }

        if ($result === 'conflict') {
            $this->renderProfile($member, $selectedSkills, [], $input, true, 409);
            return;
        }

        if ($result === 'missing') {
            http_response_code(404);
            render('error_404', ['pageTitle' => '成员档案不存在']);
            return;
        }

        if ($oldAvatarId > 0 && $oldAvatarId !== $avatarMediaId) {
            MediaModel::deleteIfUnused($oldAvatarId);
        }
        if ($oldCoverId > 0 && $oldCoverId !== $coverMediaId) {
            MediaModel::deleteIfUnused($oldCoverId);
        }

        AuthModel::audit('update', 'member', (int) $member['id'], '成员更新个人资料：' . $input['name']);
        redirect(url('me/profile', ['saved' => '1']));
    }

    public function projects(): void
    {
        $member = $this->currentMember();

        render('me/projects', [
            'pageTitle' => '我的项目 · 成员中心',
            'metaDescription' => '查看本人参与的项目、项目角色与贡献说明。',
            'meNav' => 'projects',
            'member' => $member,
            'projects' => MemberModel::projectsOf($member['slug']),
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '我的分工已保存。' : '',
            'toastType' => 'success',
        ]);
    }

    /**
     * 成员维护自己在项目中的角色与贡献说明。
     */
    public function updateContribution(): void
    {
        $this->requirePost();
        $member = $this->currentMember();

        $projectId = (int) ($_POST['project_id'] ?? 0);
        $roleName = trim((string) ($_POST['role_name'] ?? ''));
        $contribution = trim((string) ($_POST['contribution'] ?? ''));

        $errors = [];
        if ($roleName === '' || mb_strlen($roleName, 'UTF-8') > 50) {
            $errors[] = '项目角色需要 1—50 字。';
        }
        if (mb_strlen($contribution, 'UTF-8') > 255) {
            $errors[] = '贡献说明不超过 255 字。';
        }

        if ($errors !== []) {
            redirect(url('me/projects', ['state' => 'invalid']));
        }

        $updated = MemberModel::updateMyProjectAssignment(
            $projectId,
            (int) $member['id'],
            $roleName,
            $contribution
        );
        if (!$updated) {
            http_response_code(404);
            render('error_request', [
                'pageTitle' => '项目不存在 · ' . site_name(),
                'metaDescription' => '你不在该项目的成员列表中。',
                'activeNav' => '',
                'errorCode' => 404,
                'errorTitle' => '项目不存在',
                'errorMessage' => '该项目不存在，或你不在该项目的成员列表中。',
            ]);
            return;
        }

        AuthModel::audit('update', 'project', $projectId, '成员更新项目分工：' . $member['name']);
        redirect(url('me/projects', ['saved' => '1']));
    }

    public function security(): void
    {
        $member = $this->currentMember();

        render('me/security', [
            'pageTitle' => '账号安全 · 成员中心',
            'metaDescription' => '修改密码、查看最近登录记录。',
            'meNav' => 'security',
            'member' => $member,
            'errors' => [],
            'old' => ['current_password' => ''],
            'loginLogs' => $this->loginLogs(),
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '密码已修改，其他设备上的旧会话已失效。' : '',
            'toastType' => 'success',
        ]);
    }

    public function updatePassword(): void
    {
        $this->requirePost();
        $member = $this->currentMember();
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];
        if ($current === '') {
            $errors['current_password'] = '请输入当前密码。';
        } else {
            $storedHash = AuthModel::passwordHash(Auth::id());
            if ($storedHash === null || !password_verify($current, $storedHash)) {
                $errors['current_password'] = '当前密码不正确。';
            }
        }

        if (mb_strlen($new, 'UTF-8') < 10) {
            $errors['new_password'] = '新密码至少 10 位。';
        } elseif (AuthModel::passwordClassCount($new) < 3) {
            $errors['new_password'] = '新密码需包含大写、小写、数字、符号中的至少三类。';
        } elseif ($new === $current) {
            $errors['new_password'] = '新密码不能与当前密码相同。';
        } elseif (strcasecmp($new, (string) (Auth::user()['username'] ?? '')) === 0) {
            $errors['new_password'] = '新密码不能与用户名相同。';
        }

        if ($new !== $confirm) {
            $errors['confirm_password'] = '两次输入的新密码不一致。';
        }

        if ($errors !== []) {
            http_response_code(422);
            render('me/security', [
                'pageTitle' => '账号安全 · 成员中心',
                'metaDescription' => '修改密码、查看最近登录记录。',
                'meNav' => 'security',
                'member' => $member,
                'errors' => $errors,
                'old' => ['current_password' => ''],
                'loginLogs' => $this->loginLogs(),
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 10]);
        if ($newHash === false) {
            throw new RuntimeException('Unable to hash password.');
        }

        $changedAt = AuthModel::updatePassword(Auth::id(), $newHash);
        Auth::refreshPasswordStamp($changedAt);
        AuthModel::audit('update', 'user', Auth::id(), '成员修改登录密码');

        redirect(url('me/security', ['saved' => '1']));
    }

    /**
     * 个人资料表单的统一渲染入口（422 校验失败 / 409 乐观锁冲突）。
     */
    private function renderProfile(
        array $member,
        array $selectedSkills,
        array $errors,
        array $old,
        bool $conflict,
        int $status = 200
    ): void {
        http_response_code($status);
        render('me/profile', [
            'pageTitle' => '个人资料 · 成员中心',
            'metaDescription' => '编辑个人资料、头像与封面、技能标签。',
            'meNav' => 'profile',
            'member' => $member,
            'skillCatalog' => MemberModel::skillCatalog(),
            'selectedSkills' => $selectedSkills,
            'errors' => $errors,
            'old' => $old,
            'conflict' => $conflict,
            'completeness' => $this->completeness($member),
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    /**
     * 写操作统一检查：只接受 POST，且必须携带有效 CSRF token。
     */
    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            $this->abort(405, '请求方式不正确', '该地址只接受表单 POST 提交，请从成员中心页面操作。');
        }

        if (!Auth::verifyCsrf($_POST['_token'] ?? null)) {
            $this->abort(400, '页面已过期', '安全令牌已失效，请返回上一页刷新后重新提交。');
        }
    }

    private function abort(int $code, string $title, string $message): never
    {
        http_response_code($code);
        render('error_request', [
            'pageTitle' => $title . ' · ' . site_name(),
            'metaDescription' => $message,
            'activeNav' => '',
            'errorCode' => $code,
            'errorTitle' => $title,
            'errorMessage' => $message,
        ]);
        exit;
    }

    /**
     * 当前登录账号绑定的成员档案；管理员/编辑账号没有成员档案时给出提示。
     */
    private function currentMember(): array
    {
        $member = MemberModel::findByUserId(Auth::id());
        if ($member === null) {
            http_response_code(403);
            render('error_request', [
                'pageTitle' => '未绑定成员档案 · ' . site_name(),
                'metaDescription' => '当前账号没有绑定成员档案。',
                'activeNav' => '',
                'errorCode' => 403,
                'errorTitle' => '未绑定成员档案',
                'errorMessage' => '当前账号是管理员或内容编辑，没有对应的成员公开主页。请使用成员账号登录，或到后台管理相关数据。',
            ]);
            exit;
        }

        return $member;
    }

    private function completeness(array $member): int
    {
        $fields = ['name', 'role_title', 'tagline', 'bio', 'email', 'github', 'website', 'location'];
        $filled = 0;
        foreach ($fields as $field) {
            if ((string) ($member[$field] ?? '') !== '') {
                $filled++;
            }
        }
        if ((int) ($member['avatar_media_id'] ?? 0) > 0) {
            $filled++;
        }

        $skillRatio = min(1.0, count($member['skills'] ?? []) / 5);
        return (int) round(($filled / 9) * 70 + $skillRatio * 30);
    }

    /**
     * @return array<string, int>
     */
    private function skillMap(array $member): array
    {
        $map = [];
        foreach ($member['skills'] as $skill) {
            $map[$skill['name']] = (int) $skill['proficiency'];
        }

        return $map;
    }

    private function memberToOld(array $member): array
    {
        return [
            'name' => $member['name'],
            'english_name' => $member['english_name'],
            'slug' => $member['slug'],
            'role_title' => $member['role_title'],
            'tagline' => $member['tagline'],
            'bio' => $member['bio'],
            'email' => $member['email'],
            'github' => $member['github'],
            'website' => $member['website'],
            'location' => $member['location'],
        ];
    }

    private function loginLogs(): array
    {
        return LogModel::loginLogsForUser(Auth::id());
    }
}
