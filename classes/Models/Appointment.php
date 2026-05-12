<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

class Appointment extends BaseModel
{
    public function create(int $userId, string $type, string $date, string $time, ?string $notes): bool
    {
        return $this->execute(
            'INSERT INTO appointments (user_id, appointment_type, appointment_date, appointment_time, notes, status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $type, $date, $time, $notes, 'Pending']
        );
    }

    public function all(): array
    {
        return $this->fetchAll('SELECT * FROM appointments ORDER BY appointment_date DESC');
    }

    public function recent(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));

        return $this->fetchAll(
            "SELECT a.*, u.fullname AS member_name, u.username AS member_username
             FROM appointments a
             LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC
             LIMIT {$limit}"
        );
    }

    public function pending(): array
    {
        return $this->fetchAll(
            "SELECT a.*, u.fullname AS member_name, u.username AS member_username
             FROM appointments a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.status = 'Pending'
             ORDER BY a.created_at DESC"
        );
    }

    public function forUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC',
            [$userId]
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM appointments WHERE id = ?', [$id]);
    }

    public function update(int $id, int $userId, string $type, string $date, string $time, ?string $notes): bool
    {
        return $this->execute(
            'UPDATE appointments
             SET appointment_type = ?, appointment_date = ?, appointment_time = ?, notes = ?
             WHERE id = ? AND user_id = ?',
            [$type, $date, $time, $notes, $id, $userId]
        );
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->execute('UPDATE appointments SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function history(int $appointmentId, string $previous, string $new, ?int $adminId = null, ?string $remarks = null): bool
    {
        return $this->execute(
            'INSERT INTO appointment_status_history (appointment_id, previous_status, new_status, admin_id, remarks)
             VALUES (?, ?, ?, ?, ?)',
            [$appointmentId, $previous, $new, $adminId, $remarks]
        );
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->execute('DELETE FROM appointments WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function countForUser(int $userId): int
    {
        return (int) $this->fetch('SELECT COUNT(*) AS total FROM appointments WHERE user_id = ?', [$userId])['total'];
    }

    public function countByStatus(?string $status = null, ?int $userId = null): int
    {
        $where = [];
        $params = [];

        if ($status !== null) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if ($userId !== null) {
            $where[] = 'user_id = ?';
            $params[] = $userId;
        }

        $sql = 'SELECT COUNT(*) AS total FROM appointments';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return (int) $this->fetch($sql, $params)['total'];
    }

    public function queue(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT a.*, u.fullname AS member_name, u.email AS member_email
                FROM appointments a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (a.appointment_type LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR a.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND a.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(a.appointment_date) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(a.appointment_date) <= ?';
            $params[] = $filters['to'];
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->fetchAll($sql, $params);
    }

    public function countQueue(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM appointments a
                LEFT JOIN users u ON u.id = a.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (a.appointment_type LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR a.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND a.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(a.appointment_date) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(a.appointment_date) <= ?';
            $params[] = $filters['to'];
        }

        return (int) $this->fetch($sql, $params)['total'];
    }

    public function monthlyCounts(int $months = 6): array
    {
        $months = max(1, min($months, 12));

        return $this->fetchAll(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS total
             FROM appointments
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ' . (int) $months . ' MONTH)
             GROUP BY DATE_FORMAT(created_at, "%Y-%m")
             ORDER BY month ASC'
        );
    }
}
