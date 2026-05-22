<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\FileLogger;
use PDO;

final class MigrationRunner
{
    public static function run(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL UNIQUE,
                executed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $executed = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob(dirname(__DIR__, 2) . '/database/migrations/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $executed, true)) {
                continue;
            }

            $migration = require $file;

            try {
                $pdo->beginTransaction();
                $migration($pdo);
                $stmt = $pdo->prepare('INSERT IGNORE INTO migrations (migration) VALUES (?)');
                $stmt->execute([$name]);

                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;
            }
        }

        self::seedDefaultAdmin($pdo);
    }

    private static function seedDefaultAdmin(PDO $pdo): void
    {
        $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

        if ($adminCount > 0) {
            self::log('Default admin seed skipped: admin account already exists.');
            return;
        }

        $name = trim(Env::get('DEFAULT_ADMIN_NAME', ''));
        $email = trim(Env::get('DEFAULT_ADMIN_EMAIL', ''));
        $password = (string) Env::get('DEFAULT_ADMIN_PASSWORD', '');

        if ($name === '' || $email === '' || $password === '') {
            self::log('Default admin seed skipped: environment variables are incomplete.');
            return;
        }

        $existingEmail = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $existingEmail->execute([$email]);

        if ($existingEmail->fetchColumn()) {
            self::log('Default admin seed skipped: email already exists.');
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO users (fullname, email, username, password, role, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $email,
            strtok($email, '@') ?: 'admin',
            password_hash($password, PASSWORD_DEFAULT),
            'admin',
            'active',
            null,
        ]);

        self::log('Default admin seeded successfully.');
    }

    private static function log(string $message): void
    {
        (new FileLogger(dirname(__DIR__, 2) . '/storage/logs/bootstrap.log'))->info($message);
    }
}
