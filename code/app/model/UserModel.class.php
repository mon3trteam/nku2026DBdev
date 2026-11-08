<?php
/**
 * 文件名：UserModel.class.php
 * 功能：后台用户与角色的数据访问；认证查询只对登录服务暴露口令散列
 * 作者：杨斯源（学号待填）
 * 修改：后端接入数据库（2026-11-08）
 * 创建日期：2026-11-05
 * 所属模块：管理后台 / 用户与角色
 */

declare(strict_types=1);

final class UserModel
{
    /**
     * 后台用户列表。
     */
    public static function all(): array
    {
        $rows = Db::fetchAll(
            'SELECT u.id, u.username, u.display_name, u.email, u.status,
                    u.must_change_password, u.last_login_at,
                    r.code AS role_code, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             ORDER BY u.id ASC'
        );

        return array_map(
            static function (array $row): array {
                $row['status'] = (int) $row['status'];
                $row['status_label'] = match ($row['status']) {
                    1       => '正常',
                    0       => '已禁用',
                    2       => '已锁定',
                    default => '未知',
                };
                $row['must_change_password'] = (int) $row['must_change_password'];
                $row['last_login_at'] = format_datetime($row['last_login_at'] === null ? null : (string) $row['last_login_at'], '—');

                return $row;
            },
            $rows
        );
    }

    /**
     * 角色字典。
     */
    public static function roles(): array
    {
        return Db::fetchAll('SELECT id, code, name FROM roles ORDER BY id ASC');
    }

    /**
     * 登录查询：用户名或邮箱均可。返回值包含 password_hash，仅 AuthService 使用。
     */
    public static function findForAuth(string $login): ?array
    {
        $row = Db::fetchOne(
            'SELECT u.id, u.username, u.email, u.password_hash, u.display_name,
                    u.status, u.failed_login_count, u.locked_until,
                    u.must_change_password, u.password_changed_at,
                    r.code AS role_code, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.username = :login_name OR u.email = :login_email
             LIMIT 1',
            ['login_name' => $login, 'login_email' => $login]
        );

        return $row === null ? null : $row;
    }

    /**
     * 按 ID 查询公开资料（不含口令）。
     */
    public static function find(int $id): ?array
    {
        return Db::fetchOne(
            'SELECT u.id, u.username, u.display_name, u.email, u.status,
                    u.must_change_password, r.code AS role_code, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }
}
