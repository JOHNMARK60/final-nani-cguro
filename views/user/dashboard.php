<?php

declare(strict_types=1);

use App\Models\Appointment;
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
$paymentModel = new Payment($container->pdo());
$volunteerModel = new VolunteerService($container->pdo());

$user = $userModel->find(Auth::userId()) ?? [];
$recent = array_slice($certificateModel->forUser(Auth::userId()), 0, 5);
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
            <div class="rounded-xl bg-blue-600 p-8 text-white shadow-soft">
                <div class="text-5xl font-black"><?= $certificateModel->countByStatus('Pending', Auth::userId()) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest">Pending Requests</div>
            </div>
            <div class="rounded-xl bg-gold p-8 text-slate-950 shadow-soft">
                <div class="text-5xl font-black"><?= $appointmentModel->countForUser(Auth::userId()) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest">Appointments</div>
            </div>
            <div class="rounded-xl bg-sky-700 p-8 text-white shadow-soft">
                <div class="text-5xl font-black"><?= $certificateModel->countByStatus(null, Auth::userId()) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest">Certificates</div>
            </div>
            <div class="rounded-xl bg-green-700 p-8 text-white shadow-soft">
                <div class="text-5xl font-black"><?= $volunteerModel->countForUser(Auth::userId()) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest">Volunteer Activities</div>
            </div>
            <div class="rounded-xl bg-white p-8 text-slate-950 shadow-soft">
                <div class="text-3xl font-black text-green-700"><?= e(peso($paymentModel->sumByStatus('Verified', Auth::userId()))) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest text-slate-500">Verified Payments</div>
            </div>
        </section>

        <section class="mt-12 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
            <div class="flex items-center justify-between border-b border-slate-200 p-8">
                <h3 class="text-3xl font-black text-parish">Recent Requests</h3>
                <a href="certificates.php" class="font-bold text-parish">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600">
                    <tr><th class="p-5">Request Type</th><th>Status</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($recent === []): ?>
                        <tr><td colspan="4" class="p-16 text-center text-xl font-semibold text-slate-400">No records available.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recent as $request): ?>
                        <tr class="border-t border-slate-100">
                            <td class="p-5 font-semibold"><?= e($request['certificate_type']) ?></td>
                            <td><span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-bold text-parish"><?= e($request['status']) ?></span></td>
                            <td><?= e(date('M d, Y', strtotime($request['created_at']))) ?></td>
                            <td><a class="font-bold text-parish" href="certificates.php">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<?php app_footer(); page_end(); ?>
