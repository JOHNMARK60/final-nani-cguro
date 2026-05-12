<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ILogger;
use App\Contracts\MailerInterface;

final class SmtpMailer implements MailerInterface
{
    public function __construct(private readonly ILogger $logger)
    {
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $host = getenv('SMTP_HOST') ?: '';
        $port = getenv('SMTP_PORT') ?: '';
        $from = getenv('SMTP_FROM') ?: (getenv('MAIL_FROM') ?: 'no-reply@eparish.local');

        if ($host === '' || $port === '') {
            $this->logger->info('SMTP unavailable; fallback delivery used.', ['to' => $to, 'subject' => $subject]);
            return true;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $from,
        ];

        $ok = @mail($to, $subject, $body, implode("\r\n", $headers));

        if (!$ok) {
            $this->logger->info('SMTP mail failed; falling back to log delivery.', ['to' => $to, 'subject' => $subject]);
        }

        return $ok;
    }
}
