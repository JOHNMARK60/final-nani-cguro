<?php

declare(strict_types=1);

use App\Models\Certificate;
use App\Security\Auth;
use App\Security\Authorization;

$container = require __DIR__ . '/../../config/app.php';

Auth::requireLogin('/E-Parish/index.php');

$pdo = $container->pdo();
$auth = new Authorization($pdo);
$certificates = new Certificate($pdo);
$id = (int) ($_GET['id'] ?? 0);
$field = trim((string) ($_GET['field'] ?? ''));
$mode = strtolower(trim((string) ($_GET['mode'] ?? 'download')));

if ($id <= 0 || !in_array($field, ['baptismal_file', 'id_file'], true)) {
    http_response_code(404);
    exit('Not found');
}

$row = $pdo->prepare('SELECT * FROM certificate_requests WHERE id = ?');
$row->execute([$id]);
$request = $row->fetch();

if (!$request) {
    http_response_code(404);
    exit('Not found');
}

if (!$auth->canAccessUserRecord((int) ($request['user_id'] ?? 0))) {
    http_response_code(403);
    exit('Forbidden');
}

$relative = (string) ($request[$field] ?? '');
$baseDir = realpath(__DIR__ . '/../../uploads');
$filePath = $baseDir ? realpath($baseDir . DIRECTORY_SEPARATOR . ltrim($relative, '/\\')) : false;

if (!$filePath || !str_starts_with($filePath, $baseDir) || !is_file($filePath)) {
    http_response_code(404);
    exit('File not found');
}

$container->audit()->log('file_download', 'certificate_file', $id, $field . ' via ' . $mode);

$mime = mime_content_type($filePath) ?: 'application/octet-stream';
$disposition = $mode === 'preview' ? 'inline' : 'attachment';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: ' . $disposition . '; filename="' . basename($filePath) . '"');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
