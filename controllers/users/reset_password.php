<?php

declare(strict_types=1);

use App\Models\User;
use App\Security\Csrf;
use App\Validation\Validator;

$container = require __DIR__ . '/../../config/app.php';

try {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        throw new RuntimeException('Your session expired. Please try again.');
    }

    $token = trim($_POST['token'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $validator = (new Validator())->required($_POST, ['token', 'password', 'confirm_password'])->strongPassword($password);

    if (!$validator->passes()) {
        throw new RuntimeException(reset($validator->errors()));
    }

    if ($password !== $confirm) {
        throw new RuntimeException('Passwords do not match.');
    }

    if (!(new User($container->pdo()))->resetPassword($token, $password)) {
        throw new RuntimeException('The password reset link is invalid or expired.');
    }

    $_SESSION['success'] = 'Password updated. You can now sign in.';
    header('Location: ../../index.php');
    exit;
} catch (Throwable $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../../views/user/reset_password.php?token=' . urlencode($_POST['token'] ?? ''));
    exit;
}
