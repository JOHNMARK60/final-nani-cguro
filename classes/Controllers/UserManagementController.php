<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\User;
use App\Security\Auth;
use App\Validation\Validator;

final class UserManagementController extends BaseController
{
    private function users(): User
    {
        return new User($this->container->pdo());
    }

    public function store(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())
                ->required($data, ['fullname', 'email', 'username', 'password', 'role', 'status'])
                ->email($data, 'email')
                ->strongPassword((string) ($data['password'] ?? ''), 'password');

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '/E-Parish/views/admin/users.php');
            }

            $role = trim((string) $data['role']);
            $status = trim((string) $data['status']);

            if (!in_array($role, ['admin', 'user'], true) || !in_array($status, ['active', 'disabled'], true)) {
                $this->redirectWithErrors(['role' => 'Invalid role or status selected.'], $data, '/E-Parish/views/admin/users.php');
            }

            if ($this->users()->findByEmail(trim((string) $data['email']))) {
                $this->redirectWithErrors(['email' => 'Email already exists.'], $data, '/E-Parish/views/admin/users.php');
            }

            if ($this->users()->findByUsername(trim((string) $data['username']))) {
                $this->redirectWithErrors(['username' => 'Username already exists.'], $data, '/E-Parish/views/admin/users.php');
            }

            $id = $this->users()->createManaged([
                'fullname' => trim((string) $data['fullname']),
                'email' => trim((string) $data['email']),
                'username' => trim((string) $data['username']),
                'password' => (string) $data['password'],
                'role' => $role,
                'status' => $status,
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'address' => trim((string) ($data['address'] ?? '')) ?: null,
                'designation' => trim((string) ($data['designation'] ?? '')) ?: null,
            ], Auth::userId());

            $this->container->audit()->log('user_create', 'user', $id, 'User account created by admin.');
            $this->backWith('success', 'User account created.', '/E-Parish/views/admin/users.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('User creation failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/users.php');
        }
    }

    public function update(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())
                ->required($data, ['id', 'fullname', 'email', 'username', 'role', 'status'])
                ->email($data, 'email');

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '/E-Parish/views/admin/users.php');
            }

            $id = (int) $data['id'];
            $target = $this->users()->find($id);

            if (!$target) {
                $this->backWith('error', 'User account not found.', '/E-Parish/views/admin/users.php');
            }

            $role = trim((string) $data['role']);
            $status = trim((string) $data['status']);

            if (!in_array($role, ['admin', 'user'], true) || !in_array($status, ['active', 'disabled'], true)) {
                $this->redirectWithErrors(['role' => 'Invalid role or status selected.'], $data, '/E-Parish/views/admin/users.php');
            }

            $existingEmail = $this->users()->findByEmail(trim((string) $data['email']));
            if ($existingEmail && (int) $existingEmail['id'] !== $id) {
                $this->redirectWithErrors(['email' => 'Email already exists.'], $data, '/E-Parish/views/admin/users.php');
            }

            $existingUsername = $this->users()->findByUsername(trim((string) $data['username']));
            if ($existingUsername && (int) $existingUsername['id'] !== $id) {
                $this->redirectWithErrors(['username' => 'Username already exists.'], $data, '/E-Parish/views/admin/users.php');
            }

            if ($id === Auth::userId() && ($status === 'disabled' || $role !== 'admin')) {
                $this->backWith('error', 'You cannot disable or demote your own account.', '/E-Parish/views/admin/users.php');
            }

            if ($target['role'] === 'admin' && $status === 'disabled' && $target['status'] === 'active' && $this->users()->activeAdminCount() <= 1) {
                $this->backWith('error', 'At least one active admin must remain.', '/E-Parish/views/admin/users.php');
            }

            $this->users()->updateManaged($id, [
                'fullname' => trim((string) $data['fullname']),
                'email' => trim((string) $data['email']),
                'username' => trim((string) $data['username']),
                'role' => $role,
                'status' => $status,
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'address' => trim((string) ($data['address'] ?? '')) ?: null,
                'designation' => trim((string) ($data['designation'] ?? '')) ?: null,
            ]);

            $this->container->audit()->log('user_update', 'user', $id, 'User account updated by admin.');
            $this->backWith('success', 'User account updated.', '/E-Parish/views/admin/users.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('User update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/users.php');
        }
    }

    public function archive(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);
            $target = $this->users()->find($id);

            if (!$target) {
                $this->backWith('error', 'User account not found.', '/E-Parish/views/admin/users.php');
            }

            if ($id === Auth::userId()) {
                $this->backWith('error', 'You cannot archive your own account.', '/E-Parish/views/admin/users.php');
            }

            if ($target['role'] === 'admin' && $target['status'] === 'active' && $this->users()->activeAdminCount() <= 1) {
                $this->backWith('error', 'At least one active admin must remain.', '/E-Parish/views/admin/users.php');
            }

            $this->users()->setStatus($id, 'disabled');
            $this->container->audit()->log('user_archive', 'user', $id, 'User account disabled by admin.');
            $this->backWith('success', 'User account disabled safely. Existing records were kept.', '/E-Parish/views/admin/users.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('User archive failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/users.php');
        }
    }
}
