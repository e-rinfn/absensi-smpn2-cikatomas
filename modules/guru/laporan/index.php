<?php
require_once __DIR__ . '/../../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil semua kelas yang diajar oleh guru ini
$stmt = $pdo->prepare("SELECT DISTINCT k.kelas_id, k.nama_kelas 
                      FROM jadwal_pelajaran j
                      JOIN kelas k ON j.kelas_id = k.kelas_id
                      WHERE j.guru_id = ?
                      ORDER BY k.nama_kelas");
$stmt->execute([$_SESSION['user_id']]);
$kelas = $stmt->fetchAll();

// Ambil semua mata pelajaran yang diajar oleh guru ini
$stmt = $pdo->prepare("SELECT DISTINCT m.mapel_id, m.nama_mapel 
                      FROM jadwal_pelajaran j
                      JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                      WHERE j.guru_id = ?
                      ORDER BY m.nama_mapel");
$stmt->execute([$_SESSION['user_id']]);
$mapel = $stmt->fetchAll();

// Proses filter laporan
$kelas_id = $_GET['kelas_id'] ?? '';
$mapel_id = $_GET['mapel_id'] ?? '';
$bulan = $_GET['bulan'] ?? date('Y-m');

$where = [];
$params = [$_SESSION['user_id']];

if (!empty($kelas_id)) {
    $where[] = "j.kelas_id = ?";
    $params[] = $kelas_id;
}

if (!empty($mapel_id)) {
    $where[] = "j.mapel_id = ?";
    $params[] = $mapel_id;
}

$where_clause = !empty($where) ? "AND " . implode(" AND ", $where) : "";

// Query untuk rekap absensi
$query = "SELECT
        a.tanggal,
        m.nama_mapel,
        k.nama_kelas,
        COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
        COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
        COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
        COUNT(*) as total
        FROM absensi a
        JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
        JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
        JOIN kelas k ON j.kelas_id = k.kelas_id
        WHERE j.guru_id = ?
        AND DATE_FORMAT(a.tanggal, '%Y-%m') = ?
        $where_clause
        GROUP BY a.tanggal, m.nama_mapel, k.nama_kelas
        ORDER BY a.tanggal DESC";

$stmt = $pdo->prepare($query);
$stmt->execute(array_merge($params, [$bulan]));
$rekap = $stmt->fetchAll();

// Hitung total
$total_hadir = 0;
$total_sakit = 0;
$total_izin = 0;
$total_alpha = 0;
foreach ($rekap as $r) {
    $total_hadir += $r['hadir'];
    $total_sakit += $r['sakit'];
    $total_izin += $r['izin'];
    $total_alpha += $r['alpha'];
}
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
                <h3>LAPORAN</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <div class="card mb-4 shadow-sm">
                        <div class="card-header">
                            <h6 class="mb-0">Filter Laporan</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label for="kelas_id" class="form-label">Kelas</label>
                                    <select name="kelas_id" id="kelas_id" class="form-select">
                                        <option value="">Semua Kelas</option>
                                        <?php foreach ($kelas as $k): ?>
                                            <option value="<?= $k['kelas_id'] ?>" <?= ($k['kelas_id'] == $kelas_id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($k['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="mapel_id" class="form-label">Mata Pelajaran</label>
                                    <select name="mapel_id" id="mapel_id" class="form-select">
                                        <option value="">Semua Mapel</option>
                                        <?php foreach ($mapel as $m): ?>
                                            <option value="<?= $m['mapel_id'] ?>" <?= ($m['mapel_id'] == $mapel_id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['nama_mapel']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="bulan" class="form-label">Bulan</label>
                                    <input type="month" name="bulan" id="bulan" class="form-control" value="<?= $bulan ?>">
                                </div>

                                <div class="col-md-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <a href="index.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-sync-alt me-1"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>


                    <div class="card mb-4 shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Statistik Absensi</h5>
                        </div>
                        <div class="card-body py-3">
                            <div class="row text-center g-2">
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded text-white bg-success">
                                        <h6 class="mb-1">Hadir</h6>
                                        <p class="h3 mb-0"><?= $total_hadir ?></p>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded text-dark bg-warning">
                                        <h6 class="mb-1">Sakit</h6>
                                        <p class="h3 mb-0"><?= $total_sakit ?></p>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded text-white bg-info">
                                        <h6 class="mb-1">Izin</h6>
                                        <p class="h3 mb-0"><?= $total_izin ?></p>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded text-white bg-danger">
                                        <h6 class="mb-1">Alpha</h6>
                                        <p class="h3 mb-0"><?= $total_alpha ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Detail Absensi</h5>
                        </div>
                        <div class="card-body p-3">
                            <?php if (empty($rekap)): ?>
                                <div class="alert alert-warning mb-0">Tidak ada data absensi untuk filter yang dipilih.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-hover align-middle text-center mb-2">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Mapel</th>
                                                <th>Kelas</th>
                                                <th class="text-success">Hadir</th>
                                                <th class="text-warning">Sakit</th>
                                                <th class="text-info">Izin</th>
                                                <th class="text-danger">Alpha</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rekap as $r): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                                                    <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($r['nama_kelas']) ?></td>
                                                    <td><?= $r['hadir'] ?></td>
                                                    <td><?= $r['sakit'] ?></td>
                                                    <td><?= $r['izin'] ?></td>
                                                    <td><?= $r['alpha'] ?></td>
                                                    <td><strong><?= $r['total'] ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- <div class="text-end">
                                    <a href="export.php?<?= http_build_query($_GET) ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-file-excel"></i> Export ke Excel
                                    </a>
                                </div> -->
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