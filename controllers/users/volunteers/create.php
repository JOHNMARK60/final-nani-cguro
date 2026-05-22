<?php

declare(strict_types=1);

use App\Controllers\VolunteerController;

$container = require __DIR__ . '/../../../config/app.php';

(new VolunteerController($container))->store($_POST);
