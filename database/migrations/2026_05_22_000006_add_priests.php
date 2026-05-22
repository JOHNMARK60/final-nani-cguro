<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS priests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL UNIQUE,
            signature_text VARCHAR(190) DEFAULT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $stmt = $pdo->prepare(
        'INSERT INTO priests (name, signature_text, sort_order, is_active)
         VALUES (?, ?, 10, 1)
         ON DUPLICATE KEY UPDATE signature_text = VALUES(signature_text), is_active = 1'
    );
    $stmt->execute(['Gabriel Romero', 'Gabriel Romero']);

    $pdo->exec(
        "UPDATE digital_certificates
         SET officiant = 'Gabriel Romero'
         WHERE officiant IS NULL OR TRIM(officiant) = ''"
    );
};
