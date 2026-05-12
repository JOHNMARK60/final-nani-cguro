<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\VolunteerService;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');
$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$logs = (new VolunteerService($container->pdo()))->forUser(Auth::userId());

page_start('Volunteer');
sidebar('Volunteer');
app_header('Volunteer Incentives', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div><h2 class="text-5xl font-black text-parish">Volunteer Incentives</h2><p class="mt-2 text-xl">Exclusive benefits for active parish volunteers.</p></div>
            <button class="rounded-xl bg-parish px-7 py-4 text-lg font-bold text-white shadow-soft"><i class="bi bi-plus-lg mr-2"></i>New Volunteer Activity</button>
        </div>
        <section class="grid gap-8 lg:grid-cols-2">
            <div class="rounded-xl bg-white p-10 shadow-soft"><h3 class="text-3xl font-black"><i class="bi bi-cash-coin mr-4 text-parish"></i>Certificate Discounts</h3><p class="mt-6 text-lg text-slate-700">Active volunteers enjoy reduced fees when requesting baptismal, marriage, and other parish certificates.</p><div class="mt-8 flex gap-5"><span class="rounded-full bg-green-100 px-4 py-1 font-bold text-green-700">Active</span><strong class="text-xl text-parish">Discount Rate: 50%</strong></div></div>
            <div class="rounded-xl bg-white p-10 shadow-soft"><h3 class="text-3xl font-black"><i class="bi bi-award mr-4 text-amber-800"></i>Service Recognition</h3><p class="mt-6 text-lg text-slate-700">Download your Good Moral Certificate after completing 6 months or 20 hours of dedicated service.</p><button class="mt-8 rounded-lg bg-blue-100 px-8 py-3 font-bold text-slate-600" disabled>Locked</button></div>
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
                    <tr class="border-t"><td class="p-5 font-semibold"><?= e($log['activity_name']) ?></td><td><?= e((string) $log['activity_date']) ?></td><td class="font-bold"><?= e((string) $log['hours_served']) ?> Hours</td><td><span class="rounded-full bg-green-100 px-4 py-1 font-bold text-green-700"><?= e($log['status']) ?></span></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</main>
<?php app_footer(); page_end(); ?>
