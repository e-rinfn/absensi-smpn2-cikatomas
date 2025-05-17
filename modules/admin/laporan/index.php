<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Filter parameter
$kelas_id = $_GET['kelas_id'] ?? null;
$mapel_id = $_GET['mapel_id'] ?? null;
$bulan = $_GET['bulan'] ?? date('Y-m');
$tipe_laporan = $_GET['tipe'] ?? 'harian';

// Ambil data untuk filter
$stmt_kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas");
$kelas_list = $stmt_kelas->fetchAll();

$stmt_mapel = $pdo->query("SELECT * FROM mata_pelajaran ORDER BY nama_mapel");
$mapel_list = $stmt_mapel->fetchAll();

// Query berdasarkan filter
$where = [];
$params = [];

if ($kelas_id) {
    $where[] = "m.kelas_id = ?";
    $params[] = $kelas_id;
}

if ($mapel_id) {
    $where[] = "j.mapel_id = ?";
    $params[] = $mapel_id;
}

$where[] = "DATE_FORMAT(a.tanggal, '%Y-%m') = ?";
$params[] = $bulan;

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Laporan Harian
if ($tipe_laporan == 'harian') {
    $sql = "SELECT a.tanggal, 
                   COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                   COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
                   COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
                   COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
                   COUNT(*) as total
            FROM absensi a
            JOIN murid m ON a.murid_id = m.murid_id
            JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
            $where_clause
            GROUP BY a.tanggal
            ORDER BY a.tanggal DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $laporan = $stmt->fetchAll();
}
// Laporan Per Mata Pelajaran
elseif ($tipe_laporan == 'mapel') {
    $sql = "SELECT m.nama_mapel,
                   COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                   COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
                   COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
                   COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
                   COUNT(*) as total
            FROM absensi a
            JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
            JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
            $where_clause
            GROUP BY m.mapel_id
            ORDER BY m.nama_mapel";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $laporan = $stmt->fetchAll();
}
// Laporan Per Kelas
elseif ($tipe_laporan == 'kelas') {
    $sql = "SELECT k.nama_kelas,
                   COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                   COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
                   COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
                   COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
                   COUNT(*) as total
            FROM absensi a
            JOIN murid m ON a.murid_id = m.murid_id
            JOIN kelas k ON m.kelas_id = k.kelas_id
            $where_clause
            GROUP BY k.kelas_id
            ORDER BY k.nama_kelas";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $laporan = $stmt->fetchAll();
}

// Untuk select kelas dan mapel di form
$selected_kelas = $kelas_id;
$selected_mapel = $mapel_id;
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
                <h3>Judul Halaman!</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Tipe Laporan</label>
                                    <select name="tipe" class="form-select">
                                        <option value="harian" <?= $tipe_laporan == 'harian' ? 'selected' : '' ?>>Harian</option>
                                        <option value="mapel" <?= $tipe_laporan == 'mapel' ? 'selected' : '' ?>>Per Mata Pelajaran</option>
                                        <option value="kelas" <?= $tipe_laporan == 'kelas' ? 'selected' : '' ?>>Per Kelas</option>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Kelas</label>
                                    <select name="kelas_id" class="form-select">
                                        <option value="">Semua Kelas</option>
                                        <?php foreach ($kelas_list as $k): ?>
                                            <option value="<?= $k['kelas_id'] ?>" <?= $selected_kelas == $k['kelas_id'] ? 'selected' : '' ?>>
                                                <?= $k['nama_kelas'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Mata Pelajaran</label>
                                    <select name="mapel_id" class="form-select">
                                        <option value="">Semua Mapel</option>
                                        <?php foreach ($mapel_list as $m): ?>
                                            <option value="<?= $m['mapel_id'] ?>" <?= $selected_mapel == $m['mapel_id'] ? 'selected' : '' ?>>
                                                <?= $m['nama_mapel'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Bulan</label>
                                    <input type="month" name="bulan" value="<?= $bulan ?>" class="form-control">
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabel Laporan -->
                    <div class="card">
                        <div class="card-body">
                            <?php if (empty($laporan)): ?>
                                <div class="alert alert-info">Tidak ada data absensi untuk filter yang dipilih</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <?php if ($tipe_laporan == 'harian'): ?>
                                                    <th>Tanggal</th>
                                                <?php elseif ($tipe_laporan == 'mapel'): ?>
                                                    <th>Mata Pelajaran</th>
                                                <?php elseif ($tipe_laporan == 'kelas'): ?>
                                                    <th>Kelas</th>
                                                <?php endif; ?>
                                                <th>Hadir</th>
                                                <th>Sakit</th>
                                                <th>Izin</th>
                                                <th>Alpha</th>
                                                <th>Total</th>
                                                <th>Persentase Hadir</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($laporan as $row): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($tipe_laporan == 'harian'): ?>
                                                            <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                                                        <?php elseif ($tipe_laporan == 'mapel'): ?>
                                                            <?= $row['nama_mapel'] ?>
                                                        <?php elseif ($tipe_laporan == 'kelas'): ?>
                                                            <?= $row['nama_kelas'] ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $row['hadir'] ?></td>
                                                    <td><?= $row['sakit'] ?></td>
                                                    <td><?= $row['izin'] ?></td>
                                                    <td><?= $row['alpha'] ?></td>
                                                    <td><?= $row['total'] ?></td>
                                                    <td>
                                                        <?= $row['total'] > 0 ? round(($row['hadir'] / $row['total']) * 100, 2) : 0 ?>%
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3">
                                    <?php
                                    $export_url = "export.php?" . http_build_query($_GET);
                                    $pdf_url = "pdf.php?" . http_build_query($_GET);
                                    ?>
                                    <a href="<?= $export_url ?>" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export ke Excel
                                    </a>
                                    <a href="<?= $pdf_url ?>" class="btn btn-danger">
                                        <i class="fas fa-file-pdf"></i> Cetak PDF
                                    </a>
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