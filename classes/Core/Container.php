<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\ILogger;
use App\Contracts\MailerInterface;
use App\Models\AuditLog;
use App\Services\FileLogger;
use App\Services\SmtpMailer;
use App\Services\NullMailer;
use App\Services\AuditLogger;
use PDO;

final class Container
{
    private ?PDO $pdo = null;
    private ?ILogger $logger = null;
    private ?MailerInterface $mailer = null;
    private ?AuditLogger $auditLogger = null;

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = Database::connection();
        }

        return $this->pdo;
    }

    public function logger(): ILogger
    {
        if ($this->logger === null) {
            $this->logger = new FileLogger(dirname(__DIR__, 2) . '/storage/logs/app.log');
        }

        return $this->logger;
    }

    public function mailer(): MailerInterface
    {
        if ($this->mailer === null) {
            $this->mailer = new SmtpMailer($this->logger());
        }

        return $this->mailer;
    }

    public function audit(): AuditLogger
    {
        if ($this->auditLogger === null) {
            $this->auditLogger = new AuditLogger(new AuditLog($this->pdo()));
        }

        return $this->auditLogger;
    }
}
