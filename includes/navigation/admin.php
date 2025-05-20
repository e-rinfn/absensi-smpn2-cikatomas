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


<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header p-3 border-bottom bg-light">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <a href="<?= $base_url ?>" class="d-block">
                        <img src="<?= $base_url ?>assets/images/Logo.png" alt="Logo" style="height: 60px;" class="me-3" />
                    </a>
                    <div>
                        <h5 class="mb-0 fw-bold">ABSENSI SMPN 2 Cikatomas</h5>
                    </div>
                </div>
                <div class="d-xl-none">
                    <a href="#" class="sidebar-hide btn btn-sm btn-light">
                        <i class="bi bi-x fs-4"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="sidebar-menu">
            <ul class="menu list-unstyled mb-0">



                <li class="sidebar-item <?= isActive('/admin/dashboard') ?>">
                    <a href="<?= $base_url ?>modules/admin/dashboard.php" class="sidebar-link d-flex align-items-center">
                        <i class="bi bi-speedometer2 me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-title text-uppercase text-muted small fw-bold mt-3 ps-3">
                    Group Menu 1
                </li>

                <li class="sidebar-item <?= isActive('/admin/kelas') ?>">
                    <a href="<?= $base_url ?>modules/admin/kelas/index.php" class="sidebar-link d-flex align-items-center">
                        <i class="bi bi-house-fill me-2"></i>
                        <span>Kelas</span>
                    </a>
                </li>

                <li class="sidebar-item <?= isActive('/admin/murid') ?>">
                    <a href="<?= $base_url ?>modules/admin/murid/index.php" class="sidebar-link d-flex align-items-center">
                        <i class="bi bi-people-fill me-2"></i>
                        <span>Data Murid</span>
                    </a>
                </li>



                <li class="sidebar-item <?= isActive('/admin/mapel') ?>">
                    <a href="<?= $base_url ?>modules/admin/mapel/index.php" class="sidebar-link d-flex align-items-center">
                        <i class="bi bi-journal-bookmark-fill me-2"></i>
                        <span>Mata Pelajaran</span>
                    </a>
                </li>

                <li class="sidebar-item <?= isActive('/admin/jadwal') ?>">
                    <a href="<?= $base_url ?>modules/admin/jadwal/index.php" class="sidebar-link d-flex align-items-center">
                        <i class="bi bi-calendar-week-fill me-2"></i>
                        <span>Jadwal Pelajaran</span>
                    </a>
                </li>

                <li class="sidebar-title text-uppercase text-muted small fw-bold mt-4 ps-3">
                    Group Menu 2
                </li>

                <li class="sidebar-item <?= isActive('/admin/user') ?>">
                    <a href="<?= $base_url ?>modules/admin/user/index.php" class="sidebar-link d-flex align-items-center">
                        <i class="bi bi-person-badge-fill me-2"></i>
                        <span>Pengguna</span>
                    </a>
                </li>

                <li class="sidebar-item <?= isActive('/admin/laporan') ?>">
                    <a href="<?= $base_url ?>modules/admin/laporan/index.php" class="sidebar-link d-flex align-items-center">
                        <i class="bi bi-file-bar-graph-fill me-2"></i>
                        <span>Laporan</span>
                    </a>
                </li>


                <li class="sidebar-title text-uppercase text-muted small fw-bold mt-4 ps-3">
                    Group Menu 3
                </li>

                <li class="sidebar-item">
                    <a href="<?= $base_url ?>logout.php" class="sidebar-link d-flex justify-content-center align-items-center text-white bg-danger" style="height: 40px;">
                        <span class="text-center ms-0">Logout</span>
                    </a>
                </li>


                <div class="sidebar-footer p-3 mb-5 border-top bg-light mt-3">
                    <div class="text-center">
                        <p>Waktu Saat Ini</p>
                        <div class="fw-bold mb-1" id="live-clock"><?= $current_time ?></div>
                        <div class="small text-muted"><?= $current_date ?></div>
                    </div>
                </div>

            </ul>

        </div>
        <button class="sidebar-toggler btn x">
            <i data-feather="x"></i>
        </button>
    </div>
</div>

<!-- Script untuk update waktu secara realtime -->
<script>
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString();
        document.getElementById('live-clock').textContent = timeStr;

        // Update setiap detik
        setTimeout(updateClock, 1000);
    }

    // Jalankan saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        updateClock();
    });
</script>