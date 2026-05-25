<?php

declare(strict_types=1);

use App\Models\ParishService;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$serviceModel = new ParishService($container->pdo());
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'service_type' => trim((string) ($_GET['type'] ?? '')),
    'availability_status' => trim((string) ($_GET['status'] ?? '')),
];
$services = $serviceModel->rows($filters, $perPage, ($page - 1) * $perPage);
$total = $serviceModel->countRows($filters);
$totalPages = (int) max(1, ceil($total / $perPage));
$types = ['Certificate', 'Appointment', 'Other'];
$statuses = ['Active', 'Inactive'];

page_start('Service Management');
sidebar('Services');
app_header('Service Management', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>

        <section class="rounded-xl bg-white p-6 shadow-soft sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-4xl font-black text-parish">Service Management</h2>
                    <p class="mt-2 text-slate-600">Set service descriptions, prices, required documents, and availability.</p>
                </div>
                <button type="button" data-open="serviceModal" class="inline-flex items-center justify-center gap-2 rounded-xl bg-parish px-6 py-3 font-bold text-white">
                    <i class="bi bi-plus-lg"></i>
                    Add Service
                </button>
            </div>

            <form method="GET" class="mt-6 grid gap-4 lg:grid-cols-5">
                <input name="q" value="<?= e($filters['search']) ?>" class="rounded-xl border border-slate-200 px-4 py-3 lg:col-span-2" placeholder="Search service">
                <select name="type" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Types</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= e($type) ?>" <?= $filters['service_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['availability_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">Filter</button>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600">
                    <tr>
                        <th class="p-5">Service</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Required Documents</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($services === []): ?>
                        <tr><td colspan="6" class="p-16 text-center text-slate-400">No services found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($services as $service): ?>
                        <tr class="border-t align-top">
                            <td class="p-5">
                                <div class="font-black"><?= e($service['service_name']) ?></div>
                                <div class="mt-1 max-w-xl text-sm leading-6 text-slate-500"><?= e((string) ($service['description'] ?? '')) ?></div>
                            </td>
                            <td><span class="rounded-full bg-yellow-50 px-3 py-1 text-xs font-bold text-parish"><?= e($service['service_type']) ?></span></td>
                            <td class="font-bold"><?= e(peso($service['price'])) ?></td>
                            <td class="max-w-sm text-sm leading-6 text-slate-600"><?= e((string) ($service['required_documents'] ?? '-')) ?></td>
                            <td><?= status_badge((string) $service['availability_status']) ?></td>
                            <td class="p-5">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" data-edit-service='<?= e(json_encode($service, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'>Edit</button>
                                    <form method="POST" action="../../controllers/admin/services/archive.php" data-confirm="Archive this service?" data-confirm-text="Old transactions remain intact. The service will be hidden from active lists." data-confirm-icon="warning" data-confirm-button="Archive">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                                        <button class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">Archive</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= pagination_links($page, $totalPages, array_filter($filters, static fn($value): bool => $value !== '')) ?>
        </section>
    </div>
</main>

<div id="serviceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/services/create.php" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <h3 class="text-3xl font-black text-parish">Add Service</h3>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Service Name</span><input name="service_name" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label>
                <span class="mb-1 block font-bold">Type</span>
                <select name="service_type" required class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($types as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Price</span><input name="price" type="number" min="0" step="0.01" required value="0.00" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Description</span><textarea name="description" class="w-full rounded-lg border border-slate-200 p-3"></textarea></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Required Documents</span><textarea name="required_documents" class="w-full rounded-lg border border-slate-200 p-3"></textarea></label>
            <label>
                <span class="mb-1 block font-bold">Availability</span>
                <select name="availability_status" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Save Service</button>
    </form>
</div>

<div id="editServiceModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/services/update.php" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <h3 class="text-3xl font-black text-parish">Edit Service</h3>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="edit_service_id">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Service Name</span><input name="service_name" id="edit_service_name" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label>
                <span class="mb-1 block font-bold">Type</span>
                <select name="service_type" id="edit_service_type" required class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($types as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Price</span><input name="price" id="edit_service_price" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Description</span><textarea name="description" id="edit_service_description" class="w-full rounded-lg border border-slate-200 p-3"></textarea></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Required Documents</span><textarea name="required_documents" id="edit_required_documents" class="w-full rounded-lg border border-slate-200 p-3"></textarea></label>
            <label>
                <span class="mb-1 block font-bold">Availability</span>
                <select name="availability_status" id="edit_availability_status" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Save Changes</button>
    </form>
</div>

<script>
document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => {
    document.getElementById(button.dataset.open)?.classList.replace('hidden', 'flex');
}));
document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => {
    button.closest('.fixed')?.classList.replace('flex', 'hidden');
}));
document.querySelectorAll('[data-edit-service]').forEach(button => button.addEventListener('click', () => {
    const service = JSON.parse(button.dataset.editService);
    document.getElementById('edit_service_id').value = service.id;
    document.getElementById('edit_service_name').value = service.service_name || '';
    document.getElementById('edit_service_type').value = service.service_type || 'Other';
    document.getElementById('edit_service_price').value = service.price || 0;
    document.getElementById('edit_service_description').value = service.description || '';
    document.getElementById('edit_required_documents').value = service.required_documents || '';
    document.getElementById('edit_availability_status').value = service.availability_status || 'Active';
    document.getElementById('editServiceModal').classList.replace('hidden', 'flex');
}));
</script>
<?php app_footer(); page_end(); ?>
