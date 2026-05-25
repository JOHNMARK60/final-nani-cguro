<?php

declare(strict_types=1);

use App\Models\Payment;
use App\Models\ReferenceData;
use App\Models\AppSetting;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$paymentModel = new Payment($container->pdo());
$reference = new ReferenceData($container->pdo());
$settings = new AppSetting($container->pdo());
$payments = $paymentModel->forUser((int) Auth::userId());
$paymentMethods = $reference->paymentMethods();
$paymentCategories = $reference->paymentCategories();
$paidTotal = $paymentModel->sumByStatus('Verified', (int) Auth::userId());
$submittedTotal = $paymentModel->sumByStatus('Submitted', (int) Auth::userId());
$unpaidTotal = $paymentModel->sumByStatus('Unpaid', (int) Auth::userId());
$prefillCertificateId = (int) ($_GET['certificate_id'] ?? 0);
$prefillDescription = trim((string) ($_GET['description'] ?? ''));
$prefillAmount = (float) ($_GET['amount'] ?? 0);
$prefillCharge = null;

if ($prefillCertificateId > 0) {
    $prefillCharge = $paymentModel->calculateCertificateCharge($prefillCertificateId, (int) Auth::userId());
    $prefillDescription = $prefillCharge['certificate_type'] . ' - ' . $prefillCharge['full_name'];
    $prefillAmount = (float) $prefillCharge['final_amount'];
}

$gcash = $settings->gcash();
$gcashName = $gcash['account_name'] ?: 'Parish Office';
$gcashNumber = $gcash['number'];
$gcashQr = trim((string) ($gcash['qr_code'] ?? ''));

