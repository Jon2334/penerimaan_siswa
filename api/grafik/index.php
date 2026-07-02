<?php
// api/grafik/index.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../layout/header.php';

// Data Himpunan Fuzzy
// 1. Nilai Akademik
$akademik = [
    'rendah' => ['a' => 0, 'b' => 0, 'c' => 50, 'd' => 70],
    'sedang' => ['a' => 60, 'b' => 75, 'c' => 85, 'd' => 90],
    'tinggi' => ['a' => 80, 'b' => 90, 'c' => 100, 'd' => 100],
];

// 2. Pendapatan Orang Tua (Juta)
$pendapatan = [
    'rendah' => ['a' => 0, 'b' => 0, 'c' => 2, 'd' => 4],
    'sedang' => ['a' => 3, 'b' => 5, 'c' => 7, 'd' => 10],
    'tinggi' => ['a' => 8, 'b' => 12, 'c' => 20, 'd' => 20],
];

// 3. Tanggungan
$tanggungan = [
    'sedikit' => ['a' => 0, 'b' => 0, 'c' => 2, 'd' => 3],
    'sedang' =>  ['a' => 2, 'b' => 3, 'c' => 4, 'd' => 5],
    'banyak' =>  ['a' => 4, 'b' => 5, 'c' => 10, 'd' => 10],
];

