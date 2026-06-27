<?php
// siswa/download_template.php - Downloader for CSV Student Import Template
require_once __DIR__ . '/../config/config.php';
check_login();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=template_import_siswa.csv');

$output = fopen('php://output', 'w');

// Header columns matching database schema
fputcsv($output, [
    'nomor_peserta', 
    'nama_siswa', 
    'jk', 
    'tempat_lahir', 
    'tanggal_lahir', 
    'alamat', 
    'asal_sekolah', 
    'no_hp', 
    'tahun_ajaran', 
    'nilai_uan', 
    'nilai_raport', 
    'tes_kompetensi'
]);

// Sample records
fputcsv($output, [
    'REG-2026-003', 
    'Budi Santoso', 
    'L', 
    'Medan', 
    '2008-05-15', 
    'Jl. Merdeka No. 10', 
    'SMP Negeri 1 Medan', 
    '081234567890', 
    '2026/2027', 
    '34.50', 
    '85.20', 
    '78.00'
]);

fputcsv($output, [
    'REG-2026-004', 
    'Siti Aminah', 
    'P', 
    'Bandung', 
    '2008-09-20', 
    'Jl. Melati No. 5', 
    'SMP Negeri 2 Bandung', 
    '089876543210', 
    '2026/2027', 
    '38.00', 
    '90.50', 
    '88.50'
]);

fclose($output);
exit;
