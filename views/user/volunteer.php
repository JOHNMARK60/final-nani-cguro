<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\ReferenceData;
use App\Models\VolunteerService;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');
$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$volunteerModel = new VolunteerService($container->pdo());
$logs = $volunteerModel->forUser(Auth::userId());
$activityTypes = (new ReferenceData($container->pdo()))->volunteerActivities();
$approvedHours = array_sum(array_map(
    static fn(array $log): float => in_array($log['status'], ['Approved', 'Verified'], true) ? (float) $log['hours_served'] : 0.0,
    $logs
));
$hasRecognition = $approvedHours >= 20;

page_start('Volunteer');
sidebar('Volunteer');
app_header('Volunteer Incentives', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div><h2 class="text-5xl font-black text-parish">Volunteer Incentives</h2><p class="mt-2 text-xl">Exclusive benefits for active parish volunteers.</p></div>
            <button data-open="volunteerModal" class="rounded-xl bg-parish px-7 py-4 text-lg font-bold text-white shadow-soft"><i class="bi bi-plus-lg mr-2"></i>New Volunteer Activity</button>
        </div>
        <section class="grid gap-8 lg:grid-cols-2">
            <div class="rounded-xl bg-white p-10 shadow-soft"><h3 class="text-3xl font-black"><i class="bi bi-cash-coin mr-4 text-parish"></i>Certificate Discounts</h3><p class="mt-6 text-lg text-slate-700">Active volunteers enjoy reduced fees when requesting baptismal, marriage, and other parish certificates.</p><div class="mt-8 flex flex-wrap gap-5"><span class="rounded-full bg-green-100 px-4 py-1 font-bold text-green-700"><?= $approvedHours > 0 ? 'Active' : 'Pending' ?></span><strong class="text-xl text-parish">Approved Hours: <?= e(number_format($approvedHours, 1)) ?></strong></div></div>
            <div class="rounded-xl bg-white p-10 shadow-soft"><h3 class="text-3xl font-black"><i class="bi bi-award mr-4 text-amber-800"></i>Service Recognition</h3><p class="mt-6 text-lg text-slate-700">Earn Good Moral Certificate eligibility after 20 approved volunteer hours.</p><button class="mt-8 rounded-lg <?= $hasRecognition ? 'bg-parish text-white' : 'bg-blue-100 text-slate-600' ?> px-8 py-3 font-bold" disabled><?= $hasRecognition ? 'Eligible' : 'Locked' ?></button></div>
        </section>
        <section class="mt-12 overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 px-8 pt-8"><div class="inline-block border-b-2 border-parish px-1 pb-5 text-lg font-bold text-parish">Service Log</div><div class="ml-8 inline-block pb-5 text-lg">Certificates Earned</div></div>
            <table class="w-full text-left">
                <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Activity</th><th>Date</th><th>Hours Served</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($logs === []): ?>
                    <tr><td colspan="4" class="p-16 text-center text-xl font-semibold text-slate-400">No volunteer records yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                    <tr class="border-t"><td class="p-5 font-semibold"><?= e($log['activity_name']) ?></td><td><?= e((string) $log['activity_date']) ?></td><td class="font-bold"><?= e((string) $log['hours_served']) ?> Hours</td><td><?= status_badge((string) $log['status']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>

<div id="volunteerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/users/volunteers/create.php" class="w-full max-w-xl rounded-2xl bg-white p-8 shadow-soft">
        <div class="mb-6 flex items-start justify-between"><h3 class="text-3xl font-black text-parish">New Volunteer Activity</h3><button type="button" data-close class="text-2xl">&times;</button></div>
        <?= csrf_field() ?>
        <div class="grid gap-4">
            <label>
                <span class="mb-1 block font-bold">Activity</span>
                <select name="activity_name" required class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($activityTypes as $type): ?>
                        <option value="<?= e($type['name']) ?>" <?= old('activity_name') === $type['name'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Date Served</span><input type="date" name="activity_date" value="<?= e((string) old('activity_date', date('Y-m-d'))) ?>" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Hours Served</span><input type="number" name="hours_served" min="1" max="24" step="0.5" value="<?= e((string) old('hours_served', '1')) ?>" required class="w-full rounded-lg border border-slate-200 p-3"><?php if ($msg = field_error('hours_served')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?></label>
            <label><span class="mb-1 block font-bold">Notes</span><textarea name="notes" class="w-full rounded-lg border border-slate-200 p-3"><?= e((string) old('notes')) ?></textarea></label>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Submit for Review</button>
    </form>
</div>

<script>
document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => {
    const modal = document.getElementById(button.dataset.open);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}));
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => {
    const modal = button.closest('.fixed');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}));
</script>
<?php app_footer(); page_end(); ?>
<?php clear_form_state(); ?>
