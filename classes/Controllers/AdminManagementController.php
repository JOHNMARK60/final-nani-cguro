<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\User;
use App\Security\Auth;
use App\Validation\Validator;

final class AdminManagementController extends BaseController
{
    private function users(): User
    {
        return new User($this->container->pdo());
    }

    private function requireAdmin(): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');
    }

    public function store(array $data): void
    {
        $this->requireAdmin();

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())
                ->required($data, ['fullname', 'email', 'username', 'password'])
                ->email($data, 'email')
                ->strongPassword((string) ($data['password'] ?? ''), 'password');

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '/E-Parish/views/admin/admins.php');
            }

            if ($this->users()->findByEmail(trim($data['email']))) {
                $this->redirectWithErrors(['email' => 'Email already exists.'], $data, '/E-Parish/views/admin/admins.php');
            }

            if ($this->users()->findByUsername(trim($data['username']))) {
                $this->redirectWithErrors(['username' => 'Username already exists.'], $data, '/E-Parish/views/admin/admins.php');
            }

            $id = $this->users()->createAdmin([
                'fullname' => trim($data['fullname']),
                'email' => trim($data['email']),
                'username' => trim($data['username']),
                'password' => (string) $data['password'],
                'status' => 'active',
            ], Auth::userId());

            $this->container->audit()->log('admin_create', 'admin', $id, 'Admin account created.');
            $this->backWith('success', 'Admin account created.', '/E-Parish/views/admin/admins.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Admin creation failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/admins.php');
        }
    }

    public function update(array $data): void
    {
        $this->requireAdmin();

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())
                ->required($data, ['id', 'fullname', 'email', 'username', 'status'])
                ->email($data, 'email');

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '/E-Parish/views/admin/admins.php');
            }

            $id = (int) $data['id'];
            $admin = $this->users()->find($id);

            if (!$admin || $admin['role'] !== 'admin') {
                $this->backWith('error', 'Admin account not found.', '/E-Parish/views/admin/admins.php');
            }

            if (!in_array(trim($data['status']), ['active', 'disabled'], true)) {
                $this->redirectWithErrors(['status' => 'Invalid admin status.'], $data, '/E-Parish/views/admin/admins.php');
            }

            $existing = $this->users()->findByEmail(trim($data['email']));
            if ($existing && (int) $existing['id'] !== $id) {
                $this->redirectWithErrors(['email' => 'Email already exists.'], $data, '/E-Parish/views/admin/admins.php');
            }

            if ($id === Auth::userId() && trim($data['status']) === 'disabled') {
                $this->backWith('error', 'You cannot disable your own account.', '/E-Parish/views/admin/admins.php');
            }

            if (trim($data['status']) === 'disabled' && $admin['status'] === 'active' && $this->users()->activeAdminCount() <= 1) {
                $this->backWith('error', 'At least one active admin must remain.', '/E-Parish/views/admin/admins.php');
            }

            $this->users()->updateAdmin($id, [
                'fullname' => trim($data['fullname']),
                'email' => trim($data['email']),
                'username' => trim($data['username']),
                'status' => trim($data['status']),
            ]);

            $this->container->audit()->log('admin_update', 'admin', $id, 'Admin account updated.');
            $this->backWith('success', 'Admin account updated.', '/E-Parish/views/admin/admins.php');
        } catch (\Throwable $e) {
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/admins.php');
        }
    }

    public function toggle(array $data): void
    {
        $this->requireAdmin();

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);
            $target = $this->users()->find($id);

            if (!$target || $target['role'] !== 'admin') {
                $this->backWith('error', 'Admin account not found.', '/E-Parish/views/admin/admins.php');
            }

            if ($id === Auth::userId()) {
                $this->backWith('error', 'You cannot disable your own account.', '/E-Parish/views/admin/admins.php');
            }

            if ($this->users()->activeAdminCount() <= 1 && $target['status'] === 'active') {
                $this->backWith('error', 'At least one active admin must remain.', '/E-Parish/views/admin/admins.php');
            }

            $status = $target['status'] === 'active' ? 'disabled' : 'active';
            $this->users()->setAdminStatus($id, $status);
            $this->container->audit()->log('admin_status_change', 'admin', $id, 'Admin status changed to ' . $status . '.');
            $this->backWith('success', 'Admin status updated.', '/E-Parish/views/admin/admins.php');
        } catch (\Throwable $e) {
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/admins.php');
        }
    }

    public function resetPassword(array $data): void
    {
        $this->requireAdmin();

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())->required($data, ['id', 'password'])->strongPassword((string) ($data['password'] ?? ''), 'password');

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '/E-Parish/views/admin/admins.php');
            }

            $id = (int) $data['id'];
            if (!$this->users()->find($id)) {
                $this->backWith('error', 'Admin account not found.', '/E-Parish/views/admin/admins.php');
            }

            $this->users()->resetAdminPassword($id, (string) $data['password']);
            $this->container->audit()->log('admin_password_reset', 'admin', $id, 'Admin password reset.');
            $this->backWith('success', 'Admin password reset.', '/E-Parish/views/admin/admins.php');
        } catch (\Throwable $e) {
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/admins.php');
        }
    }
}
