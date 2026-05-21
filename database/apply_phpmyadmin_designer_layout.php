<?php

declare(strict_types=1);

use App\Core\Container;

/** @var Container $container */
$container = require dirname(__DIR__) . '/config/app.php';
$pdo = $container->pdo();
$dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
$pageDescription = 'E-Parish ERD';

$requiredTables = ['pma__pdf_pages', 'pma__table_coords'];
$stmt = $pdo->query(
    "SELECT TABLE_NAME
     FROM information_schema.tables
     WHERE TABLE_SCHEMA = 'phpmyadmin'
       AND TABLE_NAME IN ('pma__pdf_pages', 'pma__table_coords')"
);
$availableTables = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'TABLE_NAME');

foreach ($requiredTables as $table) {
    if (!in_array($table, $availableTables, true)) {
        fwrite(STDERR, "phpMyAdmin configuration table phpmyadmin.{$table} was not found." . PHP_EOL);
        fwrite(STDERR, 'Open phpMyAdmin once or enable phpMyAdmin configuration storage, then run this script again.' . PHP_EOL);
        exit(1);
    }
}

$coords = [
    'appointment_types' => [40, 40],
    'appointments' => [680, 40],
    'appointment_status_history' => [1020, 40],
    'digital_certificates' => [1020, 240],
    'certificate_types' => [40, 260],
    'certificate_requests' => [680, 260],
    'users' => [360, 300],
    'certificate_status_history' => [1020, 500],
    'volunteer_activities' => [40, 540],
    'volunteer_service' => [680, 540],
    'payment_methods' => [40, 800],
    'payment_categories' => [360, 800],
    'payments' => [680, 800],
    'admin_audit_logs' => [1020, 800],
    'migrations' => [360, 1020],
];

$pdo->beginTransaction();

$stmt = $pdo->prepare(
    'SELECT page_nr
     FROM phpmyadmin.pma__pdf_pages
     WHERE db_name = ? AND page_descr = ?
     LIMIT 1'
);
$stmt->execute([$dbName, $pageDescription]);
$pageNr = (int) ($stmt->fetchColumn() ?: 0);

if ($pageNr === 0) {
    $stmt = $pdo->prepare('INSERT INTO phpmyadmin.pma__pdf_pages (db_name, page_descr) VALUES (?, ?)');
    $stmt->execute([$dbName, $pageDescription]);
    $pageNr = (int) $pdo->lastInsertId();
}

$stmt = $pdo->prepare('SELECT TABLE_NAME AS table_name FROM information_schema.tables WHERE TABLE_SCHEMA = ?');
$stmt->execute([$dbName]);
$existingTables = array_flip(array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'table_name'));

$upsert = $pdo->prepare(
    'INSERT INTO phpmyadmin.pma__table_coords (db_name, table_name, pdf_page_number, x, y)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE x = VALUES(x), y = VALUES(y)'
);

foreach ($coords as $table => [$x, $y]) {
    if (isset($existingTables[$table])) {
        $upsert->execute([$dbName, $table, $pageNr, $x, $y]);
    }
}

$pdo->commit();

echo "Saved phpMyAdmin Designer layout '{$pageDescription}' for {$dbName} on page {$pageNr}." . PHP_EOL;
