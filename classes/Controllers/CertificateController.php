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
    private function normalizeLocation(string $location): string
    {
        return in_array($location, ['Near Parish', 'Far from Parish'], true) ? $location : 'Near Parish';
    }

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

            $requesterLocation = $this->normalizeLocation(trim((string) ($data['requester_location'] ?? 'Near Parish')));

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

    public function update(array $data, array $files): void
    {
        Auth::requireLogin('../../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())->required($data, ['id', 'certificate_type', 'full_name', 'birth_date']);

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '../../../views/user/certificates.php');
            }

            $id = (int) ($data['id'] ?? 0);
            $certificates = new Certificate($this->container->pdo());
            $request = $certificates->findForUser($id, Auth::userId());

            if (!$request || $request['status'] !== 'Pending') {
                $this->backWith('error', 'Only pending certificate requests can be edited.', '../../../views/user/certificates.php');
            }

            $uploads = new UploadService();
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $requesterLocation = $this->normalizeLocation(trim((string) ($data['requester_location'] ?? 'Near Parish')));

            $certificates->updateRequest($id, Auth::userId(), [
                'certificate_type' => trim($data['certificate_type']),
                'full_name' => trim($data['full_name']),
                'birth_date' => trim($data['birth_date']),
                'requester_location' => $requesterLocation,
                'delivery_option' => $requesterLocation === 'Far from Parish' ? 'E-Certificate' : 'Walk-in Pickup',
                'notes' => trim($data['notes'] ?? ''),
                'baptismal_file' => $uploads->store($files['baptismal_file'] ?? [], 'certificates', $allowed),
                'id_file' => $uploads->store($files['id_file'] ?? [], 'certificates', $allowed),
            ]);

            $this->container->audit()->log('certificate_update', 'certificate_request', $id, 'Certificate request updated by owner.');
            $this->backWith('success', 'Certificate request updated.', '../../../views/user/certificates.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Certificate update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '../../../views/user/certificates.php');
        }
    }

    public function destroy(array $data): void
    {
        Auth::requireLogin('../../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->backWith('error', 'Certificate request ID is missing.', '../../../views/user/certificates.php');
            }

            $certificates = new Certificate($this->container->pdo());
            $request = $certificates->findForUser($id, Auth::userId());

            if (!$request || $request['status'] !== 'Pending') {
                $this->backWith('error', 'Only pending certificate requests can be deleted.', '../../../views/user/certificates.php');
            }

            $certificates->deleteRequest($id, Auth::userId());

            $this->container->audit()->log('certificate_delete', 'certificate_request', $id, 'Certificate request deleted by owner.');
            $this->backWith('success', 'Certificate request deleted.', '../../../views/user/certificates.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Certificate delete failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '../../../views/user/certificates.php');
        }
    }
}
