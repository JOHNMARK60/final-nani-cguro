<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class User extends BaseModel
{
    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public function findByEmailOrUsername(string $email, string $username): ?array
    {
        return $this->fetch('SELECT id FROM users WHERE email = ? OR username = ?', [$email, $username]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function findByLogin(string $login): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE email = ? OR username = ?', [$login, $login]);
    }

    public function admins(array $filters = [], int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT u.*, c.fullname AS created_by_name
                FROM users u
                LEFT JOIN users c ON c.id = u.created_by
                WHERE u.role = "admin"';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (u.fullname LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND u.status = ?';
            $params[] = $filters['status'];
        }

        $sql .= ' ORDER BY u.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->fetchAll($sql, $params);
    }

    public function countAdmins(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM users WHERE role = "admin"';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (fullname LIKE ? OR email LIKE ? OR username LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }

        return (int) $this->fetch($sql, $params)['total'];
    }

    public function createAdmin(array $data, ?int $createdBy = null): int
    {
        $this->execute(
            'INSERT INTO users (fullname, email, username, password, role, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['fullname'],
                $data['email'],
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                'admin',
                $data['status'] ?? 'active',
                $createdBy,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateAdmin(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE users SET fullname = ?, email = ?, username = ?, status = ? WHERE id = ? AND role = "admin"',
            [$data['fullname'], $data['email'], $data['username'], $data['status'], $id]
        );
    }

    public function resetAdminPassword(int $id, string $password): bool
    {
        return $this->execute(
            'UPDATE users SET password = ? WHERE id = ? AND role = "admin"',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    public function setAdminStatus(int $id, string $status): bool
    {
        return $this->execute(
            'UPDATE users SET status = ? WHERE id = ? AND role = "admin"',
            [$status, $id]
        );
    }

    public function activeAdminCount(): int
    {
        return (int) $this->fetch("SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND status = 'active'")['total'];
    }

    public function updateLastLogin(int $id): bool
    {
        return $this->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public function register(array $data): int
    {
        $this->execute(
            'INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)',
            [
                $data['fullname'],
                $data['email'],
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['role'] ?? 'user',
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function attemptLogin(string $login, string $password): ?array
    {
        $user = $this->findByLogin($login);

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) {
            return null;
        }

        return $user;
    }

    public function updateProfile(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE users SET fullname = ?, email = ?, username = ?, phone = ?, designation = ? WHERE id = ?',
            [
                $data['fullname'],
                $data['email'],
                $data['username'],
                $data['phone'] ?? null,
                $data['designation'] ?? null,
                $id,
            ]
        );
    }

    public function updateProfilePhoto(int $id, string $fileName): bool
    {
        return $this->execute('UPDATE users SET profile_pic = ? WHERE id = ?', [$fileName, $id]);
    }

    public function updatePassword(int $id, string $password): bool
    {
        return $this->execute('UPDATE users SET password = ? WHERE id = ?', [
            password_hash($password, PASSWORD_DEFAULT),
            $id,
        ]);
    }

    public function createPasswordReset(string $email): ?string
    {
        $user = $this->fetch('SELECT id FROM users WHERE email = ?', [$email]);

        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        $this->execute(
            'UPDATE users SET reset_token_hash = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?',
            [$hash, $user['id']]
        );

        return $token;
    }

    public function resetPassword(string $token, string $password): bool
    {
        $hash = hash('sha256', $token);
        $user = $this->fetch(
            'SELECT id FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()',
            [$hash]
        );

        if (!$user) {
            return false;
        }

        return $this->execute(
            'UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $user['id']]
        );
    }

    public function countAll(): int
    {
        return (int) $this->fetch('SELECT COUNT(*) AS total FROM users')['total'];
    }
}
