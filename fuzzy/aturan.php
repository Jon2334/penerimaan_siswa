<?php
// fuzzy/aturan.php - Fuzzy Rules CRUD & Generator (Simplified to 3 Criteria)
require_once __DIR__ . '/../config/config.php';
check_login();

$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

// 1. GENERATE RULES AUTOMATICALLY
if ($action === 'generate') {
    try {
        // Clear existing rules and reset the auto-increment counter to 1
        $pdo->exec("TRUNCATE TABLE rule_fuzzy");

        // Define sets
        $uan_sets = ['Rendah', 'Sedang', 'Tinggi'];
        $raport_sets = ['Rendah', 'Sedang', 'Tinggi'];
        $kompetensi_sets = ['Rendah', 'Sedang', 'Tinggi'];

        // Prepare insert statement
        $ins_stmt = $pdo->prepare("
            INSERT INTO rule_fuzzy (uan, raport, kompetensi, hasil)
            VALUES (?, ?, ?, ?)
        ");

        $count = 0;
        foreach ($uan_sets as $uan) {
            $p_uan = ($uan === 'Rendah') ? 1 : (($uan === 'Sedang') ? 2 : 3);
            
            foreach ($raport_sets as $raport) {
                $p_rap = ($raport === 'Rendah') ? 1 : (($raport === 'Sedang') ? 2 : 3);
                
                foreach ($kompetensi_sets as $komp) {
                    $p_kom = ($komp === 'Rendah') ? 1 : (($komp === 'Sedang') ? 2 : 3);

                    // Total point calculation (Min: 3, Max: 9)
                    // If sum of points >= 6 (average), then "Lulus", else "Tidak Lulus"
                    $total_points = $p_uan + $p_rap + $p_kom;
                    $hasil = ($total_points >= 6) ? 'Lulus' : 'Tidak Lulus';

                    $ins_stmt->execute([$uan, $raport, $komp, $hasil]);
                    $count++;
                }
            }
        }

        set_flash_message('success', "Berhasil men-generate {$count} aturan fuzzy otomatis!");
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal men-generate aturan: ' . $e->getMessage());
    }
    redirect('fuzzy/aturan.php');
}

// 2. DELETE SINGLE RULE
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM rule_fuzzy WHERE id_rule = ?");
        $stmt->execute([$id]);
        set_flash_message('success', 'Aturan berhasil dihapus!');
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal menghapus aturan: ' . $e->getMessage());
    }
    redirect('fuzzy/aturan.php');
}

// 3. ADD SINGLE RULE (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $uan = sanitize($_POST['uan']);
    $raport = sanitize($_POST['raport']);
    $komp = sanitize($_POST['kompetensi']);
    $hasil = sanitize($_POST['hasil']);

    if (empty($uan) || empty($raport) || empty($komp) || empty($hasil)) {
        set_flash_message('error', 'Semua parameter kriteria wajib ditentukan!');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO rule_fuzzy (uan, raport, kompetensi, hasil)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$uan, $raport, $komp, $hasil]);
            set_flash_message('success', 'Aturan baru berhasil ditambahkan!');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menambahkan aturan: ' . $e->getMessage());
        }
    }
    redirect('fuzzy/aturan.php');
}

// 4. EDIT SINGLE RULE (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $uan = sanitize($_POST['uan']);
    $raport = sanitize($_POST['raport']);
    $komp = sanitize($_POST['kompetensi']);
    $hasil = sanitize($_POST['hasil']);

    try {
        $stmt = $pdo->prepare("
            UPDATE rule_fuzzy 
            SET uan = ?, raport = ?, kompetensi = ?, hasil = ?
            WHERE id_rule = ?
        ");
        $stmt->execute([$uan, $raport, $komp, $hasil, $id]);
        set_flash_message('success', 'Aturan berhasil diperbarui!');
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal memperbarui aturan: ' . $e->getMessage());
    }
    redirect('fuzzy/aturan.php');
}

// Fetch single rule for editing
$edit_rule = null;
if ($action === 'edit-form' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM rule_fuzzy WHERE id_rule = ?");
    $stmt->execute([$id]);
    $edit_rule = $stmt->fetch();
}

