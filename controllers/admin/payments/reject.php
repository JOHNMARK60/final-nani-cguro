<?php

declare(strict_types=1);

use App\Controllers\PaymentController;

$container = require __DIR__ . '/../../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E-Parish/views/admin/payments.php');
    exit;
}

(new PaymentController($container))->updateStatus($_POST, 'Rejected');
