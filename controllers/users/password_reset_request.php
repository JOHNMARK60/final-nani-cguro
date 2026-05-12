<?php

declare(strict_types=1);

use App\Models\User;
use App\Security\Csrf;

$container = require __DIR__ . '/../../config/app.php';

try {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        throw new RuntimeException('Your session expired. Please try again.');
    }

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }

    $token = (new User($container->pdo()))->createPasswordReset($email);

    if ($token !== null) {
        $resetUrl = '/E-Parish/views/user/reset_password.php?token=' . urlencode($token);
        $container->mailer()->send($email, 'Reset your E-Parish password', 'Reset link: ' . $resetUrl);
        $_SESSION['success'] = 'Password reset instructions were prepared. Check the app log when using NullMailer.';
    } else {
        $_SESSION['success'] = 'If that email exists, reset instructions will be sent.';
    }
} catch (Throwable $e) {
    $container->logger()->error('Password reset request failed', ['error' => $e->getMessage()]);
    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../../index.php');
exit;