page_start('Payments');
sidebar('Payments');
app_header('Payments', $user);
?>
<main class="pb-32 pt-8 lg:ml-64 lg:pb-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-12">
        <?php flash_messages(); ?>

        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-4xl font-black text-parish sm:text-5xl">Peso Payments</h2>
                <p class="mt-2 text-base text-slate-700 sm:text-xl">Monitor payments, upload proof, and print receipts in Philippine Peso.</p>
            </div>
            <button data-open="paymentModal" class="inline-flex items-center justify-center rounded-xl bg-parish px-6 py-3 font-bold text-white shadow-soft transition hover:bg-parishDark">
                <i class="bi bi-plus-lg mr-2"></i>Create Payment
            </button>
        </div>

        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-6 shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest text-slate-500">Verified Payments</div>
                <div class="mt-3 text-3xl font-black text-parish"><?= e(peso($paidTotal)) ?></div>
            </div>
            <div class="rounded-xl bg-white p-6 shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest text-slate-500">For Review</div>
                <div class="mt-3 text-3xl font-black text-parish"><?= e(peso($submittedTotal)) ?></div>
            </div>
            <div class="rounded-xl bg-gold p-6 text-slate-950 shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest">Unpaid / Cash Due</div>
                <div class="mt-3 text-3xl font-black"><?= e(peso($unpaidTotal)) ?></div>
            </div>
        </section>

        <section class="mt-8 overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 p-6 sm:p-8">
                <h3 class="text-2xl font-black text-parish sm:text-3xl">Payment History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600">
                        <tr>
                            <th class="p-5">Payment</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($payments === []): ?>
                        <tr><td colspan="6" class="p-16 text-center text-slate-400">No payment records yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr class="border-t border-slate-100 align-top">
                            <td class="p-5">
                                <div class="font-bold">#<?= (int) $payment['id'] ?> <?= e($payment['description']) ?></div>
                                <div class="text-sm text-slate-500"><?= e($payment['payable_type']) ?><?= !empty($payment['reference_number']) ? ' | Ref: ' . e($payment['reference_number']) : '' ?></div>
                                <?php if ((float) ($payment['discount_amount'] ?? 0) > 0): ?>
                                    <div class="mt-1 text-sm font-semibold text-parish">
                                        Discount: <?= e(number_format((float) $payment['discount_percent'], 0)) ?>%
                                        (<?= e(peso($payment['discount_amount'])) ?>)
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($payment['remarks'])): ?>
                                    <div class="mt-1 text-sm text-rose-600"><?= e($payment['remarks']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="font-bold text-slate-900"><?= e(peso($payment['amount'])) ?></td>
                            <td><?= e($payment['method']) ?></td>
                            <td><?= status_badge((string) $payment['status']) ?></td>
                            <td><?= e(date('M d, Y', strtotime($payment['created_at']))) ?></td>
                            <td class="p-5">
                                <div class="flex flex-wrap gap-2">
                                    <?php if (!empty($payment['proof_file'])): ?>
                                        <a class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" href="../../controllers/files/payment.php?id=<?= (int) $payment['id'] ?>&mode=preview" target="_blank">Proof</a>
                                    <?php endif; ?>
                                    <a class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" href="payment_invoice.php?id=<?= (int) $payment['id'] ?>">Receipt</a>
                                    <?php if ($payment['status'] !== 'Verified'): ?>
                                        <button class="rounded-lg bg-parish px-3 py-2 text-sm font-semibold text-white" data-edit-payment='<?= e(json_encode($payment, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'><?= $payment['status'] === 'Submitted' ? 'Update Proof' : 'Pay / Upload Proof' ?></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<div id="paymentModal" class="fixed inset-0 z-50 <?= $prefillCertificateId > 0 ? 'flex' : 'hidden' ?> items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/users/payments/create.php" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h3 class="text-3xl font-black text-parish">Create Payment</h3>
                <p class="text-slate-500">Create the bill first. Upload proof after you pay.</p>
            </div>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="payable_id" value="<?= $prefillCertificateId > 0 ? $prefillCertificateId : '' ?>">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Description</span><input name="description" value="<?= e($prefillDescription) ?>" required placeholder="Example: Baptismal certificate fee" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Amount in Peso</span><input name="amount" type="number" min="1" step="0.01" value="<?= $prefillAmount > 0 ? e((string) $prefillAmount) : '' ?>" required placeholder="0.00" <?= $prefillCertificateId > 0 ? 'readonly' : '' ?> class="w-full rounded-lg border border-slate-200 p-3 <?= $prefillCertificateId > 0 ? 'bg-slate-100' : '' ?>"></label>
            <label>
                <span class="mb-1 block font-bold">Payment For</span>
                <select name="payable_type" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($paymentCategories as $category): ?>
                        <option value="<?= e($category['name']) ?>" <?= ($prefillCertificateId > 0 && $category['name'] === 'Certificate') || ($prefillCertificateId === 0 && $category['name'] === 'General') ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="mb-1 block font-bold">Method</span>
                <select name="method" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= e($method['name']) ?>" <?= $method['name'] === 'GCash' ? 'selected' : '' ?>><?= e($method['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <?php if ($prefillCharge !== null): ?>
            <div class="mt-5 grid gap-3 rounded-lg bg-yellow-50 p-4 text-sm font-semibold text-yellow-900 sm:grid-cols-2">
                <div>Original Amount: <span class="font-black"><?= e(peso($prefillCharge['original_amount'])) ?></span></div>
                <div>Discount: <span class="font-black"><?= e(number_format((float) $prefillCharge['discount_percent'], 0)) ?>%</span></div>
                <div>Discount Amount: <span class="font-black"><?= e(peso($prefillCharge['discount_amount'])) ?></span></div>
                <div>Final Amount: <span class="font-black"><?= e(peso($prefillCharge['final_amount'])) ?></span></div>
            </div>
        <?php endif; ?>
        <div class="mt-5 rounded-lg bg-yellow-50 p-4 text-sm font-semibold text-yellow-900">
            <?php if ($gcashNumber !== ''): ?>
                Pay through GCash to <?= e($gcashName) ?> at <?= e($gcashNumber) ?>, then open this payment and upload your reference number and proof.
            <?php else: ?>
                GCash receiving number is not configured yet. Ask the parish office for the official number before paying.
            <?php endif; ?>
        </div>
        <?php if ($gcashQr !== ''): ?>
            <img src="/E-Parish/uploads/<?= e($gcashQr) ?>" alt="GCash QR Code" class="mt-4 max-h-64 w-full rounded-lg border border-slate-200 object-contain p-3">
        <?php endif; ?>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Create Payment Record</button>
    </form>
</div>

<div id="uploadPaymentModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/users/payments/upload.php" enctype="multipart/form-data" class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h3 class="text-3xl font-black text-parish">Update Payment Proof</h3>
                <p id="uploadPaymentSummary" class="text-slate-500"></p>
            </div>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="upload_payment_id">
        <div class="mb-5 rounded-lg bg-yellow-50 p-4 text-sm font-semibold text-yellow-900">
            <?php if ($gcashNumber !== ''): ?>
                GCash: send payment to <?= e($gcashName) ?> at <?= e($gcashNumber) ?>, then enter the reference number from your receipt.
            <?php else: ?>
                GCash receiving number is not configured yet. Ask the parish office for the official number before paying.
            <?php endif; ?>
        </div>
        <?php if ($gcashQr !== ''): ?>
            <img src="/E-Parish/uploads/<?= e($gcashQr) ?>" alt="GCash QR Code" class="mb-5 max-h-56 w-full rounded-lg border border-slate-200 object-contain p-3">
        <?php endif; ?>
        <div class="grid gap-4">
            <label>
                <span class="mb-1 block font-bold">Method</span>
                <select name="method" id="upload_method" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?= e($method['name']) ?>"><?= e($method['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Reference Number</span><input name="reference_number" id="upload_reference_number" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Proof of Payment</span><input name="proof_file" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border border-slate-200 p-3"></label>
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
document.querySelectorAll('[data-edit-payment]').forEach(button => button.addEventListener('click', () => {
    const payment = JSON.parse(button.dataset.editPayment);
    document.getElementById('upload_payment_id').value = payment.id;
    document.getElementById('upload_method').value = payment.method || 'GCash';
    document.getElementById('upload_reference_number').value = payment.reference_number || '';
    document.getElementById('uploadPaymentSummary').textContent = `${payment.description} - PHP ${Number(payment.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    document.getElementById('uploadPaymentModal').classList.remove('hidden');
    document.getElementById('uploadPaymentModal').classList.add('flex');
}));
</script>
<?php app_footer(); page_end(); ?>
