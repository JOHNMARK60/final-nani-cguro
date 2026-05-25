<?php

declare(strict_types=1);

use App\Controllers\InventoryController;

$container = require __DIR__ . '/../../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E-Parish/views/admin/inventory.php');
    exit;
}

(new InventoryController($container))->store($_POST);
