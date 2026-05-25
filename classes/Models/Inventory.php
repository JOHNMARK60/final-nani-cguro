<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use InvalidArgumentException;

final class Inventory extends BaseModel
{
    public function categories(): array
    {
        return $this->fetchAll(
            'SELECT *
             FROM inventory_categories
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC'
        );
    }

    public function items(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = $this->filterClause($filters);
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        return $this->fetchAll(
            'SELECT i.*, c.name AS category_name,
                    CASE
                        WHEN i.quantity <= 0 THEN "Out of Stock"
                        WHEN i.quantity <= i.low_stock_threshold THEN "Low Stock"
                        ELSE "In Stock"
                    END AS stock_status
             FROM inventory_items i
             INNER JOIN inventory_categories c ON c.id = i.inventory_category_id
             ' . $where . '
             ORDER BY c.sort_order ASC, c.name ASC, i.item_name ASC
             LIMIT ' . $limit . ' OFFSET ' . $offset,
            $params
        );
    }

    public function countItems(array $filters = []): int
    {
        [$where, $params] = $this->filterClause($filters);
        $row = $this->fetch(
            'SELECT COUNT(*) AS total
             FROM inventory_items i
             INNER JOIN inventory_categories c ON c.id = i.inventory_category_id
             ' . $where,
            $params
        );

        return (int) ($row['total'] ?? 0);
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT i.*, c.name AS category_name
             FROM inventory_items i
             INNER JOIN inventory_categories c ON c.id = i.inventory_category_id
             WHERE i.id = ?',
            [$id]
        );
    }

    public function create(array $data, ?int $createdBy = null): int
    {
        $this->validate($data);
        $this->execute(
            'INSERT INTO inventory_items
                (inventory_category_id, item_name, quantity, unit, price, low_stock_threshold, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, "Active", ?)',
            [
                (int) $data['inventory_category_id'],
                trim((string) $data['item_name']),
                (float) $data['quantity'],
                trim((string) $data['unit']),
                $data['price'] === '' || $data['price'] === null ? null : (float) $data['price'],
                (float) $data['low_stock_threshold'],
                $createdBy,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $this->validate($data);

        return $this->execute(
            'UPDATE inventory_items
             SET inventory_category_id = ?, item_name = ?, quantity = ?, unit = ?, price = ?, low_stock_threshold = ?
             WHERE id = ? AND status = "Active"',
            [
                (int) $data['inventory_category_id'],
                trim((string) $data['item_name']),
                (float) $data['quantity'],
                trim((string) $data['unit']),
                $data['price'] === '' || $data['price'] === null ? null : (float) $data['price'],
                (float) $data['low_stock_threshold'],
                $id,
            ]
        );
    }

    public function archive(int $id): bool
    {
        return $this->execute('UPDATE inventory_items SET status = "Archived" WHERE id = ?', [$id]);
    }

    public function lowStockCount(): int
    {
        return (int) $this->fetch(
            'SELECT COUNT(*) AS total
             FROM inventory_items
             WHERE status = "Active" AND quantity > 0 AND quantity <= low_stock_threshold'
        )['total'];
    }

    public function outOfStockCount(): int
    {
        return (int) $this->fetch(
            'SELECT COUNT(*) AS total
             FROM inventory_items
             WHERE status = "Active" AND quantity <= 0'
        )['total'];
    }

    public function categoryDistribution(): array
    {
        return $this->fetchAll(
            'SELECT c.name AS label, COUNT(i.id) AS total
             FROM inventory_categories c
             LEFT JOIN inventory_items i ON i.inventory_category_id = c.id AND i.status = "Active"
             WHERE c.is_active = 1
             GROUP BY c.id, c.name, c.sort_order
             ORDER BY c.sort_order ASC, c.name ASC'
        );
    }

    private function filterClause(array $filters): array
    {
        $where = ['i.status = "Active"'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = 'i.item_name LIKE ?';
            $params[] = '%' . trim((string) $filters['search']) . '%';
        }

        if (!empty($filters['category']) && $filters['category'] !== 'All') {
            $where[] = 'c.name = ?';
            $params[] = trim((string) $filters['category']);
        }

        if (!empty($filters['stock'])) {
            if ($filters['stock'] === 'Low Stock') {
                $where[] = 'i.quantity > 0 AND i.quantity <= i.low_stock_threshold';
            } elseif ($filters['stock'] === 'Out of Stock') {
                $where[] = 'i.quantity <= 0';
            }
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    private function validate(array $data): void
    {
        $categoryId = (int) ($data['inventory_category_id'] ?? 0);
        $name = trim((string) ($data['item_name'] ?? ''));
        $unit = trim((string) ($data['unit'] ?? ''));
        $quantity = (float) ($data['quantity'] ?? -1);
        $threshold = (float) ($data['low_stock_threshold'] ?? -1);

        if ($categoryId <= 0 || !$this->fetch('SELECT id FROM inventory_categories WHERE id = ? AND is_active = 1', [$categoryId])) {
            throw new InvalidArgumentException('Please select a valid inventory category.');
        }

        if ($name === '') {
            throw new InvalidArgumentException('Item name is required.');
        }

        if ($unit === '') {
            throw new InvalidArgumentException('Unit is required.');
        }

        if ($quantity < 0 || $threshold < 0) {
            throw new InvalidArgumentException('Quantity and low stock threshold cannot be negative.');
        }
    }
}
