<?php
// login/logout.php - Handle User Logout
require_once __DIR__ . '/../config/config.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a new session just to pass the flash message
session_start();
set_flash_message('success', 'Anda telah berhasil keluar dari sistem!');
redirect('login/index.php');
?>
