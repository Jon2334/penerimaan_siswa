<?php
// login/proses.php - Handle User Login Verification
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);

    if (empty($username) || empty($password)) {
        set_flash_message('error', 'Username dan password tidak boleh kosong!');
        redirect('login/index.php');
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify using password_verify, plain-text fallback, or md5 fallback
            if (password_verify($password, $user['password']) || $password === $user['password'] || md5($password) === $user['password']) {
                
                // If it was plain text or md5, upgrade it to a secure hash automatically
                if ($password === $user['password'] || md5($password) === $user['password']) {
                    try {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                        $update_stmt->execute([$new_hash, $user['id_user']]);
                    } catch (PDOException $e) {
                        // Ignore hash upgrade errors so user isn't blocked
                    }
                }

                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = $user['role'];
                
                set_flash_message('success', 'Selamat datang, ' . $user['nama'] . '!');
                redirect('dashboard/index.php');
            } else {
                set_flash_message('error', 'Password yang Anda masukkan salah!');
                redirect('login/index.php');
            }
        } else {
            set_flash_message('error', 'Username tidak ditemukan!');
            redirect('login/index.php');
        }
    } catch (PDOException $e) {
        set_flash_message('error', 'Terjadi kesalahan database: ' . $e->getMessage());
        redirect('login/index.php');
    }
} else {
    // If accessed directly, redirect to login page
    redirect('login/index.php');
}
?>
