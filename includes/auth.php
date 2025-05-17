<?php
// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika tidak login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Anda harus login terlebih dahulu';
    header('Location: ../login.php');
    exit;
}

// Include konfigurasi database
require_once __DIR__ . '/../config/database.php';

// Ambil data user dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Redirect jika user tidak ditemukan
if (!$user) {
    session_destroy();
    $_SESSION['error'] = 'Sesi tidak valid, silakan login kembali';
    header('Location: ../login.php');
    exit;
}

// Update data session dengan data terbaru dari database
$_SESSION = array_merge($_SESSION, $user);

// Fungsi untuk memeriksa role
function isAdmin()
{
    return $_SESSION['role'] === 'admin';
}

function isGuru()
{
    return $_SESSION['role'] === 'guru';
}

function isWali()
{
    return $_SESSION['role'] === 'wali_murid';
}

// Cek modul saat ini
$uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$current_module = $uri_parts[0] ?? '';

$role_map = [
    'admin' => isAdmin(),
    'guru' => isGuru(),
    'wali' => isWali()
];

// Jika modul tidak cocok dengan role user, redirect ke dashboard yang sesuai
if (isset($role_map[$current_module]) && !$role_map[$current_module]) {
    $expected_path = '/' . $_SESSION['role'] . '/dashboard.php';
    if ($_SERVER['REQUEST_URI'] !== $expected_path) {
        header('Location: ' . $expected_path);
        exit;
    }
}
