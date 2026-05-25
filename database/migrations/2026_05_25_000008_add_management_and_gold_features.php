<?php

declare(strict_types=1);

return static function (PDO $pdo): void {
    $tableExists = static function (string $table) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$table]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $columnExists = static function (string $table, string $column) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $indexExists = static function (string $table, string $index) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?'
        );
        $stmt->execute([$table, $index]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $constraintExists = static function (string $table, string $constraint) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.table_constraints
             WHERE constraint_schema = DATABASE() AND table_name = ? AND constraint_name = ?'
        );
        $stmt->execute([$table, $constraint]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $addColumn = static function (string $table, string $column, string $definition) use ($pdo, $tableExists, $columnExists): void {
        if ($tableExists($table) && !$columnExists($table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    };

    $addIndex = static function (string $table, string $index, string $columns) use ($pdo, $tableExists, $indexExists): void {
        if ($tableExists($table) && !$indexExists($table, $index)) {
            $pdo->exec("CREATE INDEX {$index} ON {$table} ({$columns})");
        }
    };

    $addForeignKey = static function (string $table, string $constraint, string $definition) use ($pdo, $tableExists, $constraintExists): void {
        if ($tableExists($table) && !$constraintExists($table, $constraint)) {
            $pdo->exec("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} {$definition}");
        }
    };

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS app_settings (
            setting_key VARCHAR(120) PRIMARY KEY,
            setting_value TEXT DEFAULT NULL,
            updated_by INT UNSIGNED DEFAULT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_app_settings_updated_by (updated_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $addForeignKey(
        'app_settings',
        'fk_app_settings_updated_by',
        'FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
    );

    $seedSetting = $pdo->prepare(
        'INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)'
    );
    foreach ([
        'church_name' => 'E-Parish Church',
        'authorized_representative' => 'Parish Administrator',
        'gcash_account_name' => '',
        'gcash_number' => '',
        'gcash_qr_code' => '',
        'volunteer_required_hours' => '20',
    ] as $key => $value) {
        $seedSetting->execute([$key, $value]);
    }

    $addColumn('users', 'status', "ENUM('active', 'disabled') NOT NULL DEFAULT 'active' AFTER role");
    $addColumn('users', 'phone', 'VARCHAR(40) DEFAULT NULL AFTER status');
    $addColumn('users', 'designation', 'VARCHAR(120) DEFAULT NULL AFTER phone');
    $addColumn('users', 'profile_pic', 'VARCHAR(255) DEFAULT NULL AFTER designation');
    $addColumn('users', 'created_by', 'INT UNSIGNED DEFAULT NULL AFTER status');
    $addColumn('users', 'last_login_at', 'DATETIME DEFAULT NULL AFTER created_by');
    $addColumn('users', 'address', 'VARCHAR(255) DEFAULT NULL AFTER phone');
    $addColumn('users', 'active_volunteer', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER designation');
    $addColumn('users', 'volunteer_eligible_at', 'DATETIME DEFAULT NULL AFTER active_volunteer');
    $addIndex('users', 'idx_users_active_volunteer', 'active_volunteer');

    $addColumn('payments', 'original_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount');
    $addColumn('payments', 'discount_percent', 'DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER original_amount');
    $addColumn('payments', 'discount_amount', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER discount_percent');

    if ($tableExists('payments') && $columnExists('payments', 'original_amount')) {
        $pdo->exec(
            'UPDATE payments
             SET original_amount = amount
             WHERE original_amount = 0.00 AND amount > 0'
        );
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS inventory_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL UNIQUE,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS inventory_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            inventory_category_id INT UNSIGNED NOT NULL,
            item_name VARCHAR(190) NOT NULL,
            quantity DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
            price DECIMAL(10,2) DEFAULT NULL,
            low_stock_threshold DECIMAL(10,2) NOT NULL DEFAULT 5.00,
            status ENUM('Active', 'Archived') NOT NULL DEFAULT 'Active',
            created_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_inventory_items_category (inventory_category_id),
            INDEX idx_inventory_items_status (status),
            INDEX idx_inventory_items_name (item_name),
            CONSTRAINT fk_inventory_items_category FOREIGN KEY (inventory_category_id) REFERENCES inventory_categories(id)
                ON DELETE RESTRICT ON UPDATE CASCADE,
            CONSTRAINT fk_inventory_items_created_by FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $seedCategory = $pdo->prepare(
        'INSERT INTO inventory_categories (name, sort_order, is_active)
         VALUES (?, ?, 1)
         ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), is_active = 1'
    );
    foreach ([
        'Church Supplies',
        'Candles',
        'Flowers',
        'Documents / Certificates',
        'Office Supplies',
        'Drinks / Beverages',
        'Other Supplies',
    ] as $index => $category) {
        $seedCategory->execute([$category, ($index + 1) * 10]);
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS parish_services (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            service_name VARCHAR(150) NOT NULL UNIQUE,
            service_type ENUM('Certificate', 'Appointment', 'Other') NOT NULL DEFAULT 'Other',
            description TEXT DEFAULT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            required_documents TEXT DEFAULT NULL,
            availability_status ENUM('Active', 'Inactive', 'Archived') NOT NULL DEFAULT 'Active',
            created_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_parish_services_type (service_type),
            INDEX idx_parish_services_status (availability_status),
            CONSTRAINT fk_parish_services_created_by FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $seedService = $pdo->prepare(
        'INSERT INTO parish_services
            (service_name, service_type, description, price, required_documents, availability_status)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            service_type = VALUES(service_type),
            description = COALESCE(parish_services.description, VALUES(description)),
            price = CASE WHEN parish_services.price = 0.00 THEN VALUES(price) ELSE parish_services.price END,
            required_documents = COALESCE(parish_services.required_documents, VALUES(required_documents)),
            availability_status = CASE WHEN parish_services.availability_status = "Archived" THEN "Archived" ELSE parish_services.availability_status END'
    );

    foreach ([
        ['Baptismal Certificate', 'Certificate', 'Official baptismal record request.', 150.00, 'Valid ID; supporting document if available', 'Active'],
        ['Marriage Certificate', 'Certificate', 'Official marriage record request.', 150.00, 'Valid ID; supporting document if available', 'Active'],
        ['Confirmation Certificate', 'Certificate', 'Official confirmation record request.', 150.00, 'Valid ID; baptismal reference if available', 'Active'],
        ['Death Certificate', 'Certificate', 'Official death record request.', 150.00, 'Valid ID; supporting document if available', 'Active'],
        ['Funeral Service', 'Appointment', 'Coordinate a funeral mass or memorial service.', 0.00, 'Requester information; preferred date and time', 'Active'],
        ['Mass Intention', 'Appointment', 'Schedule a mass intention.', 250.00, 'Intention details; preferred date', 'Active'],
        ['Appointment', 'Appointment', 'General parish office appointment.', 0.00, 'Purpose of visit; preferred date and time', 'Active'],
        ['Baptismal', 'Appointment', 'Schedule a baptismal service.', 500.00, 'Birth certificate; parent and sponsor details', 'Active'],
        ['Marriage', 'Appointment', 'Schedule marriage documentation and coordination.', 1500.00, 'Canonical interview documents; IDs; certificates', 'Active'],
        ['Confirmation', 'Appointment', 'Schedule confirmation coordination.', 0.00, 'Baptismal certificate; valid ID', 'Active'],
    ] as $service) {
        $seedService->execute($service);
    }

    if ($tableExists('certificate_types')) {
        $pdo->exec(
            'INSERT INTO parish_services (service_name, service_type, price, availability_status)
             SELECT name, "Certificate", 150.00, CASE WHEN is_active = 1 THEN "Active" ELSE "Inactive" END
             FROM certificate_types
             ON DUPLICATE KEY UPDATE availability_status = CASE WHEN parish_services.availability_status = "Archived" THEN "Archived" ELSE parish_services.availability_status END'
        );
    }

    if ($tableExists('appointment_types')) {
        $pdo->exec(
            'INSERT INTO parish_services (service_name, service_type, price, availability_status)
             SELECT name, "Appointment", 0.00, CASE WHEN is_active = 1 THEN "Active" ELSE "Inactive" END
             FROM appointment_types
             ON DUPLICATE KEY UPDATE availability_status = CASE WHEN parish_services.availability_status = "Archived" THEN "Archived" ELSE parish_services.availability_status END'
        );
    }
};
