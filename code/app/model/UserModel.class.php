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
                    r.code AS role_code, r.name AS role_name,
                    m.name AS member_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN members m ON m.user_id = u.id
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

    /**
     * 后台编辑账号所需的完整资料（不含口令）。
     */
    public static function findForAdmin(int $id): ?array
    {
        return Db::fetchOne(
            'SELECT u.id, u.username, u.display_name, u.email, u.role_id, u.status,
                    u.must_change_password, u.last_login_at,
                    r.code AS role_code, r.name AS role_name,
                    m.id AS member_id, m.name AS member_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN members m ON m.user_id = u.id
             WHERE u.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * 尚未绑定账号的成员档案，供新建账号时选择。
     */
    public static function unboundMembers(): array
    {
        return Db::fetchAll(
            'SELECT id, name, role_title
             FROM members
             WHERE user_id IS NULL
             ORDER BY sort_order ASC, id ASC'
        );
    }

    public static function usernameExists(string $username, int $excludeId = 0): bool
    {
        return (int) Db::fetchValue(
            'SELECT COUNT(*) FROM users WHERE username = :username AND id <> :id',
            ['username' => $username, 'id' => $excludeId]
        ) > 0;
    }

    public static function emailExists(string $email, int $excludeId = 0): bool
    {
        return (int) Db::fetchValue(
            'SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id',
            ['email' => $email, 'id' => $excludeId]
        ) > 0;
    }

    public static function roleExists(int $roleId): bool
    {
        return (int) Db::fetchValue('SELECT COUNT(*) FROM roles WHERE id = :id', ['id' => $roleId]) > 0;
    }

    /**
     * 新建账号并可同时绑定一个未使用的成员档案。
     */
    public static function create(array $data, int $memberId = 0): int
    {
        return (int) Db::transaction(static function () use ($data, $memberId): int {
            Db::execute(
                'INSERT INTO users
                    (role_id, username, email, password_hash, display_name, status,
                     must_change_password, password_changed_at)
                 VALUES
                    (:role_id, :username, :email, :password_hash, :display_name, :status,
                     1, NOW())',
                [
                    'role_id' => $data['role_id'],
                    'username' => $data['username'],
                    'email' => $data['email'],
                    'password_hash' => $data['password_hash'],
                    'display_name' => $data['display_name'],
                    'status' => $data['status'],
                ]
            );

            $id = Db::lastInsertId();
            if ($memberId > 0) {
                Db::execute(
                    'UPDATE members SET user_id = :user_id WHERE id = :member_id AND user_id IS NULL',
                    ['user_id' => $id, 'member_id' => $memberId]
                );
            }

            return $id;
        });
    }

    public static function updateAccount(int $id, array $data): void
    {
        Db::execute(
            'UPDATE users SET
                role_id = :role_id,
                username = :username,
                email = :email,
                display_name = :display_name
             WHERE id = :id',
            [
                'role_id' => $data['role_id'],
                'username' => $data['username'],
                'email' => $data['email'],
                'display_name' => $data['display_name'],
                'id' => $id,
            ]
        );
    }

    /**
     * 启用 / 禁用账号；启用时顺带清除失败次数与锁定。
     */
    public static function setStatus(int $id, int $status): void
    {
        Db::execute(
            'UPDATE users SET status = :status, failed_login_count = 0, locked_until = NULL WHERE id = :id',
            ['status' => $status, 'id' => $id]
        );
    }

    /**
     * 重置为临时密码，并使该账号所有旧会话失效。
     */
    public static function resetPassword(int $id, string $passwordHash): void
    {
        Db::execute(
            'UPDATE users SET
                password_hash = :password_hash,
                must_change_password = 1,
                failed_login_count = 0,
                locked_until = NULL,
                password_changed_at = NOW()
             WHERE id = :id',
            ['password_hash' => $passwordHash, 'id' => $id]
        );
    }

    /**
     * 目标账号之外是否还有其他启用中的超级管理员。
     */
    public static function hasOtherActiveSuperAdmin(int $excludeId): bool
    {
        return (int) Db::fetchValue(
            "SELECT COUNT(*)
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.code = 'super_admin' AND u.status = 1 AND u.id <> :id",
            ['id' => $excludeId]
        ) > 0;
    }
}
