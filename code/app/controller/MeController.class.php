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

final class MeController
{
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
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '资料已保存（演示）。' : '',
            'toastType' => 'success',
        ]);
    }

    public function updateProfile(): void
    {
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
        } else {
            foreach (MemberModel::allVisible() as $other) {
                if ($other['id'] !== $member['id'] && $other['slug'] === $input['slug']) {
                    $errors['slug'] = '该 slug 已被其他成员使用。';
                    break;
                }
            }
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
        }
        if (($input['slug'] ?? '') !== '' && isset($errors['slug']) === false
            && (string) ($_POST['updated_at'] ?? '') !== (string) ($member['updated_at'] ?? '')) {
            http_response_code(409);
            render('me/profile', [
                'pageTitle' => '个人资料 · 成员中心',
                'metaDescription' => '编辑个人资料、头像与封面、技能标签。',
                'meNav' => 'profile',
                'member' => $member,
                'skillCatalog' => MemberModel::skillCatalog(),
                'selectedSkills' => $selectedSkills,
                'errors' => [],
                'old' => $input,
                'conflict' => true,
                'completeness' => $this->completeness($member),
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        if ($errors !== []) {
            http_response_code(422);
            render('me/profile', [
                'pageTitle' => '个人资料 · 成员中心',
                'metaDescription' => '编辑个人资料、头像与封面、技能标签。',
                'meNav' => 'profile',
                'member' => $member,
                'skillCatalog' => MemberModel::skillCatalog(),
                'selectedSkills' => $selectedSkills,
                'errors' => $errors,
                'old' => $input,
                'conflict' => false,
                'completeness' => $this->completeness($member),
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        // 演示：不落库；后端接入后在事务内更新 members / member_skills 并写审计。
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
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
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
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '密码已修改（演示）。' : '',
            'toastType' => 'success',
        ]);
    }

    public function updatePassword(): void
    {
        $member = $this->currentMember();
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $errors = [];
        if ($current === '') {
            $errors['current_password'] = '请输入当前密码。';
        }
        if (mb_strlen($new, 'UTF-8') < 10) {
            $errors['new_password'] = '新密码至少 10 位。';
        } elseif ($this->passwordClassCount($new) < 3) {
            $errors['new_password'] = '新密码需包含大写、小写、数字、符号中的至少三类。';
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
                'old' => ['current_password' => $current],
                'loginLogs' => $this->loginLogs(),
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        // 演示：不校验当前密码、不落库；后端接入后调用 AuthService 修改密码并使旧会话失效。
        redirect(url('me/security', ['saved' => '1']));
    }

    private function currentMember(): array
    {
        $member = MemberModel::findBySlug('lin-zhiyuan');
        if ($member === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '成员不存在']);
            exit;
        }

        return $member;
    }

    private function completeness(array $member): int
    {
        $fields = ['name', 'role_title', 'tagline', 'bio', 'email', 'github', 'website', 'location', 'avatar'];
        $filled = 0;
        foreach ($fields as $field) {
            if ((string) ($member[$field] ?? '') !== '') {
                $filled++;
            }
        }

        $skillRatio = min(1.0, count($member['skills'] ?? []) / 5);
        return (int) round(($filled / count($fields)) * 70 + $skillRatio * 30);
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

    private function passwordClassCount(string $password): int
    {
        $count = 0;
        $count += preg_match('/[A-Z]/', $password) === 1 ? 1 : 0;
        $count += preg_match('/[a-z]/', $password) === 1 ? 1 : 0;
        $count += preg_match('/\d/', $password) === 1 ? 1 : 0;
        $count += preg_match('/[^A-Za-z0-9]/', $password) === 1 ? 1 : 0;

        return $count;
    }

    private function loginLogs(): array
    {
        return [
            ['time' => '2026-11-05 09:12', 'ip_hash' => 'a1b2c3d4', 'ua' => 'Edge / Windows 11', 'success' => true],
            ['time' => '2026-11-04 21:40', 'ip_hash' => 'a1b2c3d4', 'ua' => 'Edge / Windows 11', 'success' => true],
            ['time' => '2026-11-03 18:05', 'ip_hash' => '9f8e7d6c', 'ua' => 'Firefox / Windows 11', 'success' => true],
            ['time' => '2026-11-02 10:22', 'ip_hash' => '9f8e7d6c', 'ua' => 'Firefox / Windows 11', 'success' => false],
            ['time' => '2026-11-01 15:48', 'ip_hash' => 'a1b2c3d4', 'ua' => 'Edge / Windows 11', 'success' => true],
        ];
    }
}
