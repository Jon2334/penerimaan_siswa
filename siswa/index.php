<?php
// siswa/index.php - Student Management Listing
require_once __DIR__ . '/../layout/header.php';

try {
    // Select all students and check if grades have been entered
    $stmt = $pdo->query("
        SELECT s.*, n.id_nilai 
        FROM siswa s 
        LEFT JOIN nilai_siswa n ON s.id_siswa = n.id_siswa 
        ORDER BY s.id_siswa DESC
    ");
    $siswa_list = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Kesalahan mengambil data: " . $e->getMessage() . "</div>";
    $siswa_list = [];
}
?>

<div class="container-fluid">
    <!-- Breadcrumb & Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-users text-primary me-2"></i> Manajemen Siswa</h2>
            <p class="text-muted m-0">Kelola data calon siswa baru yang mendaftar</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('siswa/tambah.php'); ?>" class="btn btn-primary-custom">
                <i class="fa-solid fa-plus me-2"></i> Tambah Siswa Baru
            </a>
            <button class="btn btn-success text-white ms-2" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fa-solid fa-file-import me-2"></i> Import CSV
            </button>
            <a href="<?= base_url('laporan/cetak_siswa.php'); ?>" target="_blank" class="btn btn-outline-danger ms-2">
                <i class="fa-solid fa-file-pdf me-2"></i> Cetak PDF
            </a>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <span class="fs-5 text-dark fw-bold">Daftar Calon Siswa Baru</span>
            <span class="badge bg-primary px-3 py-2"><?= count($siswa_list); ?> Pendaftar</span>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover table-striped datatable align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">No</th>
                            <th>No Peserta</th>
                            <th>Nama Siswa</th>
                            <th>L/P</th>
                            <th>Asal Sekolah</th>
                            <th>Tahun Ajaran</th>
                            <th>Status Nilai</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($siswa_list as $row): 
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><span class="fw-semibold text-primary"><?= sanitize($row['nomor_peserta']); ?></span></td>
                            <td><?= sanitize($row['nama_siswa']); ?></td>
                            <td>
                                <?php if ($row['jk'] === 'L'): ?>
                                    <span class="badge bg-info-subtle text-info"><i class="fa-solid fa-mars me-1"></i> L</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger"><i class="fa-solid fa-venus me-1"></i> P</span>
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($row['asal_sekolah']); ?></td>
                            <td><?= sanitize($row['tahun_ajaran']); ?></td>
                            <td>
                                <?php if ($row['id_nilai']): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check me-1"></i> Sudah Diinput</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> Belum Diinput</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <?php if ($row['id_nilai']): ?>
                                        <a href="<?= base_url('nilai/input.php?id=' . $row['id_siswa']); ?>" class="btn btn-sm btn-outline-success" title="Edit Nilai">
                                            <i class="fa-solid fa-file-invoice"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url('nilai/input.php?id=' . $row['id_siswa']); ?>" class="btn btn-sm btn-success text-white" title="Input Nilai">
                                            <i class="fa-solid fa-file-invoice-dollar"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="<?= base_url('siswa/edit.php?id=' . $row['id_siswa']); ?>" class="btn btn-sm btn-primary-custom" title="Edit Biodata">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    
                                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $row['id_siswa']; ?>" data-name="<?= sanitize($row['nama_siswa']); ?>" title="Hapus Siswa">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
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

<script>
    // Delete Confirmation using SweetAlert2
    $(document).ready(function() {
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: `Biodata dan nilai siswa "${name}" akan dihapus permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `<?= base_url('siswa/hapus.php?id='); ?>${id}`;
                }
            });
        });
    });
</script>

<!-- Modal Import CSV -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="importModalLabel"><i class="fa-solid fa-file-import text-success me-2"></i> Import Calon Siswa (Massal)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('siswa/import.php'); ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="csv_file" class="form-label fw-bold">Pilih File CSV <span class="text-danger">*</span></label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <div class="form-text mt-2">Pastikan file bertipe <strong>.csv</strong> dan memiliki struktur yang benar.</div>
                    </div>

                    <div class="alert alert-info border-0 shadow-sm mb-4">
                        <h6 class="fw-bold mb-2"><i class="fa-solid fa-circle-info me-2"></i> Petunjuk Format File CSV:</h6>
                        <ul class="mb-0 small ps-3">
                            <li>Kolom wajib: <strong>nomor_peserta</strong> dan <strong>nama_siswa</strong>.</li>
                            <li>Kolom opsional data diri: <strong>jk</strong> (L/P), <strong>tempat_lahir</strong>, <strong>tanggal_lahir</strong> (format YYYY-MM-DD), <strong>alamat</strong>, <strong>asal_sekolah</strong>, <strong>no_hp</strong>, <strong>tahun_ajaran</strong>.</li>
                            <li>Kolom opsional nilai kriteria (akan langsung dimasukkan jika diisi): <strong>nilai_uan</strong>, <strong>nilai_raport</strong>, <strong>tes_kompetensi</strong>.</li>
                            <li>Data dengan <strong>nomor_peserta</strong> yang sudah ada di database akan dilewati secara otomatis.</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                        <span class="small text-muted">Butuh contoh file untuk memulai?</span>
                        <a href="<?= base_url('template_import_siswa.csv'); ?>" class="btn btn-sm btn-outline-secondary" download>
                            <i class="fa-solid fa-download me-1"></i> Unduh Template CSV
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success text-white">
                        <i class="fa-solid fa-upload me-2"></i> Mulai Unggah & Impor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
