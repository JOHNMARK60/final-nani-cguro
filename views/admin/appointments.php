<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$model = new Appointment($container->pdo());
$calendarModel = new CalendarEvent($container->pdo());
$eventTypes = ['Mass', 'Confession', 'Parish Event', 'Office Schedule', 'Other'];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$calendarMonth = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['calendar_month'] ?? '')) ? (string) $_GET['calendar_month'] : date('Y-m');
$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'from' => trim((string) ($_GET['from'] ?? '')),
    'to' => trim((string) ($_GET['to'] ?? '')),
];

$total = $model->countQueue($filters);
$items = $model->queue($filters, $perPage, ($page - 1) * $perPage);
$totalPages = (int) max(1, ceil($total / $perPage));
$calendarEvents = array_merge(
    $model->calendarMonth(null, $calendarMonth),
    $calendarModel->month($calendarMonth, false, true)
);
$parishEvents = $calendarModel->month($calendarMonth, false, true);
usort($calendarEvents, static fn(array $a, array $b): int => strcmp(
    (string) ($a['appointment_date'] . ' ' . ($a['appointment_time'] ?? '')),
    (string) ($b['appointment_date'] . ' ' . ($b['appointment_time'] ?? ''))
));

page_start('Appointment Queue');
sidebar('Appointments');
app_header('Appointment Queue', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>
        <section class="mb-10 rounded-xl bg-white p-8 shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-4xl font-bold text-parish">Manage Calendar</h2>
                    <p class="mt-1 text-slate-600">Add Mass dates and other parish events to the shared calendar.</p>
                </div>
                <button type="button" data-open="calendarEventModal" class="inline-flex items-center gap-2 rounded-xl bg-parish px-5 py-3 font-bold text-white">
                    <i class="bi bi-calendar-plus"></i>
                    New Calendar Event
                </button>
            </div>

            <?= dashboard_calendar_grid($calendarEvents, $calendarMonth, 'appointments.php', 'appointments.php', true) ?>

            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[920px] text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Event</th><th>Date</th><th>Location</th><th>Visibility</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if ($parishEvents === []): ?>
                        <tr><td colspan="6" class="p-10 text-center text-slate-400">No parish calendar events for this month.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($parishEvents as $event): ?>
                        <tr class="border-t">
                            <td class="p-5 font-semibold"><?= e($event['title']) ?><div class="text-sm text-slate-500"><?= e($event['event_type']) ?></div></td>
                            <td><?= e(date('M d, Y', strtotime((string) $event['appointment_date']))) ?><div class="text-sm text-slate-500"><?= !empty($event['appointment_time']) ? e(date('h:i A', strtotime((string) $event['appointment_time']))) : 'Whole day' ?></div></td>
                            <td><?= e((string) ($event['location'] ?? '-')) ?></td>
                            <td><?= e($event['visibility']) ?></td>
                            <td><?= status_badge((string) $event['status']) ?></td>
                            <td class="p-5">
                                <?php if ($event['status'] === 'Scheduled'): ?>
                                    <form method="POST" action="../../controllers/admin/calendar/cancel.php" data-confirm="Cancel this calendar event?" data-confirm-icon="warning" data-confirm-button="Cancel Event">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $event['id'] ?>">
                                        <input type="hidden" name="calendar_month" value="<?= e($calendarMonth) ?>">
                                        <button class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm font-semibold text-slate-400">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-xl bg-white p-8 shadow-soft">
            <h2 class="text-4xl font-bold text-parish">Appointment Queue</h2>
            <form method="GET" class="mt-6 grid gap-4 md:grid-cols-5">
                <input name="q" value="<?= e($filters['search']) ?>" class="rounded-xl border border-slate-200 px-4 py-3" placeholder="Search name, email, ref">
                <select name="status" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Status</option>
                    <option value="Pending" <?= $filters['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $filters['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $filters['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="Confirmed" <?= $filters['status'] === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                </select>
                <input type="date" name="from" value="<?= e($filters['from']) ?>" class="rounded-xl border border-slate-200 px-4 py-3">
                <input type="date" name="to" value="<?= e($filters['to']) ?>" class="rounded-xl border border-slate-200 px-4 py-3">
                <button class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">Filter</button>
            </form>
            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Request</th><th>Member</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr class="border-t">
                            <td class="p-5 font-semibold">#<?= (int) $row['id'] ?> <?= e($row['appointment_type']) ?></td>
                            <td><?= e($row['member_name'] ?? '-') ?><div class="text-sm text-slate-500"><?= e($row['member_email'] ?? '-') ?></div></td>
                            <td><?= e(date('M d, Y', strtotime($row['appointment_date']))) ?></td>
                            <td><?= status_badge((string) $row['status']) ?></td>
                            <td class="flex gap-2 p-5">
                                <?php if ($row['status'] === 'Pending'): ?>
                                    <form method="POST" action="../../views/admin/approve_appointment.php" data-confirm="Approve this appointment?" data-confirm-button="Approve">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="redirect" value="/E-Parish/views/admin/appointments.php">
                                        <button class="rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white">Approve</button>
                                    </form>
                                    <form method="POST" action="../../views/admin/reject_appointment.php" data-confirm="Reject this appointment?" data-confirm-icon="warning" data-confirm-button="Reject">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input type="hidden" name="redirect" value="/E-Parish/views/admin/appointments.php">
                                        <button class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm font-semibold text-slate-400">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= pagination_links($page, $totalPages, $filters) ?>
        </section>
    </div>
</main>

<div id="calendarEventModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/calendar/create.php" class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <h3 class="text-3xl font-black text-parish">New Calendar Event</h3>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Title</span><input name="title" required placeholder="Sunday Mass" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label>
                <span class="mb-1 block font-bold">Type</span>
                <select name="event_type" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($eventTypes as $type): ?>
                        <option value="<?= e($type) ?>"><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="mb-1 block font-bold">Visibility</span>
                <select name="visibility" class="w-full rounded-lg border border-slate-200 p-3">
                    <option>Public</option>
                    <option>Admin Only</option>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Date</span><input type="date" name="event_date" required value="<?= e(date('Y-m-d')) ?>" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Time</span><input type="time" name="event_time" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Location</span><input name="location" placeholder="Main Sanctuary" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Notes</span><textarea name="notes" class="w-full rounded-lg border border-slate-200 p-3"></textarea></label>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Save Event</button>
    </form>
</div>

<script>
document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => {
    const modal = document.getElementById(button.dataset.open);
    modal?.classList.remove('hidden');
    modal?.classList.add('flex');
}));
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => {
    const modal = button.closest('.fixed');
    modal?.classList.add('hidden');
    modal?.classList.remove('flex');
}));
</script>
<?php app_footer(); page_end(); ?>
