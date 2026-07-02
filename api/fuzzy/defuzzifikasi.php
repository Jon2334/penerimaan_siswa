<?php
// fuzzy/defuzzifikasi.php - Defuzzification COG Step Viewer
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

    $rules_stmt = $pdo->query("SELECT * FROM rule_fuzzy");
    $rules = $rules_stmt->fetchAll();
} catch (PDOException $e) {
    set_flash_message('error', 'Kesalahan database: ' . $e->getMessage());
    redirect('fuzzy/hasil.php');
}

// Compute fuzzification & aggregation
$inputs = [
    'uan' => $data['nilai_uan'],
    'raport' => $data['nilai_raport'],
    'kompetensi' => $data['tes_kompetensi']
];

$alpha_outputs = FuzzyMamdani::inferensi($inputs, $rules);
$a_lulus = $alpha_outputs['Lulus'];
$a_tidak_lulus = $alpha_outputs['Tidak Lulus'];

// Discretize & store values for the COG table
$discretization = [];
$numerator = 0.0;
$denominator = 0.0;

for ($z = 0; $z <= 100; $z++) {
    $mu_z_tl = FuzzyMamdani::mu_tidak_lulus($z);
    $mu_z_l = FuzzyMamdani::mu_lulus($z);

    $val_tl = min($a_tidak_lulus, $mu_z_tl);
    $val_l = min($a_lulus, $mu_z_l);
    $mu_agregasi = max($val_tl, $val_l);

    $numerator += $z * $mu_agregasi;
    $denominator += $mu_agregasi;

    // Save only subset of points to table for UX, e.g. every 5 steps + boundary checks
    if ($z % 5 == 0 || $mu_agregasi > 0) {
        $discretization[] = [
            'z' => $z,
            'mu_tidak_lulus' => $mu_z_tl,
            'mu_lulus' => $mu_z_l,
            'cutoff_tidak_lulus' => $val_tl,
            'cutoff_lulus' => $val_l,
            'mu_agregasi' => $mu_agregasi,
            'z_times_mu' => $z * $mu_agregasi
        ];
    }
}

$cog_result = ($denominator == 0) ? 50.0 : ($numerator / $denominator);

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-chart-line text-primary me-2"></i> Langkah 3: Defuzzifikasi</h2>
            <p class="text-muted m-0">Menghitung nilai tegas akhir (crisp value) dengan metode Centroid / COG</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('fuzzy/inferensi.php?id=' . $id_siswa); ?>" class="btn btn-secondary me-2">
                <i class="fa-solid fa-arrow-left me-2"></i> Inferensi
            </a>
            <a href="<?= base_url('fuzzy/hasil.php'); ?>" class="btn btn-primary-custom">
                Lihat Hasil Seleksi <i class="fa-solid fa-check ms-2"></i>
            </a>
        </div>
    </div>

    <!-- Student Header Summary -->
    <div class="card card-custom mb-4 bg-light border-0">
        <div class="card-body p-3">
            <div class="row text-center text-md-start">
                <div class="col-md-4"><strong>Siswa:</strong> <?= sanitize($data['nama_siswa']); ?></div>
                <div class="col-md-4"><strong>No Peserta:</strong> <?= sanitize($data['nomor_peserta']); ?></div>
                <div class="col-md-4"><strong>Tahun Ajaran:</strong> <?= sanitize($data['tahun_ajaran']); ?></div>
            </div>
        </div>
    </div>

    <!-- Final Score Display Widget -->
    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card card-custom h-100 bg-grad-primary text-white">
                <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                    <h5 class="fw-bold mb-2">Nilai Akhir Fuzzy (Z*)</h5>
                    <div class="display-3 fw-bold my-3"><?= number_format($cog_result, 4); ?></div>
                    <span class="fs-5 badge bg-white text-primary align-self-center px-4 py-2 mt-2">
                        <?= ($cog_result >= 60) ? 'Potensi Diterima' : 'Kurang Layak'; ?>
                    </span>
                    <small class="mt-4 text-white-50">Nilai akhir diperoleh dari pembagian total momen dengan total luas area.</small>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom">
                    <span class="fs-5 text-dark fw-bold">Formula Center of Gravity (COG)</span>
                </div>
                <div class="card-body p-4">
                    <div class="bg-light p-3 rounded mb-4 text-center font-monospace fs-5">
                        Z* = &Sigma; (z &times; &mu;<sub>Agregasi</sub>(z)) / &Sigma; &mu;<sub>Agregasi</sub>(z)
                    </div>
                    
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <div class="p-3 border rounded">
                                <span class="text-muted d-block small">Pembilang (&Sigma; z &times; &mu;<sub>Agregasi</sub>)</span>
                                <span class="fs-4 fw-bold text-dark"><?= number_format($numerator, 4); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="p-3 border rounded">
                                <span class="text-muted d-block small">Penyebut (&Sigma; &mu;<sub>Agregasi</sub>)</span>
                                <span class="fs-4 fw-bold text-dark"><?= number_format($denominator, 4); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fa-solid fa-lightbulb me-2"></i>
                        Interval output kelulusan didefinisikan pada rentang <strong>0 s.d 100</strong>. Daerah diintegrasikan secara numerik dengan langkah interval diskrit 1.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Discretization Table -->
    <div class="card card-custom">
        <div class="card-header-custom bg-white">
            <span class="fw-bold text-dark">Detail Integrasi Diskrit (Sampel Nilai z)</span>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>Titik z</th>
                            <th>&mu; Tidak Lulus(z)</th>
                            <th>&mu; Lulus(z)</th>
                            <th>min(&alpha;<sub>TL</sub>, &mu;<sub>TL</sub>)</th>
                            <th>min(&alpha;<sub>L</sub>, &mu;<sub>L</sub>)</th>
                            <th>&mu; Agregasi (MAX)</th>
                            <th class="text-end">Momen (z &times; &mu;<sub>Agregasi</sub>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($discretization as $point): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= $point['z']; ?></span></td>
                                <td><?= number_format($point['mu_tidak_lulus'], 3); ?></td>
                                <td><?= number_format($point['mu_lulus'], 3); ?></td>
                                <td>min(<?= number_format($a_tidak_lulus, 3); ?>, <?= number_format($point['mu_tidak_lulus'], 3); ?>) = <?= number_format($point['cutoff_tidak_lulus'], 3); ?></td>
                                <td>min(<?= number_format($a_lulus, 3); ?>, <?= number_format($point['mu_lulus'], 3); ?>) = <?= number_format($point['cutoff_lulus'], 3); ?></td>
                                <td>
                                    <?php if ($point['mu_agregasi'] > 0): ?>
                                        <strong class="text-primary"><?= number_format($point['mu_agregasi'], 4); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">0.0000</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold"><?= number_format($point['z_times_mu'], 4); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
