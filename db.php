<?php
require_once __DIR__ . '/config/db_connection.php';

// Legacy compatibility for pages that still include db.php.
// They now receive the shared PDO connection instead of mysqli.
$conn = $pdo;
