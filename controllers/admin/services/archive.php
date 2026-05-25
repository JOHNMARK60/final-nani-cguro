<?php

declare(strict_types=1);

use App\Controllers\ParishServiceController;

$container = require __DIR__ . '/../../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E-Parish/views/admin/services.php');
    exit;
}

(new ParishServiceController($container))->archive($_POST);
