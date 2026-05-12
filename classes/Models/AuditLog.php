<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class AuditLog extends BaseModel
{
    public function record(array $data): bool
    {
        return $this->execute(
            'INSERT INTO admin_audit_logs (admin_id, action_type, target_type, target_id, details, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['admin_id'] ?? null,
                $data['action_type'],
                $data['target_type'] ?? null,
                $data['target_id'] ?? null,
                $data['details'] ?? null,
                $data['ip_address'] ?? null,
                $data['user_agent'] ?? null,
            ]
        );
    }

    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT * FROM admin_audit_logs WHERE 1=1';
        $params = [];

        if (!empty($filters['action_type'])) {
            $sql .= ' AND action_type = ?';
            $params[] = $filters['action_type'];
        }

        if (!empty($filters['target_type'])) {
            $sql .= ' AND target_type = ?';
            $params[] = $filters['target_type'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (details LIKE ? OR action_type LIKE ? OR target_type LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->fetchAll($sql, $params);
    }

    public function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM admin_audit_logs WHERE 1=1';
        $params = [];

        if (!empty($filters['action_type'])) {
            $sql .= ' AND action_type = ?';
            $params[] = $filters['action_type'];
        }

        if (!empty($filters['target_type'])) {
            $sql .= ' AND target_type = ?';
            $params[] = $filters['target_type'];
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (details LIKE ? OR action_type LIKE ? OR target_type LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        return (int) $this->fetch($sql, $params)['total'];
    }
}
