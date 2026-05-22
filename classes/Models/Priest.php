<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseModel;
use InvalidArgumentException;

final class Priest extends BaseModel
{
    public const DEFAULT_NAME = 'Gabriel Romero';

    public function active(): array
    {
        $this->ensureDefault();

        return $this->fetchAll(
            'SELECT *
             FROM priests
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC'
        );
    }

    public function create(string $name, ?string $signatureText = null): bool
    {
        $name = $this->normalizeName($name);
        $signatureText = $this->normalizeName($signatureText ?? '') ?: $name;

        if ($name === '') {
            throw new InvalidArgumentException('Priest name is required.');
        }

        return $this->execute(
            'INSERT INTO priests (name, signature_text, sort_order, is_active)
             SELECT ?, ?, COALESCE(MAX(sort_order), 0) + 10, 1 FROM priests
             ON DUPLICATE KEY UPDATE signature_text = VALUES(signature_text), is_active = 1',
            [$name, $signatureText]
        );
    }

    public function defaultName(): string
    {
        $this->ensureDefault();

        $row = $this->fetch(
            'SELECT name
             FROM priests
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC
             LIMIT 1'
        );

        return trim((string) ($row['name'] ?? '')) ?: self::DEFAULT_NAME;
    }

    public function signatureForName(string $name): string
    {
        $name = $this->normalizeName($name);

        if ($name === '') {
            return self::DEFAULT_NAME;
        }

        $row = $this->fetch(
            'SELECT signature_text
             FROM priests
             WHERE name = ? AND is_active = 1
             LIMIT 1',
            [$name]
        );

        return trim((string) ($row['signature_text'] ?? '')) ?: $name;
    }

    private function ensureDefault(): void
    {
        $this->execute(
            'INSERT INTO priests (name, signature_text, sort_order, is_active)
             VALUES (?, ?, 10, 1)
             ON DUPLICATE KEY UPDATE is_active = 1',
            [self::DEFAULT_NAME, self::DEFAULT_NAME]
        );
    }

    private function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $name));
    }
}
