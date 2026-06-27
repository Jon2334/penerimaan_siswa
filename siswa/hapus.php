<?php
// siswa/hapus.php - Delete Student Handler
require_once __DIR__ . '/../config/config.php';
check_login();

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_siswa = (int)$_GET['id'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Optional: delete from child tables manually if cascades are not set up on target MySQL
        // (Though our DDL has ON DELETE CASCADE, manual removal is safer in case of engine fallback)
        $pdo->prepare("DELETE FROM hasil_seleksi WHERE id_siswa = ?")->execute([$id_siswa]);
        $pdo->prepare("DELETE FROM nilai_siswa WHERE id_siswa = ?")->execute([$id_siswa]);
        
        // Delete parent student record
        $stmt = $pdo->prepare("DELETE FROM siswa WHERE id_siswa = ?");
        $stmt->execute([$id_siswa]);

        $pdo->commit();
        
        set_flash_message('success', 'Data siswa dan nilai terkait berhasil dihapus!');
    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('error', 'Gagal menghapus data: ' . $e->getMessage());
    }
} else {
    set_flash_message('error', 'ID tidak valid!');
}

redirect('siswa/index.php');
?>
