<?php

declare(strict_types=1);

use App\Models\Certificate;
use App\Models\ReferenceData;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$certificates = new Certificate($container->pdo());
$requests = $certificates->forUserWithDigital((int) Auth::userId());
$certificateTypes = (new ReferenceData($container->pdo()))->certificateTypes();

page_start('Certificates');
sidebar('Certificates');
app_header('Certificates', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>
        <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-5xl font-black text-parish">Certificates</h2>
                <p class="mt-2 text-xl text-slate-700">Request and track your church certificates.</p>
            </div>
            <button data-open="requestModal" class="rounded-xl bg-parish px-7 py-4 text-lg font-bold text-white shadow-soft hover:bg-parishDark">
                <i class="bi bi-plus-lg mr-2"></i>Request Certificate
            </button>
        </div>

        <section class="mb-10 grid gap-6 md:grid-cols-3">
            <div class="rounded-xl bg-white p-8 shadow-soft"><div class="font-bold uppercase tracking-widest">Pending Requests</div><div class="mt-3 text-5xl font-black text-amber-800"><?= $certificates->countByStatus('Pending', Auth::userId()) ?></div></div>
            <div class="rounded-xl bg-white p-8 shadow-soft"><div class="font-bold uppercase tracking-widest">Approved</div><div class="mt-3 text-5xl font-black text-parish"><?= $certificates->countByStatus('Approved', Auth::userId()) ?></div></div>
            <div class="rounded-xl bg-white p-8 shadow-soft"><div class="font-bold uppercase tracking-widest">Total Requests</div><div class="mt-3 text-5xl font-black"><?= $certificates->countByStatus(null, Auth::userId()) ?></div></div>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 px-8 pt-8">
                <div class="inline-block border-b-2 border-parish px-1 pb-5 text-lg font-bold text-parish">My Requests</div>
                <div class="ml-8 inline-block pb-5 text-lg">Request History</div>
            </div>
            <?php if ($requests === []): ?>
                <div class="py-24 text-center">
                    <div class="mx-auto grid h-28 w-28 place-items-center rounded-full bg-slate-100 text-5xl text-slate-400"><i class="bi bi-folder-x"></i></div>
                    <h3 class="mt-8 text-3xl font-black">No certificate requests found</h3>
                    <p class="mt-3 text-lg text-slate-600">Click the request button above to get started.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Type</th><th>Name</th><th>Delivery</th><th>Status</th><th>Date</th><th>Certificate</th></tr></thead>
                        <tbody>
                        <?php foreach ($requests as $row): ?>
                            <?php
                                $hasDigital = !empty($row['digital_certificate_id']);
                                $isECertificate = ($row['delivery_mode'] ?? $row['delivery_option'] ?? '') === 'E-Certificate';
                                $hasVerifiedPayment = !empty($row['verified_payment_id']);
                                $paymentDescription = urlencode($row['certificate_type'] . ' - ' . $row['full_name']);
                            ?>
                            <tr class="border-t border-slate-100 align-top">
                                <td class="p-5 font-bold"><?= e($row['certificate_type']) ?></td>
                                <td><?= e($row['full_name']) ?></td>
                                <td>
                                    <div class="font-semibold"><?= e($row['delivery_mode'] ?? $row['delivery_option'] ?? 'Walk-in Pickup') ?></div>
                                    <div class="text-sm text-slate-500"><?= e($row['requester_location'] ?? 'Near Parish') ?></div>
                                </td>
                                <td><?= status_badge((string) $row['status']) ?></td>
                                <td><?= e(date('M d, Y', strtotime($row['created_at']))) ?></td>
                                <td class="p-5">
                                    <?php if (!$hasDigital): ?>
                                        <span class="text-sm font-semibold text-slate-400">Waiting for admin issuance</span>
                                    <?php elseif (!$isECertificate): ?>
                                        <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">Ready for parish office pickup</div>
                                    <?php elseif (!$hasVerifiedPayment): ?>
                                        <div class="grid gap-2">
                                            <span class="text-sm font-semibold text-slate-500">Locked until payment is verified</span>
                                            <a class="rounded-lg bg-parish px-3 py-2 text-center text-sm font-bold text-white" href="payments.php?certificate_id=<?= (int) $row['id'] ?>&description=<?= $paymentDescription ?>&amount=150">Pay to Unlock</a>
                                        </div>
                                    <?php else: ?>
                                        <a class="rounded-lg bg-green-600 px-3 py-2 text-sm font-bold text-white" href="certificate_view.php?id=<?= (int) $row['id'] ?>">View E-Certificate</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<div id="requestModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/users/certificates/request_certificate.php" enctype="multipart/form-data" class="w-full max-w-2xl rounded-2xl bg-white p-8 shadow-soft">
        <div class="mb-6 flex items-start justify-between"><h3 class="text-3xl font-black text-parish">Request Certificate</h3><button type="button" data-close class="text-2xl">&times;</button></div>
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="mb-1 block font-bold">Certificate Type</span>
                <select name="certificate_type" id="certificateType" required class="w-full rounded-lg border p-3">
                    <?php foreach ($certificateTypes as $type): ?>
                        <option value="<?= e($type['name']) ?>"><?= e($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Birth Date</span><input type="date" name="birth_date" required class="w-full rounded-lg border p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Full Name</span><input name="full_name" required class="w-full rounded-lg border p-3"></label>
            <label class="sm:col-span-2">
                <span class="mb-1 block font-bold">Your Location</span>
                <select name="requester_location" id="requesterLocation" class="w-full rounded-lg border p-3">
                    <option value="Near Parish">Near the parish - walk-in pickup is recommended</option>
                    <option value="Far from Parish">Far from the parish - e-certificate is recommended</option>
                </select>
                <span id="deliveryHint" class="mt-2 block rounded-lg bg-green-50 px-3 py-2 text-sm font-semibold text-green-700">Near members are guided to claim the certificate at the parish office.</span>
            </label>
            <div id="supportingDocumentGroup" class="sm:col-span-1">
                <div id="supportingDocumentField" class="max-h-0 overflow-hidden opacity-0 pointer-events-none translate-y-1 transition-all duration-200 ease-in-out">
                    <label class="block"><span class="mb-1 block text-sm font-bold">Supporting Document</span><input type="file" name="baptismal_file" id="supportingDocumentInput" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border p-3"></label>
                </div>
                <div id="supportingDocumentHelper" class="mt-2 max-h-24 rounded-lg bg-green-50 px-3 py-2 text-sm font-semibold text-green-700 opacity-100 translate-y-0 transition-all duration-200 ease-in-out">If you have an old copy of your baptismal certificate, you may upload it optionally to help us locate records faster.</div>
            </div>
            <label><span class="mb-1 block font-bold">Valid ID</span><input type="file" name="id_file" accept=".jpg,.jpeg,.png,.pdf" required class="w-full rounded-lg border p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Notes</span><textarea name="notes" class="w-full rounded-lg border p-3"></textarea></label>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Submit Request</button>
    </form>
</div>
<script>
document.querySelector('[data-open]')?.addEventListener('click', e => document.getElementById(e.currentTarget.dataset.open).classList.replace('hidden', 'flex'));
document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => b.closest('.fixed').classList.replace('flex', 'hidden')));
const requesterLocation = document.getElementById('requesterLocation');
const deliveryHint = document.getElementById('deliveryHint');
const certificateType = document.getElementById('certificateType');
const supportingDocumentGroup = document.getElementById('supportingDocumentGroup');
const supportingDocumentField = document.getElementById('supportingDocumentField');
const supportingDocumentInput = document.getElementById('supportingDocumentInput');
const supportingDocumentHelper = document.getElementById('supportingDocumentHelper');
if (requesterLocation && deliveryHint) {
    const syncDeliveryHint = () => {
        deliveryHint.textContent = requesterLocation.value === 'Far from Parish'
            ? 'Far members may receive an e-certificate after admin issuance and verified payment.'
            : 'Near members are guided to claim the certificate at the parish office.';
    };
    requesterLocation.addEventListener('change', syncDeliveryHint);
    syncDeliveryHint();
}
if (certificateType && supportingDocumentGroup && supportingDocumentField && supportingDocumentInput && supportingDocumentHelper) {
    const syncSupportingDocument = () => {
        const isBaptismal = certificateType.value === 'Baptismal Certificate';
        supportingDocumentInput.required = false;
        supportingDocumentInput.value = '';
        supportingDocumentField.classList.toggle('opacity-0', isBaptismal);
        supportingDocumentField.classList.toggle('max-h-0', isBaptismal);
        supportingDocumentField.classList.toggle('overflow-hidden', isBaptismal);
        supportingDocumentField.classList.toggle('pointer-events-none', isBaptismal);
        supportingDocumentField.classList.toggle('translate-y-1', isBaptismal);
        supportingDocumentField.classList.toggle('max-h-40', !isBaptismal);
        supportingDocumentField.classList.toggle('opacity-100', !isBaptismal);
        supportingDocumentField.classList.toggle('translate-y-0', !isBaptismal);
        supportingDocumentHelper.classList.toggle('opacity-0', !isBaptismal);
        supportingDocumentHelper.classList.toggle('max-h-0', !isBaptismal);
        supportingDocumentHelper.classList.toggle('overflow-hidden', !isBaptismal);
        supportingDocumentHelper.classList.toggle('pointer-events-none', !isBaptismal);
        supportingDocumentHelper.classList.toggle('translate-y-1', !isBaptismal);
        supportingDocumentHelper.classList.toggle('max-h-24', isBaptismal);
        supportingDocumentHelper.classList.toggle('opacity-100', isBaptismal);
        supportingDocumentHelper.classList.toggle('translate-y-0', isBaptismal);
    };
    certificateType.addEventListener('change', syncSupportingDocument);
    syncSupportingDocument();
}
</script>
<?php app_footer(); page_end(); ?>
