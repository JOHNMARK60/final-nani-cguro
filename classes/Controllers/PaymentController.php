<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Payment;
use App\Security\Auth;
use App\Services\UploadService;

final class PaymentController extends BaseController
{
    private const METHODS = ['Cash', 'GCash', 'Bank Transfer'];
    private const PAYABLE_TYPES = ['Certificate', 'Appointment', 'General'];
    private const ADMIN_STATUSES = ['Unpaid', 'Submitted', 'Verified', 'Rejected'];

    private function payments(): Payment
    {
        return new Payment($this->container->pdo());
    }

    public function store(array $data, array $files): void
    {
        Auth::requireLogin('/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);

            $description = trim((string) ($data['description'] ?? ''));
            $amount = (float) ($data['amount'] ?? 0);
            $method = trim((string) ($data['method'] ?? 'GCash'));
            $payableType = trim((string) ($data['payable_type'] ?? 'General'));
            $payableId = !empty($data['payable_id']) ? (int) $data['payable_id'] : null;

            if ($payableType !== 'Certificate' && ($description === '' || $amount <= 0)) {
                $this->backWith('error', 'Please enter a payment description and valid Peso amount.', '/E-Parish/views/user/payments.php');
            }

            if (!in_array($method, self::METHODS, true) || !in_array($payableType, self::PAYABLE_TYPES, true)) {
                $this->backWith('error', 'Invalid payment option selected.', '/E-Parish/views/user/payments.php');
            }

            $originalAmount = $amount;
            $discountPercent = 0.0;
            $discountAmount = 0.0;

            if ($payableType === 'Certificate') {
                if ($payableId === null) {
                    $this->backWith('error', 'Please choose a certificate request before creating a certificate payment.', '/E-Parish/views/user/payments.php');
                }

                $charge = $this->payments()->calculateCertificateCharge($payableId, (int) Auth::userId());
                $description = $charge['certificate_type'] . ' - ' . $charge['full_name'];
                $originalAmount = (float) $charge['original_amount'];
                $discountPercent = (float) $charge['discount_percent'];
                $discountAmount = (float) $charge['discount_amount'];
                $amount = (float) $charge['final_amount'];
            }

            $id = $this->payments()->create([
                'user_id' => Auth::userId(),
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'description' => $description,
                'amount' => $amount,
                'original_amount' => $originalAmount,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'method' => $method,
                'reference_number' => null,
                'proof_file' => null,
                'status' => 'Unpaid',
            ]);

            $this->container->audit()->log('payment_create', 'payment', $id, 'Payment record created in PHP/Peso.');
            $this->backWith('success', 'Payment record created. Pay first, then upload your reference number and proof.', '/E-Parish/views/user/payments.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Payment submission failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/user/payments.php');
        }
    }

    public function uploadProof(array $data, array $files): void
    {
        Auth::requireLogin('/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);

            $id = (int) ($data['id'] ?? 0);
            $method = trim((string) ($data['method'] ?? 'GCash'));
            $referenceNumber = trim((string) ($data['reference_number'] ?? ''));

            if ($id <= 0 || !in_array($method, self::METHODS, true)) {
                $this->backWith('error', 'Invalid payment update.', '/E-Parish/views/user/payments.php');
            }

            $payment = $this->payments()->findForUser($id, (int) Auth::userId());

            if (!$payment) {
                $this->backWith('error', 'Payment record not found.', '/E-Parish/views/user/payments.php');
            }

            if ($payment['status'] === 'Verified') {
                $this->backWith('error', 'Verified payments can no longer be changed.', '/E-Parish/views/user/payments.php');
            }

            $proofFile = (new UploadService())->store(
                $files['proof_file'] ?? [],
                'payments',
                ['jpg', 'jpeg', 'png', 'pdf']
            );

            if ($method !== 'Cash' && $referenceNumber === '') {
                $this->backWith('error', 'Reference number is required for GCash and bank transfer payments.', '/E-Parish/views/user/payments.php');
            }

            if ($method !== 'Cash' && $proofFile === null && empty($payment['proof_file'])) {
                $this->backWith('error', 'Proof of payment is required for GCash and bank transfer payments.', '/E-Parish/views/user/payments.php');
            }

            $this->payments()->submitProof(
                $id,
                (int) Auth::userId(),
                $method,
                $referenceNumber !== '' ? $referenceNumber : null,
                $proofFile
            );

            $this->container->audit()->log('payment_proof_update', 'payment', $id, 'Payment proof updated.');
            $this->backWith('success', 'Payment proof submitted for review.', '/E-Parish/views/user/payments.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Payment proof update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/user/payments.php');
        }
    }

    public function updateStatus(array $data, string $status): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);
            $remarks = trim((string) ($data['remarks'] ?? ''));

            if ($id <= 0 || !in_array($status, self::ADMIN_STATUSES, true)) {
                $this->backWith('error', 'Invalid payment status update.', '/E-Parish/views/admin/payments.php');
            }

            $payment = $this->payments()->find($id);

            if (!$payment) {
                $this->backWith('error', 'Payment record not found.', '/E-Parish/views/admin/payments.php');
            }

            if (in_array($status, ['Verified', 'Rejected'], true) && $payment['status'] !== 'Submitted') {
                $this->backWith('error', 'Only submitted payments can be verified or rejected.', '/E-Parish/views/admin/payments.php');
            }

            $this->payments()->updateStatus($id, $status, Auth::userId(), $remarks !== '' ? $remarks : null);
            $this->container->audit()->log(
                'payment_status_change',
                'payment',
                $id,
                $payment['status'] . ' => ' . $status . ($remarks !== '' ? ' | ' . $remarks : '')
            );

            $this->backWith('success', 'Payment status updated.', '/E-Parish/views/admin/payments.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Admin payment update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/payments.php');
        }
    }
}
