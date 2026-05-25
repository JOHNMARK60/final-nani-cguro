<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Inventory;
use App\Security\Auth;

final class InventoryController extends BaseController
{
    private function inventory(): Inventory
    {
        return new Inventory($this->container->pdo());
    }

    public function store(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = $this->inventory()->create($data, Auth::userId());
            $this->container->audit()->log('inventory_create', 'inventory_item', $id, (string) ($data['item_name'] ?? ''));
            $this->backWith('success', 'Inventory item added.', '/E-Parish/views/admin/inventory.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Inventory create failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/inventory.php');
        }
    }

    public function update(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->backWith('error', 'Inventory item ID is missing.', '/E-Parish/views/admin/inventory.php');
            }

            $this->inventory()->update($id, $data);
            $this->container->audit()->log('inventory_update', 'inventory_item', $id, (string) ($data['item_name'] ?? ''));
            $this->backWith('success', 'Inventory item updated.', '/E-Parish/views/admin/inventory.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Inventory update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/inventory.php');
        }
    }

    public function archive(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->backWith('error', 'Inventory item ID is missing.', '/E-Parish/views/admin/inventory.php');
            }

            $this->inventory()->archive($id);
            $this->container->audit()->log('inventory_archive', 'inventory_item', $id, 'Inventory item archived.');
            $this->backWith('success', 'Inventory item archived.', '/E-Parish/views/admin/inventory.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Inventory archive failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/inventory.php');
        }
    }
}
