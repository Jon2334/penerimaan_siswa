<?php
// fuzzy/kuota.php - Quota Management by Academic Year
require_once __DIR__ . '/../config/config.php';
check_login();

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

// 1. ADD / EDIT QUOTA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $tahun_ajaran = sanitize($_POST['tahun_ajaran']);
    $jumlah_kuota = (int)$_POST['jumlah_kuota'];

    if (empty($tahun_ajaran) || $jumlah_kuota <= 0) {
        set_flash_message('error', 'Semua inputan wajib diisi dengan benar!');
    } else {
        try {
            if ($action === 'add') {
                // Check if exists
                $chk = $pdo->prepare("SELECT COUNT(*) FROM kuota WHERE tahun_ajaran = ?");
                $chk->execute([$tahun_ajaran]);
                if ($chk->fetchColumn() > 0) {
                    set_flash_message('error', "Kuota untuk tahun ajaran {$tahun_ajaran} sudah didefinisikan!");
                } else {
                    $ins = $pdo->prepare("INSERT INTO kuota (tahun_ajaran, jumlah_kuota) VALUES (?, ?)");
                    $ins->execute([$tahun_ajaran, $jumlah_kuota]);
                    set_flash_message('success', 'Kuota berhasil ditambahkan!');
                }
            } else {
                $id = (int)$_GET['id'];
                $upd = $pdo->prepare("UPDATE kuota SET tahun_ajaran = ?, jumlah_kuota = ? WHERE id = ?");
                $upd->execute([$tahun_ajaran, $jumlah_kuota, $id]);
                set_flash_message('success', 'Kuota berhasil diperbarui!');
            }
        } catch (PDOException $e) {
            set_flash_message('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    redirect('fuzzy/kuota.php');
}

// 2. DELETE QUOTA
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $del = $pdo->prepare("DELETE FROM kuota WHERE id = ?");
        $del->execute([$id]);
        set_flash_message('success', 'Konfigurasi kuota berhasil dihapus!');
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal menghapus kuota: ' . $e->getMessage());
    }
    redirect('fuzzy/kuota.php');
}

// Fetch single quota for editing
$edit_quota = null;
if ($action === 'edit-form' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM kuota WHERE id = ?");
    $stmt->execute([$id]);
    $edit_quota = $stmt->fetch();
}

// Fetch all quotas
try {
    $quotas_stmt = $pdo->query("SELECT * FROM kuota ORDER BY tahun_ajaran DESC");
    $quotas_list = $quotas_stmt->fetchAll();
} catch (PDOException $e) {
    $quotas_list = [];
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-sliders text-primary me-2"></i> Kuota Penerimaan</h2>
            <p class="text-muted m-0">Atur batasan jumlah pendaftar yang diterima (Lulus) per tahun ajaran</p>
        </div>
    </div>

    <div class="row">
        <!-- Form Col -->
        <div class="col-lg-4 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <span class="fs-5 text-dark fw-bold"><?= $edit_quota ? 'Edit Kuota Penerimaan' : 'Tambah Kuota Baru'; ?></span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('fuzzy/kuota.php?action=' . ($edit_quota ? 'edit&id=' . $edit_quota['id'] : 'add')); ?>" method="POST">
                        <div class="mb-3">
                            <label for="tahun_ajaran" class="form-label fw-semibold">Tahun Ajaran</label>
                            <select class="form-select" id="tahun_ajaran" name="tahun_ajaran" required>
                                <?php
                                $curr_yr = (int)date('Y') - 1;
                                for ($i = 0; $i < 5; $i++) {
                                    $val = ($curr_yr + $i) . '/' . ($curr_yr + $i + 1);
                                    $selected = ($edit_quota && $edit_quota['tahun_ajaran'] === $val) ? 'selected' : '';
                                    echo "<option value='{$val}' {$selected}>{$val}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="jumlah_kuota" class="form-label fw-semibold">Jumlah Kuota (Diterima)</label>
                            <div class="input-group">
                                <input type="number" min="1" class="form-control" id="jumlah_kuota" name="jumlah_kuota" 
                                       placeholder="Contoh: 10" value="<?= $edit_quota ? $edit_quota['jumlah_kuota'] : ''; ?>" required>
                                <span class="input-group-text">Siswa</span>
                            </div>
                            <div class="form-text text-muted">Siswa dengan peringkat di luar batas ini akan dinyatakan "Tidak Lulus".</div>
                        </div>

                        <div class="d-flex gap-2">
                            <?php if ($edit_quota): ?>
                                <a href="<?= base_url('fuzzy/kuota.php'); ?>" class="btn btn-light w-50">Batal</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary-custom <?= $edit_quota ? 'w-50' : 'w-100'; ?>">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- List Table Col -->
        <div class="col-lg-8 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center bg-white">
                    <span class="fs-5 text-dark fw-bold">Konfigurasi Kuota</span>
                    <span class="badge bg-primary px-3 py-2"><?= count($quotas_list); ?> Tahun Terdaftar</span>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th width="10%">No</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Kapasitas Penerimaan (Kuota)</th>
                                    <th width="20%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($quotas_list as $row): 
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><strong class="text-primary"><?= sanitize($row['tahun_ajaran']); ?></strong></td>
                                    <td><span class="badge bg-success fs-7 px-3 py-2"><?= $row['jumlah_kuota']; ?> Calon Siswa</span></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="<?= base_url('fuzzy/kuota.php?action=edit-form&id=' . $row['id']); ?>" class="btn btn-sm btn-primary-custom" title="Edit Kuota">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger btn-delete-kuota" data-id="<?= $row['id']; ?>" data-name="<?= sanitize($row['tahun_ajaran']); ?>" title="Hapus Kuota">
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
    </div>
</div>

<script>
    $(document).ready(function() {
        $(document).on('click', '.btn-delete-kuota', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Hapus Kuota Tahun Ajaran?',
                text: `Konfigurasi kuota untuk tahun "${name}" akan dihapus permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `<?= base_url('fuzzy/kuota.php?action=delete&id='); ?>${id}`;
                }
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
