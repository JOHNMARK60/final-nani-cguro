<?php

declare(strict_types=1);

use App\Models\Certificate;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use App\Models\VolunteerService;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$users = new User($container->pdo());
$certificates = new Certificate($container->pdo());
$appointments = new Appointment($container->pdo());
$payments = new Payment($container->pdo());
$volunteers = new VolunteerService($container->pdo());
$user = $users->find(Auth::userId()) ?? [];
$recent = $certificates->recent(10);
$recentAppointments = $appointments->recent(10);
$pendingAppointments = $appointments->pending();
$pendingVolunteers = $volunteers->pending();
$certificateSeries = $certificates->monthlyCounts();
$appointmentSeries = $appointments->monthlyCounts();
$volunteerSeries = $volunteers->monthlyCounts();

$months = [];
$series = [
    'Certificates' => $certificateSeries,
    'Appointments' => $appointmentSeries,
    'Volunteers' => $volunteerSeries,
];

foreach (array_merge($certificateSeries, $appointmentSeries, $volunteerSeries) as $row) {
    $months[$row['month']] = true;
}

$monthLabels = array_keys($months);
sort($monthLabels);

$chartData = [];
foreach ($series as $label => $rows) {
    $byMonth = [];
    foreach ($rows as $row) {
        $byMonth[$row['month']] = (int) $row['total'];
    }

    $chartData[] = [
        'label' => $label,
        'data' => array_map(static fn(string $month): int => $byMonth[$month] ?? 0, $monthLabels),
    ];
}

