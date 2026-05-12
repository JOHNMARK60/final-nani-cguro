<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $columnExists = static function (string $table, string $column) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $tableExists = static function (string $table) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS admin_audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id INT UNSIGNED DEFAULT NULL,
            action_type VARCHAR(120) NOT NULL,
            target_type VARCHAR(120) DEFAULT NULL,
            target_id BIGINT UNSIGNED DEFAULT NULL,
            details TEXT DEFAULT NULL,
            ip_address VARCHAR(64) DEFAULT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_audit_action (action_type),
            INDEX idx_admin_audit_target (target_type, target_id),
            INDEX idx_admin_audit_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS appointment_status_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            appointment_id INT UNSIGNED NOT NULL,
            previous_status VARCHAR(40) NOT NULL,
            new_status VARCHAR(40) NOT NULL,
            admin_id INT UNSIGNED DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_appointment_history_appointment (appointment_id),
            INDEX idx_appointment_history_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS certificate_status_history (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            certificate_request_id INT UNSIGNED NOT NULL,
            previous_status VARCHAR(40) NOT NULL,
            new_status VARCHAR(40) NOT NULL,
            admin_id INT UNSIGNED DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_certificate_history_request (certificate_request_id),
            INDEX idx_certificate_history_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    if ($tableExists('users')) {
        if (!$columnExists('users', 'status')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'disabled') NOT NULL DEFAULT 'active' AFTER role");
        }
        if (!$columnExists('users', 'created_by')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN created_by INT UNSIGNED DEFAULT NULL AFTER status");
        }
        if (!$columnExists('users', 'last_login_at')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL AFTER created_by");
        }

        try {
            $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE");
        } catch (Throwable $e) {
            // constraint may already exist in older installs
        }
    }
};
