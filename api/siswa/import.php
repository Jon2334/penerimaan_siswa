<?php
// siswa/import.php - Bulk Import Student Data and Grades from CSV
require_once __DIR__ . '/../config/config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Metode request tidak valid!');
    redirect('siswa/index.php');
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    set_flash_message('error', 'Gagal mengunggah file! Silakan periksa ukuran file atau format.');
    redirect('siswa/index.php');
}

$file_tmp = $_FILES['csv_file']['tmp_name'];
$file_name = $_FILES['csv_file']['name'];
$ext = pathinfo($file_name, PATHINFO_EXTENSION);

if (strtolower($ext) !== 'csv') {
    set_flash_message('error', 'Hanya file dengan ekstensi .csv yang diperbolehkan!');
    redirect('siswa/index.php');
}

try {
    // Open file
    $handle = fopen($file_tmp, 'r');
    if (!$handle) {
        throw new Exception('Gagal membuka file CSV.');
    }

    // Detect separator (comma or semicolon)
    $first_line = fgets($handle);
    $separator = ',';
    if (strpos($first_line, ';') !== false && (strpos($first_line, ',') === false || strpos($first_line, ';') < strpos($first_line, ','))) {
        $separator = ';';
    }
    
    // Rewind file pointer
    rewind($handle);

    // Read header row
    $raw_headers = fgetcsv($handle, 0, $separator, '"', '\\');
    if (!$raw_headers) {
        throw new Exception('File CSV kosong atau tidak memiliki header.');
    }

    // Lowercase headers for fuzzy matching
    $headers = array_map(function($h) {
        return strtolower(trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $h))); // remove BOM or special chars
    }, $raw_headers);

    // Define aliases mappings to DB fields
    $field_map = [
        'nomor_peserta' => ['nomor_peserta', 'no_peserta', 'nomer_peserta', 'no peserta', 'nomor peserta', 'no. peserta', 'reg', 'no_reg'],
        'nama_siswa'    => ['nama_siswa', 'nama', 'nama_lengkap', 'nama lengkap', 'siswa'],
        'jk'            => ['jk', 'jenis_kelamin', 'jenis kelamin', 'gender', 'l/p', 'sex'],
        'tempat_lahir'  => ['tempat_lahir', 'tempat lahir', 'tempat', 'tmp_lahir'],
        'tanggal_lahir' => ['tanggal_lahir', 'tanggal lahir', 'tgl_lahir', 'tgl lahir', 'tanggal', 'tgl'],
        'alamat'        => ['alamat', 'alamat_rumah', 'alamat rumah'],
        'asal_sekolah'  => ['asal_sekolah', 'asal sekolah', 'sekolah', 'asal', 'smp'],
        'no_hp'         => ['no_hp', 'no hp', 'nohp', 'telepon', 'no. hp', 'hp', 'telp', 'handphone'],
        'tahun_ajaran'  => ['tahun_ajaran', 'tahun ajaran', 'tahun', 'ta', 'tahun ajaran pendaftaran'],
        // Optional grade fields
        'nilai_uan'     => ['nilai_uan', 'uan', 'nilai uan', 'un', 'nilai un'],
        'nilai_raport'  => ['nilai_raport', 'raport', 'nilai raport', 'nilai rapor', 'rapor'],
        'tes_kompetensi'=> ['tes_kompetensi', 'tes kompetensi', 'kompetensi', 'tes_kompetensi_umum', 'kompetensi umum']
    ];

    // Find index of headers in CSV file
    $header_indexes = [];
    foreach ($field_map as $db_field => $aliases) {
        $header_indexes[$db_field] = -1;
        foreach ($aliases as $alias) {
            $idx = array_search($alias, $headers);
            if ($idx !== false) {
                $header_indexes[$db_field] = $idx;
                break;
            }
        }
    }

    // Check required fields (nomor_peserta and nama_siswa are required)
    if ($header_indexes['nomor_peserta'] === -1) {
        throw new Exception('Kolom "nomor_peserta" wajib ada di dalam file CSV!');
    }
    if ($header_indexes['nama_siswa'] === -1) {
        throw new Exception('Kolom "nama_siswa" wajib ada di dalam file CSV!');
    }

    $imported_count = 0;
    $skipped_count = 0;
    $errors = [];
    $row_num = 1;

    // Start Transaction
    $pdo->beginTransaction();

    // Prepared statements for fast execution
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nomor_peserta = ?");
    
    $ins_siswa = $pdo->prepare("
        INSERT INTO siswa (nomor_peserta, nama_siswa, jk, tempat_lahir, tanggal_lahir, alamat, asal_sekolah, no_hp, tahun_ajaran)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $ins_nilai = $pdo->prepare("
        INSERT INTO nilai_siswa (id_siswa, nilai_uan, nilai_raport, tes_kompetensi)
        VALUES (?, ?, ?, ?)
    ");

    while (($row = fgetcsv($handle, 0, $separator, '"', '\\')) !== false) {
        $row_num++;
        
        // Skip empty rows
        if (count($row) === 1 && empty($row[0])) {
            continue;
        }

        // Helper closures to get value safely
        $get_val = function($field, $default = '') use ($row, $header_indexes) {
            $idx = $header_indexes[$field];
            return ($idx !== -1 && isset($row[$idx])) ? trim($row[$idx]) : $default;
        };

        // Extract and clean fields
        $nomor_peserta = $get_val('nomor_peserta');
        $nama_siswa    = $get_val('nama_siswa');
        
        if (empty($nomor_peserta) || empty($nama_siswa)) {
            $errors[] = "Baris #{$row_num}: Dilewati karena Nomor Peserta atau Nama Siswa kosong.";
            $skipped_count++;
            continue;
        }

        // Check duplicates
        $check_stmt->execute([$nomor_peserta]);
        $exists = $check_stmt->fetchColumn() > 0;
        if ($exists) {
            $errors[] = "Baris #{$row_num}: Dilewati karena Nomor Peserta '{$nomor_peserta}' sudah terdaftar.";
            $skipped_count++;
            continue;
        }

        // Process Gender (L/P)
        $raw_jk = strtolower($get_val('jk', 'L'));
        if (in_array($raw_jk, ['p', 'perempuan', 'wanita', 'female'])) {
            $jk = 'P';
        } else {
            $jk = 'L'; // default
        }

        // Process date format (convert standard format if possible, else default to null/today)
        $raw_tgl = $get_val('tanggal_lahir');
        $tanggal_lahir = null;
        if (!empty($raw_tgl)) {
            $time = strtotime($raw_tgl);
            if ($time) {
                $tanggal_lahir = date('Y-m-d', $time);
            }
        }
        if (empty($tanggal_lahir)) {
            $tanggal_lahir = date('Y-m-d'); // fallback
        }

        // Simple default school year if empty
        $tahun_ajaran = $get_val('tahun_ajaran', date('Y') . '/' . (date('Y') + 1));

        $tempat_lahir = $get_val('tempat_lahir', '-');
        $alamat = $get_val('alamat', '-');
        $asal_sekolah = $get_val('asal_sekolah', '-');
        $no_hp = $get_val('no_hp', '-');

        // Insert Student
        $ins_siswa->execute([
            $nomor_peserta, 
            $nama_siswa, 
            $jk, 
            $tempat_lahir, 
            $tanggal_lahir, 
            $alamat, 
            $asal_sekolah, 
            $no_hp, 
            $tahun_ajaran
        ]);

        $id_siswa = $pdo->lastInsertId();

        // Optional grades import
        $val_uan = $get_val('nilai_uan', '');
        $val_raport = $get_val('nilai_raport', '');
        $val_kompetensi = $get_val('tes_kompetensi', '');

        if ($val_uan !== '' || $val_raport !== '' || $val_kompetensi !== '') {
            $num_uan = (double)str_replace(',', '.', $val_uan);
            $num_raport = (double)str_replace(',', '.', $val_raport);
            $num_kompetensi = (double)str_replace(',', '.', $val_kompetensi);
            
            // Constrain value ranges
            if ($num_uan < 0) $num_uan = 0; if ($num_uan > 40) $num_uan = 40;
            if ($num_raport < 0) $num_raport = 0; if ($num_raport > 100) $num_raport = 100;
            if ($num_kompetensi < 0) $num_kompetensi = 0; if ($num_kompetensi > 100) $num_kompetensi = 100;

            $ins_nilai->execute([
                $id_siswa,
                $num_uan,
                $num_raport,
                $num_kompetensi
            ]);
        }

        $imported_count++;
    }

    $pdo->commit();
    fclose($handle);

    // Build success message
    $msg = "Berhasil mengimpor <strong>{$imported_count}</strong> data siswa baru.";
    if ($skipped_count > 0) {
        $msg .= " Sebanyak <strong>{$skipped_count}</strong> baris dilewati.";
    }
    
    set_flash_message('success', $msg);
    
    if (!empty($errors)) {
        // Limit details to show in flash message to avoid overflow
        $limited_errors = array_slice($errors, 0, 5);
        $err_text = implode('<br>', $limited_errors);
        if (count($errors) > 5) {
            $err_text .= '<br>...dan beberapa data lainnya.';
        }
        set_flash_message('warning', '<strong>Rincian peringatan:</strong><br>' . $err_text);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash_message('error', 'Terjadi kesalahan impor data: ' . $e->getMessage());
}

redirect('siswa/index.php');
