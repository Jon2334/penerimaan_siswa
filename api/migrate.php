<?php
// migrate.php - Manual database migration for PostgreSQL
// Run this script once after deploying to create/update tables.
// Usage: php migrate.php
// Or access via browser: https://your-app/migrate.php

require_once __DIR__ . '/config/config.php';

echo "Running database migration...\n";

$sql_path = __DIR__ . '/../database.postgres.sql';

if (!file_exists($sql_path)) {
    die("Migration file not found: $sql_path\n");
}

$sql = file_get_contents($sql_path);

// Split by semicolons but preserve DO blocks (anonymous functions)
// Simple approach: split by semicolons and execute each statement
$statements = explode(';', $sql);

$count = 0;
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (!empty($stmt)) {
        try {
            $pdo->exec($stmt);
            $count++;
        } catch (PDOException $e) {
            echo "Error on statement #$count: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($stmt, 0, 100) . "...\n\n";
        }
    }
}

echo "Migration complete. $count statements executed.\n";
echo "Default admin credentials: username=admin, password=admin123\n";
