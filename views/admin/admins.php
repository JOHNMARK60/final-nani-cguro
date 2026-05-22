<?php

declare(strict_types=1);

use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$users = new User($container->pdo());
$admins = $users->admins([], 50, 0);
$user = $users->find(Auth::userId()) ?? [];
$openCreate = !empty($_SESSION['form_errors']) && empty($_SESSION['old_input']['id']);
$openEdit = !empty($_SESSION['form_errors']) && !empty($_SESSION['old_input']['id']) && empty($_SESSION['old_input']['password']);
$openReset = !empty($_SESSION['form_errors']) && !empty($_SESSION['old_input']['id']) && !empty($_SESSION['old_input']['password']) && count($_SESSION['old_input']) <= 2;

page_start('Admin Management');
sidebar('Admins');
app_header('Admin Management', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-7xl px-6 lg:px-12">
        <?php flash_messages(); ?>

        <section class="rounded-xl bg-white p-8 shadow-soft">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-4xl font-bold text-parish">Admin Management</h2>
                    <p class="mt-2 text-slate-600">Create and manage admin accounts.</p>
                </div>
                <button data-open="createAdminModal" class="rounded-xl bg-parish px-6 py-3 font-semibold text-white">New Admin</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-100 text-sm uppercase tracking-widest text-slate-600">
                        <tr>
                            <th class="p-5">Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr class="border-t">
                                <td class="p-5 font-semibold"><?= e($admin['fullname']) ?></td>
                                <td><?= e($admin['email']) ?></td>
                                <td><?= e($admin['username']) ?></td>
                                <td><span class="rounded-full px-3 py-1 text-xs font-bold <?= $admin['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' ?>"><?= e($admin['status']) ?></span></td>
                                <td><?= e($admin['created_by_name'] ?? '-') ?></td>
                                <td class="flex flex-wrap gap-2 p-5">
                                    <button
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700"
                                        data-edit-admin='<?= e(json_encode($admin)) ?>'
                                    >Edit</button>
                                    <form method="POST" action="../../controllers/admin/admins/toggle.php" data-confirm="Change this admin status?" data-confirm-text="This will enable or disable the admin account." data-confirm-button="Update">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                                        <button class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white">Toggle</button>
                                    </form>
                                    <button
                                        class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white"
                                        data-reset-admin='<?= e(json_encode($admin)) ?>'
                                    >Reset Password</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<div id="createAdminModal" class="fixed inset-0 z-50 <?= $openCreate ? 'flex' : 'hidden' ?> items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/admins/create.php" class="w-full max-w-2xl rounded-2xl bg-white p-8 shadow-soft">
        <div class="mb-6 flex items-start justify-between"><h3 class="text-3xl font-bold text-slate-950">Create Admin</h3><button type="button" data-close class="text-2xl">&times;</button></div>
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2"><span class="mb-1 block text-sm font-semibold text-slate-600">Full Name</span><input name="fullname" value="<?= e((string) old('fullname')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3"><?php if ($msg = field_error('fullname')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?></label>
            <label><span class="mb-1 block text-sm font-semibold text-slate-600">Email</span><input name="email" type="email" value="<?= e((string) old('email')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3"><?php if ($msg = field_error('email')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?></label>
            <label><span class="mb-1 block text-sm font-semibold text-slate-600">Username</span><input name="username" value="<?= e((string) old('username')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3"><?php if ($msg = field_error('username')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?></label>
            <div>
                <label for="create_admin_password" class="mb-1 block text-sm font-semibold text-slate-600">Password</label>
                <div class="relative" data-password-field>
                    <input id="create_admin_password" name="password" type="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-12">
                    <button type="button" data-password-toggle class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-400 transition hover:text-parish focus:outline-none" aria-label="Show password" aria-pressed="false" title="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <?php if ($msg = field_error('password')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
            </div>
        </div>
        <button class="mt-6 rounded-xl bg-parish px-6 py-3 font-semibold text-white">Create Admin</button>
    </form>
</div>

<div id="editAdminModal" class="fixed inset-0 z-50 <?= $openEdit ? 'flex' : 'hidden' ?> items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/admins/update.php" class="w-full max-w-2xl rounded-2xl bg-white p-8 shadow-soft">
        <div class="mb-6 flex items-start justify-between"><h3 class="text-3xl font-bold text-slate-950">Edit Admin</h3><button type="button" data-close class="text-2xl">&times;</button></div>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="edit_id" value="<?= e((string) old('id')) ?>">
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2"><span class="mb-1 block text-sm font-semibold text-slate-600">Full Name</span><input name="fullname" id="edit_fullname" value="<?= e((string) old('fullname')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3"><?php if ($msg = field_error('fullname')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?></label>
            <label><span class="mb-1 block text-sm font-semibold text-slate-600">Email</span><input name="email" type="email" id="edit_email" value="<?= e((string) old('email')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3"><?php if ($msg = field_error('email')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?></label>
            <label><span class="mb-1 block text-sm font-semibold text-slate-600">Username</span><input name="username" id="edit_username" value="<?= e((string) old('username')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3"><?php if ($msg = field_error('username')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?></label>
            <label><span class="mb-1 block text-sm font-semibold text-slate-600">Status</span><select name="status" id="edit_status" class="w-full rounded-xl border border-slate-200 px-4 py-3"><option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option><option value="disabled" <?= old('status') === 'disabled' ? 'selected' : '' ?>>Disabled</option></select><?php if ($msg = field_error('status')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?></label>
        </div>
        <button class="mt-6 rounded-xl bg-parish px-6 py-3 font-semibold text-white">Save Changes</button>
    </form>
</div>

<div id="resetAdminModal" class="fixed inset-0 z-50 <?= $openReset ? 'flex' : 'hidden' ?> items-center justify-center bg-slate-950/50 p-4">
    <form method="POST" action="../../controllers/admin/admins/reset_password.php" class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-soft">
        <div class="mb-6 flex items-start justify-between"><h3 class="text-3xl font-bold text-slate-950">Reset Password</h3><button type="button" data-close class="text-2xl">&times;</button></div>
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="reset_id">
        <div>
            <label for="reset_admin_password" class="mb-1 block text-sm font-semibold text-slate-600">New Password</label>
            <div class="relative" data-password-field>
                <input id="reset_admin_password" name="password" type="password" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-12">
                <button type="button" data-password-toggle class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-400 transition hover:text-parish focus:outline-none" aria-label="Show password" aria-pressed="false" title="Show password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <?php if ($msg = field_error('password')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
        </div>
        <button class="mt-6 rounded-xl bg-slate-900 px-6 py-3 font-semibold text-white">Reset Password</button>
    </form>
</div>

<script>
document.querySelectorAll('[data-open]').forEach(btn => btn.addEventListener('click', () => {
    const modal = document.getElementById(btn.dataset.open);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}));
document.querySelectorAll('[data-close]').forEach(btn => btn.addEventListener('click', () => {
    const modal = btn.closest('.fixed');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}));
document.querySelectorAll('[data-edit-admin]').forEach(btn => btn.addEventListener('click', () => {
    const admin = JSON.parse(btn.dataset.editAdmin);
    document.getElementById('edit_id').value = admin.id;
    document.getElementById('edit_fullname').value = admin.fullname;
    document.getElementById('edit_email').value = admin.email;
    document.getElementById('edit_username').value = admin.username;
    document.getElementById('edit_status').value = admin.status;
    document.getElementById('editAdminModal').classList.remove('hidden');
    document.getElementById('editAdminModal').classList.add('flex');
}));
document.querySelectorAll('[data-reset-admin]').forEach(btn => btn.addEventListener('click', () => {
    const admin = JSON.parse(btn.dataset.resetAdmin);
    document.getElementById('reset_id').value = admin.id;
    document.getElementById('resetAdminModal').classList.remove('hidden');
    document.getElementById('resetAdminModal').classList.add('flex');
}));
</script>
<?php app_footer(); page_end(); ?>
<?php clear_form_state(); ?>
