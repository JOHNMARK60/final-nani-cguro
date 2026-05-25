<?php

declare(strict_types=1);

use App\Models\AppSetting;
use App\Models\User;
use App\Models\VolunteerService;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');

$userModel = new User($container->pdo());
$volunteerModel = new VolunteerService($container->pdo());
$settings = new AppSetting($container->pdo());
$user = $userModel->find(Auth::userId()) ?? [];
$summary = $volunteerModel->eligibilitySummary((int) Auth::userId());

if (!$summary['is_eligible']) {
    $_SESSION['error'] = 'Volunteer recognition certificate is available after the required approved service hours.';
    header('Location: /E-Parish/views/user/volunteer.php');
    exit;
}

$issuedDate = date('F d, Y');
$churchName = $settings->churchName();
$representative = $settings->authorizedRepresentative();

page_start('Volunteer Recognition Certificate');
?>
<main class="min-h-screen bg-parishSoft px-4 py-8 print:bg-white">
    <div class="mx-auto mb-6 flex max-w-5xl flex-wrap justify-between gap-3 print:hidden">
        <a href="volunteer.php" class="rounded-xl border border-slate-200 bg-white px-5 py-3 font-bold text-slate-700">Back</a>
        <button onclick="window.print()" class="rounded-xl bg-parish px-6 py-3 font-bold text-white">Print / Download PDF</button>
    </div>

    <article class="mx-auto max-w-5xl bg-white p-6 shadow-soft print:shadow-none">
        <div class="certificate-shell min-h-[980px] border-[6px] border-double border-[#B8860B] p-8 text-center">
            <img src="/E-Parish/assets/Churchlogo.png" alt="" class="mx-auto h-24 w-24 object-contain">
            <div class="mt-3 text-sm font-bold uppercase tracking-[0.28em] text-parish"><?= e($churchName) ?></div>
            <div class="mt-8 text-lg uppercase tracking-[0.38em] text-slate-500">Certificate of</div>
            <h1 class="mt-3 font-serif text-5xl font-bold text-slate-950 sm:text-6xl">Good Moral & Service Recognition</h1>

            <p class="mx-auto mt-12 max-w-3xl text-xl leading-10 text-slate-700">
                This certifies that
            </p>
            <div class="mx-auto mt-4 max-w-3xl border-b-2 border-slate-800 pb-3 font-serif text-4xl font-bold text-slate-950">
                <?= e($user['fullname'] ?? 'Parish Volunteer') ?>
            </div>
            <p class="mx-auto mt-10 max-w-3xl text-xl leading-10 text-slate-700">
                has faithfully rendered <strong><?= e(number_format((float) $summary['approved_hours'], 1)) ?> approved service hours</strong>
                to the parish community and is recognized as an eligible active volunteer of good moral standing.
                This recognition may be presented for parish certificate benefits and volunteer service acknowledgement.
            </p>

            <div class="mx-auto mt-14 grid max-w-3xl gap-10 text-left sm:grid-cols-2">
                <div>
                    <div class="text-sm font-bold uppercase tracking-widest text-slate-500">Date Issued</div>
                    <div class="mt-2 border-b border-slate-800 pb-2 text-xl font-bold"><?= e($issuedDate) ?></div>
                </div>
                <div>
                    <div class="text-sm font-bold uppercase tracking-widest text-slate-500">Volunteer Status</div>
                    <div class="mt-2 border-b border-slate-800 pb-2 text-xl font-bold">Eligible / Active Volunteer</div>
                </div>
            </div>

            <div class="mx-auto mt-24 max-w-md">
                <div class="border-b border-slate-900 pb-3 text-xl font-bold"><?= e($representative) ?></div>
                <div class="mt-2 text-sm font-bold uppercase tracking-widest text-slate-500">Authorized Church Representative</div>
            </div>
        </div>
    </article>
</main>
<style>
@media print {
    @page { size: A4 landscape; margin: 10mm; }
    body { background: #fff !important; }
    .certificate-shell { min-height: 180mm; }
}
</style>
<?php page_end(); ?>
