<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;

final class UploadService
{
    public function store(array $file, string $folder, array $allowedExtensions): ?string
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed. Please choose another file.');
        }

        $maxBytes = ((int) Env::get('UPLOAD_MAX_MB', '5')) * 1024 * 1024;

        if (($file['size'] ?? 0) > $maxBytes) {
            throw new \RuntimeException('File is too large.');
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \RuntimeException('Invalid file type.');
        }

        $baseDir = dirname(__DIR__, 2) . '/uploads/' . trim($folder, '/');

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $baseDir . '/' . $fileName;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new \RuntimeException('Unable to save uploaded file.');
        }

        return trim($folder, '/') . '/' . $fileName;
    }
}
