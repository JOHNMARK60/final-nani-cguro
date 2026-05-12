<?php

declare(strict_types=1);

use App\Core\Container;

require_once __DIR__ . '/../classes/autoload.php';

$envFile = dirname(__DIR__) . '/.env';

if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

return new Container();
