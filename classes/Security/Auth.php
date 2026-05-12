<?php

declare(strict_types=1);

namespace App\Security;

final class Auth
{
    private static function fail(string $redirect, string $message): void
    {
        $_SESSION['error'] = $message;
        header('Location: ' . $redirect);
        exit;
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::userId() !== null;
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function requireLogin(string $redirect = '/E-Parish/index.php'): void
    {
        if (!self::check()) {
            self::fail($redirect, 'Please sign in to continue.');
        }
    }

    public static function requireRole(string $role, string $redirect = '/E-Parish/index.php'): void
    {
        self::requireLogin($redirect);

        if (self::role() !== $role) {
            self::fail($redirect, 'You do not have permission to access that page.');
        }
    }
}
