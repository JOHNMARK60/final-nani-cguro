<?php

declare(strict_types=1);

use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');
$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];

$services = [
    ['Baptism Service', 'Schedule baptism and request certificate for children or adults.', 'bi-droplet', 'bg-blue-50 text-blue-700'],
    ['Wedding Service', 'Book wedding mass, manage documents, and coordinate with the priest.', 'bi-heart', 'bg-red-50 text-red-700'],
    ['Funeral Service', 'Arrange funeral mass and request official memorial certificates.', 'bi-cross', 'bg-slate-100 text-slate-900'],
    ['Confirmation', 'View confirmation requirements and schedule your session dates.', 'bi-building', 'bg-yellow-50 text-amber-800'],
];

page_start('Services');
sidebar('Services');
app_header('Parish Services', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <section class="grid gap-8 xl:grid-cols-[1fr_360px]">
            <div class="rounded-2xl bg-parish p-10 text-white shadow-soft">
                <h2 class="max-w-xl text-5xl font-black leading-tight">Streamline Your Parish Requests</h2>
                <p class="mt-6 max-w-2xl text-xl leading-8 text-blue-50">Book sacraments, request certificates, and manage documents from one secure dashboard.</p>
                <a href="appointments.php" class="mt-8 inline-block rounded-xl bg-gold px-8 py-4 font-black text-slate-950">Getting Started Guide</a>
            </div>
            <div class="rounded-2xl bg-white/70 p-10 shadow-soft">
                <h3 class="text-3xl font-black text-parish">Quick Stats</h3>
                <div class="mt-8 space-y-6 text-lg">
                    <div class="flex justify-between"><span>Pending Requests</span><span class="rounded-full bg-blue-100 px-4 py-1 font-black text-parish">0</span></div>
                    <div class="flex justify-between"><span>Active Appointments</span><span class="rounded-full bg-yellow-100 px-4 py-1 font-black text-amber-800">0</span></div>
                    <div class="border-t pt-6"><div class="flex justify-between"><span>Next Mass Service</span><strong>10:00 AM Today</strong></div></div>
                </div>
            </div>
        </section>

        <section class="mt-10 grid gap-8 md:grid-cols-2 xl:grid-cols-4">
            <?php foreach ($services as [$title, $copy, $icon, $style]): ?>
                <article class="flex min-h-[310px] flex-col items-center rounded-2xl bg-white p-8 text-center shadow-soft">
                    <div class="grid h-20 w-20 place-items-center rounded-2xl <?= e($style) ?>"><i class="bi <?= e($icon) ?> text-4xl"></i></div>
                    <h3 class="mt-8 text-3xl font-black"><?= e($title) ?></h3>
                    <p class="mt-4 flex-1 text-slate-700"><?= e($copy) ?></p>
                    <a href="appointments.php" class="mt-8 rounded-lg bg-parish px-8 py-3 font-bold text-white">View Details</a>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="mt-10 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="flex items-center justify-between p-8"><h3 class="text-3xl font-black">Service Rates & Timelines</h3><span class="rounded-lg bg-blue-50 px-4 py-2 font-bold text-parish">Updated: May 2026</span></div>
            <table class="w-full text-left">
                <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Service Type</th><th>Processing Fee</th><th>Processing Time</th><th>Availability</th></tr></thead>
                <tbody>
                    <tr class="border-t"><td class="p-5 font-bold">Sacramental Certificate</td><td><?= e(peso(150)) ?></td><td>2-3 Business Days</td><td><span class="rounded bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Immediate</span></td></tr>
                    <tr class="border-t"><td class="p-5 font-bold">Special Intent Mass</td><td><?= e(peso(250)) ?></td><td>On Scheduled Date</td><td><span class="rounded bg-yellow-100 px-3 py-1 text-xs font-bold text-amber-700">Limited Slots</span></td></tr>
                    <tr class="border-t"><td class="p-5 font-bold">Marriage Documentation</td><td><?= e(peso(1500)) ?></td><td>1 Month Process</td><td><span class="rounded bg-blue-100 px-3 py-1 text-xs font-bold text-parish">Registration Open</span></td></tr>
                </tbody>
            </table>
        </section>
    </div>
</main>
<?php app_footer(); page_end(); ?>
