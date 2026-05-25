<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\CalendarEvent;
use App\Models\Certificate;
use App\Models\Payment;
use App\Models\User;
use App\Models\VolunteerService;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');

$userModel = new User($container->pdo());
$certificateModel = new Certificate($container->pdo());
$appointmentModel = new Appointment($container->pdo());
$calendarModel = new CalendarEvent($container->pdo());
$paymentModel = new Payment($container->pdo());
$volunteerModel = new VolunteerService($container->pdo());

$userId = Auth::userId();
$user = $userModel->find($userId) ?? [];
$recentCertificates = array_map(
    static fn(array $request): array => [
        'label' => $request['certificate_type'],
        'kind' => 'Certificate',
        'status' => $request['status'],
        'sort_at' => $request['created_at'],
        'display_date' => date('M d, Y', strtotime((string) $request['created_at'])),
        'href' => 'certificates.php',
    ],
    $certificateModel->forUser($userId)
);
$recentAppointments = array_map(
    static fn(array $request): array => [
        'label' => $request['appointment_type'],
        'kind' => 'Appointment',
        'status' => $request['status'],
        'sort_at' => $request['created_at'],
        'display_date' => date('M d, Y', strtotime((string) $request['appointment_date'])) . ' ' . date('h:i A', strtotime((string) $request['appointment_time'])),
        'href' => 'appointments.php',
    ],
    $appointmentModel->recentForUser($userId, 10)
);
$recent = array_merge($recentCertificates, $recentAppointments);
usort($recent, static fn(array $a, array $b): int => strcmp((string) $b['sort_at'], (string) $a['sort_at']));
$recent = array_slice($recent, 0, 8);
$calendarMonth = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['calendar_month'] ?? '')) ? (string) $_GET['calendar_month'] : date('Y-m');
$calendarEvents = array_merge(
    $appointmentModel->calendarMonth(null, $calendarMonth),
    $calendarModel->month($calendarMonth, true)
);
usort($calendarEvents, static fn(array $a, array $b): int => strcmp(
    (string) ($a['appointment_date'] . ' ' . ($a['appointment_time'] ?? '')),
    (string) ($b['appointment_date'] . ' ' . ($b['appointment_time'] ?? ''))
));
$firstName = explode(' ', trim($user['fullname'] ?? 'Member'))[0];

page_start('Dashboard');
sidebar('Dashboard');
app_header('Dashboard', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>
        <section class="mb-12">
            <h2 class="text-5xl font-black tracking-tight">Welcome, <?= e($firstName) ?>!</h2>
            <p class="mt-3 text-xl text-slate-700">Here's your parish overview.</p>
        </section>

        <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl bg-white p-8 shadow-soft ring-1 ring-yellow-100">
                <i class="bi bi-file-earmark-text text-3xl text-parish"></i>
                <div class="text-5xl font-black"><?= $certificateModel->countByStatus('Pending', $userId) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest text-slate-500">Pending Requests</div>
            </div>
            <div class="rounded-xl bg-gold p-8 text-slate-950 shadow-soft">
                <i class="bi bi-calendar-event text-3xl"></i>
                <div class="text-5xl font-black"><?= $appointmentModel->countForUser($userId) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest">Appointments</div>
            </div>
            <div class="rounded-xl bg-parish p-8 text-slate-950 shadow-soft">
                <i class="bi bi-patch-check text-3xl"></i>
                <div class="text-5xl font-black"><?= $certificateModel->countByStatus(null, $userId) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest">Certificates</div>
            </div>
            <div class="rounded-xl bg-white p-8 shadow-soft ring-1 ring-yellow-100">
                <i class="bi bi-award text-3xl text-parish"></i>
                <div class="text-5xl font-black"><?= $volunteerModel->countForUser($userId) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest text-slate-500">Volunteer Activities</div>
            </div>
            <div class="rounded-xl bg-white p-8 text-slate-950 shadow-soft">
                <div class="text-3xl font-black text-parish"><?= e(peso($paymentModel->sumByStatus('Verified', $userId))) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest text-slate-500">Verified Payments</div>
            </div>
        </section>

        <section class="mt-12 rounded-xl border border-slate-200 bg-white p-8 shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-3xl font-black text-parish">Approved Parish Calendar</h3>
                </div>
                <a href="appointments.php" class="rounded-lg border border-slate-200 px-4 py-2 font-bold text-slate-700">View Appointments</a>
            </div>
            <?= dashboard_calendar_grid($calendarEvents, $calendarMonth, 'dashboard.php', 'appointments.php') ?>
        </section>

        <section class="mt-12 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
            <div class="flex items-center justify-between border-b border-slate-200 p-8">
                <h3 class="text-3xl font-black text-parish">Recent Requests</h3>
                <a href="certificates.php" class="font-bold text-parish">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600">
                    <tr><th class="p-5">Request Type</th><th>Category</th><th>Status</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($recent === []): ?>
                        <tr><td colspan="5" class="p-16 text-center text-xl font-semibold text-slate-400">No records available.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $request): ?>
                        <tr class="border-t border-slate-100">
                            <td class="p-5 font-semibold"><?= e($request['label']) ?></td>
                            <td><?= e($request['kind']) ?></td>
                            <td><?= status_badge((string) $request['status']) ?></td>
                            <td><?= e($request['display_date']) ?></td>
                            <td><a class="font-bold text-parish" href="<?= e($request['href']) ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<?php app_footer(); page_end(); ?>
