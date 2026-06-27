<?php
// config.php - Database connection and helper functions

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Parse NEON_DATABASE_URL if set (format: postgresql://user:pass@host:port/dbname?options=...&sslmode=require)
$neon_url = getenv('NEON_DATABASE_URL');
if ($neon_url) {
    $parts = parse_url($neon_url);
    $db_host = $parts['host'] ?? 'localhost';
    $db_port = $parts['port'] ?? '5432';
    $db_user = $parts['user'] ?? '';
    $db_pass = $parts['pass'] ?? '';
    $db_name = isset($parts['path']) ? ltrim($parts['path'], '/') : '';
    $db_options = $parts['query'] ?? '';
    // Remove channel_binding param as Neon doesn't support it
    $db_options = preg_replace('/channel_binding=[^&]*&?/', '', $db_options);
} else {
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_port = getenv('DB_PORT') ?: '5432';
    $db_user = getenv('DB_USER') ?: 'postgres';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_name = getenv('DB_NAME') ?: 'spk_siswa_fuzzy';
    $db_options = '';
}

try {
    $dsn = "pgsql:host=" . $db_host . ";port=" . $db_port . ";dbname=" . $db_name;
    if ($db_options) {
        $dsn .= ";" . str_replace('&', ';', $db_options);
    }
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Base Path URL helper
function base_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . "://" . $host . "/" . ltrim($path, '/');
}

// Redirect helper
function redirect($path) {
    header("Location: " . base_url($path));
    exit;
}

// Check if user is logged in
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . base_url('login/index.php'));
        exit;
    }
}

// Sanitize inputs
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Flash messages helpers
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function display_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo "<script>
            Swal.fire({
                icon: '{$flash['type']}',
                title: '" . ($flash['type'] == 'success' ? 'Sukses!' : 'Oops...') . "',
                text: '{$flash['message']}',
                confirmButtonColor: '#3085d6'
            });
        </script>";
    }
}
?>
