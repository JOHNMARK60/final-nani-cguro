<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;

final class AuditLogger
{
    public function __construct(private readonly AuditLog $logs)
    {
    }

    public function log(string $actionType, ?string $targetType = null, $targetId = null, ?string $details = null): void
    {
        $this->logs->record([
            'admin_id' => $_SESSION['user_id'] ?? null,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
