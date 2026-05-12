<?php

declare(strict_types=1);

use App\Controllers\UserController;

$container = require __DIR__ . '/../../config/app.php';
(new UserController($container))->updatePhoto($_POST, $_FILES);
