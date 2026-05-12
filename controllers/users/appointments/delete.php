<?php

declare(strict_types=1);

use App\Controllers\AppointmentController;

$container = require __DIR__ . '/../../../config/app.php';
(new AppointmentController($container))->destroy($_POST + $_GET);
