<?php

declare(strict_types=1);

namespace App\Security;

use App\Models\User;
use PDO;

final class Authorization
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function requireAdmin(): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');
    }

    public function canAccessUserRecord(int $userId): bool
    {
        return Auth::role() === 'admin' || Auth::userId() === $userId;
    }

    public function userModel(): User
    {
        return new User($this->db);
    }
}
