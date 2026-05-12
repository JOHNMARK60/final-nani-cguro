<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ILogger;
use App\Contracts\MailerInterface;

final class NullMailer implements MailerInterface
{
    public function __construct(private readonly ILogger $logger)
    {
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $this->logger->info('Mail queued by NullMailer', [
            'to' => $to,
            'subject' => $subject,
            'body_preview' => substr($body, 0, 120),
        ]);

        return true;
    }
}
