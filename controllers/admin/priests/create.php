<?php

declare(strict_types=1);

use App\Controllers\PriestController;

$container = require __DIR__ . '/../../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E-Parish/views/admin/certificates.php');
    exit;
}

(new PriestController($container))->store($_POST);
