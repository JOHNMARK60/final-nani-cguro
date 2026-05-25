<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class AppSetting extends BaseModel
{
    public function all(): array
    {
        $rows = $this->fetchAll('SELECT setting_key, setting_value FROM app_settings');
        $settings = [];

        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }

        return $settings;
    }

    public function get(string $key, string $default = ''): string
    {
        $row = $this->fetch('SELECT setting_value FROM app_settings WHERE setting_key = ?', [$key]);

        return $row ? (string) ($row['setting_value'] ?? '') : $default;
    }

    public function set(string $key, string $value, ?int $updatedBy = null): bool
    {
        return $this->execute(
            'INSERT INTO app_settings (setting_key, setting_value, updated_by)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by)',
            [$key, $value, $updatedBy]
        );
    }

    public function setMany(array $settings, ?int $updatedBy = null): void
    {
        foreach ($settings as $key => $value) {
            $this->set((string) $key, (string) $value, $updatedBy);
        }
    }

    public function gcash(): array
    {
        return [
            'account_name' => $this->get('gcash_account_name'),
            'number' => $this->get('gcash_number'),
            'qr_code' => $this->get('gcash_qr_code'),
        ];
    }

    public function churchName(): string
    {
        return $this->get('church_name', 'E-Parish Church') ?: 'E-Parish Church';
    }

    public function authorizedRepresentative(): string
    {
        return $this->get('authorized_representative', 'Parish Administrator') ?: 'Parish Administrator';
    }

    public function volunteerRequiredHours(): float
    {
        $hours = (float) $this->get('volunteer_required_hours', '20');

        return $hours > 0 ? $hours : 20.0;
    }
}