page_start('Admin Dashboard');
sidebar('Dashboard');
app_header('Admin Dashboard', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>
        <section class="mb-10">
            <h2 class="text-5xl font-black text-parish">Welcome Admin</h2>
            <p class="mt-2 text-xl text-slate-700">Manage parish requests and activities.</p>
        </section>
        <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl bg-blue-600 p-8 text-white shadow-soft"><div class="text-5xl font-black"><?= $certificates->countByStatus('Pending') ?></div><p class="font-bold uppercase tracking-widest">Certificate Requests</p></div>
            <div class="rounded-xl bg-gold p-8 text-slate-950 shadow-soft"><div class="text-5xl font-black"><?= $appointments->countByStatus('Pending') ?></div><p class="font-bold uppercase tracking-widest">Appointment Requests</p></div>
            <div class="rounded-xl bg-green-700 p-8 text-white shadow-soft"><div class="text-5xl font-black"><?= $volunteers->countQueue(['status' => 'Pending']) ?></div><p class="font-bold uppercase tracking-widest">Volunteer Items</p></div>
            <div class="rounded-xl bg-red-600 p-8 text-white shadow-soft"><div class="text-5xl font-black"><?= $users->countAll() ?></div><p class="font-bold uppercase tracking-widest">Total Users</p></div>
            <div class="rounded-xl bg-white p-8 text-slate-950 shadow-soft"><div class="text-3xl font-black text-green-700"><?= e(peso($payments->sumByStatus('Verified'))) ?></div><p class="font-bold uppercase tracking-widest text-slate-500">Peso Revenue</p></div>
        </section>

        <section class="mt-10 rounded-xl bg-white p-8 shadow-soft">
            <div class="flex items-center justify-between">
                <h3 class="text-3xl font-black text-parish">Activity Trends</h3>
                <span class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600">Last 6 months</span>
            </div>
            <div class="mt-6 h-80">
                <canvas id="activityChart"></canvas>
            </div>
        </section>

        <section class="mt-10 overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 p-8"><h3 class="text-3xl font-black text-parish">Recent Certificate Requests</h3></div>
            <table class="w-full text-left">
                <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Full Name</th><th>Certificate Type</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php if ($recent === []): ?>
                    <tr><td colspan="4" class="p-16 text-center text-slate-400">No requests found.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $request): ?>
                    <tr class="border-t"><td class="p-5 font-semibold"><?= e($request['full_name']) ?></td><td><?= e($request['certificate_type']) ?></td><td><span class="rounded-full bg-blue-50 px-3 py-1 font-bold text-parish"><?= e($request['status']) ?></span></td><td><?= e(date('M d, Y', strtotime($request['created_at']))) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mt-10 overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 p-8"><h3 class="text-3xl font-black text-parish">Pending Appointment Requests</h3></div>
            <table class="w-full text-left">
                <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Member</th><th>Type</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if ($pendingAppointments === []): ?>
                    <tr><td colspan="6" class="p-16 text-center text-slate-400">No pending appointment requests.</td></tr>
                <?php endif; ?>
                <?php foreach ($pendingAppointments as $request): ?>
                    <tr class="border-t">
                        <td class="p-5 font-semibold"><?= e($request['member_name'] ?? (string) $request['user_id']) ?></td>
                        <td><?= e($request['appointment_type']) ?></td>
                        <td><?= e(date('M d, Y', strtotime($request['appointment_date']))) ?></td>
                        <td><?= e(date('h:i A', strtotime($request['appointment_time']))) ?></td>
                        <td><span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-800"><?= e($request['status']) ?></span></td>
                        <td class="flex gap-3 p-5">
                            <form method="POST" action="approve_appointment.php" data-confirm="Approve this appointment?" data-confirm-button="Approve">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <button class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white">Approve</button>
                            </form>
                            <form method="POST" action="reject_appointment.php" data-confirm="Reject this appointment?" data-confirm-icon="warning" data-confirm-button="Reject">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mt-10 overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 p-8"><h3 class="text-3xl font-black text-parish">Recent Appointments</h3></div>
            <table class="w-full text-left">
                <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Member</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($recentAppointments === []): ?>
                    <tr><td colspan="4" class="p-16 text-center text-slate-400">No appointments found.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentAppointments as $request): ?>
                    <tr class="border-t">
                        <td class="p-5 font-semibold"><?= e($request['member_name'] ?? (string) $request['user_id']) ?></td>
                        <td><?= e($request['appointment_type']) ?></td>
                        <td><?= e(date('M d, Y', strtotime($request['appointment_date']))) ?></td>
                        <td><?= e($request['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="mt-10 overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 p-8"><h3 class="text-3xl font-black text-parish">Pending Volunteer Requests</h3></div>
            <table class="w-full text-left">
                <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Activity</th><th>User ID</th><th>Date</th><th>Hours</th><th>Action</th></tr></thead>
                <tbody>
                <?php if ($pendingVolunteers === []): ?>
                    <tr><td colspan="5" class="p-16 text-center text-slate-400">No pending volunteer requests.</td></tr>
                <?php endif; ?>
                <?php foreach ($pendingVolunteers as $request): ?>
                    <tr class="border-t">
                        <td class="p-5 font-semibold"><?= e($request['activity_name']) ?></td>
                        <td><?= e((string) ($request['user_id'] ?? '-')) ?></td>
                        <td><?= e((string) $request['activity_date']) ?></td>
                        <td><?= e((string) $request['hours_served']) ?></td>
                        <td class="flex gap-3 p-5">
                            <form method="POST" action="approve_volunteer.php" data-confirm="Approve this volunteer request?" data-confirm-button="Approve">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <button class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white">Approve</button>
                            </form>
                            <form method="POST" action="reject_volunteer.php" data-confirm="Reject this volunteer request?" data-confirm-icon="warning" data-confirm-button="Reject">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $request['id'] ?>">
                                <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartCanvas = document.getElementById('activityChart');
if (chartCanvas) {
    const labels = <?= json_encode($monthLabels, JSON_UNESCAPED_SLASHES) ?>;
    const rawSeries = <?= json_encode($chartData, JSON_UNESCAPED_SLASHES) ?>;
    const datasets = rawSeries.map((series, index) => ({
        label: series.label,
        data: series.data,
        borderColor: ['#1d4ed8', '#f59e0b', '#16a34a'][index] || '#64748b',
        backgroundColor: ['rgba(29,78,216,0.12)', 'rgba(245,158,11,0.12)', 'rgba(22,163,74,0.12)'][index] || 'rgba(100,116,139,0.12)',
        tension: 0.35,
        fill: true,
    }));

    new Chart(chartCanvas, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}
</script>
<?php app_footer(); page_end(); ?>
