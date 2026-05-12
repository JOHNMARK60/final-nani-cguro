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

    if (!$columnExists('certificate_requests', 'requester_location')) {
        $pdo->exec("ALTER TABLE certificate_requests ADD COLUMN requester_location ENUM('Near Parish', 'Far from Parish') NOT NULL DEFAULT 'Near Parish' AFTER birth_date");
    }

    if (!$columnExists('certificate_requests', 'delivery_option')) {
        $pdo->exec("ALTER TABLE certificate_requests ADD COLUMN delivery_option ENUM('Walk-in Pickup', 'E-Certificate') NOT NULL DEFAULT 'Walk-in Pickup' AFTER requester_location");
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS digital_certificates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            certificate_request_id INT UNSIGNED NOT NULL UNIQUE,
            certificate_number VARCHAR(80) NOT NULL UNIQUE,
            certificate_type VARCHAR(150) NOT NULL,
            delivery_mode ENUM('Walk-in Pickup', 'E-Certificate') NOT NULL DEFAULT 'Walk-in Pickup',
            church_name VARCHAR(190) NOT NULL DEFAULT 'E-Parish Church',
            parish_address VARCHAR(255) DEFAULT NULL,
            recipient_name VARCHAR(190) NOT NULL,
            parent_names VARCHAR(255) DEFAULT NULL,
            birth_date DATE DEFAULT NULL,
            event_date DATE DEFAULT NULL,
            event_place VARCHAR(255) DEFAULT NULL,
            officiant VARCHAR(190) DEFAULT NULL,
            sponsors_witnesses TEXT DEFAULT NULL,
            book_no VARCHAR(80) DEFAULT NULL,
            page_no VARCHAR(80) DEFAULT NULL,
            remarks TEXT DEFAULT NULL,
            qr_reference VARCHAR(120) NOT NULL UNIQUE,
            status ENUM('Issued', 'Voided') NOT NULL DEFAULT 'Issued',
            issued_by INT UNSIGNED DEFAULT NULL,
            issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_digital_certificates_delivery (delivery_mode),
            INDEX idx_digital_certificates_status (status),
            CONSTRAINT fk_digital_certificates_request FOREIGN KEY (certificate_request_id) REFERENCES certificate_requests(id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_digital_certificates_issuer FOREIGN KEY (issued_by) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
};
