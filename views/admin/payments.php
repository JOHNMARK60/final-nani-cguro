<?php

declare(strict_types=1);

use App\Models\Payment;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$model = new Payment($container->pdo());

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'method' => trim((string) ($_GET['method'] ?? '')),
    'from' => trim((string) ($_GET['from'] ?? '')),
    'to' => trim((string) ($_GET['to'] ?? '')),
];

$total = $model->countQueue($filters);
$items = $model->queue($filters, $perPage, ($page - 1) * $perPage);
$totalPages = (int) max(1, ceil($total / $perPage));

page_start('Payment Management');
sidebar('Payments');
app_header('Payment Management', $user);
?>
<main class="pb-32 pt-8 lg:ml-64 lg:pb-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-12">
        <?php flash_messages(); ?>

        <section class="mb-8 grid gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-green-700 p-6 text-white shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest text-green-100">Verified Revenue</div>
                <div class="mt-3 text-3xl font-black"><?= e(peso($model->sumByStatus('Verified'))) ?></div>
            </div>
            <div class="rounded-xl bg-parish p-6 text-white shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest text-blue-100">For Review</div>
                <div class="mt-3 text-3xl font-black"><?= $model->countByStatus('Submitted') ?></div>
            </div>
            <div class="rounded-xl bg-gold p-6 text-slate-950 shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest">Unpaid / Cash Due</div>
                <div class="mt-3 text-3xl font-black"><?= e(peso($model->sumByStatus('Unpaid'))) ?></div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-soft sm:p-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-3xl font-black text-parish sm:text-4xl">Peso Payment Queue</h2>
                    <p class="mt-2 text-slate-600">Verify GCash, cash, and bank transfer payments in Philippine Peso.</p>
                </div>
            </div>

            <form method="GET" class="mt-6 grid gap-4 md:grid-cols-6">
                <input name="q" value="<?= e($filters['search']) ?>" class="rounded-xl border border-slate-200 px-4 py-3 md:col-span-2" placeholder="Search member, ref, description">
                <select name="status" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Status</option>
                    <?php foreach (['Unpaid', 'Submitted', 'Verified', 'Rejected'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="method" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Methods</option>
                    <?php foreach (['Cash', 'GCash', 'Bank Transfer'] as $method): ?>
                        <option value="<?= e($method) ?>" <?= $filters['method'] === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="from" value="<?= e($filters['from']) ?>" class="rounded-xl border border-slate-200 px-4 py-3">
                <button class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">Filter</button>
                <input type="date" name="to" value="<?= e($filters['to']) ?>" class="rounded-xl border border-slate-200 px-4 py-3 md:col-start-5">
                <a href="payments.php" class="rounded-xl border border-slate-200 px-6 py-3 text-center font-semibold text-slate-700">Reset</a>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600">
                        <tr>
                            <th class="p-5">Payment</th>
                            <th>Member</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Proof</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($items === []): ?>
                        <tr><td colspan="7" class="p-16 text-center text-slate-400">No payment records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $row): ?>
                        <tr class="border-t align-top">
                            <td class="p-5">
                                <div class="font-bold">#<?= (int) $row['id'] ?> <?= e($row['description']) ?></div>
                                <div class="text-sm text-slate-500"><?= e($row['payable_type']) ?><?= !empty($row['reference_number']) ? ' | Ref: ' . e($row['reference_number']) : '' ?></div>
                                <?php if (!empty($row['remarks'])): ?>
                                    <div class="mt-1 text-sm text-rose-600"><?= e($row['remarks']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['member_name'] ?? '-') ?><div class="text-sm text-slate-500"><?= e($row['member_email'] ?? '-') ?></div></td>
                            <td class="font-bold"><?= e(peso($row['amount'])) ?></td>
                            <td><?= e($row['method']) ?></td>
                            <td><?= status_badge((string) $row['status']) ?></td>
                            <td>
                                <?php if (!empty($row['proof_file'])): ?>
                                    <a class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" href="../../controllers/files/payment.php?id=<?= (int) $row['id'] ?>&mode=preview" target="_blank">Preview</a>
                                <?php else: ?>
                                    <span class="text-sm text-slate-400">No file</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5">
                                <div class="grid gap-2">
                                    <form method="POST" action="../../controllers/admin/payments/verify.php" data-confirm="Verify this payment?" data-confirm-text="This will count the amount as verified Peso revenue." data-confirm-button="Verify">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input name="remarks" class="mb-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Optional remarks">
                                        <button class="w-full rounded-lg bg-green-600 px-3 py-2 text-sm font-semibold text-white">Verify</button>
                                    </form>
                                    <form method="POST" action="../../controllers/admin/payments/reject.php" data-confirm="Reject this payment?" data-confirm-text="The member will see the rejection remarks." data-confirm-icon="warning" data-confirm-button="Reject">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <input name="remarks" class="mb-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Reason if rejected">
                                        <button class="w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">Reject</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?= pagination_links($page, $totalPages, $filters) ?>
        </section>
    </div>
</main>
<?php app_footer(); page_end(); ?>
