<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Certificate;
use App\Models\User;
use App\Security\Auth;
use App\Services\MailTemplate;

final class AdminCertificateController extends BaseController
{
    private const DELIVERY_MODES = ['Walk-in Pickup', 'E-Certificate'];

    private function certificates(): Certificate
    {
        return new Certificate($this->container->pdo());
    }

    public function updateStatus(array $data, string $status): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);
            $status = trim($status);
            $remarks = trim((string) ($data['remarks'] ?? ''));

            if ($id <= 0) {
                $this->backWith('error', 'Certificate request ID is missing.', '/E-Parish/views/admin/certificates.php');
            }

            $row = $this->container->pdo()->prepare('SELECT * FROM certificate_requests WHERE id = ?');
            $row->execute([$id]);
            $request = $row->fetch();

            if (!$request) {
                $this->backWith('error', 'Certificate request not found.', '/E-Parish/views/admin/certificates.php');
            }

            if (!in_array($status, ['Approved', 'Rejected', 'Pending'], true)) {
                $this->backWith('error', 'Invalid certificate status.', '/E-Parish/views/admin/certificates.php');
            }

            $previous = $request['status'];
            $this->certificates()->updateStatus($id, $status);
            $this->certificates()->history($id, $previous, $status, Auth::userId(), $remarks !== '' ? $remarks : null);
            $this->container->audit()->log(
                'certificate_status_change',
                'certificate_request',
                $id,
                $previous . ' => ' . $status . ($remarks !== '' ? ' | ' . $remarks : '')
            );

            if (!empty($request['member_email'])) {
                $body = MailTemplate::simple(
                    'Certificate Request Update',
                    'Hello ' . ($request['member_name'] ?? 'Member') . ",\n\nYour certificate request has been updated to: {$status}." . ($remarks !== '' ? "\n\nRemarks: {$remarks}" : ''),
                    'Open E-Parish',
                    '/E-Parish/views/user/certificates.php'
                );
                $this->container->mailer()->send((string) $request['member_email'], 'E-Parish Certificate Update', $body);
            }

            $this->backWith('success', 'Certificate request updated.', '/E-Parish/views/admin/certificates.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Admin certificate update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/certificates.php');
        }
    }

    public function issue(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);

            $id = (int) ($data['id'] ?? 0);
            $request = $this->certificates()->findWithMember($id);

            if (!$request) {
                $this->backWith('error', 'Certificate request not found.', '/E-Parish/views/admin/certificates.php');
            }

            $deliveryMode = trim((string) ($data['delivery_mode'] ?? $request['delivery_option'] ?? 'Walk-in Pickup'));

            if (!in_array($deliveryMode, self::DELIVERY_MODES, true)) {
                $deliveryMode = 'Walk-in Pickup';
            }

            $certificateType = trim((string) ($data['certificate_type'] ?? $request['certificate_type']));
            $certificateNumber = trim((string) ($data['certificate_number'] ?? ''));

            if ($certificateNumber === '') {
                $certificateNumber = 'EP-' . date('Y') . '-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
            }

            $recipientName = trim((string) ($data['recipient_name'] ?? $request['full_name']));

            if ($recipientName === '') {
                $this->backWith('error', 'Recipient name is required.', '/E-Parish/views/admin/certificates.php');
            }

            $digitalId = $this->certificates()->issueDigital([
                'certificate_request_id' => $id,
                'certificate_number' => $certificateNumber,
                'certificate_type' => $certificateType,
                'delivery_mode' => $deliveryMode,
                'church_name' => trim((string) ($data['church_name'] ?? 'E-Parish Church')),
                'parish_address' => trim((string) ($data['parish_address'] ?? 'Parish Office')),
                'recipient_name' => $recipientName,
                'parent_names' => trim((string) ($data['parent_names'] ?? '')),
                'birth_date' => trim((string) ($data['birth_date'] ?? $request['birth_date'] ?? '')) ?: null,
                'event_date' => trim((string) ($data['event_date'] ?? '')) ?: null,
                'event_place' => trim((string) ($data['event_place'] ?? '')),
                'officiant' => trim((string) ($data['officiant'] ?? '')),
                'sponsors_witnesses' => trim((string) ($data['sponsors_witnesses'] ?? '')),
                'book_no' => trim((string) ($data['book_no'] ?? '')),
                'page_no' => trim((string) ($data['page_no'] ?? '')),
                'remarks' => trim((string) ($data['remarks'] ?? '')),
                'qr_reference' => hash('sha256', $certificateNumber . '|' . $id . '|' . ($_ENV['APP_KEY'] ?? 'e-parish')),
                'issued_by' => Auth::userId(),
            ]);

            $previous = (string) $request['status'];

            if ($previous !== 'Approved') {
                $this->certificates()->updateStatus($id, 'Approved');
                $this->certificates()->history($id, $previous, 'Approved', Auth::userId(), 'Certificate issued by admin.');
            }

            $this->container->audit()->log(
                'digital_certificate_issue',
                'digital_certificate',
                $digitalId,
                $certificateNumber . ' issued as ' . $deliveryMode
            );

            $message = $deliveryMode === 'E-Certificate'
                ? 'E-certificate issued. The user can view it after verified payment.'
                : 'Walk-in certificate prepared. The user should claim it at the parish office.';

            $this->backWith('success', $message, '/E-Parish/views/admin/certificates.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Digital certificate issue failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/certificates.php');
        }
    }
}
