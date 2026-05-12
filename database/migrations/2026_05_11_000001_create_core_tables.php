<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(150) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
            phone VARCHAR(40) DEFAULT NULL,
            designation VARCHAR(120) DEFAULT NULL,
            profile_pic VARCHAR(255) DEFAULT NULL,
            reset_token_hash VARCHAR(255) DEFAULT NULL,
            reset_token_expires_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_users_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS appointments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            appointment_type VARCHAR(150) NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            notes TEXT DEFAULT NULL,
            status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled', 'Confirmed') NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_appointments_user_date (user_id, appointment_date),
            CONSTRAINT fk_appointments_user FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS certificate_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            certificate_type VARCHAR(150) NOT NULL,
            full_name VARCHAR(150) NOT NULL,
            birth_date DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            baptismal_file VARCHAR(255) DEFAULT NULL,
            id_file VARCHAR(255) DEFAULT NULL,
            status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_certificate_requests_user_created (user_id, created_at),
            INDEX idx_certificate_requests_status (status),
            CONSTRAINT fk_certificate_requests_user FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS volunteer_service (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED DEFAULT NULL,
            activity_name VARCHAR(190) NOT NULL,
            activity_date DATE DEFAULT NULL,
            hours_served DECIMAL(5,2) NOT NULL DEFAULT 0,
            notes TEXT DEFAULT NULL,
            status ENUM('Pending', 'Approved', 'Rejected', 'Verified') NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_volunteer_service_user_date (user_id, activity_date),
            CONSTRAINT fk_volunteer_service_user FOREIGN KEY (user_id) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $columnExists = static function (string $table, string $column) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $addColumn = static function (string $table, string $column, string $definition) use ($pdo, $columnExists): void {
        if (!$columnExists($table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    };

    $addColumn('users', 'phone', 'VARCHAR(40) DEFAULT NULL AFTER role');
    $addColumn('users', 'designation', 'VARCHAR(120) DEFAULT NULL AFTER phone');
    $addColumn('users', 'profile_pic', 'VARCHAR(255) DEFAULT NULL AFTER designation');
    $addColumn('users', 'reset_token_hash', 'VARCHAR(255) DEFAULT NULL AFTER profile_pic');
    $addColumn('users', 'reset_token_expires_at', 'DATETIME DEFAULT NULL AFTER reset_token_hash');
    $addColumn('users', 'created_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    $addColumn('users', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
};
