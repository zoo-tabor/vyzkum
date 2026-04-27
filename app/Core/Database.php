<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?Config $config = null;
    private static ?PDO $pdo = null;

    public static function configure(Config $config): void
    {
        self::$config = $config;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        if (self::$config === null) {
            throw new \RuntimeException('Database is not configured.');
        }

        $host = self::$config->get('DB_HOST', '127.0.0.1');
        $port = self::$config->get('DB_PORT', '3306');
        $db = self::$config->get('DB_DATABASE', '');
        $charset = 'utf8mb4';
        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        self::$pdo = new PDO($dsn, (string) self::$config->get('DB_USERNAME', ''), (string) self::$config->get('DB_PASSWORD', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$pdo;
    }
}
