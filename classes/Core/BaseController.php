<?php

declare(strict_types=1);

namespace App\Core;

use App\Security\Csrf;

abstract class BaseController
{
    public function __construct(protected Container $container)
    {
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    protected function backWith(string $key, string $message, string $path): never
    {
        $_SESSION[$key] = $message;
        $this->redirect($path);
    }

    protected function redirectWithErrors(array $errors, array $input, string $path): never
    {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_input'] = $input;
        $this->redirect($path);
    }

    protected function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old_input'][$key] ?? $default;
    }

    protected function requireCsrf(?string $token): void
    {
        if (!Csrf::verify($token)) {
            throw new \RuntimeException('Your session expired. Please try again.');
        }
    }

    protected function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }
}
