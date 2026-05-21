<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\VolunteerService;
use App\Security\Auth;
use App\Services\MailTemplate;

final class VolunteerController extends BaseController
{
    public function updateStatus(int $id, string $status): void
    {
        Auth::requireRole('admin', '/E-Parish/index.php');
        $model = new VolunteerService($this->container->pdo());
        $status = trim($status);

        $row = $this->container->pdo()->prepare(
            'SELECT v.*, a.name AS activity_name, u.email, u.fullname
             FROM volunteer_service v
             INNER JOIN volunteer_activities a ON a.id = v.volunteer_activity_id
             LEFT JOIN users u ON u.id = v.user_id
             WHERE v.id = ?'
        );
        $row->execute([$id]);
        $request = $row->fetch();

        if (!$request) {
            $this->backWith('error', 'Volunteer request not found.', '/E-Parish/views/admin/dashboard.php');
        }

        if (!in_array($status, ['Approved', 'Rejected', 'Pending', 'Verified'], true)) {
            $this->backWith('error', 'Invalid volunteer status.', '/E-Parish/views/admin/dashboard.php');
        }

        $model->updateStatus($id, $status);
        $this->container->audit()->log('volunteer_status_change', 'volunteer_service', $id, 'Status changed to ' . $status . '.');

        if (!empty($request['email'])) {
            $body = MailTemplate::simple(
                'Volunteer Request Update',
                'Hello ' . ($request['fullname'] ?? 'Member') . ",\n\nYour volunteer request has been updated to: {$status}.",
                'Open E-Parish',
                '/E-Parish/views/user/volunteer.php'
            );
            $this->container->mailer()->send((string) $request['email'], 'E-Parish Volunteer Update', $body);
        }

        $this->backWith('success', 'Volunteer request updated.', '/E-Parish/views/admin/dashboard.php');
    }
}
