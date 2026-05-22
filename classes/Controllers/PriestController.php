<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Priest;
use App\Security\Auth;

final class PriestController extends BaseController
{
    public function store(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);

            $name = trim((string) ($data['name'] ?? ''));
            $signatureText = trim((string) ($data['signature_text'] ?? ''));

            (new Priest($this->container->pdo()))->create($name, $signatureText !== '' ? $signatureText : null);

            $this->container->audit()->log(
                'priest_create',
                'priest',
                null,
                'Priest added or reactivated: ' . $name
            );

            $this->backWith('success', 'Priest saved.', '/E-Parish/views/admin/certificates.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Priest save failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/certificates.php');
        }
    }
}
