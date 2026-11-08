<?php
/**
 * 文件名：Db.class.php
 * 功能：PDO 连接单例与查询助手；所有 SQL 均通过预处理执行
 * 作者：金暄琛（2411672）
 * 创建日期：2026-11-08
 * 所属模块：数据访问
 *
 * 使用约定：
 *   1) 业务 SQL 只写在 Model / Repository 中，控制器不直接拼 SQL。
 *   2) 表名、排序字段等不能绑定的部分必须使用代码内白名单。
 *   3) 需要多条语句一起成功时使用 transaction()。
 */

declare(strict_types=1);

final class Db
{
    private static ?PDO $pdo = null;

    /**
     * 获取共享 PDO 连接。单请求单连接，避免连接数膨胀。
     */
    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = require APP_ROOT . '/config/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            (int) $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);

        // 统一使用东八区，保证 created_at 与页面展示一致
        self::$pdo->exec("SET time_zone = '" . $config['timezone'] . "'");

        return self::$pdo;
    }

    /**
     * 查询多行。
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * 查询单行；没有结果返回 null。
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * 查询单个标量值。
     */
    public static function fetchValue(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * 执行写操作，返回受影响行数。
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 最近一次 INSERT 的自增 ID。
     */
    public static function lastInsertId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    /**
     * 事务包装：回调正常返回则提交，抛异常则回滚。
     */
    public static function transaction(callable $work): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $work($pdo);
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    private function __construct()
    {
    }
}
