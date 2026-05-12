<?php

declare(strict_types=1);

use App\Models\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRegistrationAndLoginWorkflow(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                fullname TEXT,
                email TEXT,
                username TEXT,
                password TEXT,
                role TEXT,
                phone TEXT NULL,
                designation TEXT NULL,
                profile_pic TEXT NULL,
                reset_token_hash TEXT NULL,
                reset_token_expires_at TEXT NULL
            )'
        );

        $users = new User($pdo);
        $id = $users->register([
            'fullname' => 'Test Member',
            'email' => 'member@example.com',
            'username' => 'member',
            'password' => 'Strong#123',
        ]);

        $this->assertGreaterThan(0, $id);
        $this->assertNotNull($users->attemptLogin('member', 'Strong#123'));
        $this->assertNull($users->attemptLogin('member', 'wrong-password'));
    }
}
