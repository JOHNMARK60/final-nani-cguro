<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            payable_type ENUM('Certificate', 'Appointment', 'General') NOT NULL DEFAULT 'General',
            payable_id INT UNSIGNED DEFAULT NULL,
            description VARCHAR(190) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            method ENUM('Cash', 'GCash', 'Bank Transfer') NOT NULL DEFAULT 'GCash',
            reference_number VARCHAR(120) DEFAULT NULL,
            proof_file VARCHAR(255) DEFAULT NULL,
            status ENUM('Unpaid', 'Submitted', 'Verified', 'Rejected') NOT NULL DEFAULT 'Submitted',
            remarks TEXT DEFAULT NULL,
            verified_by INT UNSIGNED DEFAULT NULL,
            verified_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_payments_user_created (user_id, created_at),
            INDEX idx_payments_status (status),
            INDEX idx_payments_method (method),
            INDEX idx_payments_verified_by (verified_by),
            CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_payments_verified_by FOREIGN KEY (verified_by) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
};
