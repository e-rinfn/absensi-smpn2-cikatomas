<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil ID murid dari URL
$id = $_GET['id'] ?? 0;

// Cek apakah murid ada
$stmt = $pdo->prepare("SELECT * FROM murid WHERE murid_id = ?");
$stmt->execute([$id]);
$murid = $stmt->fetch();

if (!$murid) {
    $_SESSION['error'] = "Murid tidak ditemukan!";
    header('Location: index.php');
    exit;
}

// Cek apakah murid memiliki data absensi
$stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE murid_id = ?");
$stmt->execute([$id]);
$hasAbsensi = $stmt->fetchColumn() > 0;

if ($hasAbsensi) {
    $_SESSION['error'] = "Murid tidak dapat dihapus karena memiliki data absensi!";
    header('Location: index.php');
    exit;
}

// Hapus murid dari database
try {
    $stmt = $pdo->prepare("DELETE FROM murid WHERE murid_id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = "Murid berhasil dihapus!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal menghapus murid: " . $e->getMessage();
}

header('Location: index.php');
exit;
