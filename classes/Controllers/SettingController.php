<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\AppSetting;
use App\Security\Auth;
use App\Services\UploadService;

final class SettingController extends BaseController
{
    private function settings(): AppSetting
    {
        return new AppSetting($this->container->pdo());
    }

    public function updateGcash(array $data, array $files): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);

            $accountName = trim((string) ($data['gcash_account_name'] ?? ''));
            $number = trim((string) ($data['gcash_number'] ?? ''));
            $churchName = trim((string) ($data['church_name'] ?? ''));
            $representative = trim((string) ($data['authorized_representative'] ?? ''));
            $requiredHours = (float) ($data['volunteer_required_hours'] ?? 20);

            if ($accountName === '' || $number === '') {
                $this->backWith('error', 'GCash account name and number are required.', '/E-Parish/views/admin/settings.php');
            }

            if (!preg_match('/^[0-9+()\\-\\s]{7,30}$/', $number)) {
                $this->backWith('error', 'Please enter a valid GCash number.', '/E-Parish/views/admin/settings.php');
            }

            if ($requiredHours <= 0) {
                $this->backWith('error', 'Volunteer required hours must be greater than zero.', '/E-Parish/views/admin/settings.php');
            }

            $settings = [
                'gcash_account_name' => $accountName,
                'gcash_number' => $number,
                'church_name' => $churchName !== '' ? $churchName : 'E-Parish Church',
                'authorized_representative' => $representative !== '' ? $representative : 'Parish Administrator',
                'volunteer_required_hours' => (string) $requiredHours,
            ];

            $qrCode = (new UploadService())->store(
                $files['gcash_qr_code'] ?? [],
                'settings',
                ['jpg', 'jpeg', 'png', 'webp']
            );

            if ($qrCode !== null) {
                $settings['gcash_qr_code'] = $qrCode;
            }

            $this->settings()->setMany($settings, Auth::userId());
            $this->container->audit()->log('settings_update', 'app_settings', null, 'GCash and parish settings updated.');
            $this->backWith('success', 'Settings saved.', '/E-Parish/views/admin/settings.php');
        } catch (\Throwable $e) {
            $this->container->logger()->error('Settings update failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/settings.php');
        }
    }
}
