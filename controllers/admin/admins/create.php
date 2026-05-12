<?php

declare(strict_types=1);

use App\Controllers\AdminManagementController;

$container = require __DIR__ . '/../../../config/app.php';
(new AdminManagementController($container))->store($_POST);
