<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$kelas_id = $_GET['id'];

// Cek apakah kelas memiliki murid
$stmt = $pdo->prepare("SELECT COUNT(*) FROM murid WHERE kelas_id = ?");
$stmt->execute([$kelas_id]);
$jumlah_murid = $stmt->fetchColumn();

if ($jumlah_murid > 0) {
    $_SESSION['error'] = 'Tidak dapat menghapus kelas karena masih memiliki murid';
    header('Location: index.php');
    exit;
}

// Hapus kelas
try {
    $stmt = $pdo->prepare("DELETE FROM kelas WHERE kelas_id = ?");
    $stmt->execute([$kelas_id]);

    $_SESSION['success'] = 'Kelas berhasil dihapus';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Gagal menghapus kelas: ' . $e->getMessage();
}

header('Location: index.php');
exit;
