<?php
/**
 * 文件名：AdminController.class.php
 * 功能：管理后台控制器：页面渲染、表单校验与数据库写入
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 修改：金暄琛（2411672）看板接入数据库统计（2026-11-08）
 * 所属模块：管理后台
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MemberModel.class.php';
require_once APP_ROOT . '/app/model/ProjectModel.class.php';
require_once APP_ROOT . '/app/model/AnnouncementModel.class.php';
require_once APP_ROOT . '/app/model/DashboardModel.class.php';
require_once APP_ROOT . '/app/model/MessageModel.class.php';
require_once APP_ROOT . '/app/model/TeamModel.class.php';
require_once APP_ROOT . '/app/model/MediaModel.class.php';
require_once APP_ROOT . '/app/model/ResourceModel.class.php';
require_once APP_ROOT . '/app/model/UserModel.class.php';
require_once APP_ROOT . '/app/model/SiteSettingModel.class.php';
require_once APP_ROOT . '/app/model/LogModel.class.php';
require_once APP_ROOT . '/app/model/AuthModel.class.php';

final class AdminController
{
    public function __construct()
    {
        Auth::requireLogin();
    }

    public function dashboard(): void
    {
        Auth::requirePermission('dashboard.view');

        render('admin/dashboard', [
            'pageTitle' => '数据看板 · 管理后台',
            'adminNav' => 'dashboard',
            'kpi' => DashboardModel::kpi(),
            'trend' => DashboardModel::dailyViews(14),
            'skills' => DashboardModel::skillDistribution(6),
            'messageStatus' => DashboardModel::messageStatus(),
            'projectStatus' => DashboardModel::projectStatus(),
            'pendingMessages' => MessageModel::pending(5),
            'announcements' => AnnouncementModel::latest(5),
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '留言状态已更新。' : '',
            'toastType' => 'success',
        ]);
    }

    public function members(): void
    {
        Auth::requirePermission('member.manage');
        render('admin/member_list', [
            'pageTitle' => '成员管理 · 管理后台',
            'adminNav' => 'members',
            'members' => MemberModel::allForAdmin(),
            'toastMessage' => match ($_GET['saved'] ?? '') {
                '1' => '成员资料已保存。',
                'visible' => '成员已恢复公开。',
                'hidden' => '成员已隐藏。',
                'featured' => '已加入首页精选。',
                'unfeatured' => '已取消首页精选。',
                default => '',
            },
            'toastType' => 'success',
        ]);
    }

    public function memberEdit(): void
    {
        Auth::requirePermission('member.manage');

        $id = (int) ($_GET['id'] ?? 0);
        $member = MemberModel::findForAdmin($id);
        if ($member === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '成员不存在']);
            return;
        }

        $this->renderMemberEdit(
            $member,
            [],
            200,
            ($_GET['saved'] ?? '') === '1' ? '成员资料已保存。' : '',
            MemberModel::skillMapForMember($id)
        );
    }

    public function updateMember(): void
    {
        Auth::requirePermission('member.manage');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $member = MemberModel::findForAdmin($id);
        if ($member === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '成员不存在']);
            return;
        }

        $input = $this->memberInput();
        $selectedSkills = $this->skillInput();
        [$errors, $data] = $this->validateMember($input, $id);

        if (count($selectedSkills) < 3 || count($selectedSkills) > 12) {
            $errors['skills'] = '技能需要 3—12 项。';
        } else {
            $catalogNames = array_column(MemberModel::skillCatalog(), 'name');
            if (array_diff(array_keys($selectedSkills), $catalogNames) !== []) {
                $errors['skills'] = '技能中包含不在技能库中的项，请刷新后重选。';
            }
        }

        if ($errors !== []) {
            $this->renderMemberEdit([...$member, ...$input], $errors, 422, '', $selectedSkills);
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
            $this->renderMemberEdit([...$member, ...$input], $errors, 422, '', $selectedSkills);
            return;
        }

        $data['avatar_media_id'] = $avatarMediaId;
        $data['cover_media_id'] = $coverMediaId;
        $data['profile_completeness'] = $this->memberCompleteness(
            $data,
            count($selectedSkills),
            $avatarMediaId > 0
        );

        MemberModel::updateForAdmin($id, $data, $selectedSkills);

        if ($oldAvatarId > 0 && $oldAvatarId !== $avatarMediaId) {
            MediaModel::deleteIfUnused($oldAvatarId);
        }
        if ($oldCoverId > 0 && $oldCoverId !== $coverMediaId) {
            MediaModel::deleteIfUnused($oldCoverId);
        }

        AuthModel::audit('update', 'member', $id, '后台更新成员资料：' . $data['name']);
        redirect(url('admin/memberEdit', ['id' => $id, 'saved' => '1']));
    }

    public function setMemberVisibility(): void
    {
        Auth::requirePermission('member.manage');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $visible = (string) ($_POST['visible'] ?? '') === '1';
        $member = MemberModel::findForAdmin($id);
        if ($member === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '成员不存在']);
            return;
        }

        MemberModel::setVisibility($id, $visible);
        AuthModel::audit(
            $visible ? 'publish' : 'archive',
            'member',
            $id,
            ($visible ? '恢复公开成员：' : '隐藏成员：') . $member['name']
        );
        redirect(url('admin/members', ['saved' => $visible ? 'visible' : 'hidden']));
    }

    public function setMemberFeatured(): void
    {
        Auth::requirePermission('member.manage');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $featured = (string) ($_POST['featured'] ?? '') === '1';
        $member = MemberModel::findForAdmin($id);
        if ($member === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '成员不存在']);
            return;
        }

        MemberModel::setFeatured($id, $featured);
        AuthModel::audit(
            'update',
            'member',
            $id,
            ($featured ? '设为首页精选成员：' : '取消首页精选：') . $member['name']
        );
        redirect(url('admin/members', ['saved' => $featured ? 'featured' : 'unfeatured']));
    }

    private function renderMemberEdit(
        array $old,
        array $errors,
        int $status,
        string $toastMessage = '',
        ?array $selectedSkills = null
    ): void {
        http_response_code($status);
        render('admin/member_edit', [
            'pageTitle' => ((int) ($old['id'] ?? 0) > 0 ? '编辑成员' : '成员资料') . ' · 管理后台',
            'adminNav' => 'members',
            'member' => $old,
            'old' => $old,
            'errors' => $errors,
            'skillCatalog' => MemberModel::skillCatalog(),
            'selectedSkills' => $selectedSkills ?? [],
            'toastMessage' => $toastMessage,
            'toastType' => 'success',
        ]);
    }

    /**
     * 解析后台成员编辑中的技能勾选与熟练度。
     *
     * @return array<string, int> 技能名 => 熟练度
     */
    private function skillInput(): array
    {
        $rawSkills = $_POST['skills'] ?? [];
        $rawProficiencies = $_POST['proficiency'] ?? [];

        if (!is_array($rawSkills)) {
            return [];
        }

        $skills = [];
        foreach ($rawSkills as $name) {
            if (!is_string($name) || $name === '') {
                continue;
            }

            $proficiency = is_array($rawProficiencies) ? (int) ($rawProficiencies[$name] ?? 3) : 3;
            $skills[$name] = max(1, min(5, $proficiency));
        }

        return $skills;
    }

    /**
     * 解析团队价值观表单（平行数组），整行留空视为未填写。
     *
     * @return array<int, array{title: string, description: string}>
     */
    private function valueInput(): array
    {
        $titles = $_POST['value_title'] ?? [];
        $descriptions = $_POST['value_description'] ?? [];

        if (!is_array($titles)) {
            return [];
        }

        $rows = [];
        foreach (array_values($titles) as $index => $title) {
            $title = is_string($title) ? trim($title) : '';
            $description = is_array($descriptions) && is_string($descriptions[$index] ?? null)
                ? trim((string) $descriptions[$index])
                : '';

            if ($title === '' && $description === '') {
                continue;
            }

            $rows[] = ['title' => $title, 'description' => $description];
        }

        return $rows;
    }

    private function memberInput(): array
    {
        return [
            'id' => (int) ($_POST['id'] ?? 0),
            'name' => trim((string) ($_POST['name'] ?? '')),
            'english_name' => trim((string) ($_POST['english_name'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'role_title' => trim((string) ($_POST['role_title'] ?? '')),
            'tagline' => trim((string) ($_POST['tagline'] ?? '')),
            'bio' => trim((string) ($_POST['bio'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'github_url' => trim((string) ($_POST['github_url'] ?? '')),
            'website_url' => trim((string) ($_POST['website_url'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'join_date' => trim((string) ($_POST['join_date'] ?? '')),
            'sort_order' => trim((string) ($_POST['sort_order'] ?? '0')),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
        ];
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, mixed>}
     */
    private function validateMember(array $input, int $id): array
    {
        $errors = [];

        $nameLength = mb_strlen($input['name'], 'UTF-8');
        if ($nameLength < 2 || $nameLength > 50) {
            $errors['name'] = '姓名需要 2—50 个字符。';
        }
        if (mb_strlen($input['english_name'], 'UTF-8') > 60) {
            $errors['english_name'] = '英文名不超过 60 字。';
        }
        if (preg_match('/^[a-z0-9-]{3,60}$/', $input['slug']) !== 1) {
            $errors['slug'] = 'slug 只允许小写字母、数字和连字符，长度 3—60。';
        } elseif (MemberModel::slugExists($input['slug'], $id)) {
            $errors['slug'] = '该 slug 已被其他成员使用。';
        }
        if ($input['role_title'] === '' || mb_strlen($input['role_title'], 'UTF-8') > 60) {
            $errors['role_title'] = '团队角色必填，且不超过 60 字。';
        }
        if (mb_strlen($input['tagline'], 'UTF-8') > 150) {
            $errors['tagline'] = '一句话介绍不超过 150 字。';
        }
        if (mb_strlen($input['bio'], 'UTF-8') > 500) {
            $errors['bio'] = '个人简介不超过 500 字。';
        }
        if ($input['email'] !== '' && (mb_strlen($input['email'], 'UTF-8') > 120 || filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false)) {
            $errors['email'] = '邮箱格式不正确或超过 120 字。';
        }
        foreach (['github_url' => 'GitHub 链接', 'website_url' => '个人网站'] as $field => $label) {
            if ($input[$field] !== '' && preg_match('#^https?://#i', $input[$field]) !== 1) {
                $errors[$field] = $label . '只允许 http/https。';
            } elseif (mb_strlen($input[$field], 'UTF-8') > 255) {
                $errors[$field] = $label . '不超过 255 字。';
            }
        }
        if (mb_strlen($input['location'], 'UTF-8') > 100) {
            $errors['location'] = '所在地不超过 100 字。';
        }

        $joinDate = null;
        if ($input['join_date'] !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $input['join_date']);
            if ($date !== false && $date->format('Y-m-d') === $input['join_date']) {
                $joinDate = $input['join_date'];
            } else {
                $errors['join_date'] = '加入日期格式不正确。';
            }
        }

        if (preg_match('/^-?\d+$/', (string) $input['sort_order']) !== 1) {
            $errors['sort_order'] = '排序需要是整数。';
        } else {
            $sortOrder = (int) $input['sort_order'];
            if ($sortOrder < -9999 || $sortOrder > 9999) {
                $errors['sort_order'] = '排序范围为 -9999—9999。';
            }
        }

        $data = [
            'name' => $input['name'],
            'english_name' => $input['english_name'],
            'slug' => $input['slug'],
            'role_title' => $input['role_title'],
            'tagline' => $input['tagline'],
            'bio' => $input['bio'],
            'email' => $input['email'],
            'github_url' => $input['github_url'],
            'website_url' => $input['website_url'],
            'location' => $input['location'],
            'join_date' => $joinDate,
            'sort_order' => (int) $input['sort_order'],
            'is_featured' => (int) $input['is_featured'],
            'is_visible' => (int) $input['is_visible'],
        ];

        return [$errors, $data];
    }

    /**
     * 成员资料完整度：9 个字段（含头像）+ 技能项，与成员中心口径一致。
     */
    private function memberCompleteness(array $data, int $skillCount, bool $hasAvatar): int
    {
        $filled = 0;
        foreach (['name', 'role_title', 'tagline', 'bio', 'email', 'github_url', 'website_url', 'location'] as $field) {
            $filled += (string) ($data[$field] ?? '') !== '' ? 1 : 0;
        }
        if ($hasAvatar) {
            $filled++;
        }

        return (int) round(($filled / 9) * 70 + min(1.0, $skillCount / 5) * 30);
    }

    public function announcements(): void
    {
        Auth::requirePermission('content.manage');
        render('admin/announcement_list', [
            'pageTitle' => '公告管理 · 管理后台',
            'adminNav' => 'announcements',
            'announcements' => AnnouncementModel::allForAdmin(),
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '公告已保存。' : '',
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
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '团队资料已保存。' : '',
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

        $values = $this->valueInput();

        $errors = [];
        $lengthLimits = [
            'name' => [1, 80, '团队名称必填，且不超过 80 字。'],
            'english_name' => [0, 80, '英文名不超过 80 字。'],
            'slogan' => [0, 120, '口号不超过 120 字。'],
            'short_intro' => [0, 500, '一句话介绍不超过 500 字。'],
            'story' => [0, 800, '团队故事不超过 800 字。'],
            'location' => [0, 100, '所在地不超过 100 字。'],
            'email' => [0, 120, '邮箱不超过 120 字。'],
            'phone' => [0, 30, '联系电话不超过 30 字。'],
        ];

        foreach ($lengthLimits as $field => [$min, $max, $message]) {
            $length = mb_strlen($input[$field], 'UTF-8');
            if ($length < $min || $length > $max) {
                $errors[$field] = $message;
            }
        }

        if ($input['email'] !== '' && filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = '邮箱格式不正确。';
        }

        $foundedOn = null;
        if ($input['founded_on'] !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $input['founded_on']);
            if ($date === false || $date->format('Y-m-d') !== $input['founded_on']) {
                $errors['founded_on'] = '成立日期格式不正确。';
            } else {
                $foundedOn = $date->format('Y-m-d');
            }
        }

        if (count($values) > 6) {
            $errors['values'] = '团队价值观最多 6 条。';
        } elseif ($values === []) {
            $errors['values'] = '至少保留一条团队价值观。';
        } else {
            foreach ($values as $value) {
                if ($value['title'] === '') {
                    $errors['values'] = '每条价值观都需要填写标题。';
                    break;
                }
                if (mb_strlen($value['title'], 'UTF-8') > 40) {
                    $errors['values'] = '价值观标题不超过 40 字。';
                    break;
                }
                if (mb_strlen($value['description'], 'UTF-8') > 160) {
                    $errors['values'] = '价值观说明不超过 160 字。';
                    break;
                }
            }
        }

        if ($errors !== []) {
            http_response_code(422);
            render('admin/team', [
                'pageTitle' => '团队资料 · 管理后台',
                'adminNav' => 'team',
                'team' => $team,
                'values' => $values !== [] ? $values : TeamModel::values(),
                'errors' => $errors,
                'old' => [...$input, 'logo' => $team['logo'], 'cover' => $team['cover']],
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        $logoMediaId = (int) ($team['logo_media_id'] ?? 0);
        $coverMediaId = (int) ($team['cover_media_id'] ?? 0);
        $oldLogoId = $logoMediaId;
        $oldCoverId = $coverMediaId;
        $uploadedIds = [];

        foreach ([['logo', 2, 'Logo'], ['cover', 5, '封面']] as [$field, $maxMb, $label]) {
            $file = $_FILES[$field] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $media = MediaModel::storeImage($file, Auth::id(), $maxMb * 1024 * 1024);
                $uploadedIds[] = $media['id'];
                if ($field === 'logo') {
                    $logoMediaId = $media['id'];
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
            http_response_code(422);
            render('admin/team', [
                'pageTitle' => '团队资料 · 管理后台',
                'adminNav' => 'team',
                'team' => $team,
                'values' => $values,
                'errors' => $errors,
                'old' => [...$input, 'logo' => $team['logo'], 'cover' => $team['cover']],
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        TeamModel::updateProfile([
            ...$input,
            'founded_on' => $foundedOn,
            'values' => $values,
            'logo_media_id' => $logoMediaId,
            'cover_media_id' => $coverMediaId,
        ], Auth::id());

        // 团队更名时同步站点品牌，避免导航栏仍显示旧队名。
        $oldName = trim((string) ($team['name'] ?? ''));
        $newName = $input['name'];
        if ($oldName !== '' && $newName !== '' && $oldName !== $newName) {
            $publicSettings = SiteSettingModel::publicValues();
            $synced = ['site.name' => $newName];

            foreach (['site.description', 'footer.copyright'] as $key) {
                $value = (string) ($publicSettings[$key] ?? '');
                if ($value !== '' && str_contains($value, $oldName)) {
                    $synced[$key] = str_replace($oldName, $newName, $value);
                }
            }

            SiteSettingModel::updateMany($synced, Auth::id());
        }

        if ($oldLogoId > 0 && $oldLogoId !== $logoMediaId) {
            MediaModel::deleteIfUnused($oldLogoId);
        }
        if ($oldCoverId > 0 && $oldCoverId !== $coverMediaId) {
            MediaModel::deleteIfUnused($oldCoverId);
        }

        AuthModel::audit('update', 'team_profile', 1, '更新团队资料：' . $input['name']);
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
            'toastMessage' => match ($_GET['saved'] ?? '') {
                'uploaded' => '资源已上传。',
                '1' => '资源已保存。',
                default => '',
            },
            'toastType' => 'success',
        ]);
    }

    public function resourceUpload(): void
    {
        Auth::requirePermission('resource.manage');
        render('admin/resource_upload', [
            'pageTitle' => '上传资源 · 管理后台',
            'adminNav' => 'resources',
            'categories' => ResourceModel::categories(),
            'errors' => [],
            'old' => [
                'title' => '',
                'category' => 'team',
                'description' => '',
                'is_public' => 1,
            ],
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    public function storeResource(): void
    {
        Auth::requirePermission('resource.manage');
        $this->requirePost();

        $input = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? 'team')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
        ];

        $errors = [];
        if ($input['title'] === '' || mb_strlen($input['title'], 'UTF-8') > 150) {
            $errors['title'] = '资源标题必填，且不超过 150 字。';
        }
        if (!array_key_exists($input['category'], ResourceModel::categories())) {
            $errors['category'] = '资源分类不存在。';
        }
        if (mb_strlen($input['description'], 'UTF-8') > 500) {
            $errors['description'] = '说明不超过 500 字。';
        }

        $file = $_FILES['file'] ?? null;
        $uploadError = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        $originalName = '';
        $extension = '';
        $tmpPath = '';

        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            $errors['file'] = '请选择要上传的文件。';
        } elseif ($uploadError !== UPLOAD_ERR_OK) {
            $errors['file'] = match ($uploadError) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '文件超过服务器允许的大小。',
                UPLOAD_ERR_PARTIAL => '文件上传不完整，请重试。',
                default => '文件上传失败，请重试。',
            };
        } else {
            $tmpPath = (string) ($file['tmp_name'] ?? '');
            $originalName = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
            $originalName = mb_substr($originalName, 0, 255, 'UTF-8');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md', 'png', 'jpg', 'jpeg'];
            $size = (int) ($file['size'] ?? 0);

            if ($originalName === '' || !in_array($extension, $allowedExtensions, true)) {
                $errors['file'] = '文件类型不支持；允许 PDF、ZIP、Office 文档、TXT/Markdown 与 PNG/JPG。';
            } elseif ($size <= 0 || $size > 10 * 1024 * 1024) {
                $errors['file'] = '文件大小需要在 1 字节到 10 MB 之间。';
            } elseif (!is_uploaded_file($tmpPath)) {
                $errors['file'] = '上传临时文件校验失败，请重试。';
            }
        }

        if ($errors !== []) {
            http_response_code(422);
            render('admin/resource_upload', [
                'pageTitle' => '上传资源 · 管理后台',
                'adminNav' => 'resources',
                'categories' => ResourceModel::categories(),
                'errors' => $errors,
                'old' => $input,
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        $subdir = match ($input['category']) {
            'team' => 'data/team',
            'personal' => 'data/personal',
            default => 'storage/uploads/resources',
        };
        $targetDir = APP_ROOT . '/' . $subdir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            $this->renderUploadError($input, '无法创建上传目录，请检查服务器目录权限。');
            return;
        }

        $diskName = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $targetPath = $targetDir . '/' . $diskName;
        $relativePath = $subdir . '/' . $diskName;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            $this->renderUploadError($input, '保存上传文件失败，请检查目录权限。');
            return;
        }
        @chmod($targetPath, 0644);

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];
        $hash = hash_file('sha256', $targetPath);

        try {
            $resourceId = ResourceModel::createWithMedia([
                'disk_name' => $diskName,
                'original_name' => $originalName,
                'mime_type' => $mimeTypes[$extension] ?? 'application/octet-stream',
                'extension' => $extension,
                'size_bytes' => (int) filesize($targetPath),
                'sha256' => $hash === false ? str_repeat('0', 64) : $hash,
                'storage_path' => $relativePath,
                'visibility' => $input['is_public'] === 1 ? 'public' : 'private',
                'uploaded_by' => Auth::id(),
            ], [
                'title' => $input['title'],
                'category' => $input['category'],
                'description' => $input['description'],
                'is_public' => $input['is_public'],
                'sort_order' => ResourceModel::nextSortOrder(),
                'uploaded_by' => Auth::id(),
            ]);
        } catch (Throwable $e) {
            @unlink($targetPath);
            error_log('[resource upload] ' . $e->getMessage());
            $this->renderUploadError($input, '写入资源记录失败，请稍后重试。');
            return;
        }

        AuthModel::audit(
            'upload',
            'resource',
            $resourceId,
            '上传资源：' . $input['title'] . ' / ' . $originalName
        );
        redirect(url('admin/resources', ['saved' => 'uploaded']));
    }

    public function resourceEdit(): void
    {
        Auth::requirePermission('resource.manage');

        $id = (int) ($_GET['id'] ?? 0);
        $resource = ResourceModel::findForAdmin($id);
        if ($resource === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '资源不存在']);
            return;
        }

        $this->renderResourceEdit($resource, [
            'id' => $id,
            'title' => (string) $resource['title'],
            'category' => (string) $resource['category'],
            'description' => (string) ($resource['description'] ?? ''),
            'is_public' => (int) $resource['is_public'],
        ], []);
    }

    public function updateResource(): void
    {
        Auth::requirePermission('resource.manage');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $resource = ResourceModel::findForAdmin($id);
        if ($resource === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '资源不存在']);
            return;
        }

        $input = [
            'id' => $id,
            'title' => trim((string) ($_POST['title'] ?? '')),
            'category' => trim((string) ($_POST['category'] ?? 'team')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'is_public' => isset($_POST['is_public']) ? 1 : 0,
        ];

        $errors = [];
        if ($input['title'] === '' || mb_strlen($input['title'], 'UTF-8') > 150) {
            $errors['title'] = '资源标题必填，且不超过 150 字。';
        }
        if (!array_key_exists($input['category'], ResourceModel::categories())) {
            $errors['category'] = '资源分类不存在。';
        }
        if (mb_strlen($input['description'], 'UTF-8') > 500) {
            $errors['description'] = '说明不超过 500 字。';
        }

        $file = $_FILES['file'] ?? null;
        $uploadError = is_array($file) ? (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        $replacement = null;
        $originalName = '';

        if ($uploadError !== UPLOAD_ERR_NO_FILE) {
            if ($uploadError !== UPLOAD_ERR_OK) {
                $errors['file'] = match ($uploadError) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '文件超过服务器允许的大小。',
                    UPLOAD_ERR_PARTIAL => '文件上传不完整，请重试。',
                    default => '文件上传失败，请重试。',
                };
            } else {
                $tmpPath = (string) ($file['tmp_name'] ?? '');
                $originalName = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
                $originalName = mb_substr($originalName, 0, 255, 'UTF-8');
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md', 'png', 'jpg', 'jpeg'];
                $size = (int) ($file['size'] ?? 0);

                if ($originalName === '' || !in_array($extension, $allowedExtensions, true)) {
                    $errors['file'] = '文件类型不支持；允许 PDF、ZIP、Office 文档、TXT/Markdown 与 PNG/JPG。';
                } elseif ($size <= 0 || $size > 10 * 1024 * 1024) {
                    $errors['file'] = '文件大小需要在 1 字节到 10 MB 之间。';
                } elseif (!is_uploaded_file($tmpPath)) {
                    $errors['file'] = '上传临时文件校验失败，请重试。';
                } else {
                    $replacement = [
                        'tmp_name' => $tmpPath,
                        'original_name' => $originalName,
                        'extension' => $extension,
                    ];
                }
            }
        }

        if ($errors !== []) {
            $this->renderResourceEdit($resource, $input, $errors, 422);
            return;
        }

        $newMedia = null;
        $newPath = null;
        if ($replacement !== null) {
            $subdir = match ($input['category']) {
                'team' => 'data/team',
                'personal' => 'data/personal',
                default => 'storage/uploads/resources',
            };
            $targetDir = APP_ROOT . '/' . $subdir;
            if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                $this->renderResourceEdit($resource, $input, ['file' => '无法创建上传目录，请检查目录权限。'], 422);
                return;
            }

            $diskName = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $replacement['extension'];
            $newPath = $targetDir . '/' . $diskName;
            $relativePath = $subdir . '/' . $diskName;

            if (!move_uploaded_file($replacement['tmp_name'], $newPath)) {
                $this->renderResourceEdit($resource, $input, ['file' => '保存上传文件失败，请检查目录权限。'], 422);
                return;
            }
            @chmod($newPath, 0644);

            $mimeTypes = [
                'pdf' => 'application/pdf',
                'zip' => 'application/zip',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'txt' => 'text/plain',
                'md' => 'text/markdown',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
            ];
            $hash = hash_file('sha256', $newPath);

            $newMedia = [
                'disk_name' => $diskName,
                'original_name' => $replacement['original_name'],
                'mime_type' => $mimeTypes[$replacement['extension']] ?? 'application/octet-stream',
                'extension' => $replacement['extension'],
                'size_bytes' => (int) filesize($newPath),
                'sha256' => $hash === false ? str_repeat('0', 64) : $hash,
                'storage_path' => $relativePath,
                'visibility' => $input['is_public'] === 1 ? 'public' : 'private',
                'uploaded_by' => Auth::id(),
            ];
        }

        try {
            $newMediaId = ResourceModel::update($id, $input, $newMedia);
        } catch (Throwable $e) {
            if ($newPath !== null) {
                @unlink($newPath);
            }
            error_log('[resource update] ' . $e->getMessage());
            $this->renderResourceEdit($resource, $input, ['file' => '保存资源失败，请稍后重试。'], 422);
            return;
        }

        if ($newMediaId !== null) {
            $oldMediaId = (int) $resource['media_id'];
            if ($oldMediaId !== $newMediaId && ResourceModel::deleteMedia($oldMediaId)) {
                $oldFile = $this->resourceFilePath((string) $resource['storage_path']);
                if ($oldFile !== null) {
                    @unlink($oldFile);
                }
            }
        }

        AuthModel::audit(
            'update',
            'resource',
            $id,
            '更新资源：' . $input['title']
                . ($replacement !== null ? ' / 替换文件 ' . $replacement['original_name'] : '')
        );
        redirect(url('admin/resources', ['saved' => '1']));
    }

    private function renderResourceEdit(array $resource, array $old, array $errors, int $status = 200): void
    {
        http_response_code($status);
        render('admin/resource_edit', [
            'pageTitle' => '编辑资源 · 管理后台',
            'adminNav' => 'resources',
            'resource' => $resource,
            'old' => $old,
            'categories' => ResourceModel::categories(),
            'errors' => $errors,
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    /**
     * 把数据库中的相对路径解析为允许目录内的真实文件；不在白名单目录内返回 null。
     */
    private function resourceFilePath(string $relative): ?string
    {
        $relative = str_replace('\\', '/', trim($relative));
        if (
            $relative === ''
            || str_contains($relative, "\0")
            || str_starts_with($relative, '/')
            || preg_match('#(^|/)\.\.(/|$)#', $relative) === 1
        ) {
            return null;
        }

        $full = realpath(APP_ROOT . '/' . $relative);
        if ($full === false || !is_file($full)) {
            return null;
        }

        foreach (['data/team', 'data/personal', 'storage/uploads'] as $allowed) {
            $root = realpath(APP_ROOT . '/' . $allowed);
            if ($root !== false && str_starts_with($full, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                return $full;
            }
        }

        return null;
    }

    public function users(): void
    {
        Auth::requirePermission('user.manage');

        $tempPassword = null;
        if (isset($_SESSION['_admin_temp_password']) && is_array($_SESSION['_admin_temp_password'])) {
            $tempPassword = $_SESSION['_admin_temp_password'];
            unset($_SESSION['_admin_temp_password']);
        }

        render('admin/user_list', [
            'pageTitle' => '用户与角色 · 管理后台',
            'adminNav' => 'users',
            'users' => UserModel::all(),
            'roles' => UserModel::roles(),
            'tempPassword' => $tempPassword,
            'toastMessage' => match ($_GET['saved'] ?? '') {
                'created' => '账号已创建。',
                'reset' => '密码已重置。',
                'updated' => '账号资料已保存。',
                'enabled' => '账号已启用。',
                'disabled' => '账号已禁用。',
                default => '',
            },
            'toastType' => 'success',
        ]);
    }

    public function userCreate(): void
    {
        Auth::requirePermission('user.manage');

        $this->renderUserForm([
            'id' => 0,
            'username' => '',
            'email' => '',
            'display_name' => '',
            'role_id' => 3,
            'status' => 1,
            'member_id' => 0,
        ], [], 200, '新建账号');
    }

    public function storeUser(): void
    {
        Auth::requirePermission('user.manage');
        $this->requirePost();

        $input = $this->userInput();
        $errors = $this->validateUserAccount($input, 0);

        if ($errors !== []) {
            $this->renderUserForm($input, $errors, 422, '新建账号');
            return;
        }

        $tempPassword = $this->temporaryPassword();
        $hash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        if ($hash === false) {
            throw new RuntimeException('Unable to hash password.');
        }

        $id = UserModel::create([
            'role_id' => $input['role_id'],
            'username' => $input['username'],
            'email' => $input['email'],
            'password_hash' => $hash,
            'display_name' => $input['display_name'],
            'status' => 1,
        ], (int) $input['member_id']);

        AuthModel::audit('create', 'user', $id, '新建账号：' . $input['username']);
        $_SESSION['_admin_temp_password'] = [
            'username' => $input['username'],
            'password' => $tempPassword,
            'kind' => 'created',
        ];

        redirect(url('admin/users', ['saved' => 'created']));
    }

    public function userEdit(): void
    {
        Auth::requirePermission('user.manage');

        $id = (int) ($_GET['id'] ?? 0);
        $user = UserModel::findForAdmin($id);
        if ($user === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '账号不存在']);
            return;
        }

        $user['member_id'] = (int) ($user['member_id'] ?? 0);
        $this->renderUserForm(
            $user,
            [],
            200,
            '编辑账号',
            ($_GET['saved'] ?? '') === '1' ? '账号资料已保存。' : ''
        );
    }

    public function updateUser(): void
    {
        Auth::requirePermission('user.manage');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $user = UserModel::findForAdmin($id);
        if ($user === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '账号不存在']);
            return;
        }

        $input = $this->userInput();
        $errors = $this->validateUserAccount($input, $id);

        if ($id === Auth::id() && (int) $input['role_id'] !== (int) $user['role_id']) {
            $errors['role_id'] = '不能修改自己的角色，请让其他超级管理员操作。';
        }
        if (
            $user['role_code'] === 'super_admin'
            && (int) $input['role_id'] !== (int) $user['role_id']
            && !UserModel::hasOtherActiveSuperAdmin($id)
        ) {
            $errors['role_id'] = '系统至少保留一名启用中的超级管理员。';
        }

        if ($errors !== []) {
            $input['member_id'] = (int) ($user['member_id'] ?? 0);
            $this->renderUserForm($input, $errors, 422, '编辑账号');
            return;
        }

        UserModel::updateAccount($id, [
            'role_id' => (int) $input['role_id'],
            'username' => $input['username'],
            'email' => $input['email'],
            'display_name' => $input['display_name'],
        ]);

        AuthModel::audit('update', 'user', $id, '更新账号：' . $input['username']);
        redirect(url('admin/userEdit', ['id' => $id, 'saved' => '1']));
    }

    public function resetUserPassword(): void
    {
        Auth::requirePermission('user.manage');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id === Auth::id()) {
            $this->abort(400, '不能重置自己的密码', '请到“账号安全”页面修改自己的登录密码。');
        }

        $user = UserModel::findForAdmin($id);
        if ($user === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '账号不存在']);
            return;
        }

        $tempPassword = $this->temporaryPassword();
        $hash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        if ($hash === false) {
            throw new RuntimeException('Unable to hash password.');
        }

        UserModel::resetPassword($id, $hash);
        AuthModel::audit('update', 'user', $id, '重置账号密码：' . $user['username']);
        $_SESSION['_admin_temp_password'] = [
            'username' => (string) $user['username'],
            'password' => $tempPassword,
            'kind' => 'reset',
        ];

        redirect(url('admin/users', ['saved' => 'reset']));
    }

    public function setUserStatus(): void
    {
        Auth::requirePermission('user.manage');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $status = (int) ($_POST['status'] ?? -1);
        if (!in_array($status, [0, 1], true)) {
            $this->abort(400, '状态不合法', '账号状态只允许启用或禁用。');
        }
        if ($id === Auth::id()) {
            $this->abort(400, '不能修改自己的状态', '请让其他超级管理员操作。');
        }

        $user = UserModel::findForAdmin($id);
        if ($user === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '账号不存在']);
            return;
        }
        if (
            $status === 0
            && $user['role_code'] === 'super_admin'
            && !UserModel::hasOtherActiveSuperAdmin($id)
        ) {
            $this->abort(400, '不能禁用最后一名超级管理员', '系统至少保留一名启用中的超级管理员。');
        }

        UserModel::setStatus($id, $status);
        AuthModel::audit(
            $status === 1 ? 'publish' : 'archive',
            'user',
            $id,
            ($status === 1 ? '启用账号：' : '禁用账号：') . $user['username']
        );
        redirect(url('admin/users', ['saved' => $status === 1 ? 'enabled' : 'disabled']));
    }

    private function renderUserForm(
        array $old,
        array $errors,
        int $status,
        string $mode,
        string $toastMessage = ''
    ): void {
        http_response_code($status);
        render('admin/user_edit', [
            'pageTitle' => $mode . ' · 管理后台',
            'adminNav' => 'users',
            'user' => $old,
            'old' => $old,
            'roles' => UserModel::roles(),
            'unboundMembers' => $old['id'] === 0 ? UserModel::unboundMembers() : [],
            'errors' => $errors,
            'toastMessage' => $toastMessage,
            'toastType' => 'success',
        ]);
    }

    private function userInput(): array
    {
        return [
            'id' => (int) ($_POST['id'] ?? 0),
            'username' => trim((string) ($_POST['username'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'display_name' => trim((string) ($_POST['display_name'] ?? '')),
            'role_id' => (int) ($_POST['role_id'] ?? 0),
            'member_id' => (int) ($_POST['member_id'] ?? 0),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validateUserAccount(array $input, int $id): array
    {
        $errors = [];

        if (preg_match('/^[A-Za-z0-9_-]{3,50}$/', $input['username']) !== 1) {
            $errors['username'] = '用户名只允许字母、数字、下划线和连字符，长度 3—50。';
        } elseif (UserModel::usernameExists($input['username'], $id)) {
            $errors['username'] = '该用户名已被使用。';
        }

        if (mb_strlen($input['email'], 'UTF-8') > 120 || filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = '邮箱格式不正确或超过 120 字。';
        } elseif (UserModel::emailExists($input['email'], $id)) {
            $errors['email'] = '该邮箱已被使用。';
        }

        $displayLength = mb_strlen($input['display_name'], 'UTF-8');
        if ($displayLength < 2 || $displayLength > 50) {
            $errors['display_name'] = '显示名需要 2—50 个字符。';
        }

        if (!UserModel::roleExists((int) $input['role_id'])) {
            $errors['role_id'] = '角色不存在。';
        }

        if ($input['member_id'] > 0) {
            $unbound = array_map(
                static fn (array $row): int => (int) $row['id'],
                UserModel::unboundMembers()
            );
            if (!in_array((int) $input['member_id'], $unbound, true)) {
                $errors['member_id'] = '所选成员档案不存在或已被其他账号绑定。';
            }
        }

        return $errors;
    }

    /**
     * 生成一次性临时密码：大小写、数字、符号齐全，长度 16。
     */
    private function temporaryPassword(): string
    {
        return 'Tmp@' . bin2hex(random_bytes(5)) . 'A9';
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
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '站点设置已保存。' : '',
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

            if ($setting['type'] === 'int' && $value !== '' && preg_match('/^-?\d+$/', $value) !== 1) {
                $errors[$key] = '请输入整数。';
            }

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

        SiteSettingModel::updateMany($input, Auth::id());
        AuthModel::audit('update', 'site_setting', null, '更新站点设置：' . count($input) . ' 项');
        redirect(url('admin/settings', ['saved' => '1']));
    }

    public function logs(): void
    {
        Auth::requirePermission('log.view');

        $loginUser = trim((string) ($_GET['login_user'] ?? ''));
        $loginUser = mb_substr($loginUser, 0, 50, 'UTF-8');

        $loginResult = (string) ($_GET['login_result'] ?? '');
        if (!in_array($loginResult, ['', '1', '0'], true)) {
            $loginResult = '';
        }

        $auditActor = trim((string) ($_GET['audit_actor'] ?? ''));
        $auditActor = mb_substr($auditActor, 0, 50, 'UTF-8');

        $auditAction = (string) ($_GET['audit_action'] ?? '');
        if (!array_key_exists($auditAction, LogModel::actionOptions())) {
            $auditAction = '';
        }

        $from = $this->filterDate((string) ($_GET['from'] ?? ''));
        $to = $this->filterDate((string) ($_GET['to'] ?? ''));

        render('admin/log_list', [
            'pageTitle' => '日志审计 · 管理后台',
            'adminNav' => 'logs',
            'loginLogs' => LogModel::loginLogs(
                $loginUser,
                $loginResult === '' ? null : (int) $loginResult,
                $from,
                $to
            ),
            'auditLogs' => LogModel::auditLogs($auditActor, $auditAction, $from, $to),
            'actionOptions' => LogModel::actionOptions(),
            'filters' => [
                'login_user' => $loginUser,
                'login_result' => $loginResult,
                'audit_actor' => $auditActor,
                'audit_action' => $auditAction,
                'from' => $from,
                'to' => $to,
            ],
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    /**
     * 日志日期筛选只接受 YYYY-MM-DD；非法值忽略，避免把脏条件带进 SQL。
     */
    private function filterDate(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value ? $value : '';
    }

    public function security(): void
    {
        $this->renderAdminSecurity([]);
    }

    /**
     * 后台账号修改本人密码；管理员没有成员档案，不能走成员中心。
     */
    public function updatePassword(): void
    {
        $this->requirePost();

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
            $this->renderAdminSecurity($errors, 422);
            return;
        }

        $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 10]);
        if ($newHash === false) {
            throw new RuntimeException('Unable to hash password.');
        }

        $changedAt = AuthModel::updatePassword(Auth::id(), $newHash);
        Auth::refreshPasswordStamp($changedAt);
        AuthModel::audit('update', 'user', Auth::id(), '管理员修改登录密码');

        redirect(url('admin/security', ['saved' => '1']));
    }

    private function renderAdminSecurity(array $errors, int $status = 200): void
    {
        http_response_code($status);
        render('admin/security', [
            'pageTitle' => '账号安全 · 管理后台',
            'adminNav' => 'security',
            'adminPageLabel' => '账号安全',
            'errors' => $errors,
            'old' => ['current_password' => ''],
            'loginLogs' => LogModel::loginLogsForUser(Auth::id(), 10),
            'toastMessage' => $errors === [] && ($_GET['saved'] ?? '') === '1'
                ? '密码已修改，其他设备上的旧会话已失效。'
                : '',
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
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '公告已保存。' : '',
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

        $input = $this->announcementInput();
        [$errors, $publishedAt, $expiresAt] = $this->validateAnnouncement($input, $id);

        if ($errors !== []) {
            http_response_code(422);
            render('admin/announcement_edit', [
                'pageTitle' => '编辑公告 · 管理后台',
                'adminNav' => 'announcements',
                'announcement' => $announcement,
                'old' => [...$announcement, ...$input],
                'categories' => AnnouncementModel::categories(),
                'errors' => $errors,
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        $coverMediaId = (int) ($announcement['cover_media_id'] ?? 0);
        $oldCoverId = $coverMediaId;
        $file = $_FILES['cover'] ?? null;
        if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $coverMediaId = MediaModel::storeImage($file, Auth::id(), 5 * 1024 * 1024)['id'];
            } catch (RuntimeException $e) {
                $errors['cover'] = '封面上传失败：' . $e->getMessage();
                http_response_code(422);
                render('admin/announcement_edit', [
                    'pageTitle' => '编辑公告 · 管理后台',
                    'adminNav' => 'announcements',
                    'announcement' => $announcement,
                    'old' => [...$announcement, ...$input],
                    'categories' => AnnouncementModel::categories(),
                    'errors' => $errors,
                    'toastMessage' => '',
                    'toastType' => 'success',
                ]);
                return;
            }
        }

        try {
            AnnouncementModel::update($id, [
                'title'        => $input['title'],
                'slug'         => $input['slug'],
                'summary'      => $input['summary'],
                'content'      => $input['content'],
                'category'     => $input['category'],
                'status'       => $input['status'],
                'is_pinned'    => $input['is_pinned'],
                'published_at' => $publishedAt,
                'expires_at'   => $expiresAt,
                'cover_media_id' => $coverMediaId,
            ]);
        } catch (Throwable $e) {
            if ($coverMediaId > 0 && $coverMediaId !== $oldCoverId) {
                MediaModel::deleteIfUnused($coverMediaId);
            }
            throw $e;
        }

        if ($oldCoverId > 0 && $oldCoverId !== $coverMediaId) {
            MediaModel::deleteIfUnused($oldCoverId);
        }

        AuthModel::audit(
            'update',
            'announcement',
            $id,
            '更新公告：' . $input['title'] . ' / ' . $input['status']
        );
        redirect(url('admin/announcements', ['saved' => '1']));
    }

    public function announcementCreate(): void
    {
        Auth::requirePermission('content.manage');

        render('admin/announcement_edit', [
            'pageTitle' => '新建公告 · 管理后台',
            'adminNav' => 'announcements',
            'old' => [
                'id' => 0,
                'title' => '',
                'slug' => '',
                'category' => 'news',
                'summary' => '',
                'content' => '',
                'status' => 'draft',
                'is_pinned' => 0,
                'published_at' => '',
                'expires_at' => '',
            ],
            'categories' => AnnouncementModel::categories(),
            'errors' => [],
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    public function storeAnnouncement(): void
    {
        Auth::requirePermission('content.manage');
        $this->requirePost();

        $input = $this->announcementInput();
        [$errors, $publishedAt, $expiresAt] = $this->validateAnnouncement($input, 0);

        if ($errors !== []) {
            http_response_code(422);
            render('admin/announcement_edit', [
                'pageTitle' => '新建公告 · 管理后台',
                'adminNav' => 'announcements',
                'old' => $input,
                'categories' => AnnouncementModel::categories(),
                'errors' => $errors,
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        $coverMediaId = 0;
        $file = $_FILES['cover'] ?? null;
        if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $coverMediaId = MediaModel::storeImage($file, Auth::id(), 5 * 1024 * 1024)['id'];
            } catch (RuntimeException $e) {
                $errors['cover'] = '封面上传失败：' . $e->getMessage();
                http_response_code(422);
                render('admin/announcement_edit', [
                    'pageTitle' => '新建公告 · 管理后台',
                    'adminNav' => 'announcements',
                    'old' => $input,
                    'categories' => AnnouncementModel::categories(),
                    'errors' => $errors,
                    'toastMessage' => '',
                    'toastType' => 'success',
                ]);
                return;
            }
        }

        try {
            $id = AnnouncementModel::create([
                'title'        => $input['title'],
                'slug'         => $input['slug'],
                'summary'      => $input['summary'],
                'content'      => $input['content'],
                'category'     => $input['category'],
                'status'       => $input['status'],
                'is_pinned'    => $input['is_pinned'],
                'published_at' => $publishedAt,
                'expires_at'   => $expiresAt,
                'cover_media_id' => $coverMediaId,
            ], (int) Auth::id());
        } catch (Throwable $e) {
            if ($coverMediaId > 0) {
                MediaModel::deleteIfUnused($coverMediaId);
            }
            throw $e;
        }

        AuthModel::audit('create', 'announcement', $id, '新建公告：' . $input['title']);
        redirect(url('admin/announcements', ['saved' => '1']));
    }

    /**
     * 公告列表的快捷发布 / 下线。
     */
    public function setAnnouncementStatus(): void
    {
        Auth::requirePermission('content.publish');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $announcement = AnnouncementModel::findById($id);

        if ($announcement === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '公告不存在']);
            return;
        }
        if (!in_array($status, ['draft', 'published', 'offline'], true)) {
            $this->abort(400, '状态不合法', '公告状态只允许草稿、已发布或已下线。');
        }

        AnnouncementModel::setStatus($id, $status);

        $labels = ['draft' => '草稿', 'published' => '已发布', 'offline' => '已下线'];
        $action = match ($status) {
            'published' => 'publish',
            'offline'   => 'archive',
            default     => 'update',
        };
        AuthModel::audit(
            $action,
            'announcement',
            $id,
            '公告状态改为' . $labels[$status] . '：' . $announcement['title']
        );

        redirect(url('admin/announcements', ['saved' => '1']));
    }

    /**
     * 收集公告表单字段；新建与编辑共用。
     */
    private function announcementInput(): array
    {
        return [
            'id' => (int) ($_POST['id'] ?? 0),
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
    }

    /**
     * 公告字段校验与时间标准化；新建时 $id 传 0。
     *
     * @return array{0: array<string, string>, 1: ?string, 2: ?string}
     */
    private function validateAnnouncement(array $input, int $id): array
    {
        $errors = [];
        if ($input['title'] === '' || mb_strlen($input['title'], 'UTF-8') > 150) {
            $errors['title'] = '标题必填，且不超过 150 字。';
        }
        if (preg_match('/^[a-z0-9-]{3,100}$/', $input['slug']) !== 1) {
            $errors['slug'] = 'slug 只允许小写字母、数字和连字符，长度 3—100。';
        } elseif (AnnouncementModel::slugExists($input['slug'], $id)) {
            $errors['slug'] = '该 slug 已被其他公告使用。';
        }
        if (!array_key_exists($input['category'], AnnouncementModel::categories())) {
            $errors['category'] = '分类不存在。';
        }
        if (mb_strlen($input['summary'], 'UTF-8') > 300) {
            $errors['summary'] = '摘要不超过 300 字。';
        }
        if ($input['content'] === '') {
            $errors['content'] = '正文不能为空。';
        } elseif (mb_strlen($input['content'], 'UTF-8') > 50000) {
            $errors['content'] = '正文不超过 50000 字。';
        }
        if (!in_array($input['status'], ['draft', 'published', 'offline'], true)) {
            $errors['status'] = '状态不合法。';
        }
        if ($input['is_pinned'] === 1 && AnnouncementModel::pinnedCount($id) >= 2) {
            $errors['is_pinned'] = '最多只能置顶 2 条公告，请先取消其他置顶。';
        }

        $normalizeDateTime = static function (string $value): ?string {
            $value = str_replace('T', ' ', trim($value));
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $value) !== 1) {
                return null;
            }

            $timestamp = strtotime($value);
            return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
        };

        $publishedAt = null;
        if ($input['published_at'] === '') {
            if ($input['status'] === 'published') {
                $publishedAt = date('Y-m-d H:i:s');
            }
        } else {
            $publishedAt = $normalizeDateTime($input['published_at']);
            if ($publishedAt === null) {
                $errors['published_at'] = '发布时间格式不正确。';
            }
        }

        $expiresAt = null;
        if ($input['expires_at'] !== '') {
            $expiresAt = $normalizeDateTime($input['expires_at']);
            if ($expiresAt === null) {
                $errors['expires_at'] = '过期时间格式不正确。';
            }
        }

        if (
            $publishedAt !== null
            && $expiresAt !== null
            && !isset($errors['published_at'])
            && !isset($errors['expires_at'])
            && strtotime($expiresAt) <= strtotime($publishedAt)
        ) {
            $errors['expires_at'] = '过期时间必须晚于发布时间。';
        }

        return [$errors, $publishedAt, $expiresAt];
    }

    public function messages(): void
    {
        Auth::requirePermission('message.moderate');
        render('admin/message_list', [
            'pageTitle' => '留言管理 · 管理后台',
            'adminNav' => 'messages',
            'messages' => MessageModel::forAdmin(),
            'counts' => MessageModel::countByStatus(),
            'toastMessage' => ($_GET['saved'] ?? '') === '1' ? '留言状态已更新。' : '',
            'toastType' => 'success',
        ]);
    }

    public function messageEdit(): void
    {
        Auth::requirePermission('message.moderate');
        $id = (int) ($_GET['id'] ?? 0);
        $message = MessageModel::findForAdmin($id);
        if ($message === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '留言不存在']);
            return;
        }

        $this->renderMessageEdit($message, $this->messageFormValues($message), [], 200,
            ($_GET['saved'] ?? '') === '1' ? '留言状态已更新。' : '');
    }

    public function updateMessage(): void
    {
        Auth::requirePermission('message.moderate');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $message = MessageModel::findForAdmin($id);
        if ($message === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '留言不存在']);
            return;
        }

        // 快捷操作按钮用 decision 覆盖下拉框；普通保存仍以 status 为准。
        $decision = trim((string) ($_POST['decision'] ?? ''));
        $status = $decision !== '' ? $decision : trim((string) ($_POST['status'] ?? 'pending'));
        $adminNote = trim((string) ($_POST['admin_note'] ?? ''));
        $reply = trim((string) ($_POST['reply'] ?? ''));
        $replyVisible = isset($_POST['reply_visible']);

        $old = [
            'id' => $id,
            'status' => $status,
            'admin_note' => $adminNote,
            'reply' => $reply,
            'reply_visible' => $replyVisible,
        ];

        $errors = [];
        if (!array_key_exists($status, MessageModel::STATUS_LABELS)) {
            $errors['status'] = '留言状态不合法。';
        }
        if (mb_strlen($adminNote, 'UTF-8') > 255) {
            $errors['admin_note'] = '审核备注不超过 255 字。';
        }
        if (mb_strlen($reply, 'UTF-8') > 1000) {
            $errors['reply'] = '回复不超过 1000 字。';
        }

        if ($errors !== []) {
            $this->renderMessageEdit($message, $old, $errors, 422);
            return;
        }

        try {
            MessageModel::moderate(
                $id,
                $status,
                $adminNote === '' ? null : $adminNote,
                $reply === '' ? null : $reply,
                $replyVisible,
                (int) Auth::id(),
                (string) (Auth::user()['display_name'] ?? Auth::user()['username'] ?? '管理员')
            );
        } catch (RuntimeException $e) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '留言不存在']);
            return;
        }

        AuthModel::audit(
            'moderate',
            'message',
            $id,
            '留言审核：' . (MessageModel::STATUS_LABELS[$status] ?? $status) . ' / ' . $message['author_name']
        );

        $returnTo = (string) ($_POST['return_to'] ?? '');
        if ($returnTo === 'list') {
            redirect(url('admin/messages', ['saved' => '1']));
        }
        if ($returnTo === 'dashboard') {
            redirect(url('admin/dashboard', ['saved' => '1']));
        }

        redirect(url('admin/messageEdit', ['id' => $id, 'saved' => '1']));
    }

    public function projects(): void
    {
        Auth::requirePermission('content.manage');
        render('admin/project_list', [
            'pageTitle' => '项目管理 · 管理后台',
            'adminNav' => 'projects',
            'projects' => ProjectModel::allForAdmin(),
            'statuses' => ProjectModel::STATUS_LABELS,
            'toastMessage' => match ($_GET['saved'] ?? '') {
                'created' => '项目已创建。',
                '1' => '项目已保存。',
                'archived' => '项目已归档并从前台隐藏。',
                'visible' => '项目已恢复公开。',
                'hidden' => '项目已隐藏。',
                default => '',
            },
            'toastType' => 'success',
        ]);
    }

    public function projectCreate(): void
    {
        Auth::requirePermission('content.manage');

        $this->renderProjectForm([
            'id' => 0,
            'title' => '',
            'slug' => '',
            'summary' => '',
            'description' => '',
            'tech_stack' => '',
            'status' => 'ongoing',
            'start_date' => '',
            'end_date' => '',
            'repo_url' => '',
            'demo_url' => '',
            'is_featured' => 0,
            'is_visible' => 1,
            'sort_order' => 0,
            'members' => [],
        ], [], 200, '新建项目');
    }

    public function storeProject(): void
    {
        Auth::requirePermission('content.manage');
        $this->requirePost();

        $input = $this->projectInput();
        [$errors, $data] = $this->validateProject($input, 0);

        if ($errors !== []) {
            $this->renderProjectForm($input, $errors, 422, '新建项目');
            return;
        }

        $coverMediaId = 0;
        $file = $_FILES['cover'] ?? null;
        if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $coverMediaId = MediaModel::storeImage($file, Auth::id(), 5 * 1024 * 1024)['id'];
            } catch (RuntimeException $e) {
                $errors['cover'] = '封面上传失败：' . $e->getMessage();
                $this->renderProjectForm($input, $errors, 422, '新建项目');
                return;
            }
        }

        $data['cover_media_id'] = $coverMediaId;
        $data['created_by'] = (int) Auth::id();

        try {
            $id = ProjectModel::create($data, $input['members']);
        } catch (Throwable $e) {
            if ($coverMediaId > 0) {
                MediaModel::deleteIfUnused($coverMediaId);
            }
            throw $e;
        }

        AuthModel::audit('create', 'project', $id, '新建项目：' . $data['title']);
        redirect(url('admin/projects', ['saved' => 'created']));
    }

    public function projectEdit(): void
    {
        Auth::requirePermission('content.manage');

        $id = (int) ($_GET['id'] ?? 0);
        $project = ProjectModel::findForAdmin($id);
        if ($project === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '项目不存在']);
            return;
        }

        $old = $project;
        $old['members'] = [];
        foreach ($project['members'] as $member) {
            $old['members'][(int) $member['member_id']] = [
                'checked' => true,
                'role_name' => (string) ($member['role_name'] ?? ''),
                'contribution' => (string) ($member['contribution'] ?? ''),
            ];
        }

        $this->renderProjectForm(
            $old,
            [],
            200,
            '编辑项目',
            ($_GET['saved'] ?? '') === '1' ? '项目已保存。' : ''
        );
    }

    public function updateProject(): void
    {
        Auth::requirePermission('content.manage');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $project = ProjectModel::findForAdmin($id);
        if ($project === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '项目不存在']);
            return;
        }

        $input = $this->projectInput();
        [$errors, $data] = $this->validateProject($input, $id);

        if ($errors !== []) {
            $this->renderProjectForm([...$project, ...$input], $errors, 422, '编辑项目');
            return;
        }

        $coverMediaId = (int) ($project['cover_media_id'] ?? 0);
        $oldCoverId = $coverMediaId;
        $file = $_FILES['cover'] ?? null;
        if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $coverMediaId = MediaModel::storeImage($file, Auth::id(), 5 * 1024 * 1024)['id'];
            } catch (RuntimeException $e) {
                $errors['cover'] = '封面上传失败：' . $e->getMessage();
                $this->renderProjectForm([...$project, ...$input], $errors, 422, '编辑项目');
                return;
            }
        }

        $data['cover_media_id'] = $coverMediaId;

        try {
            ProjectModel::update($id, $data, $input['members']);
        } catch (Throwable $e) {
            if ($coverMediaId > 0 && $coverMediaId !== $oldCoverId) {
                MediaModel::deleteIfUnused($coverMediaId);
            }
            throw $e;
        }

        if ($oldCoverId > 0 && $oldCoverId !== $coverMediaId) {
            MediaModel::deleteIfUnused($oldCoverId);
        }

        AuthModel::audit('update', 'project', $id, '更新项目：' . $data['title']);
        redirect(url('admin/projectEdit', ['id' => $id, 'saved' => '1']));
    }

    public function archiveProject(): void
    {
        Auth::requirePermission('content.publish');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $project = ProjectModel::findForAdmin($id);
        if ($project === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '项目不存在']);
            return;
        }

        ProjectModel::archive($id);
        AuthModel::audit('archive', 'project', $id, '归档项目：' . $project['title']);
        redirect(url('admin/projects', ['saved' => 'archived']));
    }

    public function setProjectVisibility(): void
    {
        Auth::requirePermission('content.publish');
        $this->requirePost();

        $id = (int) ($_POST['id'] ?? 0);
        $visible = (string) ($_POST['visible'] ?? '') === '1';
        $project = ProjectModel::findForAdmin($id);
        if ($project === null) {
            http_response_code(404);
            render('error_404', ['pageTitle' => '项目不存在']);
            return;
        }

        ProjectModel::setVisibility($id, $visible);
        AuthModel::audit(
            $visible ? 'publish' : 'archive',
            'project',
            $id,
            ($visible ? '恢复公开项目：' : '隐藏项目：') . $project['title']
        );
        redirect(url('admin/projects', ['saved' => $visible ? 'visible' : 'hidden']));
    }

    /**
     * 项目表单的渲染入口；新建与编辑共用。
     */
    private function renderProjectForm(
        array $old,
        array $errors,
        int $status,
        string $mode,
        string $toastMessage = ''
    ): void {
        http_response_code($status);
        render('admin/project_edit', [
            'pageTitle' => $mode . ' · 管理后台',
            'adminNav' => 'projects',
            'project' => $old,
            'old' => $old,
            'availableMembers' => ProjectModel::availableMembers(),
            'statuses' => ProjectModel::STATUS_LABELS,
            'errors' => $errors,
            'toastMessage' => $toastMessage,
            'toastType' => 'success',
        ]);
    }

    /**
     * 收集项目表单字段与成员分工。
     */
    private function projectInput(): array
    {
        $postedMembers = $_POST['members'] ?? [];
        $postedMembers = is_array($postedMembers) ? array_values(array_filter($postedMembers, 'is_string')) : [];
        $roles = $_POST['member_roles'] ?? [];
        $contributions = $_POST['member_contributions'] ?? [];
        $roles = is_array($roles) ? $roles : [];
        $contributions = is_array($contributions) ? $contributions : [];

        $members = [];
        foreach ($postedMembers as $memberId) {
            if (preg_match('/^\d+$/', $memberId) !== 1) {
                continue;
            }
            $id = (int) $memberId;
            $members[$id] = [
                'role_name' => trim((string) ($roles[$memberId] ?? '')),
                'contribution' => trim((string) ($contributions[$memberId] ?? '')),
            ];
        }

        return [
            'id' => (int) ($_POST['id'] ?? 0),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'slug' => trim((string) ($_POST['slug'] ?? '')),
            'summary' => trim((string) ($_POST['summary'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'tech_stack' => trim((string) ($_POST['tech_stack'] ?? '')),
            'status' => trim((string) ($_POST['status'] ?? 'ongoing')),
            'start_date' => trim((string) ($_POST['start_date'] ?? '')),
            'end_date' => trim((string) ($_POST['end_date'] ?? '')),
            'repo_url' => trim((string) ($_POST['repo_url'] ?? '')),
            'demo_url' => trim((string) ($_POST['demo_url'] ?? '')),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
            'sort_order' => trim((string) ($_POST['sort_order'] ?? '0')),
            'members' => $members,
        ];
    }

    /**
     * 校验项目字段，返回错误与可直接写库的数据。
     *
     * @return array{0: array<string, string>, 1: array<string, mixed>}
     */
    private function validateProject(array $input, int $id): array
    {
        $errors = [];

        if ($input['title'] === '' || mb_strlen($input['title'], 'UTF-8') > 120) {
            $errors['title'] = '项目名称必填，且不超过 120 字。';
        }
        if (preg_match('/^[a-z0-9-]{3,80}$/', $input['slug']) !== 1) {
            $errors['slug'] = 'slug 只允许小写字母、数字和连字符，长度 3—80。';
        } elseif (ProjectModel::slugExists($input['slug'], $id)) {
            $errors['slug'] = '该 slug 已被其他项目使用。';
        }
        if (mb_strlen($input['summary'], 'UTF-8') > 300) {
            $errors['summary'] = '摘要不超过 300 字。';
        }
        if (mb_strlen($input['description'], 'UTF-8') > 50000) {
            $errors['description'] = '项目详情不超过 50000 字。';
        }
        if (mb_strlen($input['tech_stack'], 'UTF-8') > 255) {
            $errors['tech_stack'] = '技术栈不超过 255 字。';
        }
        if (!array_key_exists($input['status'], ProjectModel::STATUS_LABELS)) {
            $errors['status'] = '项目状态不合法。';
        }

        $normalizeDate = static function (string $value): ?string {
            if ($value === '') {
                return null;
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
        };
        $startDate = $normalizeDate($input['start_date']);
        $endDate = $normalizeDate($input['end_date']);
        if ($input['start_date'] !== '' && $startDate === null) {
            $errors['start_date'] = '开始日期格式不正确。';
        }
        if ($input['end_date'] !== '' && $endDate === null) {
            $errors['end_date'] = '结束日期格式不正确。';
        }
        if ($startDate !== null && $endDate !== null && $endDate < $startDate) {
            $errors['end_date'] = '结束日期不能早于开始日期。';
        }

        foreach (['repo_url' => '仓库地址', 'demo_url' => '演示地址'] as $field => $label) {
            if ($input[$field] !== '' && preg_match('#^https?://#i', $input[$field]) !== 1) {
                $errors[$field] = $label . '只允许 http/https。';
            } elseif (mb_strlen($input[$field], 'UTF-8') > 255) {
                $errors[$field] = $label . '不超过 255 字。';
            }
        }

        if (preg_match('/^-?\d+$/', (string) $input['sort_order']) !== 1) {
            $errors['sort_order'] = '排序需要是整数。';
        } else {
            $sortOrder = (int) $input['sort_order'];
            if ($sortOrder < -9999 || $sortOrder > 9999) {
                $errors['sort_order'] = '排序范围为 -9999—9999。';
            }
        }

        $validMemberIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            ProjectModel::availableMembers()
        );
        if (count($input['members']) < 1) {
            $errors['members'] = '项目至少关联 1 名成员。';
        } elseif (count($input['members']) > 30) {
            $errors['members'] = '单个项目最多关联 30 名成员。';
        } else {
            $unknown = array_diff(array_keys($input['members']), $validMemberIds);
            if ($unknown !== []) {
                $errors['members'] = '成员列表中包含不存在的成员，请刷新后重选。';
            }
            foreach ($input['members'] as $memberId => $assignment) {
                if (mb_strlen($assignment['role_name'], 'UTF-8') > 50) {
                    $errors['members'] = '成员分工不超过 50 字。';
                    break;
                }
                if (mb_strlen($assignment['contribution'], 'UTF-8') > 255) {
                    $errors['members'] = '贡献说明不超过 255 字。';
                    break;
                }
            }
        }

        $data = [
            'title' => $input['title'],
            'slug' => $input['slug'],
            'summary' => $input['summary'] !== '' ? $input['summary'] : null,
            'description' => $input['description'] !== '' ? $input['description'] : null,
            'tech_stack' => $input['tech_stack'] !== '' ? $input['tech_stack'] : null,
            'status' => $input['status'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'repo_url' => $input['repo_url'] !== '' ? $input['repo_url'] : null,
            'demo_url' => $input['demo_url'] !== '' ? $input['demo_url'] : null,
            'is_featured' => (int) $input['is_featured'],
            'is_visible' => (int) $input['is_visible'],
            'sort_order' => (int) $input['sort_order'],
        ];

        return [$errors, $data];
    }

    private function renderUploadError(array $old, string $message): void
    {
        http_response_code(422);
        render('admin/resource_upload', [
            'pageTitle' => '上传资源 · 管理后台',
            'adminNav' => 'resources',
            'categories' => ResourceModel::categories(),
            'errors' => ['file' => $message],
            'old' => $old,
            'toastMessage' => '',
            'toastType' => 'success',
        ]);
    }

    private function renderMessageEdit(
        array $message,
        array $old,
        array $errors,
        int $status = 200,
        string $toastMessage = ''
    ): void {
        http_response_code($status);
        render('admin/message_edit', [
            'pageTitle' => '审核留言 · 管理后台',
            'adminNav' => 'messages',
            'message' => $message,
            'statuses' => MessageModel::STATUS_LABELS,
            'errors' => $errors,
            'old' => $old,
            'toastMessage' => $toastMessage,
            'toastType' => 'success',
        ]);
    }

    /**
     * @return array{id: int, status: string, admin_note: string, reply: string, reply_visible: bool}
     */
    private function messageFormValues(array $message): array
    {
        return [
            'id' => (int) $message['id'],
            'status' => (string) $message['status'],
            'admin_note' => (string) $message['admin_note'],
            'reply' => (string) ($message['reply']['content'] ?? ''),
            'reply_visible' => ($message['reply']['status'] ?? 'visible') === 'visible',
        ];
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
            'pageTitle' => $title . ' · ' . site_name(),
            'metaDescription' => $message,
            'activeNav' => '',
            'errorCode' => $code,
            'errorTitle' => $title,
            'errorMessage' => $message,
        ]);
        exit;
    }
}
