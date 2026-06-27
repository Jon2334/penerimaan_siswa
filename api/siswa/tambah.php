<?php
// siswa/tambah.php - Add New Student Form & Handler
require_once __DIR__ . '/../config/config.php';
check_login();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor_peserta = sanitize($_POST['nomor_peserta']);
    $nama_siswa = sanitize($_POST['nama_siswa']);
    $jk = sanitize($_POST['jk']);
    $tempat_lahir = sanitize($_POST['tempat_lahir']);
    $tanggal_lahir = sanitize($_POST['tanggal_lahir']);
    $alamat = sanitize($_POST['alamat']);
    $asal_sekolah = sanitize($_POST['asal_sekolah']);
    $no_hp = sanitize($_POST['no_hp']);
    $tahun_ajaran = sanitize($_POST['tahun_ajaran']);

    // Validation
    if (empty($nomor_peserta) || empty($nama_siswa) || empty($jk) || empty($tempat_lahir) || empty($tanggal_lahir) || empty($alamat) || empty($asal_sekolah) || empty($no_hp) || empty($tahun_ajaran)) {
        set_flash_message('error', 'Semua field wajib diisi!');
    } else {
        try {
            // Check if nomor_peserta is already registered
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nomor_peserta = ?");
            $check_stmt->execute([$nomor_peserta]);
            if ($check_stmt->fetchColumn() > 0) {
                set_flash_message('error', 'Nomor peserta sudah terdaftar!');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO siswa (nomor_peserta, nama_siswa, jk, tempat_lahir, tanggal_lahir, alamat, asal_sekolah, no_hp, tahun_ajaran)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nomor_peserta, $nama_siswa, $jk, $tempat_lahir, $tanggal_lahir, $alamat, $asal_sekolah, $no_hp, $tahun_ajaran]);
                
                set_flash_message('success', 'Data siswa berhasil ditambahkan!');
                redirect('siswa/index.php');
            }
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }
}

// Generate automatic registration number for ease of use
$curr_year = date('Y');
try {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM siswa");
    $next_id = $count_stmt->fetchColumn() + 1;
    $auto_reg = "REG-" . $curr_year . "-" . str_pad($next_id, 3, '0', STR_PAD_LEFT);
} catch (PDOException $e) {
    $auto_reg = "REG-" . $curr_year . "-001";
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-user-plus text-primary me-2"></i> Tambah Siswa</h2>
            <p class="text-muted m-0">Tambah biodata calon siswa baru</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('siswa/index.php'); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left me-2"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card card-custom">
        <div class="card-header-custom">
            <span class="fs-5 text-dark fw-bold">Form Biodata Calon Siswa</span>
        </div>
        <div class="card-body p-4">
            <form action="<?= base_url('siswa/tambah.php'); ?>" method="POST">
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nomor_peserta" class="form-label fw-semibold">Nomor Peserta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nomor_peserta" name="nomor_peserta" value="<?= $auto_reg; ?>" required>
                            <small class="text-muted">Format rekomendasi: REG-TAHUN-NO_URUT</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nama_siswa" class="form-label fw-semibold">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_siswa" name="nama_siswa" placeholder="Masukkan nama lengkap siswa" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block">Jenis Kelamin <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="jk" id="jk_l" value="L" checked required>
                                <label class="form-check-input-label" for="jk_l">Laki-laki</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="jk" id="jk_p" value="P" required>
                                <label class="form-check-input-label" for="jk_p">Perempuan</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tempat_lahir" class="form-label fw-semibold">Tempat Lahir <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" placeholder="Kota lahir" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tanggal_lahir" class="form-label fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" required>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="asal_sekolah" class="form-label fw-semibold">Asal Sekolah (SMP/MTs) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="asal_sekolah" name="asal_sekolah" placeholder="Nama sekolah asal" required>
                        </div>

                        <div class="mb-3">
                            <label for="no_hp" class="form-label fw-semibold">No HP/WhatsApp <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="no_hp" name="no_hp" placeholder="Contoh: 08123456789" required>
                        </div>

                        <div class="mb-3">
                            <label for="tahun_ajaran" class="form-label fw-semibold">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select class="form-select" id="tahun_ajaran" name="tahun_ajaran" required>
                                <?php
                                $curr_yr = (int)date('Y');
                                for ($i = 0; $i < 3; $i++) {
                                    $val = ($curr_yr + $i) . '/' . ($curr_yr + $i + 1);
                                    echo "<option value='{$val}'>{$val}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="alamat" class="form-label fw-semibold">Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" placeholder="Masukkan alamat lengkap rumah" required></textarea>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="text-end">
                    <button type="reset" class="btn btn-light me-2">Reset</button>
                    <button type="submit" class="btn btn-primary-custom">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Data Siswa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
