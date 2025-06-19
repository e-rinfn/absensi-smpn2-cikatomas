<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Search functionality
$search_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '%' . $search_raw . '%';

// Query untuk mengambil data jadwal pelajaran dengan kondisi search
$sql = "SELECT j.*, k.nama_kelas, m.nama_mapel, u.full_name AS nama_guru 
        FROM jadwal_pelajaran j
        JOIN kelas k ON j.kelas_id = k.kelas_id
        JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
        JOIN users u ON j.guru_id = u.user_id
        WHERE k.nama_kelas LIKE :search1 
           OR m.nama_mapel LIKE :search2 
           OR u.full_name LIKE :search3
           OR j.hari LIKE :search4
           OR j.jam_mulai LIKE :search5
           OR j.jam_selesai LIKE :search6
           OR j.ruang_kelas LIKE :search7
           OR j.semester LIKE :search8
           OR j.tahun_ajaran LIKE :search9
        ORDER BY j.hari, j.jam_mulai";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'search1' => $search_sql,
    'search2' => $search_sql,
    'search3' => $search_sql,
    'search4' => $search_sql,
    'search5' => $search_sql,
    'search6' => $search_sql,
    'search7' => $search_sql,
    'search8' => $search_sql,
    'search9' => $search_sql
]);
$jadwal = $stmt->fetchAll();

// Untuk menampilkan hari dalam format Indonesia
$nama_hari = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
?>


<?php include '../../../includes/header.php'; ?>

<body>
    <div id="app">
        <!-- Sidebar start -->

        <?php include '../../../includes/navigation/admin.php'; ?>

        <!-- Sidebar end -->

        <!-- Main start -->

        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <h3>DAFTAR JADWAL PELAJARAN</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Daftar Jadwal</span>
                            <div class="d-flex align-items-center">
                                <form method="get" class="d-flex me-2">
                                    <input type="text" name="search" class="form-control form-control-sm me-2"
                                        value="<?= htmlspecialchars($search_raw) ?>" placeholder="Cari jadwal...">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Cari</button>
                                </form>
                                <a href="index.php" class="btn btn-secondary btn-sm">
                                    Reset
                                </a>
                                <a href="tambah.php" class="btn btn-primary btn-sm ms-3">
                                    Tambah Jadwal
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($jadwal)): ?>
                                <div class="alert alert-info">Tidak ada jadwal ditemukan</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Hari</th>
                                                <th>Kelas</th>
                                                <th>Mata Pelajaran</th>
                                                <th>Guru</th>
                                                <th>Jam</th>
                                                <th>Ruang</th>
                                                <th>Semester</th>
                                                <th>Tahun Ajaran</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($jadwal as $i => $j): ?>
                                                <tr>
                                                    <td><?= $i + 1 ?></td>
                                                    <td><?= $nama_hari[$j['hari']] ?? $j['hari'] ?></td>
                                                    <td><?= htmlspecialchars($j['nama_kelas']) ?></td>
                                                    <td><?= htmlspecialchars($j['nama_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($j['nama_guru']) ?></td>
                                                    <td><?= htmlspecialchars($j['jam_mulai']) ?> - <?= htmlspecialchars($j['jam_selesai']) ?></td>
                                                    <td><?= htmlspecialchars($j['ruang_kelas']) ?></td>
                                                    <td><?= htmlspecialchars($j['semester']) ?></td>
                                                    <td><?= htmlspecialchars($j['tahun_ajaran']) ?></td>
                                                    <td>
                                                        <a href="edit.php?id=<?= $j['jadwal_id'] ?>" class="btn btn-sm btn-warning">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="hapus.php?id=<?= $j['jadwal_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus jadwal ini?')">
                                                            <i class="bi bi-trash"></i>
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