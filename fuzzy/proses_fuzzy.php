<?php
// fuzzy/proses_fuzzy.php - Orchestrator to Run Fuzzy Mamdani Calculations for All Students
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../function/fuzzy_engine.php';
check_login();

try {
    // 1. Fetch all fuzzy rules
    $rules_stmt = $pdo->query("SELECT * FROM rule_fuzzy");
    $rules = $rules_stmt->fetchAll();

    if (empty($rules)) {
        set_flash_message('error', 'Basis aturan (Rules) kosong! Silakan generate atau tambah aturan fuzzy terlebih dahulu.');
        redirect('fuzzy/aturan.php');
    }

    // 2. Fetch all students who have grades entered
    $siswa_stmt = $pdo->query("
        SELECT s.id_siswa, s.tahun_ajaran, s.nama_siswa,
               n.nilai_uan, n.nilai_raport, n.tes_kompetensi
        FROM siswa s
        INNER JOIN nilai_siswa n ON s.id_siswa = n.id_siswa
    ");
    $students = $siswa_stmt->fetchAll();

    if (empty($students)) {
        set_flash_message('error', 'Tidak ada data siswa yang memiliki nilai lengkap untuk diproses!');
        redirect('nilai/index.php');
    }

    // Begin Transaction
    $pdo->beginTransaction();

    // Clear previous results to recalculate
    $pdo->exec("DELETE FROM hasil_seleksi");

    // 3. Process each student
    foreach ($students as $student) {
        $inputs = [
            'uan' => $student['nilai_uan'],
            'raport' => $student['nilai_raport'],
            'kompetensi' => $student['tes_kompetensi']
        ];

        // Fuzzification and Inference
        $alpha_outputs = FuzzyMamdani::inferensi($inputs, $rules);

        // Defuzzification (COG)
        $score = FuzzyMamdani::defuzzifikasi($alpha_outputs);

        // Insert into hasil_seleksi (initial placeholders for ranking and status)
        $ins_stmt = $pdo->prepare("
            INSERT INTO hasil_seleksi (id_siswa, nilai_fuzzy, status_kelulusan, ranking, tanggal_proses)
            VALUES (?, ?, 'Pending', 0, NOW())
        ");
        $ins_stmt->execute([$student['id_siswa'], $score]);
    }

    $pdo->commit();

    // 4. Perform Ranking & Quota processing grouping by academic year (tahun_ajaran)
    // Get unique academic years in results
    $years_stmt = $pdo->query("
        SELECT DISTINCT s.tahun_ajaran 
        FROM hasil_seleksi h
        JOIN siswa s ON h.id_siswa = s.id_siswa
    ");
    $years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();

    foreach ($years as $tahun) {
        // Fetch quota for this academic year, default to 10 if not set
        $q_stmt = $pdo->prepare("SELECT jumlah_kuota FROM kuota WHERE tahun_ajaran = ?");
        $q_stmt->execute([$tahun]);
        $quota = $q_stmt->fetchColumn();
        if ($quota === false) {
            $quota = 10; // Default fallback
        }

        // Get student scores in this academic year sorted descending
        $score_stmt = $pdo->prepare("
            SELECT h.id_hasil, h.id_siswa, h.nilai_fuzzy
            FROM hasil_seleksi h
            JOIN siswa s ON h.id_siswa = s.id_siswa
            WHERE s.tahun_ajaran = ?
            ORDER BY h.nilai_fuzzy DESC, s.nama_siswa ASC
        ");
        $score_stmt->execute([$tahun]);
        $ranked_list = $score_stmt->fetchAll();

        // Update rank and status based on quota
        $rank = 1;
        $up_stmt = $pdo->prepare("
            UPDATE hasil_seleksi 
            SET ranking = ?, status_kelulusan = ? 
            WHERE id_hasil = ?
        ");

        foreach ($ranked_list as $row) {
            // A student is Lulus (Diterima) if within the quota AND fuzzy score >= 60.0
            $status = ($rank <= $quota && $row['nilai_fuzzy'] >= 60.0) ? 'Lulus' : 'Tidak Lulus';
            $up_stmt->execute([$rank, $status, $row['id_hasil']]);
            $rank++;
        }
    }

    $pdo->commit();

    set_flash_message('success', 'Perhitungan Fuzzy Mamdani dan perangkingan siswa berhasil diselesaikan!');
    redirect('fuzzy/hasil.php');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash_message('error', 'Terjadi kesalahan pemrosesan fuzzy: ' . $e->getMessage());
    redirect('fuzzy/hasil.php');
}
?>
