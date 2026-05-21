<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use InvalidArgumentException;

final class VolunteerService extends BaseModel
{
    public function create(array $data): bool
    {
        return $this->execute(
            'INSERT INTO volunteer_service (user_id, volunteer_activity_id, activity_date, hours_served, notes, status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $this->activityId((string) $data['activity_name']),
                $data['activity_date'],
                $data['hours_served'],
                $data['notes'] ?? null,
                'Pending',
            ]
        );
    }

    public function forUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT v.*, a.name AS activity_name
             FROM volunteer_service v
             INNER JOIN volunteer_activities a ON a.id = v.volunteer_activity_id
             WHERE v.user_id = ?
             ORDER BY v.activity_date DESC',
            [$userId]
        );
    }

    public function all(): array
    {
        return $this->fetchAll(
            'SELECT v.*, a.name AS activity_name
             FROM volunteer_service v
             INNER JOIN volunteer_activities a ON a.id = v.volunteer_activity_id
             ORDER BY v.created_at DESC'
        );
    }

    public function pending(): array
    {
        return $this->fetchAll(
            "SELECT v.*, a.name AS activity_name
             FROM volunteer_service v
             INNER JOIN volunteer_activities a ON a.id = v.volunteer_activity_id
             WHERE v.status = 'Pending'
             ORDER BY v.created_at DESC"
        );
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->execute('UPDATE volunteer_service SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function countForUser(int $userId): int
    {
        return (int) $this->fetch('SELECT COUNT(*) AS total FROM volunteer_service WHERE user_id = ?', [$userId])['total'];
    }

    public function queue(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT v.*, a.name AS activity_name, u.fullname AS member_name, u.email AS member_email
                FROM volunteer_service v
                INNER JOIN volunteer_activities a ON a.id = v.volunteer_activity_id
                LEFT JOIN users u ON u.id = v.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (a.name LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR v.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND v.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(v.activity_date) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(v.activity_date) <= ?';
            $params[] = $filters['to'];
        }

        $sql .= ' ORDER BY v.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->fetchAll($sql, $params);
    }

    public function countQueue(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM volunteer_service v
                INNER JOIN volunteer_activities a ON a.id = v.volunteer_activity_id
                LEFT JOIN users u ON u.id = v.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (a.name LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR v.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND v.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(v.activity_date) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(v.activity_date) <= ?';
            $params[] = $filters['to'];
        }

        return (int) $this->fetch($sql, $params)['total'];
    }

    public function monthlyCounts(int $months = 6): array
    {
        $months = max(1, min($months, 12));

        return $this->fetchAll(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS total
             FROM volunteer_service
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ' . (int) $months . ' MONTH)
             GROUP BY DATE_FORMAT(created_at, "%Y-%m")
             ORDER BY month ASC'
        );
    }

    private function activityId(string $name): int
    {
        $row = $this->fetch(
            'SELECT id FROM volunteer_activities WHERE name = ? AND is_active = 1',
            [trim($name)]
        );

        if (!$row) {
            throw new InvalidArgumentException('Invalid volunteer activity selected.');
        }

        return (int) $row['id'];
    }
}
