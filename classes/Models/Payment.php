<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use InvalidArgumentException;

final class Payment extends BaseModel
{
    private const DISCOUNTABLE_CERTIFICATES = [
        'Baptismal Certificate',
        'Marriage Certificate',
        'Confirmation Certificate',
        'Death Certificate',
    ];

    private const PAYMENT_SELECT = "p.*, pm.name AS method, pc.name AS payable_type,
        CASE
            WHEN pc.name = 'Certificate' THEN p.certificate_request_id
            WHEN pc.name = 'Appointment' THEN p.appointment_id
            ELSE NULL
        END AS payable_id";

    public function create(array $data): int
    {
        $payableType = (string) $data['payable_type'];
        $certificateRequestId = $payableType === 'Certificate' && !empty($data['payable_id']) ? (int) $data['payable_id'] : null;
        $appointmentId = $payableType === 'Appointment' && !empty($data['payable_id']) ? (int) $data['payable_id'] : null;

        $this->execute(
            'INSERT INTO payments
             (user_id, payment_category_id, certificate_request_id, appointment_id, description,
              amount, original_amount, discount_percent, discount_amount, payment_method_id, reference_number, proof_file, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $this->paymentCategoryId($payableType),
                $certificateRequestId,
                $appointmentId,
                $data['description'],
                $data['amount'],
                $data['original_amount'] ?? $data['amount'],
                $data['discount_percent'] ?? 0,
                $data['discount_amount'] ?? 0,
                $this->paymentMethodId((string) $data['method']),
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
            'SELECT ' . self::PAYMENT_SELECT . ', u.fullname AS member_name, u.email AS member_email
             FROM payments p
             INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
             INNER JOIN payment_categories pc ON pc.id = p.payment_category_id
             LEFT JOIN users u ON u.id = p.user_id
             WHERE p.id = ?',
            [$id]
        );
    }

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->fetch(
            'SELECT ' . self::PAYMENT_SELECT . '
             FROM payments p
             INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
             INNER JOIN payment_categories pc ON pc.id = p.payment_category_id
             WHERE p.id = ? AND p.user_id = ?',
            [$id, $userId]
        );
    }

    public function verifiedFor(string $payableType, int $payableId, ?int $userId = null): ?array
    {
        $targetColumn = match ($payableType) {
            'Certificate' => 'p.certificate_request_id',
            'Appointment' => 'p.appointment_id',
            default => null,
        };

        if ($targetColumn === null) {
            return null;
        }

        $sql = 'SELECT ' . self::PAYMENT_SELECT . '
                FROM payments p
                INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
                INNER JOIN payment_categories pc ON pc.id = p.payment_category_id
                WHERE pc.name = ? AND ' . $targetColumn . ' = ? AND p.status = "Verified"';
        $params = [$payableType, $payableId];

        if ($userId !== null) {
            $sql .= ' AND p.user_id = ?';
            $params[] = $userId;
        }

        $sql .= ' ORDER BY p.verified_at DESC, p.updated_at DESC LIMIT 1';

        return $this->fetch($sql, $params);
    }

    public function calculateCertificateCharge(int $certificateRequestId, int $userId): array
    {
        $request = $this->fetch(
            'SELECT c.id, c.user_id, c.full_name, t.name AS certificate_type
             FROM certificate_requests c
             INNER JOIN certificate_types t ON t.id = c.certificate_type_id
             WHERE c.id = ? AND c.user_id = ?
             LIMIT 1',
            [$certificateRequestId, $userId]
        );

        if (!$request) {
            throw new InvalidArgumentException('Certificate request not found for this account.');
        }

        $certificateType = (string) $request['certificate_type'];
        $originalAmount = (new ParishService($this->db))->priceFor($certificateType, 'Certificate', 150.00);
        $isDiscountable = in_array($certificateType, self::DISCOUNTABLE_CERTIFICATES, true);
        $eligible = (new VolunteerService($this->db))->eligibilitySummary($userId)['is_eligible'] ?? false;
        $discountPercent = $isDiscountable && $eligible ? 10.0 : 0.0;
        $discountAmount = round($originalAmount * ($discountPercent / 100), 2);
        $finalAmount = max(0, round($originalAmount - $discountAmount, 2));

        return [
            'certificate_type' => $certificateType,
            'full_name' => (string) $request['full_name'],
            'original_amount' => $originalAmount,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'is_eligible' => (bool) $eligible,
            'is_discountable' => $isDiscountable,
        ];
    }

    public function forUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT ' . self::PAYMENT_SELECT . '
             FROM payments p
             INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
             INNER JOIN payment_categories pc ON pc.id = p.payment_category_id
             WHERE p.user_id = ?
             ORDER BY p.created_at DESC',
            [$userId]
        );
    }

    public function submitProof(int $id, int $userId, string $method, ?string $referenceNumber, ?string $proofFile): bool
    {
        return $this->execute(
            'UPDATE payments
             SET payment_method_id = ?, reference_number = ?, proof_file = COALESCE(?, proof_file), status = ?
             WHERE id = ? AND user_id = ?',
            [$this->paymentMethodId($method), $referenceNumber, $proofFile, 'Submitted', $id, $userId]
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
        $sql = 'SELECT ' . self::PAYMENT_SELECT . ', u.fullname AS member_name, u.email AS member_email
                FROM payments p
                INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
                INNER JOIN payment_categories pc ON pc.id = p.payment_category_id
                LEFT JOIN users u ON u.id = p.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (p.description LIKE ? OR p.reference_number LIKE ? OR pc.name LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR p.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['method'])) {
            $sql .= ' AND pm.name = ?';
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
                INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
                INNER JOIN payment_categories pc ON pc.id = p.payment_category_id
                LEFT JOIN users u ON u.id = p.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (p.description LIKE ? OR p.reference_number LIKE ? OR pc.name LIKE ? OR u.fullname LIKE ? OR u.email LIKE ? OR p.id LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['method'])) {
            $sql .= ' AND pm.name = ?';
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
            "SELECT " . self::PAYMENT_SELECT . ", u.fullname AS member_name
             FROM payments p
             INNER JOIN payment_methods pm ON pm.id = p.payment_method_id
             INNER JOIN payment_categories pc ON pc.id = p.payment_category_id
             LEFT JOIN users u ON u.id = p.user_id
             ORDER BY p.created_at DESC
             LIMIT {$limit}"
        );
    }

    public function statusDistribution(): array
    {
        return $this->fetchAll(
            'SELECT status AS label, COUNT(*) AS total
             FROM payments
             GROUP BY status
             ORDER BY status ASC'
        );
    }

    private function paymentMethodId(string $name): int
    {
        $row = $this->fetch(
            'SELECT id FROM payment_methods WHERE name = ? AND is_active = 1',
            [trim($name)]
        );

        if (!$row) {
            throw new InvalidArgumentException('Invalid payment method selected.');
        }

        return (int) $row['id'];
    }

    private function paymentCategoryId(string $name): int
    {
        $row = $this->fetch(
            'SELECT id FROM payment_categories WHERE name = ? AND is_active = 1',
            [trim($name)]
        );

        if (!$row) {
            throw new InvalidArgumentException('Invalid payment category selected.');
        }

        return (int) $row['id'];
    }
}
