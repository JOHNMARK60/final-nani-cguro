<?php

declare(strict_types=1);

use App\Models\Inventory;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$inventory = new Inventory($container->pdo());
$categories = $inventory->categories();
$categoryFilters = ['All', 'Church Supplies', 'Drinks / Beverages'];
$stockFilters = ['Low Stock', 'Out of Stock'];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'category' => trim((string) ($_GET['category'] ?? 'All')),
    'stock' => trim((string) ($_GET['stock'] ?? '')),
];

$items = $inventory->items($filters, $perPage, ($page - 1) * $perPage);
$total = $inventory->countItems($filters);
$totalPages = (int) max(1, ceil($total / $perPage));
$queryBase = ['q' => $filters['search']];

page_start('Inventory');
sidebar('Inventory');
app_header('Inventory', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>

        <section class="mb-8 grid gap-4 md:grid-cols-3">
            <div class="rounded-xl bg-white p-6 shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest text-slate-500">Inventory Items</div>
                <div class="mt-3 text-4xl font-black text-parish"><?= (int) $inventory->countItems([]) ?></div>
            </div>
            <div class="rounded-xl bg-gold p-6 text-slate-950 shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest">Low Stock</div>
                <div class="mt-3 text-4xl font-black"><?= (int) $inventory->lowStockCount() ?></div>
            </div>
            <div class="rounded-xl bg-red-50 p-6 shadow-soft">
                <div class="text-sm font-bold uppercase tracking-widest text-red-700">Out of Stock</div>
                <div class="mt-3 text-4xl font-black text-red-700"><?= (int) $inventory->outOfStockCount() ?></div>
            </div>
        </section>

        <section class="rounded-xl bg-white p-6 shadow-soft sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-4xl font-black text-parish">Supply Inventory</h2>
                    <p class="mt-2 text-slate-600">Arrange church supplies by category, stock status, and item name.</p>
                </div>
                <button type="button" data-open="inventoryModal" class="inline-flex items-center justify-center gap-2 rounded-xl bg-parish px-6 py-3 font-bold text-white">
                    <i class="bi bi-plus-lg"></i>
                    Add Item
                </button>
            </div>

            <form method="GET" class="mt-6 grid gap-3 lg:grid-cols-[1fr_auto]">
                <input name="q" value="<?= e($filters['search']) ?>" class="rounded-xl border border-slate-200 px-4 py-3" placeholder="Search item name">
                <button class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">Search</button>
            </form>

            <div class="mt-6 flex flex-wrap gap-2">
                <?php foreach ($categoryFilters as $category): ?>
                    <?php $url = 'inventory.php?' . http_build_query($queryBase + ['category' => $category]); ?>
                    <a href="<?= e($url) ?>" class="rounded-full px-4 py-2 text-sm font-bold <?= $filters['category'] === $category && $filters['stock'] === '' ? 'bg-parish text-white' : 'bg-yellow-50 text-parish' ?>"><?= e($category) ?></a>
                <?php endforeach; ?>
                <?php foreach ($stockFilters as $stock): ?>
                    <?php $url = 'inventory.php?' . http_build_query($queryBase + ['category' => 'All', 'stock' => $stock]); ?>
                    <a href="<?= e($url) ?>" class="rounded-full px-4 py-2 text-sm font-bold <?= $filters['stock'] === $stock ? 'bg-parish text-white' : 'bg-slate-100 text-slate-700' ?>"><?= e($stock) ?></a>
                <?php endforeach; ?>
                <a href="inventory.php" class="rounded-full bg-white px-4 py-2 text-sm font-bold text-slate-600 ring-1 ring-slate-200">Reset</a>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[980px] text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600">
                    <tr>
                        <th class="p-5">Category</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Dates</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($items === []): ?>
                        <tr><td colspan="8" class="p-16 text-center text-slate-400">No inventory items found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <tr class="border-t align-top">
                            <td class="p-5 font-bold"><?= e($item['category_name']) ?></td>
                            <td><?= e($item['item_name']) ?></td>
                            <td class="font-black"><?= e(number_format((float) $item['quantity'], 2)) ?></td>
                            <td><?= e($item['unit']) ?></td>
                            <td><?= $item['price'] !== null ? e(peso($item['price'])) : '<span class="text-slate-400">N/A</span>' ?></td>
                            <td><?= status_badge((string) $item['stock_status']) ?></td>
                            <td>
                                <div class="text-sm font-semibold"><?= e(date('M d, Y', strtotime((string) $item['created_at']))) ?></div>
                                <div class="text-xs text-slate-500">Updated <?= e(date('M d, Y', strtotime((string) $item['updated_at']))) ?></div>
                            </td>
                            <td class="p-5">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" data-edit-item='<?= e(json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'>Edit</button>
                                    <form method="POST" action="../../controllers/admin/inventory/archive.php" data-confirm="Archive this inventory item?" data-confirm-text="The item will be hidden but not removed from history." data-confirm-icon="warning" data-confirm-button="Archive">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">Archive</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= pagination_links($page, $totalPages, array_filter($filters, static fn($value): bool => $value !== '' && $value !== 'All')) ?>
        </section>
    </div>
</main>

<div id="inventoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/inventory/create.php" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <h3 class="text-3xl font-black text-parish">Add Inventory Item</h3>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="mb-1 block font-bold">Category</span>
                <select name="inventory_category_id" required class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Item Name</span><input name="item_name" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Quantity</span><input name="quantity" type="number" min="0" step="0.01" required value="0" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Unit</span><input name="unit" required value="pcs" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Price</span><input name="price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Low Stock Threshold</span><input name="low_stock_threshold" type="number" min="0" step="0.01" required value="5" class="w-full rounded-lg border border-slate-200 p-3"></label>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Save Item</button>
    </form>
</div>

<div id="editInventoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/inventory/update.php" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <h3 class="text-3xl font-black text-parish">Edit Inventory Item</h3>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="edit_inventory_id">
        <div class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="mb-1 block font-bold">Category</span>
                <select name="inventory_category_id" id="edit_inventory_category_id" required class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><span class="mb-1 block font-bold">Item Name</span><input name="item_name" id="edit_item_name" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Quantity</span><input name="quantity" id="edit_quantity" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Unit</span><input name="unit" id="edit_unit" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Price</span><input name="price" id="edit_price" type="number" min="0" step="0.01" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Low Stock Threshold</span><input name="low_stock_threshold" id="edit_low_stock_threshold" type="number" min="0" step="0.01" required class="w-full rounded-lg border border-slate-200 p-3"></label>
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
document.querySelectorAll('[data-edit-item]').forEach(button => button.addEventListener('click', () => {
    const item = JSON.parse(button.dataset.editItem);
    document.getElementById('edit_inventory_id').value = item.id;
    document.getElementById('edit_inventory_category_id').value = item.inventory_category_id;
    document.getElementById('edit_item_name').value = item.item_name || '';
    document.getElementById('edit_quantity').value = item.quantity || 0;
    document.getElementById('edit_unit').value = item.unit || 'pcs';
    document.getElementById('edit_price').value = item.price || '';
    document.getElementById('edit_low_stock_threshold').value = item.low_stock_threshold || 5;
    document.getElementById('editInventoryModal').classList.replace('hidden', 'flex');
}));
</script>
<?php app_footer(); page_end(); ?>
