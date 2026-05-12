<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    public static function connection(): PDO
    {
        $host = Env::get('DB_HOST', 'localhost');
        $name = Env::get('DB_DATABASE', 'eparish_db');
        $user = Env::get('DB_USERNAME', 'root');
        $pass = Env::get('DB_PASSWORD', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        $server = new PDO(
            "mysql:host={$host};charset={$charset}",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $server->exec(
            "CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci"
        );

        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset={$charset}",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        MigrationRunner::run($pdo);

        return $pdo;
    }
}
