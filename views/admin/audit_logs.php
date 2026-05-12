<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$model = new AuditLog($container->pdo());

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'action_type' => trim((string) ($_GET['action_type'] ?? '')),
    'target_type' => trim((string) ($_GET['target_type'] ?? '')),
];

$total = $model->count($filters);
$items = $model->search($filters, $perPage, ($page - 1) * $perPage);
$totalPages = (int) max(1, ceil($total / $perPage));

page_start('Audit Logs');
sidebar('Audit Logs');
app_header('Audit Logs', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <section class="rounded-xl bg-white p-8 shadow-soft">
            <h2 class="text-4xl font-bold text-parish">Audit Logs</h2>
            <form method="GET" class="mt-6 grid gap-4 md:grid-cols-4">
                <input name="q" value="<?= e($filters['search']) ?>" class="rounded-xl border border-slate-200 px-4 py-3" placeholder="Search details">
                <input name="action_type" value="<?= e($filters['action_type']) ?>" class="rounded-xl border border-slate-200 px-4 py-3" placeholder="Action type">
                <input name="target_type" value="<?= e($filters['target_type']) ?>" class="rounded-xl border border-slate-200 px-4 py-3" placeholder="Target type">
                <button class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">Filter</button>
            </form>
            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600"><tr><th class="p-5">Action</th><th>Target</th><th>Admin ID</th><th>IP</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr class="border-t">
                            <td class="p-5 font-semibold"><?= e($row['action_type']) ?></td>
                            <td><?= e(($row['target_type'] ?? '-') . ' #' . ($row['target_id'] ?? '-')) ?></td>
                            <td><?= e((string) ($row['admin_id'] ?? '-')) ?></td>
                            <td><?= e((string) ($row['ip_address'] ?? '-')) ?></td>
                            <td><?= e(date('M d, Y h:i A', strtotime($row['created_at']))) ?></td>
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
