<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS parish_calendar_events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(190) NOT NULL,
            event_type ENUM('Mass', 'Confession', 'Parish Event', 'Office Schedule', 'Other') NOT NULL DEFAULT 'Parish Event',
            event_date DATE NOT NULL,
            event_time TIME DEFAULT NULL,
            location VARCHAR(190) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            visibility ENUM('Public', 'Admin Only') NOT NULL DEFAULT 'Public',
            status ENUM('Scheduled', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
            created_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_parish_calendar_date (event_date, event_time),
            INDEX idx_parish_calendar_status (status),
            INDEX idx_parish_calendar_visibility (visibility),
            CONSTRAINT fk_parish_calendar_created_by FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
};
