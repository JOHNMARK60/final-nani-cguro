<?php

declare(strict_types=1);

use App\Models\Certificate;
use App\Models\Payment;
use App\Models\Priest;
use App\Security\Auth;
use App\Security\Authorization;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');

$requestId = (int) ($_GET['id'] ?? 0);
$certificateModel = new Certificate($container->pdo());
$paymentModel = new Payment($container->pdo());
$auth = new Authorization($container->pdo());

$certificate = $certificateModel->digitalForRequest($requestId);

if (!$certificate || !$auth->canAccessUserRecord((int) $certificate['user_id'])) {
    http_response_code(404);
    exit('Certificate not found');
}

$isAdmin = Auth::role() === 'admin';
$verifiedPayment = $paymentModel->verifiedFor('Certificate', $requestId, (int) $certificate['user_id']);
$canViewDigital = $isAdmin || ($certificate['delivery_mode'] === 'E-Certificate' && $verifiedPayment !== null);
$title = (string) $certificate['certificate_type'];
$charge = !$isAdmin
    ? $paymentModel->calculateCertificateCharge($requestId, (int) $certificate['user_id'])
    : ['final_amount' => 150, 'discount_percent' => 0];
$eventDate = !empty($certificate['event_date']) ? date('F d, Y', strtotime($certificate['event_date'])) : '________________';
$birthDate = !empty($certificate['birth_date']) ? date('F d, Y', strtotime($certificate['birth_date'])) : '________________';
$issuedDate = !empty($certificate['issued_at']) ? date('F d, Y', strtotime($certificate['issued_at'])) : date('F d, Y');
$priestName = trim((string) ($certificate['officiant'] ?? '')) ?: Priest::DEFAULT_NAME;
$signatureText = (new Priest($container->pdo()))->signatureForName($priestName);

