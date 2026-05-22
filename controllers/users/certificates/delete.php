<?php

declare(strict_types=1);

use App\Controllers\CertificateController;

$container = require __DIR__ . '/../../../config/app.php';

(new CertificateController($container))->destroy($_POST);
