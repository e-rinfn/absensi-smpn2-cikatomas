<?php
require_once __DIR__ . '/../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil jumlah kelas yang diajar
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT kelas_id) FROM jadwal_pelajaran WHERE guru_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_kelas = $stmt->fetchColumn();

// Ambil jumlah mapel yang diajar
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT mapel_id) FROM jadwal_pelajaran WHERE guru_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_mapel = $stmt->fetchColumn();

// Ambil jadwal hari ini
$hari_ini = date('N') - 1; // Konversi ke format 0-6 (Senin-Minggu)
$hari_map = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$nama_hari = $hari_map[date('N') - 1];

$stmt = $pdo->prepare("SELECT j.*, k.nama_kelas, m.nama_mapel 
                      FROM jadwal_pelajaran j
                      JOIN kelas k ON j.kelas_id = k.kelas_id
                      JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                      WHERE j.guru_id = ? AND j.hari = ?
                      ORDER BY j.jam_mulai");
$stmt->execute([$_SESSION['user_id'], $nama_hari]);
$jadwal_hari_ini = $stmt->fetchAll();
?>


<?php include '../../includes/header.php'; ?>

<body>
    <div id="app">
        <!-- Sidebar start -->

        <?php include '../../includes/navigation/guru.php'; ?>

        <!-- Sidebar end -->

        <!-- Main start -->

        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <h3>Judul Halaman!</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Kelas yang Diajar</h5>
                                    <p class="display-4"><?= $total_kelas ?></p>
                                    <a href="jadwal/" class="btn btn-primary">Lihat Jadwal</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Mata Pelajaran</h5>
                                    <p class="display-4"><?= $total_mapel ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4>Jadwal Mengajar Hari Ini (<?= $nama_hari ?>)</h4>
                        </div>
                        <div class="card-body">
                            <?php if (empty($jadwal_hari_ini)): ?>
                                <div class="alert alert-info">Tidak ada jadwal mengajar hari ini.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Jam</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Kelas</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($jadwal_hari_ini as $jadwal): ?>
                                                <tr>
                                                    <td><?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?></td>
                                                    <td><?= htmlspecialchars($jadwal['nama_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($jadwal['nama_kelas']) ?></td>
                                                    <td>
                                                        <a href="absensi/input.php?jadwal_id=<?= $jadwal['jadwal_id'] ?>&tanggal=<?= date('Y-m-d') ?>"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="fas fa-clipboard-check"></i> Absensi
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Main content end -->
                </section>
            </div>
        </div>

        <!-- Main end -->
    </div>


    <!-- Javascript add start -->

    <!-- your javascript code here -->

    <!-- Javascript add end -->

    <!-- Javascript template mazer start -->
    <script src="<?= $base_url ?>/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= $base_url ?>/assets/js/bootstrap.bundle.min.js"></script>

    <script src="<?= $base_url ?>/assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="<?= $base_url ?>/assets/js/pages/dashboard.js"></script>

    <script src="<?= $base_url ?>/assets/js/main.js"></script>
    <!-- Javascrip template mazer end -->
</body>