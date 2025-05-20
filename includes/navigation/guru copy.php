<?php
require_once __DIR__ . '../../../config/config.php';

$current_uri = $_SERVER['REQUEST_URI'];

function isActive($path)
{
    global $current_uri;
    return strpos($current_uri, $path) !== false ? 'active' : '';
}

// Mendapatkan waktu saat ini
$current_time = date('H:i:s'); // Format waktu: Jam:Menit:Detik
$current_date = date('d F Y'); // Format tanggal: 01 January 2023
?>

<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link active" href="../guru/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="../guru/jadwal/index.php">
            <i class="bi bi-calendar-event"></i> Jadwal Mengajar
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="../guru/absensi/index.php">
            <i class="bi bi-clipboard-check"></i> Absensi
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="../guru/laporan/index.php">
            <i class="bi bi-file-earmark-text"></i> Laporan
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="../guru/profil.php">
            <i class="bi bi-person"></i> Profil
        </a>
    </li>
</ul>