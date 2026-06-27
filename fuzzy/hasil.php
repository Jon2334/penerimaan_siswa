<?php
// fuzzy/hasil.php - Selection & Ranking Results Dashboard
require_once __DIR__ . '/../layout/header.php';

// Fetch distinct academic years from students list for filtering
try {
    $years_stmt = $pdo->query("SELECT DISTINCT tahun_ajaran FROM siswa ORDER BY tahun_ajaran DESC");
    $years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $years = [];
}

// Get selected year or default to first available
$filter_year = isset($_GET['tahun_ajaran']) ? sanitize($_GET['tahun_ajaran']) : ($years[0] ?? '');

// Fetch calculation results
$results = [];
if (!empty($filter_year)) {
    try {
        $stmt = $pdo->prepare("
            SELECT h.id_hasil, h.nilai_fuzzy, h.status_kelulusan, h.ranking, h.tanggal_proses,
                   s.id_siswa, s.nomor_peserta, s.nama_siswa, s.jk, s.asal_sekolah, s.tahun_ajaran,
                   n.id_nilai
            FROM hasil_seleksi h
            INNER JOIN siswa s ON h.id_siswa = s.id_siswa
            INNER JOIN nilai_siswa n ON s.id_siswa = n.id_siswa
            WHERE s.tahun_ajaran = ?
            ORDER BY h.ranking ASC, s.nama_siswa ASC
        ");
        $stmt->execute([$filter_year]);
        $results = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Kesalahan mengambil data hasil: " . $e->getMessage() . "</div>";
    }
}

// Count complete grade entry status
try {
    $total_students_stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE tahun_ajaran = ?");
    $total_students_stmt->execute([$filter_year]);
    $total_students = $total_students_stmt->fetchColumn();

    $graded_students_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM siswa s 
        INNER JOIN nilai_siswa n ON s.id_siswa = n.id_siswa 
        WHERE s.tahun_ajaran = ?
    ");
    $graded_students_stmt->execute([$filter_year]);
    $graded_students = $graded_students_stmt->fetchColumn();
    
    // Fetch quota for selected year
    $quota_stmt = $pdo->prepare("SELECT jumlah_kuota FROM kuota WHERE tahun_ajaran = ?");
    $quota_stmt->execute([$filter_year]);
    $quota_val = $quota_stmt->fetchColumn();
    if ($quota_val === false) $quota_val = 0;
} catch (PDOException $e) {
    $total_students = $graded_students = $quota_val = 0;
}
?>

<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-square-poll-vertical text-primary me-2"></i> Hasil Seleksi & Ranking</h2>
            <p class="text-muted m-0">Proses Fuzzy Mamdani, lihat peringkat, dan cetak kelulusan</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <?php if ($graded_students > 0): ?>
                <a href="<?= base_url('fuzzy/proses_fuzzy.php'); ?>" class="btn btn-primary-custom" id="btn-process">
                    <i class="fa-solid fa-calculator me-2"></i> Mulai Proses Perhitungan
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter & Statistics Panel -->
    <div class="row mb-4">
        <!-- Filter Card -->
        <div class="col-lg-4 mb-3">
            <div class="card card-custom h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-filter me-2 text-muted"></i>Filter Tahun Ajaran</h5>
                    <form action="" method="GET">
                        <select class="form-select form-select-lg mb-3" name="tahun_ajaran" onchange="this.form.submit()">
                            <?php if (empty($years)): ?>
                                <option value="">-- Tidak Ada Data Siswa --</option>
                            <?php else: ?>
                                <?php foreach ($years as $yr): ?>
                                    <option value="<?= $yr; ?>" <?= ($filter_year === $yr) ? 'selected' : ''; ?>><?= $yr; ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </form>
                    <small class="text-muted">Pilih tahun ajaran untuk memfilter urutan ranking siswa pendaftar.</small>
                </div>
            </div>
        </div>

        <!-- Progress Card -->
        <div class="col-lg-8 mb-3">
            <div class="card card-custom h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-info me-2 text-muted"></i>Informasi Data Pendaftar (<?= sanitize($filter_year); ?>)</h5>
                    <div class="row text-center">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block small">Total Pendaftar</span>
                                <span class="fs-3 fw-bold text-dark"><?= $total_students; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block small">Sudah Input Nilai</span>
                                <span class="fs-3 fw-bold text-success"><?= $graded_students; ?> / <?= $total_students; ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block small">Kuota Penerimaan</span>
                                <span class="fs-3 fw-bold text-primary"><?= $quota_val; ?> Siswa</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Results Card -->
    <div class="card card-custom">
        <div class="card-header-custom d-flex flex-wrap justify-content-between align-items-center gap-3">
            <span class="fs-5 text-dark fw-bold">Daftar Urutan Peringkat Kelulusan</span>
            
            <?php if (!empty($results)): ?>
                <div class="d-flex gap-2">
                    <a href="<?= base_url('laporan/cetak_seleksi.php?tahun_ajaran=' . urlencode($filter_year)); ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="fa-solid fa-print me-1"></i> Cetak Seleksi
                    </a>
                    <a href="<?= base_url('laporan/cetak_ranking.php?tahun_ajaran=' . urlencode($filter_year)); ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="fa-solid fa-list-ol me-1"></i> Cetak Ranking
                    </a>
                    <a href="<?= base_url('laporan/export_excel.php?tahun_ajaran=' . urlencode($filter_year)); ?>" class="btn btn-sm btn-outline-success">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body p-4">
            <?php if (empty($results)): ?>
                <div class="alert alert-warning text-center mb-0 p-4">
                    <i class="fa-solid fa-circle-exclamation fs-3 mb-2 d-block"></i>
                    Belum ada hasil perhitungan untuk tahun ajaran ini, atau biodata dan nilai siswa belum diinput. 
                    <br>Tekan tombol <strong>"Mulai Proses Perhitungan"</strong> jika data nilai pendaftar sudah lengkap.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle w-100">
                        <thead class="table-dark">
                            <tr>
                                <th width="8%" class="text-center">Peringkat</th>
                                <th>No Peserta</th>
                                <th>Nama Siswa</th>
                                <th>Asal Sekolah</th>
                                <th width="15%">Nilai Akhir Fuzzy (Z*)</th>
                                <th width="12%">Status</th>
                                <th width="20%" class="text-center">Detail Langkah Perhitungan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                            <tr class="<?= ($row['status_kelulusan'] === 'Lulus') ? 'table-success-light' : ''; ?>">
                                <td class="text-center">
                                    <?php if ($row['ranking'] == 1): ?>
                                        <span class="badge bg-warning text-dark p-2 fs-7 shadow-sm"><i class="fa-solid fa-trophy text-danger"></i> 1</span>
                                    <?php elseif ($row['ranking'] == 2): ?>
                                        <span class="badge bg-secondary p-2 fs-7 shadow-sm">2</span>
                                    <?php elseif ($row['ranking'] == 3): ?>
                                        <span class="badge bg-dark-subtle text-dark p-2 fs-7 shadow-sm">3</span>
                                    <?php else: ?>
                                        <span class="fw-bold text-muted"><?= $row['ranking']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="fw-semibold text-primary"><?= sanitize($row['nomor_peserta']); ?></span></td>
                                <td><strong><?= sanitize($row['nama_siswa']); ?></strong></td>
                                <td><?= sanitize($row['asal_sekolah']); ?></td>
                                <td>
                                    <span class="font-monospace fw-bold"><?= number_format($row['nilai_fuzzy'], 4); ?></span>
                                </td>
                                <td>
                                    <?php if ($row['status_kelulusan'] === 'Lulus'): ?>
                                        <span class="badge bg-success-subtle text-success fs-7 px-3 py-2 w-100"><i class="fa-solid fa-check me-1"></i> Diterima</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger fs-7 px-3 py-2 w-100"><i class="fa-solid fa-xmark me-1"></i> Tidak Diterima</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown text-center">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fa-solid fa-gears me-1"></i> Detail Proses
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('fuzzy/fuzzifikasi.php?id=' . $row['id_siswa']); ?>">
                                                    <i class="fa-solid fa-calculator me-2 text-primary"></i> 1. Fuzzifikasi
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('fuzzy/inferensi.php?id=' . $row['id_siswa']); ?>">
                                                    <i class="fa-solid fa-code-fork me-2 text-success"></i> 2. Inferensi & Agregasi
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= base_url('fuzzy/defuzzifikasi.php?id=' . $row['id_siswa']); ?>">
                                                    <i class="fa-solid fa-chart-line me-2 text-danger"></i> 3. Defuzzifikasi (COG)
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#btn-process').on('click', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            
            Swal.fire({
                title: 'Mulai Proses Seleksi?',
                text: 'Sistem akan menghitung derajat keanggotaan, rule inference, dan perangkingan fuzzy untuk pendaftar tahun <?= sanitize($filter_year); ?>.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hitung!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Melakukan Perhitungan...',
                        text: 'Silakan tunggu sebentar, sistem sedang melakukan kalkulasi Mamdani Centroid.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    window.location.href = href;
                }
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
