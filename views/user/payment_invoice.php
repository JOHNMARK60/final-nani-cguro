<?php

declare(strict_types=1);

use App\Models\Payment;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');

$id = (int) ($_GET['id'] ?? 0);
$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$payment = (new Payment($container->pdo()))->findForUser($id, (int) Auth::userId());

if (!$payment) {
    http_response_code(404);
    exit('Payment not found');
}

page_start('Payment Receipt');
?>
<main class="min-h-screen bg-slate-100 px-4 py-8 print:bg-white">
    <div class="mx-auto max-w-4xl bg-white p-8 shadow-soft print:shadow-none">
        <div class="grid gap-6 sm:grid-cols-[1fr_240px]">
            <div class="font-sans text-6xl font-bold tracking-wide text-blue-950">RECEIPT</div>
            <div class="font-sans text-sm leading-5">
                <strong>E-Parish Services Management System</strong><br>
                Parish Office<br>
                Official Payment Receipt<br>
                <?= e(date('M d, Y')) ?>
            </div>
        </div>

        <div class="mt-8 grid gap-6 font-sans text-xl sm:grid-cols-2">
            <div>RECEIPT #: <span class="inline-block min-w-32 border-b border-slate-900 px-3"><?= (int) $payment['id'] ?></span></div>
            <div>DATE: <span class="inline-block min-w-44 border-b border-slate-900 px-3"><?= e(date('M d, Y', strtotime($payment['created_at']))) ?></span></div>
        </div>

        <section class="mt-8">
            <div class="bg-blue-950 px-2 py-1 font-sans text-2xl text-white">CUSTOMER INFORMATION</div>
            <div class="space-y-4 border-x-2 border-blue-950 px-1 py-4 font-sans text-xl">
                <div>NAME: <span class="inline-block min-w-[80%] border-b border-slate-900 px-3"><?= e($user['fullname'] ?? 'Parish Member') ?></span></div>
                <div>ADDRESS: <span class="inline-block min-w-[75%] border-b border-slate-900 px-3"><?= e($user['designation'] ?? '') ?></span></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>EMAIL: <span class="inline-block min-w-52 border-b border-slate-900 px-3"><?= e($user['email'] ?? '') ?></span></div>
                    <div>PHONE NUMBER: <span class="inline-block min-w-40 border-b border-slate-900 px-3"><?= e($user['phone'] ?? '') ?></span></div>
                </div>
            </div>
        </section>

        <section>
            <div class="bg-blue-950 px-2 py-1 font-sans text-2xl text-white">BILL OF MATERIALS OR SERVICES</div>
            <table class="w-full border-2 border-blue-950 font-sans text-xl">
                <thead>
                    <tr class="border-b-2 border-blue-950 text-left">
                        <th class="border-r-2 border-blue-950 p-2">DESCRIPTION</th>
                        <th class="border-r-2 border-blue-950 p-2 text-center">QTY</th>
                        <th class="border-r-2 border-blue-950 p-2 text-center">UNIT COST</th>
                        <th class="p-2 text-center">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="h-14 border-b-2 border-blue-950">
                        <td class="border-r-2 border-blue-950 p-2"><?= e($payment['description']) ?></td>
                        <td class="border-r-2 border-blue-950 p-2 text-center">1</td>
                        <td class="border-r-2 border-blue-950 p-2 text-right"><?= e(peso($payment['amount'])) ?></td>
                        <td class="p-2 text-right"><?= e(peso($payment['amount'])) ?></td>
                    </tr>
                    <?php for ($i = 0; $i < 7; $i++): ?>
                        <tr class="h-12 border-b-2 border-blue-950"><td class="border-r-2 border-blue-950"></td><td class="border-r-2 border-blue-950"></td><td class="border-r-2 border-blue-950"></td><td></td></tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </section>

        <section class="mt-8 grid gap-6 sm:grid-cols-[1fr_260px]">
            <div class="min-h-32 border-2 border-blue-950 p-2 font-sans text-xl">
                COMMENTS:<br>
                <span class="text-base">Method: <?= e($payment['method']) ?> | Reference: <?= e($payment['reference_number'] ?: '-') ?> | Status: <?= e($payment['status']) ?></span>
            </div>
            <div class="space-y-3 font-sans text-xl">
                <div class="flex justify-between gap-4">SUBTOTAL: <span class="min-w-32 border-b border-slate-900 text-right"><?= e(peso($payment['amount'])) ?></span></div>
                <div class="flex justify-between gap-4">OTHER: <span class="min-w-32 border-b border-slate-900 text-right"><?= e(peso(0)) ?></span></div>
                <div class="flex justify-between gap-4">TAXES: <span class="min-w-32 border-b border-slate-900 text-right"><?= e(peso(0)) ?></span></div>
                <div class="flex justify-between gap-4 font-bold">TOTAL: <span class="min-w-32 border-b border-slate-900 text-right"><?= e(peso($payment['amount'])) ?></span></div>
            </div>
        </section>

        <div class="mt-8 border-2 border-blue-950 p-3 text-center font-sans text-xl">Thank you for supporting the parish!</div>

        <div class="mt-8 flex flex-wrap gap-3 print:hidden">
            <button onclick="window.print()" class="rounded-xl bg-parish px-6 py-3 font-bold text-white">Print Receipt</button>
            <a href="payments.php" class="rounded-xl border border-slate-200 px-6 py-3 font-bold text-slate-700">Back to Payments</a>
        </div>
    </div>
</main>
<?php page_end(); ?>
