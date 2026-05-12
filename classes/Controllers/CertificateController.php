<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Certificate;
use App\Security\Auth;
use App\Services\UploadService;
use App\Validation\Validator;

final class CertificateController extends BaseController
{
    public function store(array $data, array $files): void
    {
        Auth::requireLogin('../../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())->required($data, ['certificate_type', 'full_name', 'birth_date']);

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '../../../views/user/certificates.php');
            }

            $uploads = new UploadService();
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

            $requesterLocation = trim((string) ($data['requester_location'] ?? 'Near Parish'));

            if (!in_array($requesterLocation, ['Near Parish', 'Far from Parish'], true)) {
                $requesterLocation = 'Near Parish';
            }

            (new Certificate($this->container->pdo()))->request([
                'user_id' => Auth::userId(),
                'certificate_type' => trim($data['certificate_type']),
                'full_name' => trim($data['full_name']),
                'birth_date' => trim($data['birth_date']),
                'requester_location' => $requesterLocation,
                'delivery_option' => $requesterLocation === 'Far from Parish' ? 'E-Certificate' : 'Walk-in Pickup',
                'notes' => trim($data['notes'] ?? ''),
                'baptismal_file' => $uploads->store($files['baptismal_file'] ?? [], 'certificates', $allowed),
                'id_file' => $uploads->store($files['id_file'] ?? [], 'certificates', $allowed),
            ]);

            $this->container->audit()->log('certificate_create', 'certificate_request', null, 'New certificate request submitted.');

            $this->redirect('../../../views/user/certificates.php?success=1');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Certificate request failed', ['error' => $e->getMessage()]);
            $this->redirect('../../../views/user/certificates.php?error=1');
        }
    }
}
