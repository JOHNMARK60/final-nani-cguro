<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use InvalidArgumentException;

final class ParishService extends BaseModel
{
    private const TYPES = ['Certificate', 'Appointment', 'Other'];
    private const STATUSES = ['Active', 'Inactive', 'Archived'];

    public function active(array $filters = []): array
    {
        return $this->rows($filters + ['availability_status' => 'Active'], 100, 0);
    }

    public function rows(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = $this->filterClause($filters);
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        return $this->fetchAll(
            'SELECT *
             FROM parish_services
             ' . $where . '
             ORDER BY FIELD(service_type, "Certificate", "Appointment", "Other"), service_name ASC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );
    }

    public function countRows(array $filters = []): int
    {
        [$where, $params] = $this->filterClause($filters);
        $row = $this->fetch('SELECT COUNT(*) AS total FROM parish_services ' . $where, $params);

        return (int) ($row['total'] ?? 0);
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM parish_services WHERE id = ?', [$id]);
    }

    public function findByName(string $name): ?array
    {
        return $this->fetch(
            'SELECT *
             FROM parish_services
             WHERE service_name = ? AND availability_status = "Active"
             LIMIT 1',
            [trim($name)]
        );
    }

    public function create(array $data, ?int $createdBy = null): int
    {
        $this->validate($data);
        $this->execute(
            'INSERT INTO parish_services
                (service_name, service_type, description, price, required_documents, availability_status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                trim((string) $data['service_name']),
                trim((string) $data['service_type']),
                trim((string) ($data['description'] ?? '')) ?: null,
                (float) $data['price'],
                trim((string) ($data['required_documents'] ?? '')) ?: null,
                trim((string) ($data['availability_status'] ?? 'Active')),
                $createdBy,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->validate($data);

        return $this->execute(
            'UPDATE parish_services
             SET service_name = ?, service_type = ?, description = ?, price = ?, required_documents = ?, availability_status = ?
             WHERE id = ?',
            [
                trim((string) $data['service_name']),
                trim((string) $data['service_type']),
                trim((string) ($data['description'] ?? '')) ?: null,
                (float) $data['price'],
                trim((string) ($data['required_documents'] ?? '')) ?: null,
                trim((string) ($data['availability_status'] ?? 'Active')),
                $id,
            ]
        );
    }

    public function archive(int $id): bool
    {
        return $this->execute('UPDATE parish_services SET availability_status = "Archived" WHERE id = ?', [$id]);
    }

    public function priceFor(string $serviceName, string $serviceType = 'Certificate', float $fallback = 0.0): float
    {
        $row = $this->fetch(
            'SELECT price
             FROM parish_services
             WHERE service_name = ? AND service_type = ? AND availability_status = "Active"
             LIMIT 1',
            [trim($serviceName), trim($serviceType)]
        );

        return $row ? (float) $row['price'] : $fallback;
    }

    public function serviceTypeDistribution(): array
    {
        return $this->fetchAll(
            'SELECT service_type AS label, COUNT(*) AS total
             FROM parish_services
             WHERE availability_status <> "Archived"
             GROUP BY service_type
             ORDER BY service_type ASC'
        );
    }

    private function filterClause(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(service_name LIKE ? OR description LIKE ?)';
            $like = '%' . trim((string) $filters['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['service_type'])) {
            $where[] = 'service_type = ?';
            $params[] = trim((string) $filters['service_type']);
        }

        if (!empty($filters['availability_status'])) {
            $where[] = 'availability_status = ?';
            $params[] = trim((string) $filters['availability_status']);
        } elseif (($filters['include_archived'] ?? false) !== true) {
            $where[] = 'availability_status <> "Archived"';
        }

        return [$where === [] ? '' : 'WHERE ' . implode(' AND ', $where), $params];
    }

    private function validate(array $data): void
    {
        $name = trim((string) ($data['service_name'] ?? ''));
        $type = trim((string) ($data['service_type'] ?? ''));
        $status = trim((string) ($data['availability_status'] ?? 'Active'));
        $price = (float) ($data['price'] ?? -1);

        if ($name === '') {
            throw new InvalidArgumentException('Service name is required.');
        }

        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Invalid service type selected.');
        }

        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid service status selected.');
        }

        if ($price < 0) {
            throw new InvalidArgumentException('Service price cannot be negative.');
        }
    }
}
