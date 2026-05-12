<?php

declare(strict_types=1);

// Backward-compatible entry point for older includes.
$container = require __DIR__ . '/app.php';
$pdo = $container->pdo();
