<?php
/**
 * 文件名：MessageController.class.php
 * 功能：留言板控制器；演示表单校验与 PRG 跳转，数据库接入后替换成功分支
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：公共门户 / 留言
 */

declare(strict_types=1);

require_once APP_ROOT . '/app/model/MessageModel.class.php';
require_once APP_ROOT . '/app/model/MemberModel.class.php';

final class MessageController
{
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = MessageModel::approved($page, 5);

        render('public/message_index', [
            'pageTitle' => '留言板 · 数聚 TeamHub',
            'metaDescription' => '在数聚 TeamHub 留言板留下建议。留言需要审核后才会公开，邮箱不会公开。',
            'activeNav' => 'message',
            'messages' => $result['items'],
            'pagination' => $result,
            'members' => MemberModel::allVisible(),
            'errors' => [],
            'old' => [
                'author_name' => '',
                'author_email' => '',
                'author_website' => '',
                'member_slug' => '',
                'content' => '',
            ],
            'toastMessage' => ($_GET['sent'] ?? '') === '1' ? '留言已提交，审核通过后公开。' : '',
            'toastType' => 'success',
        ]);
    }

    public function store(): void
    {
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
            http_response_code(422);
            $result = MessageModel::approved(1, 5);

            render('public/message_index', [
                'pageTitle' => '留言板 · 数聚 TeamHub',
                'metaDescription' => '在数聚 TeamHub 留言板留下建议。留言需要审核后才会公开，邮箱不会公开。',
                'activeNav' => 'message',
                'messages' => $result['items'],
                'pagination' => $result,
                'members' => MemberModel::allVisible(),
                'errors' => $errors,
                'old' => $input,
                'toastMessage' => '',
                'toastType' => 'success',
            ]);
            return;
        }

        // 演示：不落库，直接 PRG 回显成功；后端接入后在此写 messages 表并返回待审核状态。
        if ($member !== null) {
            redirect(url('member/show', ['slug' => $member['slug'], 'sent' => '1']));
        }

        redirect(url('message/index', ['sent' => '1']));
    }
}
