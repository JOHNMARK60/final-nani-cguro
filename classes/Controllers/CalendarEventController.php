<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\CalendarEvent;
use App\Security\Auth;

final class CalendarEventController extends BaseController
{
    private function events(): CalendarEvent
    {
        return new CalendarEvent($this->container->pdo());
    }

    public function store(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);

            $id = $this->events()->create($data + ['created_by' => Auth::userId()]);
            $this->container->audit()->log('calendar_event_create', 'parish_calendar_event', $id, (string) ($data['title'] ?? ''));

            $month = substr((string) ($data['event_date'] ?? date('Y-m-d')), 0, 7);
            $this->backWith('success', 'Calendar event saved.', '/E-Parish/views/admin/appointments.php?calendar_month=' . urlencode($month));
        } catch (\Throwable $e) {
            $this->container->logger()->error('Calendar event save failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/appointments.php');
        }
    }

    public function cancel(array $data): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');

        try {
            $this->requireCsrf($data['csrf_token'] ?? null);
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->backWith('error', 'Calendar event ID is missing.', '/E-Parish/views/admin/appointments.php');
            }

            $this->events()->cancel($id);
            $this->container->audit()->log('calendar_event_cancel', 'parish_calendar_event', $id, 'Calendar event cancelled.');
            $month = preg_match('/^\d{4}-\d{2}$/', (string) ($data['calendar_month'] ?? '')) ? (string) $data['calendar_month'] : date('Y-m');
            $this->backWith('success', 'Calendar event cancelled.', '/E-Parish/views/admin/appointments.php?calendar_month=' . urlencode($month));
        } catch (\Throwable $e) {
            $this->container->logger()->error('Calendar event cancel failed', ['error' => $e->getMessage()]);
            $this->backWith('error', $e->getMessage(), '/E-Parish/views/admin/appointments.php');
        }
    }
}
