<?php
// laporan/cetak_ranking.php - Print Rank Summary PDF
require_once __DIR__ . '/../config/config.php';
check_login();

$tahun_ajaran = isset($_GET['tahun_ajaran']) ? sanitize($_GET['tahun_ajaran']) : '';

if (empty($tahun_ajaran)) {
    die("Pilih Tahun Ajaran terlebih dahulu!");
}

try {
    $stmt = $pdo->prepare("
        SELECT h.nilai_fuzzy, h.status_kelulusan, h.ranking,
               s.nomor_peserta, s.nama_siswa, s.jk, s.asal_sekolah
        FROM hasil_seleksi h
        INNER JOIN siswa s ON h.id_siswa = s.id_siswa
        WHERE s.tahun_ajaran = ?
        ORDER BY h.ranking ASC
    ");
    $stmt->execute([$tahun_ajaran]);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data ranking: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Perangkingan Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            color: #000000;
            background-color: #ffffff;
            margin: 20px;
        }
        .kop-surat {
            border-bottom: 3px double #000000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-detail {
            text-align: center;
        }
        .kop-detail h4 {
            margin: 0;
            font-weight: 700;
            text-transform: uppercase;
        }
        .kop-detail p {
            margin: 2px 0 0;
            font-size: 13px;
        }
        .table-print th {
            background-color: #f2f2f2 !important;
            color: #000 !important;
            border: 1px solid #000000 !important;
            text-align: center;
            font-weight: bold;
        }
        .table-print td {
            border: 1px solid #000000 !important;
        }
        .ttd-section {
            margin-top: 50px;
            float: right;
            text-align: center;
            width: 250px;
            page-break-inside: avoid;
        }
        .ttd-space {
            height: 80px;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 20mm;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="kop-surat d-flex align-items-center justify-content-center gap-3">
        <div class="kop-detail">
            <h4>PANITIA PENERIMAAN SISWA BARU</h4>
            <h4 class="fw-bold">SMA NEGERI TERPADU</h4>
            <p>Jalan Pendidikan No. 45, Kecamatan Sukamaju, Kota Cerdas</p>
            <p>Telp: (021) 555-0129 | Email: info@smanterpadu.sch.id | Kode Pos: 40123</p>
        </div>
    </div>

    <div class="text-center my-4">
        <h5 class="fw-bold text-decoration-underline">LAPORAN PERANGKINGAN HASIL SELEKSI</h5>
        <p class="small mb-1">Penerimaan Siswa Baru SMA Negeri Terpadu</p>
        <p class="small">Tahun Ajaran: <?= sanitize($tahun_ajaran); ?></p>
    </div>

    <table class="table table-bordered table-print align-middle">
        <thead>
            <tr>
                <th width="10%">Peringkat</th>
                <th width="18%">No Peserta</th>
                <th>Nama Calon Siswa</th>
                <th width="10%">L/P</th>
                <th>Asal Sekolah</th>
                <th width="20%">Nilai Fuzzy (Z*)</th>
                <th width="15%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr>
                    <td colspan="7" class="text-center">Data kosong atau belum diproses.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td class="text-center fw-bold"><?= $row['ranking']; ?></td>
                    <td class="text-center font-monospace fw-semibold"><?= sanitize($row['nomor_peserta']); ?></td>
                    <td><?= sanitize($row['nama_siswa']); ?></td>
                    <td class="text-center"><?= $row['jk']; ?></td>
                    <td><?= sanitize($row['asal_sekolah']); ?></td>
                    <td class="text-center font-monospace fw-bold"><?= number_format($row['nilai_fuzzy'], 4); ?></td>
                    <td class="text-center fw-semibold">
                        <?php if ($row['status_kelulusan'] === 'Lulus'): ?>
                            Diterima
                        <?php else: ?>
                            Tidak Diterima
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="ttd-section">
        <p>Kota Cerdas, <?= date('d F Y'); ?></p>
        <p>Ketua Panitia PSB,</p>
        <div class="ttd-space"></div>
        <p class="fw-bold text-decoration-underline">Drs. H. Mulyono, M.Pd.</p>
        <p class="small text-muted">NIP. 19740912 200312 1 002</p>
    </div>

</body>
</html>
