<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\VolunteerService;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$model = new VolunteerService($container->pdo());

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
    'from' => trim((string) ($_GET['from'] ?? '')),
    'to' => trim((string) ($_GET['to'] ?? '')),
];

$total = $model->countQueue($filters);
$items = $model->queue($filters, $perPage, ($page - 1) * $perPage);
$totalPages = (int) max(1, ceil($total / $perPage));

page_start('Volunteer Queue');
sidebar('Volunteers');
app_header('Volunteer Queue', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>
        <section class="rounded-xl bg-white p-8 shadow-soft">
            <h2 class="text-4xl font-bold text-parish">Volunteer Queue</h2>
            <form method="GET" class="mt-6 grid gap-4 md:grid-cols-5">
                <input name="q" value="<?= e($filters['search']) ?>" class="rounded-xl border border-slate-200 px-4 py-3" placeholder="Search name, email, ref">
                <select name="status" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Status</option>
                    <option value="Pending" <?= $filters['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Approved" <?= $filters['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $filters['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="Verified" <?= $filters['status'] === 'Verified' ? 'selected' : '' ?>>Verified</option>
                </select>
                <input type="date" name="from" value="<?= e($filters['from']) ?>" class="rounded-xl border border-slate-200 px-4 py-3">
                <input type="date" name="to" value="<?= e($filters['to']) ?>" class="rounded-xl border border-slate-200 px-4 py-3">
                <button class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">Filter</button>
            </form>
            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Activity</th><th>Member</th><th>Date</th><th>Hours</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr class="border-t">
                            <td class="p-5 font-semibold">#<?= (int) $row['id'] ?> <?= e($row['activity_name']) ?></td>
                            <td><?= e($row['member_name'] ?? '-') ?><div class="text-sm text-slate-500"><?= e($row['member_email'] ?? '-') ?></div></td>
                            <td><?= e((string) $row['activity_date']) ?></td>
                            <td><?= e((string) $row['hours_served']) ?></td>
                            <td><?= e($row['status']) ?></td>
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
