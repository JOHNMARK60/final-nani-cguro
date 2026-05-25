<?php

declare(strict_types=1);

use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$userModel = new User($container->pdo());
$user = $userModel->find(Auth::userId()) ?? [];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$filters = [
    'search' => trim((string) ($_GET['q'] ?? '')),
    'role' => trim((string) ($_GET['role'] ?? '')),
    'status' => trim((string) ($_GET['status'] ?? '')),
];
$items = $userModel->users($filters, $perPage, ($page - 1) * $perPage);
$total = $userModel->countUsers($filters);
$totalPages = (int) max(1, ceil($total / $perPage));
$roles = ['user', 'admin'];
$statuses = ['active', 'disabled'];

page_start('User Management');
sidebar('Users');
app_header('User Management', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>

        <section class="rounded-xl bg-white p-6 shadow-soft sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-4xl font-black text-parish">User Management</h2>
                    <p class="mt-2 text-slate-600">Add, edit, and safely disable member or admin accounts without deleting transaction records.</p>
                </div>
                <button type="button" data-open="userModal" class="inline-flex items-center justify-center gap-2 rounded-xl bg-parish px-6 py-3 font-bold text-white">
                    <i class="bi bi-person-plus"></i>
                    Add User
                </button>
            </div>

            <form method="GET" class="mt-6 grid gap-4 lg:grid-cols-5">
                <input name="q" value="<?= e($filters['search']) ?>" class="rounded-xl border border-slate-200 px-4 py-3 lg:col-span-2" placeholder="Search name, email, username, phone">
                <select name="role" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= e($role) ?>" <?= $filters['role'] === $role ? 'selected' : '' ?>><?= e(ucfirst($role)) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="rounded-xl border border-slate-200 px-4 py-3">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">Filter</button>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600">
                    <tr>
                        <th class="p-5">User</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Volunteer</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($items === []): ?>
                        <tr><td colspan="7" class="p-16 text-center text-slate-400">No users found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $row): ?>
                        <tr class="border-t align-top">
                            <td class="p-5">
                                <div class="font-black"><?= e($row['fullname']) ?></div>
                                <div class="text-sm text-slate-500">@<?= e($row['username']) ?></div>
                            </td>
                            <td>
                                <div><?= e($row['email']) ?></div>
                                <div class="text-sm text-slate-500"><?= e($row['phone'] ?: '-') ?></div>
                            </td>
                            <td class="max-w-xs text-sm leading-6 text-slate-600"><?= e($row['address'] ?: '-') ?></td>
                            <td><span class="rounded-full bg-yellow-50 px-3 py-1 text-xs font-bold text-parish"><?= e(ucfirst($row['role'])) ?></span></td>
                            <td><?= status_badge((string) $row['status']) ?></td>
                            <td>
                                <?php if ((int) ($row['active_volunteer'] ?? 0) === 1): ?>
                                    <?= status_badge('Eligible') ?>
                                    <div class="mt-1 text-xs text-slate-500"><?= !empty($row['volunteer_eligible_at']) ? e(date('M d, Y', strtotime((string) $row['volunteer_eligible_at']))) : '' ?></div>
                                <?php else: ?>
                                    <span class="text-sm text-slate-400">Not eligible</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5">
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700" data-edit-user='<?= e(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'>Edit</button>
                                    <?php if ((int) $row['id'] !== (int) Auth::userId()): ?>
                                        <form method="POST" action="../../controllers/admin/users/archive.php" data-confirm="Disable this user?" data-confirm-text="Their records remain in place, but they cannot sign in until reactivated." data-confirm-icon="warning" data-confirm-button="Disable">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <button class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white">Disable</button>
                                        </form>
                                    <?php endif; ?>
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

<div id="userModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/users/create.php" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <h3 class="text-3xl font-black text-parish">Add User</h3>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Full Name</span><input name="fullname" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Email</span><input name="email" type="email" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Username</span><input name="username" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Contact Number</span><input name="phone" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Designation</span><input name="designation" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Address</span><input name="address" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label>
                <span class="mb-1 block font-bold">Role</span>
                <select name="role" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($roles as $role): ?><option value="<?= e($role) ?>"><?= e(ucfirst($role)) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="mb-1 block font-bold">Status</span>
                <select name="status" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>"><?= e(ucfirst($status)) ?></option><?php endforeach; ?>
                </select>
            </label>
            <div class="sm:col-span-2">
                <label for="create_user_password" class="mb-1 block font-bold">Password</label>
                <div class="relative" data-password-field>
                    <input id="create_user_password" name="password" type="password" required class="w-full rounded-lg border border-slate-200 p-3 pr-12">
                    <button type="button" data-password-toggle class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-400 transition hover:text-parish focus:outline-none" aria-label="Show password" aria-pressed="false" title="Show password"><i class="bi bi-eye"></i></button>
                </div>
            </div>
        </div>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Create User</button>
    </form>
</div>

<div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/users/update.php" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-soft sm:p-8">
        <div class="mb-6 flex items-start justify-between">
            <h3 class="text-3xl font-black text-parish">Edit User</h3>
            <button type="button" data-close class="text-2xl text-slate-400">&times;</button>
        </div>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="edit_user_id">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Full Name</span><input name="fullname" id="edit_user_fullname" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Email</span><input name="email" id="edit_user_email" type="email" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Username</span><input name="username" id="edit_user_username" required class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Contact Number</span><input name="phone" id="edit_user_phone" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label><span class="mb-1 block font-bold">Designation</span><input name="designation" id="edit_user_designation" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label class="sm:col-span-2"><span class="mb-1 block font-bold">Address</span><input name="address" id="edit_user_address" class="w-full rounded-lg border border-slate-200 p-3"></label>
            <label>
                <span class="mb-1 block font-bold">Role</span>
                <select name="role" id="edit_user_role" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($roles as $role): ?><option value="<?= e($role) ?>"><?= e(ucfirst($role)) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="mb-1 block font-bold">Status</span>
                <select name="status" id="edit_user_status" class="w-full rounded-lg border border-slate-200 p-3">
                    <?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>"><?= e(ucfirst($status)) ?></option><?php endforeach; ?>
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
document.querySelectorAll('[data-edit-user]').forEach(button => button.addEventListener('click', () => {
    const user = JSON.parse(button.dataset.editUser);
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_user_fullname').value = user.fullname || '';
    document.getElementById('edit_user_email').value = user.email || '';
    document.getElementById('edit_user_username').value = user.username || '';
    document.getElementById('edit_user_phone').value = user.phone || '';
    document.getElementById('edit_user_designation').value = user.designation || '';
    document.getElementById('edit_user_address').value = user.address || '';
    document.getElementById('edit_user_role').value = user.role || 'user';
    document.getElementById('edit_user_status').value = user.status || 'active';
    document.getElementById('editUserModal').classList.replace('hidden', 'flex');
}));
</script>
<?php app_footer(); page_end(); ?>
