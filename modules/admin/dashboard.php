<?php
require_once __DIR__ . '/../../includes/auth.php';

if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Query untuk statistik
$total_kelas = $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
$total_murid = $pdo->query("SELECT COUNT(*) FROM murid")->fetchColumn();
$total_guru = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guru'")->fetchColumn();
$total_mapel = $pdo->query("SELECT COUNT(*) FROM mata_pelajaran")->fetchColumn();

// Query untuk absensi hari ini
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');
$absensi_hari_ini = $pdo->query("
    SELECT COUNT(*) as total, 
           SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir,
           SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) as sakit,
           SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) as izin,
           SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) as alpha
    FROM absensi 
    WHERE tanggal = '$today'
")->fetch();

// Query untuk aktivitas terbaru
$aktivitas_terbaru = $pdo->query("
    SELECT a.*, m.nama_lengkap as nama_murid, k.nama_kelas, mp.nama_mapel
    FROM absensi a
    JOIN murid m ON a.murid_id = m.murid_id
    JOIN kelas k ON m.kelas_id = k.kelas_id
    JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
    JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll();
?>

<?php include '../../includes/header.php'; ?>

<body>
    <div id="app">
        <!-- Sidebar start -->

        <?php include '../../includes/navigation/admin.php'; ?>

        <!-- Sidebar end -->

        <!-- Main start -->

        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <h3>DASHBOARD ADMIN!</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <!-- Info Boxes -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-start border-4 border-danger shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Kelas</h5>
                                    <h2 class="card-text"><?= $total_kelas ?></h2>
                                    <a href="kelas/" class="text-decoration-none">Lihat detail <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-start border-4 border-success shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Murid</h5>
                                    <h2 class="card-text"><?= $total_murid ?></h2>
                                    <a href="murid/" class="text-decoration-none">Lihat detail <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-start border-4 border-info shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Guru</h5>
                                    <h2 class="card-text"><?= $total_guru ?></h2>
                                    <a href="user/?role=guru" class="text-decoration-none">Lihat detail <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-start border-4 border-warning shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Mapel</h5>
                                    <h2 class="card-text"><?= $total_mapel ?></h2>
                                    <a href="mapel/" class="text-decoration-none">Lihat detail <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card w-75">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="kelas/tambah.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-plus-circle"></i> Tambah Kelas
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="user/tambah.php" class="btn btn-outline-success w-100">
                                        <i class="fas fa-user-plus"></i> Tambah User
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="murid/tambah.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-user-graduate"></i> Tambah Murid
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="mapel/tambah.php" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-book"></i> Tambah Mapel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="row">
                        <!-- Absensi Hari Ini -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0">Absensi Hari Ini (<?= date('d/m/Y H:i') ?> WIB)</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($absensi_hari_ini['total'] > 0): ?>
                                        <div class="chart-container" style="height: 250px;">
                                            <canvas id="absensiChart"></canvas>
                                        </div>
                                        <div class="mt-3">
                                            <p>Total: <strong><?= $absensi_hari_ini['total'] ?></strong> catatan absensi</p>
                                            <p>Hadir: <?= $absensi_hari_ini['hadir'] ?> |
                                                Sakit: <?= $absensi_hari_ini['sakit'] ?> |
                                                Izin: <?= $absensi_hari_ini['izin'] ?> |
                                                Alpha: <?= $absensi_hari_ini['alpha'] ?></p>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted mt-3">Belum ada data absensi hari ini.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Aktivitas Terbaru -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0">Aktivitas Terbaru</h5>
                                </div>
                                <hr class="my-0">
                                <div class="card-body mt-3">
                                    <?php if (count($aktivitas_terbaru) > 0): ?>
                                        <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                                            <?php foreach ($aktivitas_terbaru as $aktivitas): ?>
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1"><?= $aktivitas['nama_murid'] ?> (<?= $aktivitas['nama_kelas'] ?>)</h6>
                                                        <small><?= date('H:i', strtotime($aktivitas['created_at'])) ?></small>
                                                    </div>
                                                    <p class="mb-1">
                                                        <span class="badge 
                                                            <?= $aktivitas['status'] == 'hadir' ? 'bg-success' : '' ?>
                                                            <?= $aktivitas['status'] == 'sakit' ? 'bg-info' : '' ?>
                                                            <?= $aktivitas['status'] == 'izin' ? 'bg-warning' : '' ?>
                                                            <?= $aktivitas['status'] == 'alpha' ? 'bg-danger' : '' ?>">
                                                            <?= ucfirst($aktivitas['status']) ?>
                                                        </span>
                                                        pada <?= $aktivitas['nama_mapel'] ?>
                                                    </p>
                                                    <?php if (!empty($aktivitas['keterangan'])): ?>
                                                        <small class="text-muted">Keterangan: <?= $aktivitas['keterangan'] ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">Belum ada aktivitas terbaru.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>



                    <!-- Main content end -->
                </section>
            </div>
        </div>

        <!-- Main end -->
    </div>


    <!-- Javascript add start -->

    <!-- Chart JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if ($absensi_hari_ini['total'] > 0): ?>
            // Absensi Chart
            const ctx = document.getElementById('absensiChart').getContext('2d');
            const absensiChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Hadir', 'Sakit', 'Izin', 'Alpha'],
                    datasets: [{
                        data: [
                            <?= $absensi_hari_ini['hadir'] ?>,
                            <?= $absensi_hari_ini['sakit'] ?>,
                            <?= $absensi_hari_ini['izin'] ?>,
                            <?= $absensi_hari_ini['alpha'] ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#17a2b8',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>

    <!-- Javascript add end -->

    <!-- Javascript template mazer start -->
    <script src="<?= $base_url ?>/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= $base_url ?>/assets/js/bootstrap.bundle.min.js"></script>

    <script src="<?= $base_url ?>/assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="<?= $base_url ?>/assets/js/pages/dashboard.js"></script>

    <script src="<?= $base_url ?>/assets/js/main.js"></script>
    <!-- Javascrip template mazer end -->
</body>