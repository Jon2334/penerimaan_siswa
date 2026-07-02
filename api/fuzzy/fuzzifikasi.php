<?php
// fuzzy/fuzzifikasi.php - Fuzzification Step Viewer
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../function/fuzzy_engine.php';
check_login();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('error', 'ID Siswa tidak valid!');
    redirect('fuzzy/hasil.php');
}

$id_siswa = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT s.*, n.* 
        FROM siswa s 
        INNER JOIN nilai_siswa n ON s.id_siswa = n.id_siswa 
        WHERE s.id_siswa = ?
    ");
    $stmt->execute([$id_siswa]);
    $data = $stmt->fetch();

    if (!$data) {
        set_flash_message('error', 'Data siswa atau nilai belum lengkap!');
        redirect('fuzzy/hasil.php');
    }
} catch (PDOException $e) {
    set_flash_message('error', 'Kesalahan mengambil data: ' . $e->getMessage());
    redirect('fuzzy/hasil.php');
}

// Calculate membership values
$mu_uan = FuzzyMamdani::uan($data['nilai_uan']);
$mu_raport = FuzzyMamdani::raport($data['nilai_raport']);
$mu_kompetensi = FuzzyMamdani::kompetensi($data['tes_kompetensi']);

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-calculator text-primary me-2"></i> Langkah 1: Fuzzifikasi</h2>
            <p class="text-muted m-0">Derajat keanggotaan kriteria untuk <?= sanitize($data['nama_siswa']); ?></p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('fuzzy/hasil.php'); ?>" class="btn btn-secondary me-2">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali ke Hasil
            </a>
            <a href="<?= base_url('fuzzy/inferensi.php?id=' . $id_siswa); ?>" class="btn btn-primary-custom">
                Lanjut ke Inferensi <i class="fa-solid fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>

    <!-- Student Info Card -->
    <div class="card card-custom mb-4 bg-light border-0">
        <div class="card-body p-3">
            <div class="row text-center text-md-start">
                <div class="col-md-4"><strong>No Peserta:</strong> <span class="text-primary fw-semibold"><?= sanitize($data['nomor_peserta']); ?></span></div>
                <div class="col-md-4"><strong>Nama Lengkap:</strong> <?= sanitize($data['nama_siswa']); ?></div>
                <div class="col-md-4"><strong>Asal Sekolah:</strong> <?= sanitize($data['asal_sekolah']); ?></div>
            </div>
        </div>
    </div>

    <!-- Fuzzification Rows -->
    <div class="row">
        <!-- UAN -->
        <div class="col-md-6 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">1. Nilai UAN SMP</span>
                    <span class="badge bg-primary fs-6">Nilai: <?= number_format($data['nilai_uan'], 2); ?></span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Fungsi keanggotaan: Rendah (0-10-20), Sedang (15-22-29), Tinggi (25-35-40)</p>
                    <?php foreach ($mu_uan as $set => $val): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold text-secondary"><?= $set; ?></span>
                                <span class="fw-bold text-primary"><?= number_format($val, 4); ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $val * 100; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Raport -->
        <div class="col-md-6 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">2. Nilai Rata-rata Raport</span>
                    <span class="badge bg-primary fs-6">Nilai: <?= number_format($data['nilai_raport'], 2); ?></span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Fungsi keanggotaan: Rendah (0-55-65), Sedang (60-72.5-85), Tinggi (75-88-100)</p>
                    <?php foreach ($mu_raport as $set => $val): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold text-secondary"><?= $set; ?></span>
                                <span class="fw-bold text-primary"><?= number_format($val, 4); ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $val * 100; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Kompetensi -->
        <div class="col-md-6 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark">3. Tes Kompetensi Umum</span>
                    <span class="badge bg-primary fs-6">Nilai: <?= number_format($data['tes_kompetensi'], 2); ?></span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Fungsi keanggotaan: Rendah (0-50-60), Sedang (55-67.5-80), Tinggi (75-88-100)</p>
                    <?php foreach ($mu_kompetensi as $set => $val): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold text-secondary"><?= $set; ?></span>
                                <span class="fw-bold text-primary"><?= number_format($val, 4); ?></span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $val * 100; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
