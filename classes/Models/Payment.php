<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class Payment extends BaseModel
{
    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO payments
             (user_id, payable_type, payable_id, description, amount, method, reference_number, proof_file, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['payable_type'],
                $data['payable_id'] ?? null,
                $data['description'],
                $data['amount'],
                $data['method'],
                $data['reference_number'] ?? null,
                $data['proof_file'] ?? null,
                $data['status'],
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT p.*, u.fullname AS member_name, u.email AS member_email
             FROM payments p
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.id = ?',
            [$id]
        );
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetch('SELECT * FROM payments WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function verifiedFor(string $payableType, int $payableId, ?int $userId = null): ?array
    {
        $sql = 'SELECT * FROM payments WHERE payable_type = ? AND payable_id = ? AND status = "Verified"';
        $params = [$payableType, $payableId];

        if ($userId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }

        $sql .= ' ORDER BY verified_at DESC, updated_at DESC LIMIT 1';

        return $this->fetch($sql, $params);
    }

    public function forUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    public function submitProof(int $id, int $userId, string $method, ?string $referenceNumber, ?string $proofFile): bool
    {
        return $this->execute(
            'UPDATE payments
             SET method = ?, reference_number = ?, proof_file = COALESCE(?, proof_file), status = ?
             WHERE id = ? AND user_id = ?',
            [$method, $referenceNumber, $proofFile, 'Submitted', $id, $userId]
        );
    }

    public function updateStatus(int $id, string $status, ?int $adminId = null, ?string $remarks = null): bool
    {
        return $this->execute(
            'UPDATE payments
             SET status = ?, remarks = ?, verified_by = ?, verified_at = CASE WHEN ? = "Verified" THEN NOW() ELSE NULL END
             WHERE id = ?',
            [$status, $remarks, $adminId, $status, $id]
        );
    }

    public function queue(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT p.*, u.fullname AS member_name, u.email AS member_email
                FROM payments p
                LEFT JOIN users u ON u.id = p.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (p.description LIKE ? OR p.reference_number LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR p.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['method'])) {
            $sql .= ' AND p.method = ?';
            $params[] = $filters['method'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(p.created_at) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(p.created_at) <= ?';
            $params[] = $filters['to'];
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->fetchAll($sql, $params);
    }

    public function countQueue(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM payments p
                LEFT JOIN users u ON u.id = p.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (p.description LIKE ? OR p.reference_number LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR p.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['method'])) {
            $sql .= ' AND p.method = ?';
            $params[] = $filters['method'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(p.created_at) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(p.created_at) <= ?';
            $params[] = $filters['to'];
        }

        return (int) $this->fetch($sql, $params)['total'];
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

        $sql = 'SELECT COUNT(*) AS total FROM payments';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return (int) $this->fetch($sql, $params)['total'];
    }

    public function sumByStatus(?string $status = null, ?int $userId = null): float
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

        $sql = 'SELECT COALESCE(SUM(amount), 0) AS total FROM payments';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return (float) $this->fetch($sql, $params)['total'];
    }

    public function recent(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));

        return $this->fetchAll(
            "SELECT p.*, u.fullname AS member_name
             FROM payments p
             LEFT JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC
             LIMIT {$limit}"
        );
    }
}
