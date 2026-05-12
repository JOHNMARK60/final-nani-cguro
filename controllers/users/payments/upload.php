<?php

declare(strict_types=1);

use App\Controllers\PaymentController;

$container = require __DIR__ . '/../../../config/app.php';

(new PaymentController($container))->uploadProof($_POST, $_FILES);
