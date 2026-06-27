<?php
// config.php - Database connection and helper functions

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Custom PDO session handler for stateless Vercel environment
class PdoSessionHandler implements SessionHandlerInterface {
    private $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function open($savePath, $sessionName): bool { return true; }
    public function close(): bool { return true; }
    public function read($sessionId): string {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ? AND last_accessed > NOW() - INTERVAL '24 hours'");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetchColumn();
        return $row ? $row : '';
    }
    public function write($sessionId, $data): bool {
        $stmt = $this->pdo->prepare("INSERT INTO sessions (id, data, last_accessed) VALUES (?, ?, NOW()) ON CONFLICT (id) DO UPDATE SET data = ?, last_accessed = NOW()");
        $stmt->execute([$sessionId, $data, $data]);
        return true;
    }
    public function destroy($sessionId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        return true;
    }
    public function gc($maxLifetime): int {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_accessed < NOW() - INTERVAL '24 hours'");
        $stmt->execute();
        return $stmt->rowCount();
    }
}

// (session handler initialized after PDO below)

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
    // Add endpoint ID option for Neon SNI (must be plain, not percent-encoded)
    $host_parts = explode('.', $db_host);
    $endpoint = $host_parts[0] ?? '';
    if ($endpoint && strpos($endpoint, 'ep-') === 0) {
        $endpoint_opt = 'options=endpoint=' . $endpoint;
        if ($db_options) {
            $db_options .= '&' . $endpoint_opt;
        } else {
            $db_options = $endpoint_opt;
        }
    }
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

// Initialize session handler with PDO
session_set_save_handler(new PdoSessionHandler($pdo), true);

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base Path URL helper
function base_url($path = '') {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $protocol = 'https';
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $protocol = 'https';
    } elseif ($_SERVER['SERVER_PORT'] == 443) {
        $protocol = 'https';
    }
    $host = $_SERVER['HTTP_HOST'];
    // Determine the project root relative to the web server root
    $script_name = $_SERVER['SCRIPT_NAME'];
    // e.g. /spk_kayawan/api/layout/header.php or /api/layout/header.php
    $dir = dirname($script_name);
    // Go up three levels (from api/layout to project root)
    $base_path = dirname(dirname(dirname($dir)));
    // Normalize: on Linux / Vercel, dirname('/api') = '/', dirname('/') = '/'
    if ($base_path === '/' || $base_path === '\\' || $base_path === '.') {
        $base_path = '';
    }
    return $protocol . "://" . $host . $base_path . "/" . ltrim($path, '/');
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
