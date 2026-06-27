<?php
// laporan/cetak_siswa.php - Print Student Data PDF
require_once __DIR__ . '/../config/config.php';
check_login();

try {
    $stmt = $pdo->query("SELECT * FROM siswa ORDER BY nomor_peserta ASC");
    $siswa_list = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Data Calon Siswa Baru</title>
    <!-- Bootstrap 5 CSS (needed for grid/tables layout) -->
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
        .kop-logo {
            font-size: 40px;
            font-weight: bold;
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

    <!-- Kop Surat (Formal Document Header) -->
    <div class="kop-surat d-flex align-items-center justify-content-center gap-3">
        <div class="kop-detail">
            <h4>PANITIA PENERIMAAN SISWA BARU</h4>
            <h4 class="fw-bold">SMA NEGERI TERPADU</h4>
            <p>Jalan Pendidikan No. 45, Kecamatan Sukamaju, Kota Cerdas</p>
            <p>Telp: (021) 555-0129 | Email: info@smanterpadu.sch.id | Kode Pos: 40123</p>
        </div>
    </div>

    <!-- Document Title -->
    <div class="text-center my-4">
        <h5 class="fw-bold text-decoration-underline">LAPORAN DATA CALON SISWA BARU</h5>
        <p class="small">Tahun Ajaran Cetak: <?= date('Y'); ?></p>
    </div>

    <!-- Main Table -->
    <table class="table table-bordered table-print align-middle">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">No Peserta</th>
                <th>Nama Calon Siswa</th>
                <th width="8%">L/P</th>
                <th>Tempat/Tanggal Lahir</th>
                <th>Asal Sekolah</th>
                <th width="15%">No HP</th>
                <th width="12%">Tahun Ajaran</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($siswa_list as $row): 
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td class="text-center font-monospace fw-semibold"><?= sanitize($row['nomor_peserta']); ?></td>
                <td><?= sanitize($row['nama_siswa']); ?></td>
                <td class="text-center"><?= $row['jk']; ?></td>
                <td><?= sanitize($row['tempat_lahir']); ?>, <?= date('d-m-Y', strtotime($row['tanggal_lahir'])); ?></td>
                <td><?= sanitize($row['asal_sekolah']); ?></td>
                <td><?= sanitize($row['no_hp']); ?></td>
                <td class="text-center"><?= sanitize($row['tahun_ajaran']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Signature Block -->
    <div class="ttd-section">
        <p>Kota Cerdas, <?= date('d F Y'); ?></p>
        <p>Ketua Panitia PSB,</p>
        <div class="ttd-space"></div>
        <p class="fw-bold text-decoration-underline">Drs. H. Mulyono, M.Pd.</p>
        <p class="small text-muted">NIP. 19740912 200312 1 002</p>
    </div>

</body>
</html>
