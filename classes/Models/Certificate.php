<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use InvalidArgumentException;

final class Certificate extends BaseModel
{
    public function request(array $data): bool
    {
        return $this->execute(
            'INSERT INTO certificate_requests
             (user_id, certificate_type_id, full_name, birth_date, requester_location, delivery_option, notes, baptismal_file, id_file, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $this->certificateTypeId((string) $data['certificate_type']),
                $data['full_name'],
                $data['birth_date'],
                $data['requester_location'] ?? 'Near Parish',
                $data['delivery_option'] ?? 'Walk-in Pickup',
                $data['notes'] ?? null,
                $data['baptismal_file'] ?? null,
                $data['id_file'] ?? null,
                'Pending',
            ]
        );
    }

    public function forUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT c.*, t.name AS certificate_type
             FROM certificate_requests c
             INNER JOIN certificate_types t ON t.id = c.certificate_type_id
             WHERE c.user_id = ?
             ORDER BY c.created_at DESC',
            [$userId]
        );
    }

    public function forUserWithDigital(int $userId): array
    {
        return $this->fetchAll(
            'SELECT c.*, request_type.name AS certificate_type,
                    d.id AS digital_certificate_id, d.certificate_number, d.delivery_mode,
                    d.issued_at, d.status AS digital_status,
                    digital_type.name AS digital_certificate_type,
                    vp.id AS verified_payment_id
             FROM certificate_requests c
             INNER JOIN certificate_types request_type ON request_type.id = c.certificate_type_id
             LEFT JOIN digital_certificates d ON d.certificate_request_id = c.id
             LEFT JOIN certificate_types digital_type ON digital_type.id = d.certificate_type_id
             LEFT JOIN payments vp ON vp.certificate_request_id = c.id
                AND vp.user_id = c.user_id
                AND vp.status = "Verified"
             WHERE c.user_id = ?
             ORDER BY c.created_at DESC',
            [$userId]
        );
    }

    public function findWithMember(int $id): ?array
    {
        return $this->fetch(
            'SELECT c.*, t.name AS certificate_type, u.email AS member_email, u.fullname AS member_name
             FROM certificate_requests c
             INNER JOIN certificate_types t ON t.id = c.certificate_type_id
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.id = ?',
            [$id]
        );
    }

    public function recent(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));

        return $this->fetchAll(
            "SELECT c.*, t.name AS certificate_type
             FROM certificate_requests c
             INNER JOIN certificate_types t ON t.id = c.certificate_type_id
             ORDER BY c.created_at DESC
             LIMIT {$limit}"
        );
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

        $sql = 'SELECT COUNT(*) AS total FROM certificate_requests';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return (int) $this->fetch($sql, $params)['total'];
    }

    public function queue(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT c.*, t.name AS certificate_type, u.email AS member_email, u.fullname AS member_name,
                    d.certificate_number, d.delivery_mode, d.issued_at, d.status AS digital_status
                FROM certificate_requests c
                INNER JOIN certificate_types t ON t.id = c.certificate_type_id
                LEFT JOIN users u ON u.id = c.user_id
                LEFT JOIN digital_certificates d ON d.certificate_request_id = c.id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (c.full_name LIKE ? OR t.name LIKE ? OR u.email LIKE ? OR c.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(c.created_at) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(c.created_at) <= ?';
            $params[] = $filters['to'];
        }

        $sql .= ' ORDER BY c.created_at DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->fetchAll($sql, $params);
    }

    public function countQueue(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM certificate_requests c
                INNER JOIN certificate_types t ON t.id = c.certificate_type_id
                LEFT JOIN users u ON u.id = c.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (c.full_name LIKE ? OR t.name LIKE ? OR u.email LIKE ? OR c.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND DATE(c.created_at) >= ?';
            $params[] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND DATE(c.created_at) <= ?';
            $params[] = $filters['to'];
        }

        return (int) $this->fetch($sql, $params)['total'];
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->execute('UPDATE certificate_requests SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function history(int $requestId, string $previous, string $new, ?int $adminId = null, ?string $remarks = null): bool
    {
        return $this->execute(
            'INSERT INTO certificate_status_history (certificate_request_id, previous_status, new_status, admin_id, remarks)
             VALUES (?, ?, ?, ?, ?)',
            [$requestId, $previous, $new, $adminId, $remarks]
        );
    }

    public function digitalForRequest(int $requestId): ?array
    {
        return $this->fetch(
            'SELECT d.*, digital_type.name AS certificate_type,
                    c.user_id, c.status AS request_status, c.full_name,
                    request_type.name AS request_certificate_type
             FROM digital_certificates d
             INNER JOIN certificate_requests c ON c.id = d.certificate_request_id
             INNER JOIN certificate_types digital_type ON digital_type.id = d.certificate_type_id
             INNER JOIN certificate_types request_type ON request_type.id = c.certificate_type_id
             WHERE d.certificate_request_id = ?',
            [$requestId]
        );
    }

    public function issueDigital(array $data): int
    {
        $certificateTypeId = $this->certificateTypeId((string) $data['certificate_type']);
        $params = [
            $data['certificate_number'],
            $certificateTypeId,
            $data['delivery_mode'],
            $data['church_name'],
            $data['parish_address'] ?? null,
            $data['recipient_name'],
            $data['parent_names'] ?? null,
            $data['birth_date'] ?? null,
            $data['event_date'] ?? null,
            $data['event_place'] ?? null,
            $data['officiant'] ?? null,
            $data['sponsors_witnesses'] ?? null,
            $data['book_no'] ?? null,
            $data['page_no'] ?? null,
            $data['remarks'] ?? null,
            $data['qr_reference'],
            $data['issued_by'] ?? null,
        ];

        $existing = $this->fetch('SELECT id FROM digital_certificates WHERE certificate_request_id = ?', [(int) $data['certificate_request_id']]);

        if ($existing) {
            $this->execute(
                'UPDATE digital_certificates
                 SET certificate_number = ?, certificate_type_id = ?, delivery_mode = ?, church_name = ?, parish_address = ?,
                     recipient_name = ?, parent_names = ?, birth_date = ?, event_date = ?, event_place = ?,
                     officiant = ?, sponsors_witnesses = ?, book_no = ?, page_no = ?, remarks = ?,
                     qr_reference = ?, status = "Issued", issued_by = ?, issued_at = NOW()
                 WHERE certificate_request_id = ?',
                [...$params, $data['certificate_request_id']]
            );

            return (int) $existing['id'];
        }

        $this->execute(
            'INSERT INTO digital_certificates
             (certificate_number, certificate_type_id, delivery_mode, church_name, parish_address,
              recipient_name, parent_names, birth_date, event_date, event_place, officiant, sponsors_witnesses,
              book_no, page_no, remarks, qr_reference, status, issued_by, issued_at, certificate_request_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "Issued", ?, NOW(), ?)',
            [...$params, $data['certificate_request_id']]
        );

        $row = $this->fetch('SELECT id FROM digital_certificates WHERE certificate_request_id = ?', [(int) $data['certificate_request_id']]);

        return (int) ($row['id'] ?? 0);
    }

    public function monthlyCounts(int $months = 6): array
    {
        $months = max(1, min($months, 12));

        return $this->fetchAll(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS month, COUNT(*) AS total
             FROM certificate_requests
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ' . (int) $months . ' MONTH)
             GROUP BY DATE_FORMAT(created_at, "%Y-%m")
             ORDER BY month ASC'
        );
    }

    private function certificateTypeId(string $name): int
    {
        $row = $this->fetch(
            'SELECT id FROM certificate_types WHERE name = ? AND is_active = 1',
            [trim($name)]
        );

        if (!$row) {
            throw new InvalidArgumentException('Invalid certificate type selected.');
        }

        return (int) $row['id'];
    }
}
