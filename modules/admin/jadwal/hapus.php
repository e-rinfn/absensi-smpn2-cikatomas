<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil ID dari URL
$jadwal_id = $_GET['id'] ?? 0;

// Validasi ID
if (empty($jadwal_id)) {
    $_SESSION['error'] = "ID jadwal tidak valid";
    header('Location: index.php');
    exit;
}

// Cek apakah jadwal ada
$stmt = $pdo->prepare("SELECT * FROM jadwal_pelajaran WHERE jadwal_id = ?");
$stmt->execute([$jadwal_id]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    $_SESSION['error'] = "Jadwal tidak ditemukan";
    header('Location: index.php');
    exit;
}

// Hapus jadwal
$stmt = $pdo->prepare("DELETE FROM jadwal_pelajaran WHERE jadwal_id = ?");
$stmt->execute([$jadwal_id]);

// Hapus juga absensi terkait jadwal ini
$stmt = $pdo->prepare("DELETE FROM absensi WHERE jadwal_id = ?");
$stmt->execute([$jadwal_id]);

$_SESSION['success'] = "Jadwal berhasil dihapus";
header('Location: index.php');
exit;
