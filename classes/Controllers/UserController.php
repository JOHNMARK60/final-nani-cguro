<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\User;
use App\Security\Auth;
use App\Services\UploadService;
use App\Validation\Validator;

final class UserController extends BaseController
{
    private function users(): User
    {
        return new User($this->container->pdo());
    }

    public function register(array $data): void
    {
        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())
                ->required($data, ['fullname', 'email', 'username', 'password', 'role'])
                ->email($data, 'email')
                ->strongPassword((string) ($data['password'] ?? ''), 'password');

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '../../views/user/login.php');
            }

            $users = $this->users();

            if ($users->findByEmailOrUsername(trim($data['email']), trim($data['username']))) {
                $this->redirectWithErrors(['email' => 'Email or username is already taken.'], $data, '../../views/user/login.php');
            }

            $role = strtolower(trim($data['role'] ?? 'user'));

            if ($role === 'admin') {
                $inviteCode = trim($data['admin_code'] ?? '');
                $expectedCode = trim((string) ($_ENV['ADMIN_REGISTRATION_CODE'] ?? getenv('ADMIN_REGISTRATION_CODE') ?: ''));

                if ($inviteCode === '' || $expectedCode === '' || !hash_equals($expectedCode, $inviteCode)) {
                    $this->redirectWithErrors(['admin_code' => 'Invalid admin invite code.'], $data, '../../views/user/login.php');
                }
            } else {
                $role = 'user';
            }

            $users->register([
                'fullname' => trim($data['fullname']),
                'email' => trim($data['email']),
                'username' => trim($data['username']),
                'password' => (string) $data['password'],
                'role' => $role,
            ]);

            $this->container->audit()->log('register', 'user', null, 'New ' . $role . ' account created.');

            $this->backWith('success', 'Registration successful. You can now sign in.', '../../views/user/login.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Registration failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '../../views/user/login.php');
        }
    }

    public function login(array $data): void
    {
        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())->required($data, ['username', 'password']);

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '../../views/user/login.php');
            }

            $user = $this->users()->attemptLogin(trim($data['username']), (string) $data['password']);

            if (!$user) {
                $this->container->audit()->log('login_failed', 'user', null, 'Failed login attempt for ' . trim($data['username']));
                $this->redirectWithErrors(['password' => 'Invalid username or password.'], $data, '../../views/user/login.php');
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            $this->users()->updateLastLogin((int) $user['id']);
            $this->container->audit()->log('login', 'user', (int) $user['id'], 'User signed in.');
            $_SESSION['success'] = 'Welcome back, ' . $user['fullname'] . '!';

            $this->redirect($user['role'] === 'admin' ? '../../views/admin/dashboard.php' : '../../views/user/dashboard.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Login failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '../../views/user/login.php');
        }
    }

    public function updateProfile(array $data): void
    {
        Auth::requireLogin('../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())->required($data, ['fullname', 'email', 'username'])->email($data, 'email');

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '../../views/user/settings.php');
            }

            $this->users()->updateProfile(Auth::userId(), [
                'fullname' => trim($data['fullname']),
                'email' => trim($data['email']),
                'username' => trim($data['username']),
                'phone' => trim($data['phone'] ?? ''),
                'designation' => trim($data['designation'] ?? ''),
            ]);

            $_SESSION['fullname'] = trim($data['fullname']);
            $this->container->audit()->log('profile_update', 'user', Auth::userId(), 'Profile information updated.');
            $this->backWith('success', 'Profile information updated.', '../../views/user/settings.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Profile update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '../../views/user/settings.php');
        }
    }

    public function updatePhoto(array $data, array $files): void
    {
        Auth::requireLogin('../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $fileName = (new UploadService())->store(
                $files['profile_pic'] ?? [],
                'profiles',
                ['jpg', 'jpeg', 'png', 'gif', 'webp']
            );

            if ($fileName === null) {
                $this->backWith('error', 'Please choose a valid image.', '../../views/user/settings.php');
            }

            $this->users()->updateProfilePhoto(Auth::userId(), basename($fileName));
            $_SESSION['profile_pic'] = basename($fileName);
            $this->container->audit()->log('profile_photo_update', 'user', Auth::userId(), 'Profile photo updated.');
            $this->backWith('success', 'Profile photo updated.', '../../views/user/settings.php');
        } catch (\Throwable $e) {
            $this->backWith('error', $e->getMessage(), '../../views/user/settings.php');
        }
    }

    public function changePassword(array $data): void
    {
        Auth::requireLogin('../../views/user/login.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $validator = (new Validator())->required($data, ['current_password', 'new_password', 'confirm_password'])
                ->strongPassword((string) ($data['new_password'] ?? ''), 'new_password');

            if (!$validator->passes()) {
                $this->redirectWithErrors($validator->errors(), $data, '../../views/user/settings.php');
            }

            if ($data['new_password'] !== $data['confirm_password']) {
                $this->backWith('error', 'New password and confirmation do not match.', '../../views/user/settings.php');
            }

            $user = $this->users()->find(Auth::userId());

            if (!$user || !password_verify((string) $data['current_password'], $user['password'])) {
                $this->backWith('error', 'Current password is incorrect.', '../../views/user/settings.php');
            }

            $this->users()->updatePassword(Auth::userId(), (string) $data['new_password']);
            $this->backWith('success', 'Password changed successfully.', '../../views/user/settings.php');
        } catch (\Throwable $e) {
            $this->backWith('error', $e->getMessage(), '../../views/user/settings.php');
        }
    }
}
