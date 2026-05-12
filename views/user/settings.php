<?php

declare(strict_types=1);

use App\Models\User;
use App\Security\Auth;

$container = require __DIR__ . '/../../config/app.php';
require __DIR__ . '/../../includes/ui.php';

Auth::requireLogin('/E-Parish/index.php');

$user = (new User($container->pdo()))->find(Auth::userId()) ?? [];
$profilePic = !empty($user['profile_pic']) ? '/E-Parish/uploads/profiles/' . e($user['profile_pic']) : '/E-Parish/assets/Churchlogo.png';

page_start('Account Settings');
sidebar('Account Settings');
app_header('Account Settings', $user);
?>
<main class="pb-24 pt-10 lg:ml-64">
    <div class="mx-auto max-w-6xl px-6 lg:px-12">
        <?php flash_messages(); ?>
        <section class="overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 p-8 text-2xl font-semibold"><i class="bi bi-person mr-4 text-parish"></i>Profile Info</div>
            <div class="grid gap-8 p-8 lg:grid-cols-[220px_1fr]">
                <form method="POST" action="../../controllers/users/update_profile.php" enctype="multipart/form-data" class="text-center">
                    <?= csrf_field() ?>
                    <img src="<?= $profilePic ?>" alt="Profile" class="mx-auto h-40 w-40 rounded-2xl object-cover ring-2 ring-slate-200">
                    <label class="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-lg bg-parish px-4 py-3 font-bold text-white">
                        <i class="bi bi-camera"></i> Update Photo
                        <input type="file" name="profile_pic" class="hidden" onchange="this.form.submit()" accept=".jpg,.jpeg,.png,.gif,.webp">
                    </label>
                </form>
                <form method="POST" action="../../controllers/users/update_information.php" class="grid gap-5 sm:grid-cols-2">
                    <?= csrf_field() ?>
                    <label><span class="mb-2 block text-sm font-black uppercase tracking-widest">Full Name</span><input name="fullname" value="<?= e($user['fullname'] ?? '') ?>" class="w-full rounded-lg bg-slate-100 px-5 py-4"></label>
                    <label><span class="mb-2 block text-sm font-black uppercase tracking-widest">Email Address</span><input name="email" type="email" value="<?= e($user['email'] ?? '') ?>" class="w-full rounded-lg bg-slate-100 px-5 py-4"></label>
                    <label><span class="mb-2 block text-sm font-black uppercase tracking-widest">Phone Number</span><input name="phone" value="<?= e($user['phone'] ?? '') ?>" class="w-full rounded-lg bg-slate-100 px-5 py-4"></label>
                    <label><span class="mb-2 block text-sm font-black uppercase tracking-widest">Username</span><input name="username" value="<?= e($user['username'] ?? '') ?>" class="w-full rounded-lg bg-slate-100 px-5 py-4"></label>
                    <label class="sm:col-span-2"><span class="mb-2 block text-sm font-black uppercase tracking-widest">Designation</span><input name="designation" value="<?= e($user['designation'] ?? ($user['role'] === 'admin' ? 'Parish Administrator' : 'Parish Member')) ?>" class="w-full rounded-lg bg-slate-100 px-5 py-4"></label>
                    <div class="sm:col-span-2 flex justify-end"><button class="rounded-lg bg-parish px-8 py-4 font-bold text-white">Save Changes</button></div>
                </form>
            </div>
        </section>

        <section class="mt-10 overflow-hidden rounded-xl bg-white shadow-soft">
            <div class="border-b border-slate-200 p-8 text-2xl font-semibold"><i class="bi bi-shield-lock mr-4 text-parish"></i>Password Update</div>
            <form method="POST" action="../../controllers/users/change_password.php" class="grid gap-5 p-8 sm:grid-cols-2">
                <?= csrf_field() ?>
                <label class="sm:col-span-2"><span class="mb-2 block text-sm font-black uppercase tracking-widest">Current Password</span><input name="current_password" type="password" class="w-full rounded-lg bg-slate-100 px-5 py-4"></label>
                <label><span class="mb-2 block text-sm font-black uppercase tracking-widest">New Password</span><input name="new_password" type="password" class="w-full rounded-lg bg-slate-100 px-5 py-4"></label>
                <label><span class="mb-2 block text-sm font-black uppercase tracking-widest">Confirm Password</span><input name="confirm_password" type="password" class="w-full rounded-lg bg-slate-100 px-5 py-4"></label>
                <div class="rounded-lg border border-blue-100 bg-blue-50 p-5 text-slate-700 sm:col-span-2"><i class="bi bi-info-circle mr-2 text-parish"></i>Passwords should contain at least 8 characters, including one uppercase letter, one special character, and one number.</div>
                <div class="sm:col-span-2 flex justify-end"><button class="rounded-lg bg-parish px-8 py-4 font-bold text-white">Change Password</button></div>
            </form>
        </section>
    </div>
</main>
<?php app_footer(); page_end(); ?>