// 4. Kelayakan (Output)
$kelayakan = [
    'tidak_layak' => ['a' => 0, 'b' => 0, 'c' => 40, 'd' => 60],
    'dipertimbangkan' => ['a' => 50, 'b' => 65, 'c' => 75, 'd' => 85],
    'layak' => ['a' => 75, 'b' => 90, 'c' => 100, 'd' => 100],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fa-solid fa-chart-line text-primary me-2"></i>Grafik Himpunan Keanggotaan Fuzzy</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard/index.php'); ?>">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Grafik Keanggotaan</li>
        </ol>
    </nav>
</div>

<div class="row">
    <!-- Chart: Nilai Akademik -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa-solid fa-graduation-cap me-2 text-info"></i>Variabel Nilai Akademik</h5>
            </div>
            <div class="card-body">
                <canvas id="chartAkademik"></canvas>
                <div class="mt-3 small text-muted">
                    Himpunan:
                    <ul class="mb-0">
                        <li><strong>Rendah:</strong> [0, 0, 50, 70]</li>
                        <li><strong>Sedang:</strong> [60, 75, 85, 90]</li>
                        <li><strong>Tinggi:</strong> [80, 90, 100, 100]</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Pendapatan -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa-solid fa-wallet me-2 text-warning"></i>Variabel Pendapatan Orang Tua</h5>
            </div>
            <div class="card-body">
                <canvas id="chartPendapatan"></canvas>
                <div class="mt-3 small text-muted">
                    Himpunan:
                    <ul class="mb-0">
                        <li><strong>Rendah:</strong> [0, 0, 2jt, 4jt]</li>
                        <li><strong>Sedang:</strong> [3jt, 5jt, 7jt, 10jt]</li>
                        <li><strong>Tinggi:</strong> [8jt, 12jt, 20jt, 20jt]</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Tanggungan -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa-solid fa-users me-2 text-danger"></i>Variabel Tanggungan</h5>
            </div>
            <div class="card-body">
                <canvas id="chartTanggungan"></canvas>
                <div class="mt-3 small text-muted">
                    Himpunan:
                    <ul class="mb-0">
                        <li><strong>Sedikit:</strong> [0, 0, 2, 3]</li>
                        <li><strong>Sedang:</strong> [2, 3, 4, 5]</li>
                        <li><strong>Banyak:</strong> [4, 5, 10, 10]</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Kelayakan -->
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fa-solid fa-star me-2 text-success"></i>Variabel Kelayakan (Output)</h5>
            </div>
            <div class="card-body">
                <canvas id="chartKelayakan"></canvas>
                <div class="mt-3 small text-muted">
                    Himpunan:
                    <ul class="mb-0">
                        <li><strong>Tidak Layak:</strong> [0, 0, 40, 60]</li>
                        <li><strong>Dipertimbangkan:</strong> [50, 65, 75, 85]</li>
                        <li><strong>Layak:</strong> [75, 90, 100, 100]</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Konfigurasi umum Chart
    const commonOptions = {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                callbacks: {
                    title: (context) => {
                        return `Nilai X: ${context[0].raw.x}`;
                    },
                    label: (context) => {
                        return `${context.dataset.label}: ${context.raw.y}`;
                    }
                }
            }
        },
        scales: {
            y: {
                min: 0,
                max: 1.1,
                title: {
                    display: true,
                    text: 'Derajat Keanggotaan (μ)'
                }
            },
            x: {
                type: 'linear',
                position: 'bottom',
                title: {
                    display: true,
                    text: 'Nilai Input'
                }
            }
        }
    };

    // Fungsi helper untuk generate trapezoidal points
    function generateTrapezoidalData(a, b, c, d) {
        return [
            { x: Math.max(0, a - (b-a || 5)), y: 0 },
            { x: a, y: 0 },
            { x: b, y: 1 },
            { x: c, y: 1 },
            { x: d, y: 0 },
            { x: d + (d-c || 5), y: 0 } // extend a bit to right
        ].sort((A, B) => A.x - B.x);
    }

    // 1. Chart Akademik
    const ctxAkademik = document.getElementById('chartAkademik').getContext('2d');
    new Chart(ctxAkademik, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'Rendah',
                    data: [
                        {x: 0, y: 1}, {x: 50, y: 1}, {x: 70, y: 0}, {x: 100, y: 0}
                    ],
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0
                },
                {
                    label: 'Sedang',
                    data: generateTrapezoidalData(60, 75, 85, 90),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0
                },
                {
                    label: 'Tinggi',
                    data: [
                        {x: 0, y: 0}, {x: 80, y: 0}, {x: 90, y: 1}, {x: 100, y: 1}
                    ],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                x: {
                    ...commonOptions.scales.x,
                    min: 0,
                    max: 100
                }
            }
        }
    });

    // 2. Chart Pendapatan
    const ctxPendapatan = document.getElementById('chartPendapatan').getContext('2d');
    new Chart(ctxPendapatan, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'Rendah',
                    data: [
                        {x: 0, y: 1}, {x: 2, y: 1}, {x: 4, y: 0}, {x: 20, y: 0}
                    ],
                    borderColor: '#10b981', // Hijau untuk rendah (bagus untuk beasiswa)
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0
                },
                {
                    label: 'Sedang',
                    data: generateTrapezoidalData(3, 5, 7, 10),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0
                },
                {
                    label: 'Tinggi',
                    data: [
                        {x: 0, y: 0}, {x: 8, y: 0}, {x: 12, y: 1}, {x: 20, y: 1}
                    ],
                    borderColor: '#ef4444', // Merah untuk tinggi (kurang peluang)
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                x: {
                    ...commonOptions.scales.x,
                    min: 0,
                    max: 20,
                    title: {
                        display: true,
                        text: 'Pendapatan (Juta Rp)'
                    }
                }
            }
        }
    });

    // 3. Chart Tanggungan
    const ctxTanggungan = document.getElementById('chartTanggungan').getContext('2d');
    new Chart(ctxTanggungan, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'Sedikit',
                    data: [
                        {x: 0, y: 1}, {x: 2, y: 1}, {x: 3, y: 0}, {x: 10, y: 0}
                    ],
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0
                },
                {
                    label: 'Sedang',
                    data: generateTrapezoidalData(2, 3, 4, 5),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0
                },
                {
                    label: 'Banyak',
                    data: [
                        {x: 0, y: 0}, {x: 4, y: 0}, {x: 5, y: 1}, {x: 10, y: 1}
                    ],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                x: {
                    ...commonOptions.scales.x,
                    min: 0,
                    max: 10,
                    title: {
                        display: true,
                        text: 'Jumlah Tanggungan'
                    }
                }
            }
        }
    });

    // 4. Chart Kelayakan (Output)
    const ctxKelayakan = document.getElementById('chartKelayakan').getContext('2d');
    new Chart(ctxKelayakan, {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'Tidak Layak',
                    data: [
                        {x: 0, y: 1}, {x: 40, y: 1}, {x: 60, y: 0}, {x: 100, y: 0}
                    ],
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0
                },
                {
                    label: 'Dipertimbangkan',
                    data: generateTrapezoidalData(50, 65, 75, 85),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0
                },
                {
                    label: 'Layak',
                    data: [
                        {x: 0, y: 0}, {x: 75, y: 0}, {x: 90, y: 1}, {x: 100, y: 1}
                    ],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0
                }
            ]
        },
        options: {
            ...commonOptions,
            scales: {
                ...commonOptions.scales,
                x: {
                    ...commonOptions.scales.x,
                    min: 0,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Nilai Kelayakan'
                    }
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>