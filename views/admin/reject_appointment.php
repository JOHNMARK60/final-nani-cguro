<?php

declare(strict_types=1);

use App\Controllers\AdminAppointmentController;
use App\Security\Csrf;

$container = require __DIR__ . '/../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E-Parish/views/admin/dashboard.php');
    exit;
}

if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
    $_SESSION['error'] = 'Your session expired. Please try again.';
    header('Location: /E-Parish/views/admin/dashboard.php');
    exit;
}

(new AdminAppointmentController($container))->updateStatus($_POST, 'Rejected');
