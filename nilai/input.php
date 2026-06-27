<?php
// nilai/input.php - Insert/Update Student Grades (Simplified)
require_once __DIR__ . '/../config/config.php';
check_login();

// Retrieve student ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('error', 'ID Siswa tidak valid!');
    redirect('nilai/index.php');
}

$id_siswa = (int)$_GET['id'];

try {
    // Get student details
    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
    $stmt->execute([$id_siswa]);
    $siswa = $stmt->fetch();
    
    if (!$siswa) {
        set_flash_message('error', 'Siswa tidak ditemukan!');
        redirect('nilai/index.php');
    }

    // Get existing grades (if any)
    $grade_stmt = $pdo->prepare("SELECT * FROM nilai_siswa WHERE id_siswa = ?");
    $grade_stmt->execute([$id_siswa]);
    $grade = $grade_stmt->fetch();
} catch (PDOException $e) {
    set_flash_message('error', 'Kesalahan database: ' . $e->getMessage());
    redirect('nilai/index.php');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nilai_uan = (double)$_POST['nilai_uan'];
    $nilai_raport = (double)$_POST['nilai_raport'];
    $tes_kompetensi = (double)$_POST['tes_kompetensi'];

    // Validation ranges
    $errors = [];
    if ($nilai_uan < 0 || $nilai_uan > 40) $errors[] = "Nilai UAN harus berkisar antara 0 - 40.";
    if ($nilai_raport < 0 || $nilai_raport > 100) $errors[] = "Nilai Raport harus berkisar antara 0 - 100.";
    if ($tes_kompetensi < 0 || $tes_kompetensi > 100) $errors[] = "Tes Kompetensi harus berkisar antara 0 - 100.";

    if (!empty($errors)) {
        set_flash_message('error', implode('<br>', $errors));
    } else {
        try {
            if ($grade) {
                // Update
                $up_stmt = $pdo->prepare("
                    UPDATE nilai_siswa 
                    SET nilai_uan = ?, nilai_raport = ?, tes_kompetensi = ?
                    WHERE id_siswa = ?
                ");
                $up_stmt->execute([$nilai_uan, $nilai_raport, $tes_kompetensi, $id_siswa]);
                set_flash_message('success', 'Nilai siswa berhasil diperbarui!');
            } else {
                // Insert
                $in_stmt = $pdo->prepare("
                    INSERT INTO nilai_siswa (id_siswa, nilai_uan, nilai_raport, tes_kompetensi)
                    VALUES (?, ?, ?, ?)
                ");
                $in_stmt->execute([$id_siswa, $nilai_uan, $nilai_raport, $tes_kompetensi]);
                set_flash_message('success', 'Nilai siswa berhasil dimasukkan!');
            }
            
            // Clean up old calculation result for this student since grades changed
            $del_res = $pdo->prepare("DELETE FROM hasil_seleksi WHERE id_siswa = ?");
            $del_res->execute([$id_siswa]);

            redirect('nilai/index.php');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal memproses nilai: ' . $e->getMessage());
        }
    }
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-file-signature text-primary me-2"></i> Input Nilai Siswa</h2>
            <p class="text-muted m-0">Input atau ubah kriteria nilai untuk calon siswa baru</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('nilai/index.php'); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Info Siswa -->
        <div class="col-lg-4 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom bg-light">
                    <span class="fs-5 text-dark fw-bold">Detail Siswa</span>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($siswa['nama_siswa']); ?>&background=random&color=fff&size=100" class="rounded-circle shadow-sm mb-3" alt="Avatar">
                        <h4 class="fw-bold text-dark mb-1"><?= sanitize($siswa['nama_siswa']); ?></h4>
                        <span class="badge bg-primary fs-7 px-3 py-2"><?= sanitize($siswa['nomor_peserta']); ?></span>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0 py-3">
                            <span class="text-muted"><i class="fa-solid fa-venus-mars me-2"></i>Jenis Kelamin:</span>
                            <span class="fw-semibold"><?= $siswa['jk'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-3">
                            <span class="text-muted"><i class="fa-solid fa-school me-2"></i>Asal Sekolah:</span>
                            <span class="fw-semibold text-end"><?= sanitize($siswa['asal_sekolah']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0 py-3">
                            <span class="text-muted"><i class="fa-solid fa-calendar me-2"></i>Tahun Ajaran:</span>
                            <span class="fw-semibold"><?= sanitize($siswa['tahun_ajaran']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Form Input Nilai -->
        <div class="col-lg-8 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom">
                    <span class="fs-5 text-dark fw-bold"><?= $grade ? 'Edit Nilai Seleksi' : 'Input Nilai Seleksi Baru'; ?></span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('nilai/input.php?id=' . $siswa['id_siswa']); ?>" method="POST" id="grade-form">
                        
                        <div class="row">
                            <!-- Kriteria 1: UAN -->
                            <div class="col-md-6 mb-4">
                                <label for="nilai_uan" class="form-label fw-semibold">Nilai UAN SMP <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="40" class="form-control" id="nilai_uan" name="nilai_uan" 
                                           placeholder="Skala 0 - 40" value="<?= $grade ? $grade['nilai_uan'] : ''; ?>" required>
                                    <span class="input-group-text">Max 40</span>
                                </div>
                                <div class="form-text">Batas nilai UAN adalah 0 s.d 40.</div>
                            </div>

                            <!-- Kriteria 2: Raport -->
                            <div class="col-md-6 mb-4">
                                <label for="nilai_raport" class="form-label fw-semibold">Nilai Rata-Rata Raport <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="nilai_raport" name="nilai_raport" 
                                           placeholder="Skala 0 - 100" value="<?= $grade ? $grade['nilai_raport'] : ''; ?>" required>
                                    <span class="input-group-text">Max 100</span>
                                </div>
                                <div class="form-text">Batas nilai rata-rata raport adalah 0 s.d 100.</div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Kriteria 3: Tes Kompetensi -->
                            <div class="col-md-6 mb-4">
                                <label for="tes_kompetensi" class="form-label fw-semibold">Tes Kompetensi Umum <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="tes_kompetensi" name="tes_kompetensi" 
                                           placeholder="Skala 0 - 100" value="<?= $grade ? $grade['tes_kompetensi'] : ''; ?>" required>
                                    <span class="input-group-text">Max 100</span>
                                </div>
                                <div class="form-text">Batas nilai tes kompetensi adalah 0 s.d 100.</div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary-custom px-4">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Nilai Siswa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
