<?php

declare(strict_types=1);

use App\Core\Database;
use App\Security\Auth;
use App\Security\Csrf;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function asset_path(string $path): string
{
    return '/E-Parish/' . ltrim($path, '/');
}

function csrf_field(): string
{
    return Csrf::field();
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old_input'][$key] ?? $default;
}

function field_error(string $key): string
{
    return $_SESSION['form_errors'][$key] ?? '';
}

function clear_form_state(): void
{
    unset($_SESSION['form_errors'], $_SESSION['old_input']);
}

function pagination_links(int $page, int $totalPages, array $query = []): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<div class="mt-6 flex flex-wrap gap-2">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $query['page'] = $i;
        $url = '?' . http_build_query($query);
        $active = $i === $page ? 'bg-parish text-white' : 'bg-white text-slate-700 border border-slate-200';
        $html .= '<a class="rounded-lg px-4 py-2 text-sm font-semibold ' . $active . '" href="' . htmlspecialchars($url, ENT_QUOTES) . '">' . $i . '</a>';
    }
    $html .= '</div>';

    return $html;
}

function peso(float|int|string|null $amount): string
{
    return 'PHP ' . number_format((float) $amount, 2);
}

function status_badge(string $status): string
{
    $classes = match ($status) {
        'Approved', 'Verified', 'active' => 'bg-green-100 text-green-700',
        'Pending', 'Submitted', 'Confirmed' => 'bg-blue-50 text-parish',
        'Unpaid' => 'bg-amber-100 text-amber-800',
        'Rejected', 'disabled' => 'bg-red-100 text-red-700',
        'Cancelled' => 'bg-slate-100 text-slate-600',
        default => 'bg-slate-100 text-slate-700',
    };

    return '<span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ' . $classes . '">' . e($status) . '</span>';
}

function page_start(string $title): void
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> | E-Parish</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif']
                        },
                        colors: {
                            parish: '#22c55e',
                            parishDark: '#16a34a',
                            parishSoft: '#eef2f7',
                            gold: '#f59e0b'
                        },
                        boxShadow: {
                            soft: '0 10px 30px rgba(15, 23, 42, 0.08)'
                        }
                    }
                }
            }
        </script>
    </head>
    <body class="min-h-screen bg-parishSoft font-sans text-slate-900">
    <?php
}

function page_end(): void
{
    ?>
    <script>
    document.querySelectorAll('[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.confirmed === '1' || typeof Swal === 'undefined') {
                return;
            }

            event.preventDefault();
            Swal.fire({
                title: form.dataset.confirm || 'Continue?',
                text: form.dataset.confirmText || 'This action will update the record.',
                icon: form.dataset.confirmIcon || 'question',
                showCancelButton: true,
                confirmButtonColor: '#1d4ed8',
                cancelButtonColor: '#64748b',
                confirmButtonText: form.dataset.confirmButton || 'Yes, continue'
            }).then(result => {
                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
            });
        });
    });
    </script>
    </body>
    </html>
    <?php
}

