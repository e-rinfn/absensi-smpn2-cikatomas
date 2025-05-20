<?php
// Mulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika belum login
if (empty($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Anda harus login terlebih dahulu';
    header('Location: /login.php');
    exit;
}

// Include konfigurasi
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Ambil data user dari database berdasarkan ID sesi
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika user tidak ditemukan di database, hapus session dan redirect
if (!$user || !is_array($user)) {
    session_destroy();
    header('Location: /login.php');
    exit;
}

// Update session dengan data terbaru dari database
foreach ($user as $key => $value) {
    $_SESSION[$key] = $value;
}

// Fungsi untuk memeriksa role user
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isGuru()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'guru';
}

function isWali()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'wali_murid';
}

// Mendapatkan modul/segmen URI saat ini
$uri_parts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$current_module = $uri_parts[0] ?? '';

// Mapping role ke fungsi pemeriksa
$role_map = [
    'admin' => isAdmin(),
    'guru'  => isGuru(),
    'wali'  => isWali()
];

// Jika user mengakses modul yang tidak sesuai dengan rolenya, redirect ke dashboard yang benar
if (isset($role_map[$current_module]) && !$role_map[$current_module]) {
    $redirect_path = '/' . $_SESSION['role'] . '/dashboard.php';
    if ($_SERVER['REQUEST_URI'] !== $redirect_path) {
        header('Location: ' . $redirect_path);
        exit;
    }
}