// Fetch all rules for listing
try {
    $rules_stmt = $pdo->query("SELECT * FROM rule_fuzzy ORDER BY id_rule ASC");
    $rules_list = $rules_stmt->fetchAll();
} catch (PDOException $e) {
    $rules_list = [];
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-scale-balanced text-primary me-2"></i> Aturan Fuzzy (Rule Base)</h2>
            <p class="text-muted m-0">Kelola model pengetahuan logika inferensi Fuzzy Mamdani</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('fuzzy/aturan.php?action=generate'); ?>" class="btn btn-outline-primary" id="btn-generate">
                <i class="fa-solid fa-wand-magic-sparkles me-2"></i> Generate Rule Otomatis (27 Rules)
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Input Form Col -->
        <div class="col-lg-4 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <span class="fs-5 text-dark fw-bold"><?= $edit_rule ? 'Edit Aturan Fuzzy' : 'Tambah Aturan Baru'; ?></span>
                </div>
                <div class="card-body p-4">
                    <form action="<?= base_url('fuzzy/aturan.php?action=' . ($edit_rule ? 'edit&id=' . $edit_rule['id_rule'] : 'add')); ?>" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">UAN</label>
                            <select class="form-select" name="uan" required>
                                <option value="Rendah" <?= ($edit_rule && $edit_rule['uan'] === 'Rendah') ? 'selected' : ''; ?>>Rendah</option>
                                <option value="Sedang" <?= ($edit_rule && $edit_rule['uan'] === 'Sedang') ? 'selected' : ''; ?>>Sedang</option>
                                <option value="Tinggi" <?= ($edit_rule && $edit_rule['uan'] === 'Tinggi') ? 'selected' : ''; ?>>Tinggi</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Raport</label>
                            <select class="form-select" name="raport" required>
                                <option value="Rendah" <?= ($edit_rule && $edit_rule['raport'] === 'Rendah') ? 'selected' : ''; ?>>Rendah</option>
                                <option value="Sedang" <?= ($edit_rule && $edit_rule['raport'] === 'Sedang') ? 'selected' : ''; ?>>Sedang</option>
                                <option value="Tinggi" <?= ($edit_rule && $edit_rule['raport'] === 'Tinggi') ? 'selected' : ''; ?>>Tinggi</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tes Kompetensi</label>
                            <select class="form-select" name="kompetensi" required>
                                <option value="Rendah" <?= ($edit_rule && $edit_rule['kompetensi'] === 'Rendah') ? 'selected' : ''; ?>>Rendah</option>
                                <option value="Sedang" <?= ($edit_rule && $edit_rule['kompetensi'] === 'Sedang') ? 'selected' : ''; ?>>Sedang</option>
                                <option value="Tinggi" <?= ($edit_rule && $edit_rule['kompetensi'] === 'Tinggi') ? 'selected' : ''; ?>>Tinggi</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-primary">THEN (Output Kelulusan)</label>
                            <select class="form-select border-primary" name="hasil" required>
                                <option value="Lulus" <?= ($edit_rule && $edit_rule['hasil'] === 'Lulus') ? 'selected' : ''; ?>>Lulus</option>
                                <option value="Tidak Lulus" <?= ($edit_rule && $edit_rule['hasil'] === 'Tidak Lulus') ? 'selected' : ''; ?>>Tidak Lulus</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <?php if ($edit_rule): ?>
                                <a href="<?= base_url('fuzzy/aturan.php'); ?>" class="btn btn-light w-50">Batal</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary-custom <?= $edit_rule ? 'w-50' : 'w-100'; ?>">
                                <i class="fa-solid fa-floppy-disk me-2"></i> Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rules List Table Col -->
        <div class="col-lg-8 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom d-flex justify-content-between align-items-center bg-white">
                    <span class="fs-5 text-dark fw-bold">Daftar Basis Aturan (IF-THEN)</span>
                    <span class="badge bg-primary px-3 py-2"><?= count($rules_list); ?> Aturan</span>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped datatable align-middle w-100">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Kombinasi Premis (IF)</th>
                                    <th width="25%">Hasil (THEN)</th>
                                    <th width="15%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($rules_list as $row): 
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <small>
                                            UAN: <strong><?= $row['uan']; ?></strong>, 
                                            Raport: <strong><?= $row['raport']; ?></strong>, 
                                            Komp: <strong><?= $row['kompetensi']; ?></strong>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($row['hasil'] === 'Lulus'): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> Lulus</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="fa-solid fa-xmark me-1"></i> Tidak Lulus</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="<?= base_url('fuzzy/aturan.php?action=edit-form&id=' . $row['id_rule']); ?>" class="btn btn-sm btn-primary-custom" title="Edit Rule">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger btn-delete-rule" data-id="<?= $row['id_rule']; ?>" title="Hapus Rule">
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
        // Confirmation for auto generate rules
        $('#btn-generate').on('click', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            
            Swal.fire({
                title: 'Generate Aturan Fuzzy?',
                text: 'Proses ini akan mengosongkan dan men-generate ulang 27 kombinasi aturan secara otomatis!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Generate!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Harap tunggu sebentar.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    window.location.href = href;
                }
            });
        });

        // Delete confirmation
        $(document).on('click', '.btn-delete-rule', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Hapus Aturan?',
                text: 'Aturan ini akan dihapus dari sistem!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `<?= base_url('fuzzy/aturan.php?action=delete&id='); ?>${id}`;
                }
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
