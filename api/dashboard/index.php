<?php
// dashboard/index.php - Responsive Admin Dashboard Home
require_once __DIR__ . '/../layout/header.php';

// Retrieve Metrics
try {
    // 1. Total pendaftar
    $stmt_pendaftar = $pdo->query("SELECT COUNT(*) FROM siswa");
    $total_pendaftar = $stmt_pendaftar->fetchColumn();

    // 2. Total lulus
    $stmt_lulus = $pdo->query("SELECT COUNT(*) FROM hasil_seleksi WHERE status_kelulusan = 'Lulus'");
    $total_lulus = $stmt_lulus->fetchColumn();

    // 3. Total tidak lulus
    $stmt_tidak_lulus = $pdo->query("SELECT COUNT(*) FROM hasil_seleksi WHERE status_kelulusan = 'Tidak Lulus'");
    $total_tidak_lulus = $stmt_tidak_lulus->fetchColumn();

    // 4. Latest academic year quota
    $stmt_kuota = $pdo->query("SELECT jumlah_kuota FROM kuota ORDER BY tahun_ajaran DESC LIMIT 1");
    $latest_kuota = $stmt_kuota->fetchColumn();
    if ($latest_kuota === false) {
        $latest_kuota = 0;
    }

    // Chart 1: Gender Distribution
    $stmt_gender = $pdo->query("SELECT jk, COUNT(*) as qty FROM siswa GROUP BY jk");
    $genders = $stmt_gender->fetchAll();
    $gender_labels = [];
    $gender_values = [];
    foreach ($genders as $g) {
        $gender_labels[] = $g['jk'] === 'L' ? 'Laki-laki' : 'Perempuan';
        $gender_values[] = $g['qty'];
    }

    // Chart 2: Average Scores of Criteria
    $stmt_avg = $pdo->query("
        SELECT 
            AVG(nilai_uan) as avg_uan, 
            AVG(nilai_raport) as avg_raport, 
            AVG(tes_kompetensi) as avg_kompetensi 
        FROM nilai_siswa
    ");
    $avg_scores = $stmt_avg->fetch();
    $criteria_names = ['UAN SMP', 'Raport Rata-rata', 'Tes Kompetensi'];
    $criteria_avgs = [
        round($avg_scores['avg_uan'] ?? 0, 2),
        round($avg_scores['avg_raport'] ?? 0, 2),
        round($avg_scores['avg_kompetensi'] ?? 0, 2)
    ];

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Kesalahan database: " . $e->getMessage() . "</div>";
    $total_pendaftar = $total_lulus = $total_tidak_lulus = $latest_kuota = 0;
    $gender_labels = $gender_values = $criteria_avgs = [];
}
?>

<div class="container-fluid">
    <!-- Welcome Header -->
    <div class="row align-items-center mb-4">
        <div class="col">
            <h2 class="fw-bold m-0"><i class="fa-solid fa-square-poll-horizontal text-primary me-2"></i> Dashboard Utama</h2>
            <p class="text-muted m-0">Ringkasan sistem pendukung keputusan seleksi penerimaan siswa baru</p>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <!-- Pendaftar -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card bg-grad-primary h-100 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 fw-semibold d-block small mb-1">JUMLAH PENDAFTAR</span>
                    <span class="display-5 fw-bold"><?= $total_pendaftar; ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            </div>
        </div>

        <!-- Lulus / Diterima -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card bg-grad-success h-100 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 fw-semibold d-block small mb-1">SISWA LULUS / DITERIMA</span>
                    <span class="display-5 fw-bold"><?= $total_lulus; ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
            </div>
        </div>

        <!-- Tidak Lulus / Ditolak -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card bg-grad-danger h-100 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 fw-semibold d-block small mb-1">SISWA TIDAK LULUS</span>
                    <span class="display-5 fw-bold"><?= $total_tidak_lulus; ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-user-xmark"></i></div>
            </div>
        </div>

        <!-- Kuota Aktif -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card bg-grad-warning h-100 d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-white-50 fw-semibold d-block small mb-1">KUOTA PENERIMAAN (BARU)</span>
                    <span class="display-5 fw-bold"><?= $latest_kuota; ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-sliders"></i></div>
            </div>
        </div>
    </div>

    <!-- Interactive Analytics Row -->
    <div class="row">
        <!-- Average Scores Radar/Bar Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom bg-white">
                    <span class="fw-bold text-dark"><i class="fa-solid fa-chart-simple me-2 text-muted"></i>Rata-Rata Nilai Kriteria Masuk</span>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="averageScoresChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gender Doughnut Chart -->
        <div class="col-lg-4 mb-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom bg-white">
                    <span class="fw-bold text-dark"><i class="fa-solid fa-chart-pie me-2 text-muted"></i>Rasio Jenis Kelamin</span>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="height: 250px; width: 100%;">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Script Configuration -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Average Criteria Scores Chart (Bar Chart)
        const avgCtx = document.getElementById('averageScoresChart').getContext('2d');
        new Chart(avgCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($criteria_names); ?>,
                datasets: [{
                    label: 'Nilai Rerata',
                    data: <?= json_encode($criteria_avgs); ?>,
                    backgroundColor: [
                        'rgba(79, 70, 229, 0.65)',
                        'rgba(16, 185, 129, 0.65)',
                        'rgba(245, 158, 11, 0.65)'
                    ],
                    borderColor: [
                        'rgb(79, 70, 229)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });

        // Gender Breakdown Doughnut Chart
        const genCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($gender_labels); ?>,
                datasets: [{
                    data: <?= json_encode($gender_values); ?>,
                    backgroundColor: ['#0ea5e9', '#ec4899'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            font: { family: 'Outfit' }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
