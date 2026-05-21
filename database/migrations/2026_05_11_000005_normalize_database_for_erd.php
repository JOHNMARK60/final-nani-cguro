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

    $dropColumn = static function (string $table, string $column) use ($pdo, $tableExists, $columnExists): void {
        if ($tableExists($table) && $columnExists($table, $column)) {
            $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
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

    $seedLookup = static function (string $table, array $names) use ($pdo): void {
        $stmt = $pdo->prepare(
            "INSERT INTO {$table} (name, sort_order, is_active)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), is_active = 1"
        );

        foreach (array_values($names) as $index => $name) {
            $stmt->execute([$name, ($index + 1) * 10]);
        }
    };

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS appointment_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL UNIQUE,
            description VARCHAR(255) DEFAULT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS certificate_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL UNIQUE,
            requires_supporting_document TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS volunteer_activities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL UNIQUE,
            description VARCHAR(255) DEFAULT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payment_methods (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL UNIQUE,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS payment_categories (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL UNIQUE,
            description VARCHAR(255) DEFAULT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $seedLookup('appointment_types', [
        'Mass Intentions',
        'Counseling Session',
        'Baptism Service',
        'Wedding Service',
        'Funeral Service',
    ]);

    $seedLookup('certificate_types', [
        'Baptismal Certificate',
        'Confirmation Certificate',
        'Marriage Certificate',
        'Death Certificate',
    ]);

    $pdo->exec(
        "UPDATE certificate_types
         SET requires_supporting_document = CASE WHEN name = 'Baptismal Certificate' THEN 0 ELSE 1 END"
    );

    $seedLookup('volunteer_activities', [
        'Choir Service',
        'Catechism Assistance',
        'Parish Outreach',
        'Office Support',
        'Event Ushering',
    ]);

    $seedLookup('payment_methods', ['GCash', 'Cash', 'Bank Transfer']);
    $seedLookup('payment_categories', ['General', 'Certificate', 'Appointment']);

    if ($tableExists('appointments') && $columnExists('appointments', 'appointment_type')) {
        $pdo->exec(
            "INSERT INTO appointment_types (name)
             SELECT DISTINCT TRIM(appointment_type)
             FROM appointments
             WHERE appointment_type IS NOT NULL AND TRIM(appointment_type) <> ''
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );
    }

    $addColumn('appointments', 'appointment_type_id', 'INT UNSIGNED NULL AFTER user_id');

    if ($tableExists('appointments') && $columnExists('appointments', 'appointment_type')) {
        $pdo->exec(
            "UPDATE appointments a
             INNER JOIN appointment_types t ON t.name = a.appointment_type
             SET a.appointment_type_id = t.id
             WHERE a.appointment_type_id IS NULL"
        );
    }

    if ($tableExists('appointments') && $columnExists('appointments', 'appointment_type_id')) {
        $pdo->exec(
            "UPDATE appointments
             SET appointment_type_id = (SELECT id FROM appointment_types WHERE name = 'Mass Intentions' LIMIT 1)
             WHERE appointment_type_id IS NULL"
        );
        $pdo->exec('ALTER TABLE appointments MODIFY appointment_type_id INT UNSIGNED NOT NULL');
        $addIndex('appointments', 'idx_appointments_type', 'appointment_type_id');
        $addForeignKey(
            'appointments',
            'fk_appointments_type',
            'FOREIGN KEY (appointment_type_id) REFERENCES appointment_types(id) ON DELETE RESTRICT ON UPDATE CASCADE'
        );
        $dropColumn('appointments', 'appointment_type');
    }

    if ($tableExists('certificate_requests') && $columnExists('certificate_requests', 'certificate_type')) {
        $pdo->exec(
            "INSERT INTO certificate_types (name)
             SELECT DISTINCT TRIM(certificate_type)
             FROM certificate_requests
             WHERE certificate_type IS NOT NULL AND TRIM(certificate_type) <> ''
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );
    }

    $addColumn('certificate_requests', 'certificate_type_id', 'INT UNSIGNED NULL AFTER user_id');

    if ($tableExists('certificate_requests') && $columnExists('certificate_requests', 'certificate_type')) {
        $pdo->exec(
            "UPDATE certificate_requests c
             INNER JOIN certificate_types t ON t.name = c.certificate_type
             SET c.certificate_type_id = t.id
             WHERE c.certificate_type_id IS NULL"
        );
    }

    if ($tableExists('certificate_requests') && $columnExists('certificate_requests', 'certificate_type_id')) {
        $pdo->exec(
            "UPDATE certificate_requests
             SET certificate_type_id = (SELECT id FROM certificate_types WHERE name = 'Baptismal Certificate' LIMIT 1)
             WHERE certificate_type_id IS NULL"
        );
        $pdo->exec('ALTER TABLE certificate_requests MODIFY certificate_type_id INT UNSIGNED NOT NULL');
        $addIndex('certificate_requests', 'idx_certificate_requests_type', 'certificate_type_id');
        $addForeignKey(
            'certificate_requests',
            'fk_certificate_requests_type',
            'FOREIGN KEY (certificate_type_id) REFERENCES certificate_types(id) ON DELETE RESTRICT ON UPDATE CASCADE'
        );
        $dropColumn('certificate_requests', 'certificate_type');
    }

    if ($tableExists('digital_certificates') && $columnExists('digital_certificates', 'certificate_type')) {
        $pdo->exec(
            "INSERT INTO certificate_types (name)
             SELECT DISTINCT TRIM(certificate_type)
             FROM digital_certificates
             WHERE certificate_type IS NOT NULL AND TRIM(certificate_type) <> ''
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );
    }

    $addColumn('digital_certificates', 'certificate_type_id', 'INT UNSIGNED NULL AFTER certificate_number');

    if ($tableExists('digital_certificates') && $columnExists('digital_certificates', 'certificate_type')) {
        $pdo->exec(
            "UPDATE digital_certificates d
             LEFT JOIN certificate_types typed ON typed.name = d.certificate_type
             LEFT JOIN certificate_requests r ON r.id = d.certificate_request_id
             LEFT JOIN certificate_types requested ON requested.id = r.certificate_type_id
             SET d.certificate_type_id = COALESCE(typed.id, requested.id)
             WHERE d.certificate_type_id IS NULL"
        );
    } elseif ($tableExists('digital_certificates') && $columnExists('digital_certificates', 'certificate_type_id')) {
        $pdo->exec(
            "UPDATE digital_certificates d
             INNER JOIN certificate_requests r ON r.id = d.certificate_request_id
             SET d.certificate_type_id = r.certificate_type_id
             WHERE d.certificate_type_id IS NULL"
        );
    }

    if ($tableExists('digital_certificates') && $columnExists('digital_certificates', 'certificate_type_id')) {
        $pdo->exec(
            "UPDATE digital_certificates
             SET certificate_type_id = (SELECT id FROM certificate_types WHERE name = 'Baptismal Certificate' LIMIT 1)
             WHERE certificate_type_id IS NULL"
        );
        $pdo->exec('ALTER TABLE digital_certificates MODIFY certificate_type_id INT UNSIGNED NOT NULL');
        $addIndex('digital_certificates', 'idx_digital_certificates_type', 'certificate_type_id');
        $addForeignKey(
            'digital_certificates',
            'fk_digital_certificates_type',
            'FOREIGN KEY (certificate_type_id) REFERENCES certificate_types(id) ON DELETE RESTRICT ON UPDATE CASCADE'
        );
        $dropColumn('digital_certificates', 'certificate_type');
    }

    if ($tableExists('volunteer_service') && $columnExists('volunteer_service', 'activity_name')) {
        $pdo->exec(
            "INSERT INTO volunteer_activities (name)
             SELECT DISTINCT TRIM(activity_name)
             FROM volunteer_service
             WHERE activity_name IS NOT NULL AND TRIM(activity_name) <> ''
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );
    }

    $addColumn('volunteer_service', 'volunteer_activity_id', 'INT UNSIGNED NULL AFTER user_id');

    if ($tableExists('volunteer_service') && $columnExists('volunteer_service', 'activity_name')) {
        $pdo->exec(
            "UPDATE volunteer_service v
             INNER JOIN volunteer_activities a ON a.name = v.activity_name
             SET v.volunteer_activity_id = a.id
             WHERE v.volunteer_activity_id IS NULL"
        );
    }

    if ($tableExists('volunteer_service') && $columnExists('volunteer_service', 'volunteer_activity_id')) {
        $pdo->exec(
            "UPDATE volunteer_service
             SET volunteer_activity_id = (SELECT id FROM volunteer_activities WHERE name = 'Parish Outreach' LIMIT 1)
             WHERE volunteer_activity_id IS NULL"
        );
        $pdo->exec('ALTER TABLE volunteer_service MODIFY volunteer_activity_id INT UNSIGNED NOT NULL');
        $addIndex('volunteer_service', 'idx_volunteer_service_activity', 'volunteer_activity_id');
        $addForeignKey(
            'volunteer_service',
            'fk_volunteer_service_activity',
            'FOREIGN KEY (volunteer_activity_id) REFERENCES volunteer_activities(id) ON DELETE RESTRICT ON UPDATE CASCADE'
        );
        $dropColumn('volunteer_service', 'activity_name');
    }

    if ($tableExists('payments') && $columnExists('payments', 'method')) {
        $pdo->exec(
            "INSERT INTO payment_methods (name)
             SELECT DISTINCT TRIM(method)
             FROM payments
             WHERE method IS NOT NULL AND TRIM(method) <> ''
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );
    }

    if ($tableExists('payments') && $columnExists('payments', 'payable_type')) {
        $pdo->exec(
            "INSERT INTO payment_categories (name)
             SELECT DISTINCT TRIM(payable_type)
             FROM payments
             WHERE payable_type IS NOT NULL AND TRIM(payable_type) <> ''
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );
    }

    $addColumn('payments', 'payment_category_id', 'INT UNSIGNED NULL AFTER user_id');
    $addColumn('payments', 'certificate_request_id', 'INT UNSIGNED NULL AFTER payment_category_id');
    $addColumn('payments', 'appointment_id', 'INT UNSIGNED NULL AFTER certificate_request_id');
    $addColumn('payments', 'payment_method_id', 'INT UNSIGNED NULL AFTER amount');

    if ($tableExists('payments') && $columnExists('payments', 'method')) {
        $pdo->exec(
            "UPDATE payments p
             INNER JOIN payment_methods m ON m.name = p.method
             SET p.payment_method_id = m.id
             WHERE p.payment_method_id IS NULL"
        );
    }

    if ($tableExists('payments') && $columnExists('payments', 'payable_type')) {
        $pdo->exec(
            "UPDATE payments p
             INNER JOIN payment_categories c ON c.name = p.payable_type
             SET p.payment_category_id = c.id
             WHERE p.payment_category_id IS NULL"
        );
    }

    if ($tableExists('payments') && $columnExists('payments', 'payable_id') && $columnExists('payments', 'payable_type')) {
        $pdo->exec(
            "UPDATE payments p
             INNER JOIN payment_categories c ON c.id = p.payment_category_id AND c.name = 'Certificate'
             INNER JOIN certificate_requests r ON r.id = p.payable_id
             SET p.certificate_request_id = r.id
             WHERE p.certificate_request_id IS NULL"
        );
        $pdo->exec(
            "UPDATE payments p
             INNER JOIN payment_categories c ON c.id = p.payment_category_id AND c.name = 'Appointment'
             INNER JOIN appointments a ON a.id = p.payable_id
             SET p.appointment_id = a.id
             WHERE p.appointment_id IS NULL"
        );
    }

    if ($tableExists('payments') && $columnExists('payments', 'payment_method_id')) {
        $pdo->exec(
            "UPDATE payments
             SET payment_method_id = (SELECT id FROM payment_methods WHERE name = 'GCash' LIMIT 1)
             WHERE payment_method_id IS NULL"
        );
        $pdo->exec('ALTER TABLE payments MODIFY payment_method_id INT UNSIGNED NOT NULL');
        $addIndex('payments', 'idx_payments_payment_method', 'payment_method_id');
        $addForeignKey(
            'payments',
            'fk_payments_payment_method',
            'FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT ON UPDATE CASCADE'
        );
    }

    if ($tableExists('payments') && $columnExists('payments', 'payment_category_id')) {
        $pdo->exec(
            "UPDATE payments
             SET payment_category_id = (SELECT id FROM payment_categories WHERE name = 'General' LIMIT 1)
             WHERE payment_category_id IS NULL"
        );
        $pdo->exec('ALTER TABLE payments MODIFY payment_category_id INT UNSIGNED NOT NULL');
        $addIndex('payments', 'idx_payments_payment_category', 'payment_category_id');
        $addForeignKey(
            'payments',
            'fk_payments_payment_category',
            'FOREIGN KEY (payment_category_id) REFERENCES payment_categories(id) ON DELETE RESTRICT ON UPDATE CASCADE'
        );
    }

    if ($tableExists('payments') && $columnExists('payments', 'certificate_request_id')) {
        $addIndex('payments', 'idx_payments_certificate_request', 'certificate_request_id');
        $addForeignKey(
            'payments',
            'fk_payments_certificate_request',
            'FOREIGN KEY (certificate_request_id) REFERENCES certificate_requests(id) ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    if ($tableExists('payments') && $columnExists('payments', 'appointment_id')) {
        $addIndex('payments', 'idx_payments_appointment', 'appointment_id');
        $addForeignKey(
            'payments',
            'fk_payments_appointment',
            'FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    if ($tableExists('payments') && $indexExists('payments', 'idx_payments_method')) {
        $pdo->exec('ALTER TABLE payments DROP INDEX idx_payments_method');
    }

    $dropColumn('payments', 'method');
    $dropColumn('payments', 'payable_type');
    $dropColumn('payments', 'payable_id');

    if ($tableExists('users') && $columnExists('users', 'created_by')) {
        $pdo->exec(
            'UPDATE users u
             LEFT JOIN users creator ON creator.id = u.created_by
             SET u.created_by = NULL
             WHERE u.created_by IS NOT NULL AND creator.id IS NULL'
        );
        $addIndex('users', 'idx_users_created_by', 'created_by');
        $addForeignKey(
            'users',
            'fk_users_created_by',
            'FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    if ($tableExists('admin_audit_logs') && $columnExists('admin_audit_logs', 'admin_id')) {
        $pdo->exec(
            'UPDATE admin_audit_logs l
             LEFT JOIN users u ON u.id = l.admin_id
             SET l.admin_id = NULL
             WHERE l.admin_id IS NOT NULL AND u.id IS NULL'
        );
        $addIndex('admin_audit_logs', 'idx_admin_audit_admin', 'admin_id');
        $addForeignKey(
            'admin_audit_logs',
            'fk_admin_audit_logs_admin',
            'FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    if ($tableExists('appointment_status_history')) {
        $pdo->exec(
            'DELETE h FROM appointment_status_history h
             LEFT JOIN appointments a ON a.id = h.appointment_id
             WHERE a.id IS NULL'
        );
        $pdo->exec(
            'UPDATE appointment_status_history h
             LEFT JOIN users u ON u.id = h.admin_id
             SET h.admin_id = NULL
             WHERE h.admin_id IS NOT NULL AND u.id IS NULL'
        );
        $addIndex('appointment_status_history', 'idx_appointment_history_admin', 'admin_id');
        $addForeignKey(
            'appointment_status_history',
            'fk_appointment_history_appointment',
            'FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE ON UPDATE CASCADE'
        );
        $addForeignKey(
            'appointment_status_history',
            'fk_appointment_history_admin',
            'FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    if ($tableExists('certificate_status_history')) {
        $pdo->exec(
            'DELETE h FROM certificate_status_history h
             LEFT JOIN certificate_requests r ON r.id = h.certificate_request_id
             WHERE r.id IS NULL'
        );
        $pdo->exec(
            'UPDATE certificate_status_history h
             LEFT JOIN users u ON u.id = h.admin_id
             SET h.admin_id = NULL
             WHERE h.admin_id IS NOT NULL AND u.id IS NULL'
        );
        $addIndex('certificate_status_history', 'idx_certificate_history_admin', 'admin_id');
        $addForeignKey(
            'certificate_status_history',
            'fk_certificate_history_request',
            'FOREIGN KEY (certificate_request_id) REFERENCES certificate_requests(id) ON DELETE CASCADE ON UPDATE CASCADE'
        );
        $addForeignKey(
            'certificate_status_history',
            'fk_certificate_history_admin',
            'FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }
};
