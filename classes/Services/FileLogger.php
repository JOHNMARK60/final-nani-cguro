<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ILogger;

final class FileLogger implements ILogger
{
    public function __construct(private readonly string $path)
    {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $directory = dirname($this->path);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $line = sprintf(
            "[%s] %s: %s %s%s",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context === [] ? '' : json_encode($context),
            PHP_EOL
        );

        file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX);
    }
}
