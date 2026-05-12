<?php

declare(strict_types=1);

require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

$token = $_GET['token'] ?? '';
page_start('Reset Password');
?>
<main class="grid min-h-screen place-items-center bg-parishSoft p-4">
    <form method="POST" action="../../controllers/users/reset_password.php" class="w-full max-w-md rounded-2xl bg-white p-8 shadow-soft">
        <h1 class="text-3xl font-black text-parish">Reset Password</h1>
        <p class="mt-2 text-slate-600">Choose a new secure password.</p>
        <div class="mt-6"><?php flash_messages(); ?></div>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e((string) $token) ?>">
        <label class="mt-4 block"><span class="mb-1 block font-bold">New Password</span><input type="password" name="password" required class="w-full rounded-lg border p-3"></label>
        <label class="mt-4 block"><span class="mb-1 block font-bold">Confirm Password</span><input type="password" name="confirm_password" required class="w-full rounded-lg border p-3"></label>
        <button class="mt-6 w-full rounded-lg bg-parish py-3 font-bold text-white">Update Password</button>
    </form>
</main>
<?php page_end(); ?>
