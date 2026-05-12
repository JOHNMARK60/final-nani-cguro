<?php

declare(strict_types=1);

use App\Security\Auth;
use App\Security\Authorization;

$container = require __DIR__ . '/../../config/app.php';

Auth::requireLogin('/E-Parish/index.php');

$pdo = $container->pdo();
$auth = new Authorization($pdo);
$id = (int) ($_GET['id'] ?? 0);
$mode = strtolower(trim((string) ($_GET['mode'] ?? 'download')));

if ($id <= 0) {
    http_response_code(404);
    exit('Not found');
}

$row = $pdo->prepare('SELECT * FROM payments WHERE id = ?');
$row->execute([$id]);
$payment = $row->fetch();

if (!$payment) {
    http_response_code(404);
    exit('Not found');
}

if (!$auth->canAccessUserRecord((int) $payment['user_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$relative = (string) ($payment['proof_file'] ?? '');
$baseDir = realpath(__DIR__ . '/../../uploads');
$filePath = $baseDir ? realpath($baseDir . DIRECTORY_SEPARATOR . ltrim($relative, '/\\')) : false;

if ($relative === '' || !$filePath || !str_starts_with($filePath, $baseDir) || !is_file($filePath)) {
    http_response_code(404);
    exit('File not found');
}

$container->audit()->log('file_download', 'payment_proof', $id, 'proof_file via ' . $mode);

$mime = mime_content_type($filePath) ?: 'application/octet-stream';
$disposition = $mode === 'preview' ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: ' . $disposition . '; filename="' . basename($filePath) . '"');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
