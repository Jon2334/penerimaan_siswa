<?php
require __DIR__ . '/config/config.php';
// Create sessions table
$pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    data TEXT NOT NULL DEFAULT '',
    last_accessed TIMESTAMP DEFAULT NOW()
)");
// Update admin password
$hash = '$2y$12$4NtiCkKR.vHYpP4/wvVWb.bAF3sSeQOrMjXdKa35mOMUqKMwVJwtK';
$pdo->exec("UPDATE users SET password = '{$hash}' WHERE username = 'admin'");
echo "Sessions table created and password updated.\n";
