<?php
declare(strict_types=1);
namespace App\Core;

use PDO;
final class Database
{
    private static ?PDO $connection = null;
    public static function connect(): PDO
    {
        if (self::$connection) return self::$connection;
        if (env('DB_DRIVER', 'mysql') === 'sqlite') {
            $path = env('DB_SQLITE_PATH', BASE_PATH . '/storage/local.sqlite');
            if (!is_dir(dirname($path))) mkdir(dirname($path), 0770, true);
            $pdo = new PDO('sqlite:' . $path);
            $pdo->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000; PRAGMA journal_mode = WAL;');
        } else {
            $name = env('DB_DATABASE', 'commerce_pulse');
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) throw new \RuntimeException('Invalid database name');
            $dsn = 'mysql:host=' . env('DB_HOST','127.0.0.1') . ';port=' . env('DB_PORT','3306') . ';dbname=' . $name . ';charset=utf8mb4';
            $pdo = new PDO($dsn, env('DB_USERNAME','pulse'), env('DB_PASSWORD'), [PDO::ATTR_EMULATE_PREPARES=>false]);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return self::$connection = $pdo;
    }
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connect()->prepare($sql);
        foreach (array_values($params) as $i=>$value) $stmt->bindValue($i+1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $stmt->execute();
        return $stmt;
    }
    public static function lock(): string { return self::connect()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : ''; }
}
