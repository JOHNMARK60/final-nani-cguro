<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Appointment;
use App\Security\Auth;
use App\Validation\Validator;

final class AppointmentController extends BaseController
{
    private function appointments(): Appointment
    {
        return new Appointment($this->container->pdo());
    }

    public function store(array $data): void
    {
        Auth::requireLogin('../../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())->required($data, ['appointment_type', 'appointment_date', 'appointment_time']);

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '../../../views/user/appointments.php');
            }

            $this->appointments()->create(
                Auth::userId(),
                trim($data['appointment_type']),
                trim($data['appointment_date']),
                trim($data['appointment_time']),
                trim($data['notes'] ?? '')
            );

            $this->container->audit()->log('appointment_create', 'appointment', null, 'New appointment requested.');

            $this->redirect('../../../views/user/appointments.php?success=1');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Appointment creation failed', ['error' => $e->getMessage()]);
            $this->redirect('../../../views/user/appointments.php?error=1');
        }
    }

    public function update(array $data): void
    {
        Auth::requireLogin('../../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())->required($data, ['id', 'appointment_type', 'appointment_date', 'appointment_time']);

            if (!$validator->passes()) {
                $this->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $ok = $this->appointments()->update(
                (int) $data['id'],
                Auth::userId(),
                trim($data['appointment_type']),
                trim($data['appointment_date']),
                trim($data['appointment_time']),
                trim($data['notes'] ?? '')
            );

            if ($ok) {
                $this->container->audit()->log('appointment_update', 'appointment', (int) $data['id'], 'Appointment updated by owner.');
            }

            $this->json(['success' => $ok, 'message' => $ok ? 'Appointment updated.' : 'No appointment updated.']);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function destroy(array $data): void
    {
        Auth::requireLogin('../../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->json(['success' => false, 'message' => 'Appointment ID is missing.'], 422);
            }

            $ok = $this->appointments()->delete($id, Auth::userId());

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                $this->json(['success' => $ok, 'message' => $ok ? 'Appointment deleted.' : 'Delete failed.']);
            }

            $this->redirect('../../../views/user/appointments.php?success=' . ($ok ? '1' : '0'));
        } catch (\Throwable $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            }

            $this->redirect('../../../views/user/appointments.php?error=1');
        }
    }
}
