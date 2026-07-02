<?php
// fuzzy/inferensi.php - Rule Inference Step Viewer (Simplified)
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

    // Fetch rules
    $rules_stmt = $pdo->query("SELECT * FROM rule_fuzzy");
    $rules = $rules_stmt->fetchAll();
} catch (PDOException $e) {
    set_flash_message('error', 'Kesalahan database: ' . $e->getMessage());
    redirect('fuzzy/hasil.php');
}

// Compute fuzzified memberships
$mu_uan = FuzzyMamdani::uan($data['nilai_uan']);
$mu_raport = FuzzyMamdani::raport($data['nilai_raport']);
$mu_kompetensi = FuzzyMamdani::kompetensi($data['tes_kompetensi']);

// Evaluate rules in detail
$fired_rules = [];
$alpha_lulus = [];
$alpha_tidak_lulus = [];

foreach ($rules as $rule) {
    $v_uan = $mu_uan[$rule['uan']] ?? 0.0;
    $v_rap = $mu_raport[$rule['raport']] ?? 0.0;
    $v_kom = $mu_kompetensi[$rule['kompetensi']] ?? 0.0;

    // operator AND is MIN
    $alpha = min($v_uan, $v_rap, $v_kom);

    if ($alpha > 0) {
        $fired_rules[] = [
            'id_rule' => $rule['id_rule'],
            'uan' => $rule['uan'], 'mu_uan' => $v_uan,
            'raport' => $rule['raport'], 'mu_rap' => $v_rap,
            'kompetensi' => $rule['kompetensi'], 'mu_kom' => $v_kom,
            'hasil' => $rule['hasil'],
            'alpha' => $alpha
        ];

        if ($rule['hasil'] === 'Lulus') {
            $alpha_lulus[] = $alpha;
        } else {
            $alpha_tidak_lulus[] = $alpha;
        }
    }
}

// MAX Aggregation
$max_lulus = !empty($alpha_lulus) ? max($alpha_lulus) : 0.0;
$max_tidak_lulus = !empty($alpha_tidak_lulus) ? max($alpha_tidak_lulus) : 0.0;

require_once __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-code-fork text-primary me-2"></i> Langkah 2: Inferensi & Agregasi</h2>
            <p class="text-muted m-0">Evaluasi aturan fuzzy dengan implikasi MIN dan agregasi MAX</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <a href="<?= base_url('fuzzy/fuzzifikasi.php?id=' . $id_siswa); ?>" class="btn btn-secondary me-2">
                <i class="fa-solid fa-arrow-left me-2"></i> Fuzzifikasi
            </a>
            <a href="<?= base_url('fuzzy/defuzzifikasi.php?id=' . $id_siswa); ?>" class="btn btn-primary-custom">
                Lanjut ke Defuzzifikasi <i class="fa-solid fa-arrow-right ms-2"></i>
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

    <!-- Aggregation Results Card -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card card-custom bg-grad-success text-white h-100">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-2">Nilai Agregasi Maksimum (Lulus)</h5>
                    <div class="display-4 fw-bold">&alpha;<sub>Lulus</sub> = <?= number_format($max_lulus, 4); ?></div>
                    <p class="mt-2 mb-0 small">Diambil dari nilai keanggotaan minimum terbesar dari semua aturan yang menghasilkan "Lulus".</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-custom bg-grad-danger text-white h-100">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-2">Nilai Agregasi Maksimum (Tidak Lulus)</h5>
                    <div class="display-4 fw-bold">&alpha;<sub>Tidak Lulus</sub> = <?= number_format($max_tidak_lulus, 4); ?></div>
                    <p class="mt-2 mb-0 small">Diambil dari nilai keanggotaan minimum terbesar dari semua aturan yang menghasilkan "Tidak Lulus".</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rules Fired Table -->
    <div class="card card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <span class="fs-5 text-dark fw-bold">Aturan yang Aktif (&alpha; > 0)</span>
            <span class="badge bg-success px-3 py-2"><?= count($fired_rules); ?> Aturan Aktif</span>
        </div>
        <div class="card-body p-4">
            <?php if (empty($fired_rules)): ?>
                <div class="alert alert-warning mb-0">Tidak ada aturan yang aktif untuk nilai siswa ini.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th width="8%">ID Rule</th>
                                <th>Premis (Kombinasi Kondisi Input)</th>
                                <th width="15%">Konsekuen (Output)</th>
                                <th width="15%" class="text-end">Nilai Implikasi (&alpha;)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fired_rules as $f_rule): ?>
                                <tr>
                                    <td><span class="fw-bold">#<?= $f_rule['id_rule']; ?></span></td>
                                    <td>
                                        <small class="d-block">
                                            IF UAN = <strong><?= $f_rule['uan']; ?></strong> (<?= $f_rule['mu_uan']; ?>) <br>
                                            AND Raport = <strong><?= $f_rule['raport']; ?></strong> (<?= $f_rule['mu_rap']; ?>) <br>
                                            AND Kompetensi = <strong><?= $f_rule['kompetensi']; ?></strong> (<?= $f_rule['mu_kom']; ?>)
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($f_rule['hasil'] === 'Lulus'): ?>
                                            <span class="badge bg-success">THEN Lulus</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">THEN Tidak Lulus</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary fs-7">
                                            min(<?= implode(', ', [
                                                $f_rule['mu_uan'], $f_rule['mu_rap'], $f_rule['mu_kom']
                                            ]); ?>) = <strong><?= number_format($f_rule['alpha'], 4); ?></strong>
                                        </span>
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

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
