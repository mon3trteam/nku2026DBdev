<?php
/**
 * 文件名：MessageController.class.php
 * 功能：留言板控制器；公开留言写入待审核队列，审核在后台完成
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 修改：接入 messages 表与提交限流（2026-11-08）
 * 所属模块：公共门户 / 留言
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MessageModel.class.php';
require_once APP_ROOT . '/app/model/MemberModel.class.php';

final class MessageController
{
    public function index(): void
    {
        $this->renderPage($this->emptyInput(), []);
    }

    public function store(): void
    {
        $this->requirePost();

        $input = [
            'author_name' => trim((string) ($_POST['author_name'] ?? '')),
            'author_email' => trim((string) ($_POST['author_email'] ?? '')),
            'author_website' => trim((string) ($_POST['author_website'] ?? '')),
            'member_slug' => trim((string) ($_POST['member_slug'] ?? '')),
            'content' => trim((string) ($_POST['content'] ?? '')),
        ];

        // 蜜罐字段：真实访客看不到；被填写时按垃圾留言处理，但不暴露判断逻辑。
        if (trim((string) ($_POST['website_hp'] ?? '')) !== '') {
            redirect(url('message/index', ['sent' => '1']));
        }

        // 同一 IP 10 分钟最多 5 条，防止公开表单被刷。
        if (MessageModel::recentCountByIp(Auth::ipHash(), 10) >= 5) {
            $this->renderPage($input, [], 429, '提交过于频繁，请 10 分钟后再试。');
            return;
        }

        $errors = [];
        $nameLength = mb_strlen($input['author_name'], 'UTF-8');
        $contentLength = mb_strlen($input['content'], 'UTF-8');

        if ($nameLength < 2 || $nameLength > 30) {
            $errors['author_name'] = '昵称需要 2—30 个字符。';
        }
        if ($input['author_email'] !== '' && filter_var($input['author_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['author_email'] = '邮箱格式不正确。';
        }
        if ($input['author_website'] !== '' && preg_match('#^https?://#i', $input['author_website']) !== 1) {
            $errors['author_website'] = '网址只允许 http/https。';
        }
        if ($contentLength < 10 || $contentLength > 500) {
            $errors['content'] = '留言内容需要 10—500 个字符。';
        }

        $member = null;
        if ($input['member_slug'] !== '') {
            $member = MemberModel::findBySlug($input['member_slug']);
            if ($member === null) {
                $errors['member_slug'] = '目标成员不存在。';
            }
        }

        if ($errors !== []) {
            $this->renderPage($input, $errors, 422);
            return;
        }

        MessageModel::create([
            'member_id' => $member === null ? null : (int) $member['id'],
            'author_name' => $input['author_name'],
            'author_email' => $input['author_email'],
            'author_website' => $input['author_website'],
            'content' => $input['content'],
            'status' => 'pending',
            'ip_hash' => Auth::ipHash(),
            'user_agent' => Auth::userAgent(),
        ]);

        if ($member !== null) {
            redirect(url('member/show', ['slug' => $member['slug'], 'sent' => '1']));
        }

        redirect(url('message/index', ['sent' => '1']));
    }

    private function renderPage(array $input, array $errors, int $status = 200, string $formNotice = ''): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = MessageModel::approved($page, 5);

        http_response_code($status);
        render('public/message_index', [
            'pageTitle' => '留言板 · ' . site_name(),
            'metaDescription' => '在' . site_name() . '留言板留下建议。留言需要审核后才会公开，邮箱不会公开。',
            'activeNav' => 'message',
            'messages' => $result['items'],
            'pagination' => $result,
            'members' => MemberModel::allVisible(),
            'errors' => $errors,
            'formNotice' => $formNotice,
            'old' => $input,
            'toastMessage' => ($_GET['sent'] ?? '') === '1' ? '留言已提交，审核通过后公开。' : '',
            'toastType' => 'success',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function emptyInput(): array
    {
        return [
            'author_name' => '',
            'author_email' => '',
            'author_website' => '',
            'member_slug' => '',
            'content' => '',
        ];
    }

    /**
     * 公开写操作只接受 POST，并校验 CSRF token。
     */
    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            $this->abort(405, '请求方式不正确', '留言只接受表单 POST 提交，请从留言板页面操作。');
        }

        if (!Auth::verifyCsrf($_POST['_token'] ?? null)) {
            $this->abort(400, '页面已过期', '安全令牌已失效，请返回留言板刷新后重新提交。');
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
