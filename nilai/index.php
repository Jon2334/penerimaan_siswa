<?php
// nilai/index.php - Student Grades Listing
require_once __DIR__ . '/../layout/header.php';

try {
    $stmt = $pdo->query("
        SELECT s.id_siswa, s.nomor_peserta, s.nama_siswa, s.tahun_ajaran,
               n.nilai_uan, n.nilai_raport, n.tes_kompetensi, n.id_nilai
        FROM siswa s 
        LEFT JOIN nilai_siswa n ON s.id_siswa = n.id_siswa 
        ORDER BY s.id_siswa DESC
    ");
    $grades_list = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Kesalahan mengambil data: " . $e->getMessage() . "</div>";
    $grades_list = [];
}
?>

<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-file-invoice text-primary me-2"></i> Input & Kelola Nilai</h2>
            <p class="text-muted m-0">Masukkan dan sunting nilai kriteria seleksi calon siswa</p>
        </div>
    </div>

    <!-- Info Box -->
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-3 mb-4">
        <i class="fa-solid fa-circle-info fs-4 text-info"></i>
        <div>
            <strong>Petunjuk:</strong> Nilai kriteria ini akan diproses menggunakan metode Fuzzy Mamdani untuk menentukan kelayakan kelulusan pendaftar. Pastikan semua siswa telah diinput nilainya sebelum melakukan proses seleksi.
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <span class="fs-5 text-dark fw-bold">Daftar Nilai Siswa</span>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover table-striped datatable align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">No</th>
                            <th>No Peserta</th>
                            <th>Nama Siswa</th>
                            <th>UAN (40)</th>
                            <th>Raport (100)</th>
                            <th>Kompetensi (100)</th>
                            <th width="12%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($grades_list as $row): 
                            $has_grades = !is_null($row['id_nilai']);
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><span class="fw-semibold text-primary"><?= sanitize($row['nomor_peserta']); ?></span></td>
                            <td><?= sanitize($row['nama_siswa']); ?></td>
                            
                            <!-- Grades columns -->
                            <td><?= $has_grades ? number_format($row['nilai_uan'], 1) : '<span class="text-muted">-</span>'; ?></td>
                            <td><?= $has_grades ? number_format($row['nilai_raport'], 1) : '<span class="text-muted">-</span>'; ?></td>
                            <td><?= $has_grades ? number_format($row['tes_kompetensi'], 1) : '<span class="text-muted">-</span>'; ?></td>
                            
                            <td>
                                <div class="text-center">
                                    <?php if ($has_grades): ?>
                                        <a href="<?= base_url('nilai/input.php?id=' . $row['id_siswa']); ?>" class="btn btn-sm btn-primary-custom" title="Edit Nilai">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url('nilai/input.php?id=' . $row['id_siswa']); ?>" class="btn btn-sm btn-success text-white" title="Input Nilai">
                                            <i class="fa-solid fa-plus me-1"></i> Input
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
