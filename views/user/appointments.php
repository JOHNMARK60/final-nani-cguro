<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\ReferenceData;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$appointments = (new Appointment($container->pdo()))->forUser(Auth::userId());
$appointmentTypes = (new ReferenceData($container->pdo()))->appointmentTypes();
$today = date('Y-m-d');
$upcoming = array_values(array_filter($appointments, static fn ($item) => $item['appointment_date'] >= $today && $item['status'] !== 'Rejected'));
$history = array_values(array_filter($appointments, static fn ($item) => $item['appointment_date'] < $today || $item['status'] === 'Rejected'));

page_start('Appointments');
sidebar('Appointments');
app_header('Dashboard', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-5xl font-black text-parish">Appointments</h2>
                <p class="mt-2 text-xl text-slate-700">Schedule and manage your parish appointments.</p>
            </div>
            <button data-open="appointmentModal" class="rounded-xl bg-parish px-7 py-4 text-lg font-bold text-white shadow-soft">
                <i class="bi bi-plus-lg mr-2"></i>New Appointment
            </button>
        </div>

        <section class="overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 px-8 pt-8">
                <button data-tab="upcoming" class="tab-btn border-b-2 border-parish px-8 pb-5 text-lg font-bold text-parish">Upcoming</button>
                <button data-tab="history" class="tab-btn px-8 pb-5 text-lg">History</button>
            </div>
            <?php foreach (['upcoming' => $upcoming, 'history' => $history] as $tab => $rows): ?>
                <div id="<?= e($tab) ?>" class="tab-panel <?= $tab === 'history' ? 'hidden' : '' ?>">
                    <table class="w-full text-left">
                        <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-500"><tr><th class="p-5">Date</th><th>Time</th><th>Type</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="5" class="p-16 text-center text-xl font-semibold text-slate-400">No appointments found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $appointment): ?>
                            <tr class="border-t border-slate-100">
                                <td class="p-5"><?= e(date('M d, Y', strtotime($appointment['appointment_date']))) ?></td>
                                <td><?= e(date('h:i A', strtotime($appointment['appointment_time']))) ?></td>
                                <td class="font-semibold"><i class="bi bi-houses mr-2 text-parish"></i><?= e($appointment['appointment_type']) ?></td>
                                <td><span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-bold text-parish"><?= e($appointment['status']) ?></span></td>
                                <td>
                                    <form method="POST" action="../../controllers/users/appointments/delete.php" data-confirm="Delete this appointment?" data-confirm-text="This appointment request will be removed." data-confirm-icon="warning" data-confirm-button="Delete">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                                        <button class="font-bold text-red-600">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="mt-10 grid gap-6 md:grid-cols-3">
            <div class="rounded-xl bg-white p-8 shadow-soft"><div class="font-bold uppercase tracking-widest text-slate-500">Total Monthly</div><div class="mt-3 text-4xl font-black text-parish"><?= count($appointments) ?></div><p>Confirmed bookings</p></div>
            <div class="rounded-xl bg-white p-8 shadow-soft"><div class="font-bold uppercase tracking-widest text-slate-500">Wait Time</div><div class="mt-3 text-4xl font-black text-amber-800">~2 Days</div><p>Avg. approval speed</p></div>
            <div class="rounded-xl bg-parish p-8 text-white shadow-soft"><div class="font-bold uppercase tracking-widest text-blue-100">Parish Center</div><div class="mt-3 text-3xl font-black">Main Sanctuary</div><p>Location for next visit</p></div>
        </section>
    </div>
</main>

<div id="appointmentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/users/appointments/request_appointment.php" class="w-full max-w-xl rounded-2xl bg-white p-8 shadow-soft">
        <div class="mb-6 flex items-start justify-between"><h3 class="text-3xl font-black text-parish">New Appointment</h3><button type="button" data-close class="text-2xl">&times;</button></div>
        <?= csrf_field() ?>
        <div class="grid gap-4">
            <label>
                <span class="mb-1 block font-bold">Type</span>
                <select name="appointment_type" required class="w-full rounded-lg border p-3">
                    <?php foreach ($appointmentTypes as $type): ?>
                        <option value="<?= e($type['name']) ?>"><?= e($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Date</span><input type="date" name="appointment_date" required class="w-full rounded-lg border p-3"></label>
            <label><span class="mb-1 block font-bold">Time</span><input type="time" name="appointment_time" required class="w-full rounded-lg border p-3"></label>
            <label><span class="mb-1 block font-bold">Notes</span><textarea name="notes" class="w-full rounded-lg border p-3"></textarea></label>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Submit Appointment</button>
    </form>
</div>
<script>
document.querySelector('[data-open]')?.addEventListener('click', e => document.getElementById(e.currentTarget.dataset.open).classList.replace('hidden', 'flex'));
document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => b.closest('.fixed').classList.replace('flex', 'hidden')));
document.querySelectorAll('.tab-btn').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.add('hidden'));
    document.getElementById(button.dataset.tab).classList.remove('hidden');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.className = 'tab-btn px-8 pb-5 text-lg');
    button.className = 'tab-btn border-b-2 border-parish px-8 pb-5 text-lg font-bold text-parish';
}));
</script>
<?php app_footer(); page_end(); ?>
