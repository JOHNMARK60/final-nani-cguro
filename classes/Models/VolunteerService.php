<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use InvalidArgumentException;

final class VolunteerService extends BaseModel
{
    private const ELIGIBLE_STATUSES = ['Approved', 'Verified'];

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

    public function approvedHoursForUser(int $userId): float
    {
        $row = $this->fetch(
            'SELECT COALESCE(SUM(hours_served), 0) AS total
             FROM volunteer_service
             WHERE user_id = ? AND status IN ("Approved", "Verified")',
            [$userId]
        );

        return (float) ($row['total'] ?? 0);
    }

    public function isEligible(int $userId): bool
    {
        return $this->approvedHoursForUser($userId) >= $this->requiredHours();
    }

    public function markEligibleIfQualified(int $userId): bool
    {
        if (!$this->isEligible($userId)) {
            return false;
        }

        (new User($this->db))->markActiveVolunteer($userId);

        return true;
    }

    public function eligibilitySummary(int $userId): array
    {
        $approvedHours = $this->approvedHoursForUser($userId);
        $requiredHours = $this->requiredHours();
        $isEligible = $approvedHours >= $requiredHours;

        if ($isEligible) {
            (new User($this->db))->markActiveVolunteer($userId);
        }

        return [
            'approved_hours' => $approvedHours,
            'required_hours' => $requiredHours,
            'is_eligible' => $isEligible,
            'remaining_hours' => max(0, $requiredHours - $approvedHours),
        ];
    }

    public function eligibleVolunteerCount(): int
    {
        return (int) $this->fetch(
            'SELECT COUNT(*) AS total
             FROM users
             WHERE active_volunteer = 1 AND status = "active"'
        )['total'];
    }

    public function eligibilityDistribution(): array
    {
        return $this->fetchAll(
            'SELECT CASE WHEN active_volunteer = 1 THEN "Eligible / Active" ELSE "Not Eligible" END AS label,
                    COUNT(*) AS total
             FROM users
             WHERE role = "user"
             GROUP BY active_volunteer
             ORDER BY active_volunteer DESC'
        );
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

    private function requiredHours(): float
    {
        return (new AppSetting($this->db))->volunteerRequiredHours();
    }
}
