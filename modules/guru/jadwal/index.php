<?php
// require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../../includes/auth.php';

if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil semua jadwal mengajar guru ini
$stmt = $pdo->prepare("SELECT j.*, k.nama_kelas, m.nama_mapel 
                      FROM jadwal_pelajaran j
                      JOIN kelas k ON j.kelas_id = k.kelas_id
                      JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                      WHERE j.guru_id = ?
                      ORDER BY 
                        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
                        j.jam_mulai");
$stmt->execute([$_SESSION['user_id']]);
$jadwal = $stmt->fetchAll();

// Kelompokkan jadwal per hari untuk tampilan yang lebih baik
$jadwal_per_hari = [];
foreach ($jadwal as $j) {
    $jadwal_per_hari[$j['hari']][] = $j;
}

// Daftar hari dalam bahasa Indonesia
$hari_indonesia = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];

// Dapatkan hari ini dalam format Indonesia
$hari_ini = $hari_indonesia[date('l')];
$tanggal_hari_ini = date('Y-m-d');
?>


<?php include '../../../includes/header.php'; ?>

<body>
    <div id="app">
        <!-- Sidebar start -->

        <?php include '../../../includes/navigation/guru.php'; ?>

        <!-- Sidebar end -->

        <!-- Main start -->

        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <h3>JADWAL MENGAJAR</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <?php if (empty($jadwal_per_hari)): ?>
                        <div class="alert alert-info">Anda belum memiliki jadwal mengajar.</div>
                    <?php else: ?>
                        <?php foreach ($jadwal_per_hari as $hari => $jadwal_hari): ?>
                            <div class="card mb-3 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0 text-white"><?= htmlspecialchars($hari) ?></h6>
                                </div>
                                <div class="card-body p-2">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered table-hover mb-0 align-middle text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Jam</th>
                                                    <th>Mata Pelajaran</th>
                                                    <th>Kelas</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($jadwal_hari as $j): ?>
                                                    <?php
                                                    // Cek apakah hari jadwal sama dengan hari ini
                                                    $is_hari_ini = ($j['hari'] === $hari_ini);
                                                    ?>
                                                    <tr>
                                                        <td><?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?></td>
                                                        <td><?= htmlspecialchars($j['nama_mapel']) ?></td>
                                                        <td><?= htmlspecialchars($j['nama_kelas']) ?></td>
                                                        <td>
                                                            <?php if ($is_hari_ini): ?>
                                                                <a href="../absensi/input.php?jadwal_id=<?= $j['jadwal_id'] ?>&tanggal=<?= $tanggal_hari_ini ?>"
                                                                    class="btn btn-sm btn-primary" title="Input Absensi">
                                                                    <i class="fas fa-clipboard-check"></i> Absensi
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-outline-secondary" disabled title="Hanya bisa diakses pada hari <?= $j['hari'] ?>">
                                                                    <i class="fas fa-clipboard-check"></i> Absensi
                                                                </button>
                                                            <?php endif; ?>

                                                            <a href="../absensi/rekap.php?jadwal_id=<?= $j['jadwal_id'] ?>"
                                                                class="btn btn-sm btn-secondary" title="Lihat Rekap">
                                                                <i class="fas fa-chart-bar"></i> Rekap
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

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