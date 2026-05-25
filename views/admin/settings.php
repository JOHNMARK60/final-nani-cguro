<?php

declare(strict_types=1);

use App\Models\AppSetting;
use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireRole('admin', '/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$settings = new AppSetting($container->pdo());
$gcash = $settings->gcash();
$qrPath = trim((string) ($gcash['qr_code'] ?? ''));

page_start('Admin Settings');
sidebar('Settings');
app_header('Admin Settings', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-5xl px-6 lg:px-12">
        <?php flash_messages(); ?>

        <section class="rounded-xl bg-white p-6 shadow-soft sm:p-8">
            <div class="mb-8">
                <h2 class="text-4xl font-black text-parish">Church Payment Setup</h2>
                <p class="mt-2 text-slate-600">Configure the official GCash account shown to members during payment.</p>
            </div>

            <form method="POST" action="../../controllers/admin/settings/update_gcash.php" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1fr_280px]">
                <?= csrf_field() ?>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="sm:col-span-2">
                        <span class="mb-1 block font-bold">Church Name</span>
                        <input name="church_name" value="<?= e($settings->churchName()) ?>" class="w-full rounded-lg border border-slate-200 p-3">
                    </label>
                    <label class="sm:col-span-2">
                        <span class="mb-1 block font-bold">Authorized Representative</span>
                        <input name="authorized_representative" value="<?= e($settings->authorizedRepresentative()) ?>" class="w-full rounded-lg border border-slate-200 p-3">
                    </label>
                    <label>
                        <span class="mb-1 block font-bold">GCash Account Name</span>
                        <input name="gcash_account_name" required value="<?= e($gcash['account_name']) ?>" class="w-full rounded-lg border border-slate-200 p-3">
                    </label>
                    <label>
                        <span class="mb-1 block font-bold">GCash Number</span>
                        <input name="gcash_number" required value="<?= e($gcash['number']) ?>" class="w-full rounded-lg border border-slate-200 p-3">
                    </label>
                    <label>
                        <span class="mb-1 block font-bold">Required Volunteer Hours</span>
                        <input name="volunteer_required_hours" type="number" min="1" step="0.5" value="<?= e((string) $settings->volunteerRequiredHours()) ?>" class="w-full rounded-lg border border-slate-200 p-3">
                    </label>
                    <label>
                        <span class="mb-1 block font-bold">GCash QR Code</span>
                        <input name="gcash_qr_code" type="file" accept=".jpg,.jpeg,.png,.webp" class="w-full rounded-lg border border-slate-200 p-3">
                    </label>
                    <div class="sm:col-span-2 rounded-lg bg-yellow-50 p-4 text-sm font-semibold text-yellow-900">
                        Members will see this account name, number, and QR code before uploading their payment reference and proof.
                    </div>
                    <div class="sm:col-span-2">
                        <button class="rounded-xl bg-parish px-7 py-3 font-bold text-white">Save Settings</button>
                    </div>
                </div>

                <aside class="rounded-xl border border-yellow-100 bg-yellow-50 p-5">
                    <div class="text-sm font-bold uppercase tracking-widest text-parish">Current GCash</div>
                    <div class="mt-4 rounded-lg bg-white p-4 shadow-sm">
                        <div class="font-black"><?= e($gcash['account_name'] ?: 'Not configured') ?></div>
                        <div class="mt-1 text-slate-600"><?= e($gcash['number'] ?: 'No number yet') ?></div>
                    </div>
                    <?php if ($qrPath !== ''): ?>
                        <img src="/E-Parish/uploads/<?= e($qrPath) ?>" alt="GCash QR Code" class="mt-4 aspect-square w-full rounded-lg bg-white object-contain p-3 shadow-sm">
                    <?php else: ?>
                        <div class="mt-4 grid aspect-square w-full place-items-center rounded-lg bg-white p-6 text-center text-sm font-semibold text-slate-400 shadow-sm">
                            No QR code uploaded
                        </div>
                    <?php endif; ?>
                </aside>
            </form>
        </section>
    </div>
</main>
<?php app_footer(); page_end(); ?>
