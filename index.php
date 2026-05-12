<?php

declare(strict_types=1);

require __DIR__ . '/config/app.php';
require __DIR__ . '/includes/ui.php';

$hasFormErrors = !empty($_SESSION['form_errors']);
$looksLikeRegister = isset($_SESSION['old_input']['fullname']) || isset($_SESSION['old_input']['role']);
$showLoginModal = $hasFormErrors && !$looksLikeRegister;
$showRegisterModal = $hasFormErrors && $looksLikeRegister;

page_start('Welcome');
?>
<main class="min-h-screen overflow-hidden bg-white">
    <section class="grid min-h-screen items-center gap-10 px-6 py-10 lg:grid-cols-[0.95fr_1.05fr] lg:px-20">
        <div class="max-w-xl">
            <div class="mb-12 flex items-center gap-4">
                <div class="grid h-14 w-14 place-items-center rounded-2xl bg-parish text-white shadow-soft">
                    <i class="bi bi-house-door-fill text-2xl"></i>
                </div>
                <div>
                    <div class="text-3xl font-extrabold tracking-tight text-parish">E-Parish</div>
                    <div class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Parish Management</div>
                </div>
            </div>

            <?php flash_messages(); ?>

            <h1 class="max-w-lg text-5xl font-extrabold leading-[0.95] tracking-tight text-slate-950 sm:text-6xl">
                Parish services,
                appointments,
                and <span class="text-parish">volunteer records</span>
                in one secure dashboard.
            </h1>
            <p class="mt-8 max-w-xl text-lg leading-8 text-slate-600">
                Manage church requests with a clean digital workflow built for members, parish staff, and administrators.
            </p>

            <div class="mt-10 flex flex-wrap gap-4">
                <button data-modal="loginModal" class="rounded-xl bg-parish px-8 py-4 text-base font-semibold text-white shadow-[0_10px_30px_rgba(29,78,216,0.25)] transition hover:bg-parishDark">
                    Sign In
                </button>
                <button data-modal="registerModal" class="rounded-xl border-2 border-parish bg-white px-8 py-4 text-base font-semibold text-parish transition hover:bg-slate-50">
                    Create Account
                </button>
            </div>
        </div>

        <div class="mx-auto w-full max-w-2xl">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-100 shadow-[0_24px_80px_rgba(15,23,42,0.16)]">
                <div class="relative h-[34rem] w-full">
                    <img src="assets/church-interior.jpg" alt="Church interior" class="absolute inset-0 h-full w-full object-cover object-center">
                    <div class="absolute inset-0 bg-white/30"></div>

                    <div class="absolute left-6 right-6 top-6 rounded-[1.6rem] border border-slate-200 bg-white p-6 shadow-soft sm:left-8 sm:right-8">
                        <div class="mb-5 flex items-center justify-between">
                            <div class="text-sm font-semibold uppercase tracking-[0.28em] text-parish">Live Overview</div>
                            <div class="flex gap-2">
                                <span class="h-3 w-3 rounded-full bg-rose-200"></span>
                                <span class="h-3 w-3 rounded-full bg-slate-200"></span>
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <div class="text-4xl font-bold text-parish">0</div>
                                <div class="mt-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Pending Requests</div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <div class="text-4xl font-bold text-parish">0</div>
                                <div class="mt-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Appointments</div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <div class="text-4xl font-bold text-parish">0</div>
                                <div class="mt-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Certificates</div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                <div class="text-4xl font-bold text-parish">0</div>
                                <div class="mt-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Volunteer Activities</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-[1.6rem] border border-slate-200 bg-white p-8 shadow-soft">
                <div class="flex items-start gap-5">
                    <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-slate-100 text-parish">
                        <i class="bi bi-check2-circle text-3xl"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight text-slate-950">Fast, organized parish workflows</h2>
                        <p class="mt-4 max-w-xl text-lg leading-8 text-slate-600">
                            Members can request certificates, schedule appointments, and monitor approvals directly through their personal portal.
                        </p>
                        <div class="mt-8 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full w-1/3 rounded-full bg-parish"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<div id="loginModal" class="fixed inset-0 z-50 <?= $showLoginModal ? 'flex' : 'hidden' ?> items-center justify-center bg-slate-950/40 p-4">
    <form method="POST" action="controllers/users/login.php" class="w-full max-w-md rounded-2xl bg-white p-8 shadow-soft" novalidate>
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-950">Sign In</h2>
                <p class="text-slate-500">Access your parish dashboard.</p>
            </div>
            <button type="button" data-close class="text-2xl text-slate-400" aria-label="Close">&times;</button>
        </div>
        <?= csrf_field() ?>
        <label class="mb-4 block">
            <span class="mb-1 block text-sm font-semibold text-slate-600">Username</span>
            <input name="username" value="<?= e((string) old('username')) ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-parish">
            <?php if ($msg = field_error('username')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
        </label>
        <label class="mb-6 block">
            <span class="mb-1 block text-sm font-semibold text-slate-600">Password</span>
            <input name="password" type="password" required class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-parish">
            <?php if ($msg = field_error('password')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
        </label>
        <button class="w-full rounded-xl bg-parish py-3 font-semibold text-white transition hover:bg-parishDark">Sign In</button>
        <p class="mt-4 text-center text-sm text-slate-500">
            Don't have an account?
            <button type="button" data-switch="registerModal" class="font-semibold text-parish">Create one</button>
        </p>
    </form>
</div>

<div id="registerModal" class="fixed inset-0 z-50 <?= $showRegisterModal ? 'flex' : 'hidden' ?> items-center justify-center bg-slate-950/40 p-4">
    <form method="POST" action="controllers/users/create.php" class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-soft" novalidate>
        <div class="mb-6 flex items-start justify-between">
            <div>
                <h2 class="text-3xl font-bold text-slate-950">Create Account</h2>
                <p class="text-slate-500">Join the E-Parish member portal.</p>
            </div>
            <button type="button" data-close class="text-2xl text-slate-400" aria-label="Close">&times;</button>
        </div>
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="sm:col-span-2">
                <span class="mb-1 block text-sm font-semibold text-slate-600">Full name</span>
                <input name="fullname" value="<?= e((string) old('fullname')) ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-parish">
                <?php if ($msg = field_error('fullname')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
            </label>
            <label>
                <span class="mb-1 block text-sm font-semibold text-slate-600">Email</span>
                <input name="email" type="email" value="<?= e((string) old('email')) ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-parish">
                <?php if ($msg = field_error('email')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
            </label>
            <label>
                <span class="mb-1 block text-sm font-semibold text-slate-600">Username</span>
                <input name="username" value="<?= e((string) old('username')) ?>" required class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-parish">
                <?php if ($msg = field_error('username')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
            </label>
            <label class="sm:col-span-2">
                <span class="mb-1 block text-sm font-semibold text-slate-600">Password</span>
                <input name="password" type="password" required minlength="8" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-parish">
                <?php if ($msg = field_error('password')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
            </label>
            <label>
                <span class="mb-1 block text-sm font-semibold text-slate-600">Register as</span>
                <select name="role" id="roleSelect" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-parish">
                    <option value="user" <?= old('role', 'user') === 'user' ? 'selected' : '' ?>>Parish Member</option>
                    <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Administrator</option>
                </select>
            </label>
            <label id="adminCodeWrap" class="hidden sm:col-span-2">
                <span class="mb-1 block text-sm font-semibold text-slate-600">Admin invite code</span>
                <input name="admin_code" type="password" value="<?= e((string) old('admin_code')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none transition focus:border-parish">
                <?php if ($msg = field_error('admin_code')): ?><span class="mt-1 block text-sm text-rose-600"><?= e($msg) ?></span><?php endif; ?>
            </label>
        </div>
        <p class="mt-3 text-sm text-slate-500">Use at least 8 characters with uppercase, number, and special character.</p>
        <button class="mt-6 w-full rounded-xl bg-parish py-3 font-semibold text-white transition hover:bg-parishDark">Register</button>
        <p class="mt-4 text-center text-sm text-slate-500">
            Already have an account?
            <button type="button" data-switch="loginModal" class="font-semibold text-parish">Sign in</button>
        </p>
    </form>
</div>

<script>
document.querySelectorAll('[data-modal]').forEach(button => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.dataset.modal);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });
});
document.querySelectorAll('[data-close]').forEach(button => {
    button.addEventListener('click', () => {
        const modal = button.closest('.fixed');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });
});
document.querySelectorAll('[data-switch]').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.fixed').forEach(modal => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
        const target = document.getElementById(button.dataset.switch);
        target.classList.remove('hidden');
        target.classList.add('flex');
    });
});
const roleSelect = document.getElementById('roleSelect');
const adminCodeWrap = document.getElementById('adminCodeWrap');
if (roleSelect && adminCodeWrap) {
    const syncRole = () => {
        adminCodeWrap.classList.toggle('hidden', roleSelect.value !== 'admin');
    };
    roleSelect.addEventListener('change', syncRole);
    syncRole();
}
</script>
<?php clear_form_state(); page_end(); ?>
