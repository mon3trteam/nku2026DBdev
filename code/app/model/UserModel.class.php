<?php
/**
 * 文件名：UserModel.class.php
 * 功能：后台用户与角色的演示数据
 * 作者：杨斯源（学号待填）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 用户与角色
 */

declare(strict_types=1);

final class UserModel
{
    private static array $roles = [
        ['id' => 1, 'code' => 'super_admin', 'name' => '超级管理员'],
        ['id' => 2, 'code' => 'editor', 'name' => '编辑'],
        ['id' => 3, 'code' => 'member', 'name' => '成员'],
    ];

    private static array $users = [
        [
            'id' => 1,
            'username' => 'admin',
            'display_name' => '管理员',
            'email' => 'admin@example.com',
            'role_code' => 'super_admin',
            'role_name' => '超级管理员',
            'status' => 1,
            'status_label' => '正常',
            'must_change_password' => 1,
            'last_login_at' => '2026-11-05 09:12',
        ],
        [
            'id' => 2,
            'username' => 'lin-zhiyuan',
            'display_name' => '林知远',
            'email' => 'lin@example.com',
            'role_code' => 'member',
            'role_name' => '成员',
            'status' => 1,
            'status_label' => '正常',
            'must_change_password' => 0,
            'last_login_at' => '2026-11-04 21:40',
        ],
        [
            'id' => 3,
            'username' => 'su-wanqing',
            'display_name' => '苏晚晴',
            'email' => 'su@example.com',
            'role_code' => 'member',
            'role_name' => '成员',
            'status' => 1,
            'status_label' => '正常',
            'must_change_password' => 0,
            'last_login_at' => '2026-11-03 18:05',
        ],
        [
            'id' => 4,
            'username' => 'editor',
            'display_name' => '内容编辑',
            'email' => 'editor@example.com',
            'role_code' => 'editor',
            'role_name' => '编辑',
            'status' => 1,
            'status_label' => '正常',
            'must_change_password' => 0,
            'last_login_at' => '2026-11-02 10:22',
        ],
        [
            'id' => 5,
            'username' => 'archive',
            'display_name' => '归档账号',
            'email' => 'archive@example.com',
            'role_code' => 'member',
            'role_name' => '成员',
            'status' => 0,
            'status_label' => '已禁用',
            'must_change_password' => 0,
            'last_login_at' => '2026-10-28 16:00',
        ],
    ];

    public static function all(): array
    {
        return self::$users;
    }

    public static function roles(): array
    {
        return self::$roles;
    }
}
