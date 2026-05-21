<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;

final class ReferenceData extends BaseModel
{
    public function appointmentTypes(): array
    {
        return $this->activeRows('appointment_types');
    }

    public function certificateTypes(): array
    {
        return $this->activeRows('certificate_types');
    }

    public function paymentMethods(): array
    {
        return $this->activeRows('payment_methods');
    }

    public function paymentCategories(): array
    {
        return $this->activeRows('payment_categories');
    }

    public function volunteerActivities(): array
    {
        return $this->activeRows('volunteer_activities');
    }

    private function activeRows(string $table): array
    {
        return $this->fetchAll(
            "SELECT *
             FROM {$table}
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC"
        );
    }
}
