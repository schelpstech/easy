<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = Config::get('DB_HOST', '127.0.0.1');
        $port = Config::get('DB_PORT', '3306');
        $database = Config::get('DB_NAME', 'easyway_logistics');
        $username = Config::get('DB_USER', 'root');
        $password = Config::get('DB_PASSWORD', '');

        try {
            self::$connection = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $database),
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
        } catch (PDOException $exception) {
            error_log('Easyway database connection failed: ' . $exception->getMessage());
            throw new RuntimeException('The logistics database is temporarily unavailable.');
        }

        return self::$connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}

