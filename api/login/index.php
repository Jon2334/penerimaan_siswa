<?php
// login/index.php - User Login Interface
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard/index.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPK Penerimaan Siswa Baru</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= base_url('assets/css/style.css'); ?>" rel="stylesheet">
</head>
<body class="login-bg">

    <div class="login-card text-center">
        <div class="mb-4">
            <div class="display-4 text-primary mb-2">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <h3 class="fw-bold">SPK Penerimaan Siswa</h3>
            <p class="text-white-50">Metode Fuzzy Mamdani</p>
        </div>

        <form action="<?= base_url('login/proses.php'); ?>" method="POST">
            <div class="mb-3 text-start">
                <label for="username" class="form-label text-white-50 fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text login-input"><i class="fa-solid fa-user text-primary"></i></span>
                    <input type="text" class="form-control login-input" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            
            <div class="mb-4 text-start">
                <label for="password" class="form-label text-white-50 fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text login-input"><i class="fa-solid fa-lock text-primary"></i></span>
                    <input type="password" class="form-control login-input" id="password" name="password" placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary-custom w-100 py-2 fs-6 mb-3">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Masuk Aplikasi
            </button>
        </form>
        
        <div class="text-white-50 mt-3 fs-7">
            <small>Demo: <strong>admin</strong> / <strong>admin123</strong></small>
        </div>
    </div>

    <!-- jQuery & SweetAlert2 -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Display Flash Messages if any -->
    <?php display_flash_message(); ?>
</body>
</html>
