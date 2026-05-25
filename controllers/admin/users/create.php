<?php

declare(strict_types=1);

use App\Controllers\UserManagementController;

$container = require __DIR__ . '/../../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E-Parish/views/admin/users.php');
    exit;
}

(new UserManagementController($container))->store($_POST);
