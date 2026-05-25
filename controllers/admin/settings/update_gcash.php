<?php

declare(strict_types=1);

use App\Controllers\SettingController;

$container = require __DIR__ . '/../../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E-Parish/views/admin/settings.php');
    exit;
}

(new SettingController($container))->updateGcash($_POST, $_FILES);