page_start($title);
?>
<main class="min-h-screen bg-slate-100 px-4 py-8 print:bg-white">
    <?php if (!$canViewDigital): ?>
        <section class="mx-auto max-w-2xl rounded-2xl bg-white p-8 text-center shadow-soft">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-amber-100 text-3xl text-amber-800"><i class="bi bi-lock-fill"></i></div>
            <h1 class="mt-6 text-3xl font-black text-slate-950">Certificate Locked</h1>
            <?php if ($certificate['delivery_mode'] === 'Walk-in Pickup'): ?>
                <p class="mt-3 text-slate-600">This certificate is prepared for walk-in pickup at the parish office.</p>
                <a href="certificates.php" class="mt-6 inline-block rounded-xl bg-parish px-6 py-3 font-bold text-white">Back to Certificates</a>
            <?php else: ?>
                <p class="mt-3 text-slate-600">The admin already issued your e-certificate. You can view it after your certificate payment is verified.</p>
                <a href="payments.php?certificate_id=<?= $requestId ?>&description=<?= urlencode($title . ' - ' . $certificate['recipient_name']) ?>&amount=<?= e((string) $charge['final_amount']) ?>" class="mt-6 inline-block rounded-xl bg-parish px-6 py-3 font-bold text-white">Submit Payment<?= $charge['discount_percent'] > 0 ? ' with 10% Discount' : '' ?></a>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <div class="mx-auto mb-4 flex max-w-4xl flex-wrap justify-end gap-3 print:hidden">
            <button onclick="window.print()" class="rounded-xl bg-parish px-6 py-3 font-bold text-white"><i class="bi bi-printer mr-2"></i>Print Certificate</button>
            <a href="<?= $isAdmin ? '/E-Parish/views/admin/certificates.php' : 'certificates.php' ?>" class="rounded-xl border border-slate-200 bg-white px-6 py-3 font-bold text-slate-700">Back</a>
        </div>

        <article class="certificate-paper mx-auto max-w-4xl bg-white p-8 shadow-soft print:shadow-none">
            <div class="certificate-border relative min-h-[1040px] border-[6px] border-double border-[#B8860B] p-8 text-center">
                <div class="corner corner-tl"></div>
                <div class="corner corner-tr"></div>
                <div class="corner corner-bl"></div>
                <div class="corner corner-br"></div>

                <div class="mt-4 font-sans text-sm uppercase tracking-[0.28em] text-parish"><?= e($certificate['church_name']) ?></div>
                <?php if (!empty($certificate['parish_address'])): ?>
                    <div class="mt-1 font-sans text-sm text-slate-600"><?= e($certificate['parish_address']) ?></div>
                <?php endif; ?>
                <div class="mx-auto mt-8 grid h-20 w-20 place-items-center text-6xl text-parish">+</div>
                <h1 class="certificate-heading mt-6 text-5xl text-slate-950 sm:text-6xl"><?= e($title) ?></h1>
                <div class="mt-4 font-sans text-sm uppercase tracking-widest text-slate-500">No. <?= e($certificate['certificate_number']) ?></div>

                <div class="mt-14 text-3xl font-black tracking-wide">This is to Certify</div>
                <?= certificate_body($certificate, $eventDate, $birthDate) ?>

                <div class="mt-12 grid gap-8 text-left sm:grid-cols-2">
                    <div>
                        <div class="font-sans text-sm text-slate-500">Book No.</div>
                        <div class="border-b border-slate-700 pb-2 font-sans text-lg"><?= e($certificate['book_no'] ?: '__________') ?></div>
                    </div>
                    <div>
                        <div class="font-sans text-sm text-slate-500">Page No.</div>
                        <div class="border-b border-slate-700 pb-2 font-sans text-lg"><?= e($certificate['page_no'] ?: '__________') ?></div>
                    </div>
                </div>

                <div class="mt-16 grid items-end gap-10 sm:grid-cols-2">
                    <div class="text-left font-sans text-sm text-slate-600">
                        <div>Dated: <span class="font-bold text-slate-900"><?= e($issuedDate) ?></span></div>
                        <div class="mt-2">Verification Ref: <?= e(substr((string) $certificate['qr_reference'], 0, 18)) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="certificate-signature mx-auto h-14 w-72 overflow-hidden text-4xl text-slate-950"><?= e($signatureText) ?></div>
                        <div class="mx-auto w-72 border-b border-slate-700 pb-2 font-sans text-lg"><?= e($priestName) ?></div>
                        <div class="mt-2 text-xl font-bold">Priest / Parish Registrar</div>
                    </div>
                </div>
            </div>
        </article>
    <?php endif; ?>
</main>

<style>
.certificate-paper {
    aspect-ratio: 8.5 / 11;
}
.certificate-heading {
    font-family: "Poppins", ui-sans-serif, system-ui, sans-serif;
    font-weight: 800;
    letter-spacing: 0;
}
.certificate-signature {
    font-family: "Brush Script MT", "Segoe Script", "Lucida Handwriting", cursive;
    font-weight: 400;
    letter-spacing: 0;
    transform: rotate(-2deg);
}
.certificate-border {
    background:
        radial-gradient(circle at 20% 20%, rgba(37, 99, 235, 0.08), transparent 26%),
        radial-gradient(circle at 80% 35%, rgba(34, 197, 94, 0.08), transparent 28%),
        linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}
.corner {
    position: absolute;
    width: 78px;
    height: 78px;
    border-color: #B8860B;
}
.corner::before,
.corner::after {
    content: "";
    position: absolute;
    border: 3px solid #B8860B;
    border-radius: 999px;
}
.corner::before {
    width: 42px;
    height: 42px;
}
.corner::after {
    width: 22px;
    height: 22px;
}
.corner-tl { left: 16px; top: 16px; border-left: 4px solid; border-top: 4px solid; }
.corner-tr { right: 16px; top: 16px; border-right: 4px solid; border-top: 4px solid; }
.corner-bl { left: 16px; bottom: 16px; border-left: 4px solid; border-bottom: 4px solid; }
.corner-br { right: 16px; bottom: 16px; border-right: 4px solid; border-bottom: 4px solid; }
.corner-tl::before { left: 8px; top: 8px; }
.corner-tl::after { left: 38px; top: 38px; }
.corner-tr::before { right: 8px; top: 8px; }
.corner-tr::after { right: 38px; top: 38px; }
.corner-bl::before { left: 8px; bottom: 8px; }
.corner-bl::after { left: 38px; bottom: 38px; }
.corner-br::before { right: 8px; bottom: 8px; }
.corner-br::after { right: 38px; bottom: 38px; }
@media print {
    @page { size: letter; margin: 0.35in; }
    .certificate-paper { width: 100%; max-width: none; padding: 0; }
}
</style>
<?php page_end(); ?>

