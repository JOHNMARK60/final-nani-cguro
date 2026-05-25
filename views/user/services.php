<?php

declare(strict_types=1);

use App\Models\Appointment;
use App\Models\CalendarEvent;
use App\Models\Certificate;
use App\Models\ParishService;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');
$userId = (int) Auth::userId();
$user = (new User($container->pdo()))->find($userId) ?? [];
$appointmentModel = new Appointment($container->pdo());
$certificateModel = new Certificate($container->pdo());
$calendarModel = new CalendarEvent($container->pdo());
$serviceModel = new ParishService($container->pdo());
$pendingRequests = $certificateModel->countByStatus('Pending', $userId) + $appointmentModel->countByStatus('Pending', $userId);
$activeAppointments = $appointmentModel->countActiveForUser($userId);
$nextMass = $calendarModel->nextPublicByType('Mass');
$nextMassTime = $nextMass && !empty($nextMass['event_time']) ? date('h:i A', strtotime((string) $nextMass['event_time'])) : null;
$nextMassDate = null;

if ($nextMass) {
    $massDate = (string) $nextMass['event_date'];
    $nextMassDate = match ($massDate) {
        date('Y-m-d') => 'Today',
        date('Y-m-d', strtotime('+1 day')) => 'Tomorrow',
        default => date('M d, Y', strtotime($massDate)),
    };
}

$services = [
    ['Baptism Service', 'Schedule baptism and request certificate for children or adults.', 'bi-droplet', 'bg-yellow-50 text-parish'],
    ['Wedding Service', 'Book wedding mass, manage documents, and coordinate with the priest.', 'bi-heart', 'bg-red-50 text-red-700'],
    ['Funeral Service', 'Arrange funeral mass and request official memorial certificates.', 'bi-flower1', 'bg-slate-100 text-slate-900'],
    ['Confirmation', 'View confirmation requirements and schedule your session dates.', 'bi-building', 'bg-yellow-50 text-amber-800'],
];

$serviceRates = array_map(
    static fn(array $service): array => [
        $service['service_name'],
        (float) $service['price'] > 0 ? peso($service['price']) : 'No processing fee',
        $service['required_documents'] ?: 'Standard parish requirements',
        $service['availability_status'],
        $service['availability_status'] === 'Active' ? 'bg-yellow-100 text-parish' : 'bg-slate-100 text-slate-700',
    ],
    $serviceModel->active()
);

if ($serviceRates === []) {
    $serviceRates = [
    ['Baptismal Certificate', peso(150), '2-3 Business Days', 'Immediate', 'bg-yellow-100 text-parish'],
    ['Confirmation Certificate', peso(150), '2-3 Business Days', 'Immediate', 'bg-yellow-100 text-parish'],
    ['Marriage Certificate', peso(150), '3-5 Business Days', 'Registration Open', 'bg-yellow-100 text-parish'],
    ['Death Certificate', peso(150), '2-3 Business Days', 'Immediate', 'bg-yellow-100 text-parish'],
    ['Mass Intentions', peso(250), 'On Scheduled Date', 'Limited Slots', 'bg-yellow-100 text-amber-700'],
    ['Counseling Session', 'No processing fee', 'On Scheduled Date', 'Appointment Required', 'bg-slate-100 text-slate-700'],
    ['Baptism Service', peso(500), '1-2 Weeks Coordination', 'Registration Open', 'bg-yellow-100 text-parish'],
    ['Wedding Service / Marriage Documentation', peso(1500), '1 Month Process', 'Registration Open', 'bg-yellow-100 text-parish'],
    ['Funeral Service', 'No processing fee', 'Immediate Coordination', 'Priority Assistance', 'bg-red-100 text-red-700'],
    ['Volunteer Service Recognition', 'No processing fee', 'After 20 Approved Hours', 'Volunteer Benefit', 'bg-yellow-100 text-parish'],
    ];
}

page_start('Services');
sidebar('Services');
app_header('Parish Services', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <section class="grid gap-8 xl:grid-cols-[1fr_360px]">
            <div class="rounded-2xl bg-parish p-10 text-slate-950 shadow-soft">
                <h2 class="max-w-xl text-5xl font-black leading-tight">Streamline Your Parish Requests</h2>
                <p class="mt-6 max-w-2xl text-xl leading-8 text-slate-800">Book sacraments, request certificates, and manage documents from one secure dashboard.</p>
                <a href="appointments.php" class="mt-8 inline-block rounded-xl bg-gold px-8 py-4 font-black text-slate-950">Getting Started Guide</a>
            </div>
            <div class="rounded-2xl bg-white/70 p-10 shadow-soft">
                <h3 class="text-3xl font-black text-parish">Quick Stats</h3>
                <div class="mt-8 space-y-6 text-lg">
                    <div class="flex items-center justify-between gap-4"><span>Pending Requests</span><span class="min-w-12 rounded-full bg-yellow-100 px-4 py-1 text-center font-black text-parish"><?= (int) $pendingRequests ?></span></div>
                    <div class="flex items-center justify-between gap-4"><span>Active Appointments</span><span class="min-w-12 rounded-full bg-yellow-100 px-4 py-1 text-center font-black text-amber-800"><?= (int) $activeAppointments ?></span></div>
                    <div class="border-t pt-6">
                        <div class="flex items-start justify-between gap-4">
                            <span>Next Mass Service</span>
                            <?php if ($nextMass): ?>
                                <strong class="text-right leading-7">
                                    <?= e($nextMassTime ?? 'Time TBA') ?>
                                    <span class="block"><?= e((string) $nextMassDate) ?></span>
                                </strong>
                            <?php else: ?>
                                <strong class="max-w-32 text-right leading-7 text-slate-500">No Mass Scheduled</strong>
                            <?php endif; ?>
                        </div>
                    </div>
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
            <div class="flex items-center justify-between p-8"><h3 class="text-3xl font-black">Service Rates & Requirements</h3><span class="rounded-lg bg-yellow-50 px-4 py-2 font-bold text-parish">Managed by Admin</span></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Service Type</th><th>Processing Fee</th><th>Required Documents</th><th>Availability</th></tr></thead>
                    <tbody>
                        <?php foreach ($serviceRates as [$serviceType, $fee, $time, $availability, $badgeClass]): ?>
                            <tr class="border-t">
                                <td class="p-5 font-bold"><?= e($serviceType) ?></td>
                                <td><?= e($fee) ?></td>
                                <td><?= e($time) ?></td>
                                <td><span class="rounded px-3 py-1 text-xs font-bold <?= e($badgeClass) ?>"><?= e($availability) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<?php app_footer(); page_end(); ?>