function sidebar(string $active): void
{
    $role = $_SESSION['role'] ?? 'user';
    $items = $role === 'admin'
        ? [
            ['Dashboard', 'bi-grid', '/E-Parish/views/admin/dashboard.php'],
            ['Admins', 'bi-person-badge', '/E-Parish/views/admin/admins.php'],
            ['Certificates', 'bi-file-earmark-text', '/E-Parish/views/admin/certificates.php'],
            ['Appointments', 'bi-calendar-event', '/E-Parish/views/admin/appointments.php'],
            ['Payments', 'bi-cash-coin', '/E-Parish/views/admin/payments.php'],
            ['Volunteers', 'bi-people', '/E-Parish/views/admin/volunteers.php'],
            ['Audit Logs', 'bi-journal-text', '/E-Parish/views/admin/audit_logs.php'],
        ]
        : [
            ['Dashboard', 'bi-grid', '/E-Parish/views/user/dashboard.php'],
            ['Services', 'bi-houses', '/E-Parish/views/user/services.php'],
            ['Certificates', 'bi-file-earmark-text', '/E-Parish/views/user/certificates.php'],
            ['Appointments', 'bi-calendar-event', '/E-Parish/views/user/appointments.php'],
            ['Payments', 'bi-cash-coin', '/E-Parish/views/user/payments.php'],
            ['Volunteer', 'bi-people', '/E-Parish/views/user/volunteer.php'],
            ['Account Settings', 'bi-gear', '/E-Parish/views/user/settings.php'],
        ];
    ?>
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 bg-white text-slate-900 shadow-soft ring-1 ring-slate-200 lg:flex lg:flex-col">
        <div class="flex items-center gap-4 px-7 py-8">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-green-100 text-parish">
                <i class="bi bi-houses text-2xl"></i>
            </div>
            <div>
                <div class="text-2xl font-extrabold leading-none tracking-tight text-slate-950"><span class="text-parish">O</span> E-Parish</div>
                <div class="mt-1 text-xs font-medium uppercase tracking-[0.2em] text-slate-400">Services</div>
            </div>
        </div>
        <nav class="mt-4 flex-1 space-y-1 px-3">
            <?php foreach ($items as [$label, $icon, $href]): ?>
                <a href="<?= e($href) ?>" class="flex items-center gap-4 rounded-lg px-4 py-3 text-sm font-semibold transition <?= $active === $label ? 'bg-green-50 text-parish' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                    <i class="bi <?= e($icon) ?> text-lg"></i>
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <a href="/E-Parish/controllers/users/logout.php" class="flex items-center gap-4 border-t border-slate-200 px-8 py-5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
            <i class="bi bi-box-arrow-left text-xl"></i>
            Logout
        </a>
    </aside>
    <nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-200 bg-white/95 px-2 py-2 shadow-[0_-10px_30px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden">
        <div class="flex gap-2 overflow-x-auto">
            <?php foreach ($items as [$label, $icon, $href]): ?>
                <a href="<?= e($href) ?>" class="flex min-w-[5rem] flex-col items-center gap-1 rounded-xl px-3 py-2 text-xs font-semibold transition <?= $active === $label ? 'bg-blue-50 text-parish' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?>">
                    <i class="bi <?= e($icon) ?> text-lg"></i>
                    <span class="whitespace-nowrap"><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
    <?php
}

function notification_items(): array
{
    if (!Auth::check()) {
        return [];
    }

    try {
        $pdo = Database::connection();

        if (Auth::role() === 'admin') {
            $certificatePending = (int) $pdo->query("SELECT COUNT(*) FROM certificate_requests WHERE status = 'Pending'")->fetchColumn();
            $paymentSubmitted = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Submitted'")->fetchColumn();
            $appointmentPending = (int) $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();

            return array_values(array_filter([
                $certificatePending > 0 ? ['Certificates', $certificatePending . ' certificate requests need review.', '/E-Parish/views/admin/certificates.php', 'bi-file-earmark-text'] : null,
                $paymentSubmitted > 0 ? ['Payments', $paymentSubmitted . ' payments are waiting for verification.', '/E-Parish/views/admin/payments.php', 'bi-cash-coin'] : null,
                $appointmentPending > 0 ? ['Appointments', $appointmentPending . ' appointments are pending.', '/E-Parish/views/admin/appointments.php', 'bi-calendar-event'] : null,
            ]));
        }

        $userId = (int) Auth::userId();
        $issuedLocked = $pdo->prepare(
            'SELECT COUNT(*)
             FROM digital_certificates d
             INNER JOIN certificate_requests c ON c.id = d.certificate_request_id
             WHERE c.user_id = ?
                AND d.delivery_mode = "E-Certificate"
                AND NOT EXISTS (
                    SELECT 1 FROM payments p
                    WHERE p.payable_type = "Certificate"
                        AND p.payable_id = c.id
                        AND p.user_id = c.user_id
                        AND p.status = "Verified"
                )'
        );
        $issuedLocked->execute([$userId]);

        $available = $pdo->prepare(
            'SELECT COUNT(*)
             FROM digital_certificates d
             INNER JOIN certificate_requests c ON c.id = d.certificate_request_id
             WHERE c.user_id = ?
                AND d.delivery_mode = "E-Certificate"
                AND EXISTS (
                    SELECT 1 FROM payments p
                    WHERE p.payable_type = "Certificate"
                        AND p.payable_id = c.id
                        AND p.user_id = c.user_id
                        AND p.status = "Verified"
                )'
        );
        $available->execute([$userId]);

        $rejectedPayments = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE user_id = ? AND status = "Rejected"');
        $rejectedPayments->execute([$userId]);

        $lockedCount = (int) $issuedLocked->fetchColumn();
        $availableCount = (int) $available->fetchColumn();
        $rejectedCount = (int) $rejectedPayments->fetchColumn();

        return array_values(array_filter([
            $availableCount > 0 ? ['E-Certificate', $availableCount . ' e-certificate is ready to view.', '/E-Parish/views/user/certificates.php', 'bi-patch-check'] : null,
            $lockedCount > 0 ? ['Payment Required', $lockedCount . ' e-certificate needs verified payment.', '/E-Parish/views/user/certificates.php', 'bi-lock'] : null,
            $rejectedCount > 0 ? ['Payment Update', $rejectedCount . ' payment needs correction.', '/E-Parish/views/user/payments.php', 'bi-exclamation-circle'] : null,
        ]));
    } catch (\Throwable) {
        return [];
    }
}

function app_header(string $title, array $user): void
{
    $name = $user['fullname'] ?? $_SESSION['fullname'] ?? 'Parish Member';
    $role = $user['role'] ?? $_SESSION['role'] ?? 'user';
    $pic = !empty($user['profile_pic']) ? '/E-Parish/uploads/profiles/' . e($user['profile_pic']) : '/E-Parish/assets/Churchlogo.png';
    $notifications = notification_items();
    ?>
    <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white px-4 lg:ml-64 lg:px-8">
        <div>
            <h1 class="text-xl font-black tracking-tight text-slate-950 sm:text-2xl"><?= e($title) ?></h1>
            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-parish"><?= e($role === 'admin' ? 'Admin Console' : 'Member Portal') ?></div>
        </div>
        <div class="flex items-center gap-3">
            <label class="hidden items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-500 md:flex">
                <i class="bi bi-search"></i>
                <input class="w-40 bg-transparent text-sm outline-none" placeholder="Search...">
            </label>
            <div class="relative">
                <button type="button" data-dropdown="notificationMenu" class="relative grid h-11 w-11 place-items-center rounded-full bg-slate-100 text-slate-600 hover:text-parish">
                    <i class="bi bi-bell-fill"></i>
                    <?php if ($notifications !== []): ?>
                        <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white"></span>
                    <?php endif; ?>
                </button>
                <div id="notificationMenu" class="absolute right-0 top-14 z-50 hidden w-80 rounded-xl border border-slate-200 bg-white p-4 shadow-soft">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="font-black text-slate-950">Notifications</div>
                        <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-bold text-parish"><?= count($notifications) ?> new</span>
                    </div>
                    <div class="space-y-2">
                        <?php if ($notifications === []): ?>
                            <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No new notifications.</div>
                        <?php endif; ?>
                        <?php foreach ($notifications as [$label, $message, $href, $icon]): ?>
                            <a href="<?= e($href) ?>" class="flex gap-3 rounded-lg border border-slate-100 p-3 hover:bg-slate-50">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-green-50 text-parish"><i class="bi <?= e($icon) ?>"></i></span>
                                <span>
                                    <span class="block text-sm font-bold text-slate-900"><?= e($label) ?></span>
                                    <span class="block text-xs leading-5 text-slate-500"><?= e($message) ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="relative">
                <button type="button" data-dropdown="accountMenu" class="flex items-center gap-3 rounded-full border border-slate-200 bg-white py-1 pl-2 pr-3 shadow-sm">
                    <img src="<?= $pic ?>" alt="" class="h-10 w-10 rounded-full object-cover ring-2 ring-slate-100">
                    <span class="hidden text-left sm:block">
                        <span class="block text-sm font-bold"><?= e($name) ?></span>
                        <span class="block text-xs text-slate-500"><?= e($role === 'admin' ? 'Administrator' : 'Member') ?></span>
                    </span>
                    <i class="bi bi-chevron-down text-xs text-slate-500"></i>
                </button>
                <div id="accountMenu" class="absolute right-0 top-14 z-50 hidden w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-soft">
                    <a href="/E-Parish/views/user/settings.php" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="bi bi-person-gear"></i> Profile Settings</a>
                    <a href="/E-Parish/controllers/users/logout.php" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-red-600 hover:bg-red-50"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
    <script>
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-dropdown]');
        document.querySelectorAll('[id$="Menu"]').forEach(menu => {
            if (!trigger || trigger.dataset.dropdown !== menu.id) {
                menu.classList.add('hidden');
            }
        });

        if (trigger) {
            document.getElementById(trigger.dataset.dropdown)?.classList.toggle('hidden');
        }
    });
    </script>
    <?php
}

function app_footer(): void
{
    ?>
    <footer class="fixed bottom-0 right-0 hidden h-14 items-center justify-center bg-parish text-sm font-medium tracking-wide text-white lg:left-64 lg:flex">
        &copy; 2026 E-Parish Services Management System | Faith in every click, service that is quick.
    </footer>
    <?php
}

function flash_messages(): void
{
    foreach (['success' => 'success', 'error' => 'error'] as $key => $icon) {
        if (!empty($_SESSION[$key])) {
            $title = $key === 'success' ? 'Success' : 'Please check this';
            echo '<script>
                document.addEventListener("DOMContentLoaded", () => {
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            toast: true,
                            position: "top-end",
                            icon: ' . json_encode($icon) . ',
                            title: ' . json_encode((string) $_SESSION[$key]) . ',
                            showConfirmButton: false,
                            timer: 2600,
                            timerProgressBar: true
                        });
                    }
                });
            </script>';
            echo '<noscript><div class="mb-5 rounded-lg border px-4 py-3">' . e($title . ': ' . (string) $_SESSION[$key]) . '</div></noscript>';
            unset($_SESSION[$key]);
        }
    }
}