<?php
function certificate_body(array $certificate, string $eventDate, string $birthDate): string
{
    $type = strtolower((string) $certificate['certificate_type']);
    $recipient = e((string) $certificate['recipient_name']);
    $parents = e((string) ($certificate['parent_names'] ?: '____________________________'));
    $place = e((string) ($certificate['event_place'] ?: '____________________________'));
    $witnesses = e((string) ($certificate['sponsors_witnesses'] ?: '____________________________'));
    $remarks = e((string) ($certificate['remarks'] ?: 'as appears from the Roman Catholic parish sacramental register.'));

    if (str_contains($type, 'marriage')) {
        return <<<HTML
        <div class="mx-auto mt-10 max-w-3xl space-y-7 text-left font-sans text-xl leading-10">
            <p>That <span class="inline-block min-w-[16rem] border-b border-slate-700 px-3 text-center font-bold">{$recipient}</span></p>
            <p>and <span class="inline-block min-w-[16rem] border-b border-slate-700 px-3 text-center">{$parents}</span> were lawfully <span class="text-3xl font-black">Married</span></p>
            <p>on <span class="inline-block min-w-[14rem] border-b border-slate-700 px-3 text-center">{$eventDate}</span> at <span class="inline-block min-w-[14rem] border-b border-slate-700 px-3 text-center">{$place}</span>.</p>
            <p>In the presence of <span class="inline-block min-w-[22rem] border-b border-slate-700 px-3 text-center">{$witnesses}</span>.</p>
            <p>{$remarks}</p>
        </div>
HTML;
    }

    if (str_contains($type, 'death')) {
        return <<<HTML
        <div class="mx-auto mt-10 max-w-3xl space-y-7 text-left font-sans text-xl leading-10">
            <p>This certifies that <span class="inline-block min-w-[18rem] border-b border-slate-700 px-3 text-center font-bold">{$recipient}</span></p>
            <p>born on <span class="inline-block min-w-[14rem] border-b border-slate-700 px-3 text-center">{$birthDate}</span>, died on <span class="inline-block min-w-[14rem] border-b border-slate-700 px-3 text-center">{$eventDate}</span>.</p>
            <p>Place of death/service: <span class="inline-block min-w-[20rem] border-b border-slate-700 px-3 text-center">{$place}</span>.</p>
            <p>{$remarks}</p>
        </div>
HTML;
    }

    $rite = str_contains($type, 'confirmation') ? 'Confirmed' : 'Baptized';

    return <<<HTML
    <div class="mx-auto mt-10 max-w-3xl space-y-7 text-left font-sans text-xl leading-10">
        <p>That <span class="inline-block min-w-[20rem] border-b border-slate-700 px-3 text-center font-bold">{$recipient}</span></p>
        <p>child of <span class="inline-block min-w-[22rem] border-b border-slate-700 px-3 text-center">{$parents}</span></p>
        <p>born on <span class="inline-block min-w-[14rem] border-b border-slate-700 px-3 text-center">{$birthDate}</span> was <span class="text-3xl font-black">{$rite}</span></p>
        <p>on <span class="inline-block min-w-[14rem] border-b border-slate-700 px-3 text-center">{$eventDate}</span> at <span class="inline-block min-w-[14rem] border-b border-slate-700 px-3 text-center">{$place}</span>.</p>
        <p>Sponsors/Witnesses: <span class="inline-block min-w-[22rem] border-b border-slate-700 px-3 text-center">{$witnesses}</span></p>
        <p>{$remarks}</p>
    </div>
HTML;
}
?>
