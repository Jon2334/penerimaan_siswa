<?php
// header.php - Main Layout Header
require_once __DIR__ . '/../config/config.php';
check_login();

// Determine active page
$current_page = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPK Penerimaan Siswa Baru - Fuzzy Mamdani</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom Style -->
    <style>
        <?php echo file_get_contents(__DIR__ . '/../../assets/css/style.css'); ?>
    </style>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { background: #f8fafc; font-family: 'Outfit', -apple-system, BlinkMacSystemFont, sans-serif; min-height: 100vh; }
        .sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 260px; background: #1e293b; color: #94a3b8; z-index: 100; transition: transform .3s ease; overflow-y: auto; }
        .sidebar-brand { display: flex; align-items: center; gap: 10px; padding: 1.5rem; font-weight: 700; color: #fff; border-bottom: 1px solid rgba(255,255,255,.08); }
        .nav-link-custom { color: #94a3b8 !important; display: flex; align-items: center; gap: 12px; padding: .75rem 1.5rem; text-decoration: none; border-left: 4px solid transparent; }
        .nav-link-custom:hover { color: #fff !important; background: #334155; }
        .nav-link-custom.active { color: #fff !important; border-left-color: #4f46e5; background: rgba(79,70,229,.15); font-weight: 600; }
        .nav-link-custom i { width: 20px; text-align: center; }
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 2rem; min-height: 100vh; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-260px); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; width: 100%; padding: 1rem; }
        }
    </style>
    <!-- jQuery (Required for inline scripts) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-graduation-cap text-primary"></i>
            <span>SPK MAMDANI</span>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-heading">MENU UTAMA</div>
            
            <a href="<?= base_url('dashboard/index.php'); ?>" class="nav-link-custom <?= (strpos($current_page, 'dashboard') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge"></i>
                <span>Dashboard</span>
            </a>
            
            <div class="nav-heading">MANAJEMEN DATA</div>
            
            <a href="<?= base_url('siswa/index.php'); ?>" class="nav-link-custom <?= (strpos($current_page, 'siswa') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>Data Siswa</span>
            </a>
            
            <a href="<?= base_url('nilai/index.php'); ?>" class="nav-link-custom <?= (strpos($current_page, 'nilai') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-invoice"></i>
                <span>Input Nilai</span>
            </a>
            
            <div class="nav-heading">METODE FUZZY</div>
            
            <a href="<?= base_url('fuzzy/kuota.php'); ?>" class="nav-link-custom <?= (strpos($current_page, 'kuota.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-sliders"></i>
                <span>Kuota Penerimaan</span>
            </a>

            <a href="<?= base_url('fuzzy/aturan.php'); ?>" class="nav-link-custom <?= (strpos($current_page, 'aturan.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-scale-balanced"></i>
                <span>Aturan Fuzzy</span>
            </a>
            
            <a href="<?= base_url('fuzzy/hasil.php'); ?>" class="nav-link-custom <?= (strpos($current_page, 'hasil.php') !== false || strpos($current_page, 'proses_fuzzy.php') !== false) ? 'active' : ''; ?>">
                <i class="fa-solid fa-square-poll-vertical"></i>
                <span>Hasil Seleksi</span>
            </a>
            
            <div class="nav-heading">AKUN</div>
            
            <a href="<?= base_url('login/logout.php'); ?>" class="nav-link-custom text-danger">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- Content Area Container -->
    <main class="main-content" id="main-content">
        <header class="top-navbar">
            <button class="btn btn-light d-lg-none" id="sidebar-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="d-none d-md-inline fw-semibold text-muted">
                    <?= isset($_SESSION['user_nama']) ? sanitize($_SESSION['user_nama']) : 'Administrator'; ?> (<?= isset($_SESSION['user_role']) ? sanitize($_SESSION['user_role']) : 'Admin'; ?>)
                </span>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_nama'] ?? 'Admin'); ?>&background=4f46e5&color=fff" alt="Profile avatar" class="rounded-circle" width="40" height="40">

        </header>
        
        <!-- Content gets injected here -->

