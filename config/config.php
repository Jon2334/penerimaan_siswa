<?php
// config.php - Database connection and helper functions

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (from env or fallback for local)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'spk_siswa_fuzzy');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // If database doesn't exist, connect to MySQL server to create it or show error
    try {
        $pdo_init = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $ex) {
        die("Koneksi Database Gagal: " . $ex->getMessage());
    }
}

// Auto-run schema migration if users table is missing
try {
    $table_exists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    if (!$table_exists) {
        $sql_path = __DIR__ . '/../database.sql';
        if (file_exists($sql_path)) {
            $sql = file_get_contents($sql_path);
            
            // Remove comments and execute queries
            $sql_clean = preg_replace('/--.*\n/', '', $sql);
            $queries = explode(';', $sql_clean);
            foreach ($queries as $query) {
                $q = trim($query);
                if (!empty($q)) {
                    $pdo->exec($q);
                }
            }
        }
    }
} catch (PDOException $e) {
    // Silently proceed or log, to avoid breaking execution if check fails
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
