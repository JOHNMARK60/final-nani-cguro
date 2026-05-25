<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\ParishService;
use App\Security\Auth;

final class ParishServiceController extends BaseController
{
    private function services(): ParishService
    {
        return new ParishService($this->container->pdo());
    }

    public function store(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = $this->services()->create($data, Auth::userId());
            $this->container->audit()->log('service_create', 'parish_service', $id, (string) ($data['service_name'] ?? ''));
            $this->backWith('success', 'Service added.', '/E-Parish/views/admin/services.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Service create failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/services.php');
        }
    }

    public function update(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->backWith('error', 'Service ID is missing.', '/E-Parish/views/admin/services.php');
            }

            $this->services()->update($id, $data);
            $this->container->audit()->log('service_update', 'parish_service', $id, (string) ($data['service_name'] ?? ''));
            $this->backWith('success', 'Service updated.', '/E-Parish/views/admin/services.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Service update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/services.php');
        }
    }

    public function archive(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->backWith('error', 'Service ID is missing.', '/E-Parish/views/admin/services.php');
            }

            $this->services()->archive($id);
            $this->container->audit()->log('service_archive', 'parish_service', $id, 'Service archived.');
            $this->backWith('success', 'Service archived.', '/E-Parish/views/admin/services.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Service archive failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/services.php');
        }
    }
}
