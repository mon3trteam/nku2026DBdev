<?php
/**
 * 文件名：AuthController.class.php
 * 功能：登录页控制器；渲染演示状态，提交逻辑由后端认证模块接入
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：认证
 */

declare(strict_types=1);

final class AuthController
{
    public function login(): void
    {
        $state = (string) ($_GET['state'] ?? '');

        // 演示状态：error / locked / rate / success，便于对照验收标准检查界面。
        $states = [
            'error' => ['danger', '用户名或密码不正确。'],
            'locked' => ['warning', '尝试次数过多，请于 14:32 后重试。'],
            'rate' => ['warning', '操作过于频繁，请 60 秒后再试。'],
            'success' => ['success', '登录成功（演示）。正在跳转…'],
        ];

        $stateType = $states[$state][0] ?? '';
        $stateMessage = $states[$state][1] ?? '';
        $disabled = in_array($state, ['locked', 'rate'], true);

        render('auth/login', [
            'pageTitle' => '成员登录 · 数聚 TeamHub',
            'metaDescription' => '数聚 TeamHub 成员登录入口。账号由管理员创建，不开放公开注册。',
            'activeNav' => '',
            'next' => (string) ($_GET['next'] ?? ''),
            'state' => $state,
            'stateType' => $stateType,
            'stateMessage' => $stateMessage,
            'disabled' => $disabled,
        ]);
    }
}
