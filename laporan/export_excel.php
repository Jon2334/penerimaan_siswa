<?php
// laporan/export_excel.php - Export Selection Results to Excel (Simplified)
require_once __DIR__ . '/../config/config.php';
check_login();

$tahun_ajaran = isset($_GET['tahun_ajaran']) ? sanitize($_GET['tahun_ajaran']) : '';

if (empty($tahun_ajaran)) {
    die("Pilih Tahun Ajaran terlebih dahulu!");
}

try {
    $stmt = $pdo->prepare("
        SELECT h.nilai_fuzzy, h.status_kelulusan, h.ranking,
               s.nomor_peserta, s.nama_siswa, s.jk, s.asal_sekolah,
               n.nilai_uan, n.nilai_raport, n.tes_kompetensi
        FROM hasil_seleksi h
        INNER JOIN siswa s ON h.id_siswa = s.id_siswa
        INNER JOIN nilai_siswa n ON s.id_siswa = n.id_siswa
        WHERE s.tahun_ajaran = ?
        ORDER BY h.ranking ASC
    ");
    $stmt->execute([$tahun_ajaran]);
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data: " . $e->getMessage());
}

// Set Headers for Excel Download
$filename = "Laporan_Hasil_Seleksi_" . str_replace('/', '-', $tahun_ajaran) . "_" . date('Y-m-d') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
</head>
<body>

    <div style="text-align: center; font-family: sans-serif;">
        <h3>LAPORAN SELEKSI PENERIMAAN SISWA BARU</h3>
        <h4>METODE FUZZY MAMDANI - TAHUN AJARAN <?= $tahun_ajaran; ?></h4>
        <p>Tanggal Export: <?= date('d-m-Y H:i'); ?></p>
    </div>

    <table border="1" style="font-family: sans-serif; border-collapse: collapse; width: 100%;">
        <thead>
            <tr style="background-color: #f2f2f2; font-weight: bold; text-align: center;">
                <th>Rank</th>
                <th>No Peserta</th>
                <th>Nama Calon Siswa</th>
                <th>Jenis Kelamin</th>
                <th>Asal Sekolah</th>
                <th>Nilai UAN (Max 40)</th>
                <th>Nilai Raport (Max 100)</th>
                <th>Tes Kompetensi (Max 100)</th>
                <th>Nilai Fuzzy (Z*)</th>
                <th>Status Kelulusan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr>
                    <td colspan="10" style="text-align: center;">Data kosong atau belum diproses.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td style="text-align: center; font-weight: bold;"><?= $row['ranking']; ?></td>
                    <td style="text-align: center; mso-number-format:'\@';"><?= sanitize($row['nomor_peserta']); ?></td>
                    <td><?= sanitize($row['nama_siswa']); ?></td>
                    <td style="text-align: center;"><?= $row['jk'] === 'L' ? 'Laki-Laki' : 'Perempuan'; ?></td>
                    <td><?= sanitize($row['asal_sekolah']); ?></td>
                    <td style="text-align: right;"><?= number_format($row['nilai_uan'], 2); ?></td>
                    <td style="text-align: right;"><?= number_format($row['nilai_raport'], 2); ?></td>
                    <td style="text-align: right;"><?= number_format($row['tes_kompetensi'], 2); ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($row['nilai_fuzzy'], 4); ?></td>
                    <td style="text-align: center; font-weight: bold; background-color: <?= ($row['status_kelulusan'] === 'Lulus') ? '#d4edda' : '#f8d7da'; ?>;">
                        <?= ($row['status_kelulusan'] === 'Lulus') ? 'Lulus' : 'Tidak Lulus'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
