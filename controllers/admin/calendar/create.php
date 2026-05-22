<?php

declare(strict_types=1);

use App\Controllers\CalendarEventController;

$container = require __DIR__ . '/../../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /E-Parish/views/admin/appointments.php');
    exit;
}

(new CalendarEventController($container))->store($_POST);
