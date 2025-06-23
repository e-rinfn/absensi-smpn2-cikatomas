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
$semester = $_GET['semester'] ?? null;
$tahun = $_GET['tahun'] ?? date('Y');
$tipe_laporan = $_GET['tipe'] ?? 'harian';

// Ambil data untuk filter
$stmt_kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas");
$kelas_list = $stmt_kelas->fetchAll();

$stmt_mapel = $pdo->query("SELECT * FROM mata_pelajaran ORDER BY nama_mapel");
$mapel_list = $stmt_mapel->fetchAll();

// Generate tahun options (3 tahun terakhir dan 2 tahun ke depan)
$current_year = date('Y');
$years = range($current_year - 3, $current_year + 2);

// Query berdasarkan filter
$where = [];
$params = [];

if ($kelas_id) {
    if ($tipe_laporan == 'mapel') {
        $where[] = "mu.kelas_id = ?";
    } else {
        $where[] = "m.kelas_id = ?";
    }
    $params[] = $kelas_id;
}

if ($mapel_id) {
    $where[] = "j.mapel_id = ?";
    $params[] = $mapel_id;
}

// Filter berdasarkan periode
if ($semester) {
    if ($semester == 1) {
        $where[] = "(MONTH(a.tanggal) BETWEEN 1 AND 6)";
    } else {
        $where[] = "(MONTH(a.tanggal) BETWEEN 7 AND 12)";
    }
    $where[] = "YEAR(a.tanggal) = ?";
    $params[] = $tahun;
} else {
    // Default filter bulan jika tidak memilih semester
    $where[] = "DATE_FORMAT(a.tanggal, '%Y-%m') = ?";
    $params[] = $bulan;
}

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
            JOIN murid mu ON a.murid_id = mu.murid_id
            $where_clause
            GROUP BY m.mapel_id
            ORDER BY m.nama_mapel";
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
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// Untuk select kelas dan mapel di form
$selected_kelas = $kelas_id;
$selected_mapel = $mapel_id;
$selected_semester = $semester;
$selected_tahun = $tahun;
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
                <h3>LAPORAN</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="get" class="row g-3" id="filterForm">
                                <div class="col-md-3">
                                    <label class="form-label">Tipe Laporan</label>
                                    <select name="tipe" class="form-select" id="tipeSelect">
                                        <option value="harian" <?= $tipe_laporan == 'harian' ? 'selected' : '' ?>>Harian</option>
                                        <option value="mapel" <?= $tipe_laporan == 'mapel' ? 'selected' : '' ?>>Per Mata Pelajaran</option>
                                        <option value="kelas" <?= $tipe_laporan == 'kelas' ? 'selected' : '' ?>>Per Kelas</option>
                                    </select>
                                </div>

                                <div class="col-md-3" id="kelasField">
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

                                <div class="col-md-3" id="mapelField">
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
                                    <label class="form-label">Periode</label>
                                    <select name="semester" class="form-select" id="semesterSelect">
                                        <option value="">Pilih Periode</option>
                                        <option value="1" <?= $selected_semester == '1' ? 'selected' : '' ?>>Semester 1 (Jan-Jun)</option>
                                        <option value="2" <?= $selected_semester == '2' ? 'selected' : '' ?>>Semester 2 (Jul-Des)</option>
                                    </select>
                                </div>

                                <div class="col-md-2" id="tahunField">
                                    <label class="form-label">Tahun</label>
                                    <select name="tahun" class="form-select">
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?= $year ?>" <?= $selected_tahun == $year ? 'selected' : '' ?>>
                                                <?= $year ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2" id="bulanField">
                                    <label class="form-label">Bulan</label>
                                    <input type="month" name="bulan" value="<?= $bulan ?>" class="form-control">
                                </div>

                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-50">Filter</button>
                                    <a href="index.php" class="btn btn-secondary w-50 ms-3">Reset</a>
                                </div>

                            </form>
                        </div>
                    </div>

                    <!-- Script to toggle visibility -->
                    <script>
                        function toggleFields() {
                            const tipe = document.getElementById('tipeSelect').value;
                            const kelasField = document.getElementById('kelasField');
                            const mapelField = document.getElementById('mapelField');
                            const semesterSelect = document.getElementById('semesterSelect');
                            const bulanField = document.getElementById('bulanField');
                            const tahunField = document.getElementById('tahunField');

                            // Tampilkan sesuai tipe
                            kelasField.style.display = (tipe === 'kelas') ? 'block' : 'none';
                            mapelField.style.display = (tipe === 'mapel') ? 'block' : 'none';

                            // Toggle bulan/semester field
                            if (semesterSelect.value) {
                                bulanField.style.display = 'none';
                                tahunField.style.display = 'block';
                            } else {
                                bulanField.style.display = 'block';
                                tahunField.style.display = 'none';
                            }
                        }

                        document.addEventListener('DOMContentLoaded', function() {
                            document.getElementById('tipeSelect').addEventListener('change', toggleFields);
                            document.getElementById('semesterSelect').addEventListener('change', toggleFields);
                            toggleFields(); // jalankan saat pertama kali load halaman
                        });
                    </script>


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
                                                <th>No</th>
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
                                            <?php foreach ($laporan as $i => $row): ?>
                                                <tr>
                                                    <td><?= $i + 1 ?></td>
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