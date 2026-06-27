<?php
// Standalone fix script that does NOT call config.php (avoids session chicken-egg)
$neon_url = getenv('NEON_DATABASE_URL');
if ($neon_url) {
    $parts = parse_url($neon_url);
    $db_host = $parts['host'] ?? 'localhost';
    $db_port = $parts['port'] ?? '5432';
    $db_user = $parts['user'] ?? '';
    $db_pass = $parts['pass'] ?? '';
    $db_name = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
    $db_options = $parts['query'] ?? '';
    $db_options = preg_replace('/channel_binding=[^&]*&?/', '', $db_options);
    $host_parts = explode('.', $db_host);
    $endpoint = $host_parts[0] ?? '';
    if ($endpoint && strpos($endpoint, 'ep-') === 0) {
        $db_options .= ($db_options ? '&' : '') . 'options=endpoint=' . $endpoint;
    }
} else {
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_port = getenv('DB_PORT') ?: '5432';
    $db_user = getenv('DB_USER') ?: 'postgres';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_NAME') ?: 'spk_siswa_fuzzy';
    $db_options = '';
}
$dsn = "pgsql:host=" . $db_host . ";port=" . $db_port . ";dbname=" . $db_name;
if ($db_options) {
    $dsn .= ";" . str_replace('&', ';', $db_options);
}
$pdo = new PDO($dsn, $db_user, $db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

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
