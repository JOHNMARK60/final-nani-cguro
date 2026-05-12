<?php

declare(strict_types=1);

require_once __DIR__ . '/../classes/autoload.php';

use App\Security\Csrf;

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_check')) {
    function csrf_check(): bool
    {
        return Csrf::verify($_POST['csrf_token'] ?? null);
    }
}
