<?php
// 数据库连接助手（SQLite 单例）

namespace App\Libraries;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $path = config('db.path');
            if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA journal_mode=WAL');
            $pdo->exec('PRAGMA busy_timeout=5000');
            $pdo->exec('PRAGMA foreign_keys=ON');
            self::$pdo = $pdo;
        }
        return self::$pdo;
    }

    public static function q(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::q($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::q($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = implode(',', array_keys($data));
        $ph   = implode(',', array_fill(0, count($data), '?'));
        self::q("INSERT INTO {$table} ({$cols}) VALUES ({$ph})", array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = implode(',', array_map(fn($k) => "{$k}=?", array_keys($data)));
        $stmt = self::q("UPDATE {$table} SET {$sets} WHERE {$where}", array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }
}
