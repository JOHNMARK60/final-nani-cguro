<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Appointment;
use App\Security\Auth;
use App\Services\MailTemplate;

final class AdminAppointmentController extends BaseController
{
    private function appointments(): Appointment
    {
        return new Appointment($this->container->pdo());
    }

    public function updateStatus(array $data, string $status): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);
            $status = trim($status);
            $remarks = trim((string) ($data['remarks'] ?? ''));
            $redirect = $this->adminRedirect((string) ($data['redirect'] ?? '/E-Parish/views/admin/dashboard.php'));

            if ($id <= 0) {
                $this->backWith('error', 'Appointment ID is missing.', $redirect);
            }

            $row = $this->container->pdo()->prepare(
                'SELECT a.*, u.email, u.fullname
                 FROM appointments a
                 LEFT JOIN users u ON u.id = a.user_id
                 WHERE a.id = ?'
            );
            $row->execute([$id]);
            $request = $row->fetch();

            if (!$request) {
                $this->backWith('error', 'Appointment request not found.', $redirect);
            }

            if (!in_array($status, ['Approved', 'Rejected', 'Pending', 'Confirmed'], true)) {
                $this->backWith('error', 'Invalid appointment status.', $redirect);
            }

            $previous = (string) $request['status'];

            if (in_array($status, ['Approved', 'Rejected'], true) && $previous !== 'Pending') {
                $this->backWith('error', 'Only pending appointments can be approved or rejected.', $redirect);
            }

            $this->appointments()->updateStatus($id, $status);
            $this->appointments()->history($id, $previous, $status, Auth::userId(), $remarks !== '' ? $remarks : null);
            $this->container->audit()->log(
                'appointment_status_change',
                'appointment',
                $id,
                $previous . ' => ' . $status . ($remarks !== '' ? ' | ' . $remarks : '')
            );

            if (!empty($request['email'])) {
                $body = MailTemplate::simple(
                    'Appointment Update',
                    'Hello ' . ($request['fullname'] ?? 'Member') . ",\n\nYour appointment request status has been updated to: {$status}." . ($remarks !== '' ? "\n\nRemarks: {$remarks}" : ''),
                    'Open E-Parish',
                    '/E-Parish/views/user/appointments.php'
                );
                $this->container->mailer()->send((string) $request['email'], 'E-Parish Appointment Update', $body);
            }

            $this->backWith('success', 'Appointment updated.', $redirect);
        } catch (\Throwable $e) {
            $this->container->logger()->error('Admin appointment update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), $redirect ?? '/E-Parish/views/admin/dashboard.php');
        }
    }

    private function adminRedirect(string $path): string
    {
        return str_starts_with($path, '/E-Parish/views/admin/')
            ? $path
            : '/E-Parish/views/admin/dashboard.php';
    }
}
