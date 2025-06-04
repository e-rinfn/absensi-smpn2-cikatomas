<?php
require_once __DIR__ . '/../../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil parameter filter
$jadwal_id = $_GET['jadwal_id'] ?? 0;
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? 'all';
$keyword = $_GET['keyword'] ?? '';

// Validasi apakah guru mengajar jadwal ini
$stmt = $pdo->prepare("SELECT j.*, k.nama_kelas, m.nama_mapel 
                      FROM jadwal_pelajaran j
                      JOIN kelas k ON j.kelas_id = k.kelas_id
                      JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                      WHERE j.jadwal_id = ? AND j.guru_id = ?");
$stmt->execute([$jadwal_id, $_SESSION['user_id']]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    header('Location: ../jadwal/index.php');
    exit;
}

// Query dasar untuk absensi
$sql = "SELECT a.*, m.nis, m.nama_lengkap 
        FROM absensi a
        JOIN murid m ON a.murid_id = m.murid_id
        WHERE a.jadwal_id = ? AND a.tanggal = ?";

$params = [$jadwal_id, $tanggal];

// Tambahkan filter status
if ($status_filter !== 'all') {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}

// Tambahkan filter keyword
if (!empty($keyword)) {
    $sql .= " AND (m.nis LIKE ? OR m.nama_lengkap LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

$sql .= " ORDER BY m.nama_lengkap";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absensi = $stmt->fetchAll();

// Hitung statistik
$total_murid = count($absensi);
$hadir = 0;
$sakit = 0;
$izin = 0;
$alpha = 0;

foreach ($absensi as $a) {
    switch ($a['status']) {
        case 'hadir':
            $hadir++;
            break;
        case 'sakit':
            $sakit++;
            break;
        case 'izin':
            $izin++;
            break;
        case 'alpha':
            $alpha++;
            break;
    }
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
                <h3>REKAP ABSENSI</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Filter Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Filter Data</h4>
                        </div>
                        <div class="card-body">
                            <form method="get" class="row g-3">
                                <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                                <input type="hidden" name="tanggal" value="<?= $tanggal ?>">

                                <div class="col-md-4">
                                    <label class="form-label">Status Kehadiran</label>
                                    <select name="status" class="form-select">
                                        <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>Semua Status</option>
                                        <option value="hadir" <?= $status_filter == 'hadir' ? 'selected' : '' ?>>Hadir</option>
                                        <option value="sakit" <?= $status_filter == 'sakit' ? 'selected' : '' ?>>Sakit</option>
                                        <option value="izin" <?= $status_filter == 'izin' ? 'selected' : '' ?>>Izin</option>
                                        <option value="alpha" <?= $status_filter == 'alpha' ? 'selected' : '' ?>>Alpha</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Cari NIS/Nama Murid</label>
                                    <input type="text" name="keyword" class="form-control"
                                        placeholder="Masukkan NIS atau nama murid" value="<?= htmlspecialchars($keyword) ?>">
                                </div>

                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="rekap.php?jadwal_id=<?= $jadwal_id ?>&tanggal=<?= $tanggal ?>"
                                        class="btn btn-secondary">
                                        <i class="fas fa-sync"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Jadwal and Stats Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4><?= htmlspecialchars($jadwal['nama_mapel']) ?> - <?= htmlspecialchars($jadwal['nama_kelas']) ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($tanggal)) ?></p>
                                    <p><strong>Hari:</strong> <?= $jadwal['hari'] ?></p>
                                    <p><strong>Jam:</strong> <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <h5>Statistik Kehadiran</h5>
                                        <p>Hadir: <?= $hadir ?> murid</p>
                                        <p>Sakit: <?= $sakit ?> murid</p>
                                        <p>Izin: <?= $izin ?> murid</p>
                                        <p>Alpha: <?= $alpha ?> murid</p>
                                        <p>Total: <?= $total_murid ?> murid</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Absensi -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Daftar Absensi Murid</h5>
                            <div>
                                <span class="badge bg-primary">
                                    Total Data: <?= $total_murid ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($absensi)): ?>
                                <div class="alert alert-warning">Tidak ada data absensi yang sesuai dengan filter.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="10%">NIS</th>
                                                <th width="30%">Nama Murid</th>
                                                <th width="15%">Status</th>
                                                <th width="45%">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($absensi as $a):
                                                $status_class = '';
                                                if ($a['status'] == 'hadir') $status_class = 'text-success font-weight-bold';
                                                elseif ($a['status'] == 'alpha') $status_class = 'text-danger font-weight-bold';
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($a['nis']) ?></td>
                                                    <td><?= htmlspecialchars($a['nama_lengkap']) ?></td>
                                                    <td class="<?= $status_class ?>"><?= ucfirst($a['status']) ?></td>
                                                    <td><?= htmlspecialchars($a['keterangan']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="input.php?jadwal_id=<?= $jadwal_id ?>&tanggal=<?= $tanggal ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Absensi
                        </a>
                        <a href="../jadwal/" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Jadwal
                        </a>
                    </div>
                </section>
            </div>
        </div>
        <!-- Main end -->
    </div>

    <!-- Javascript -->
    <script src="<?= $base_url ?>/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= $base_url ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $base_url ?>/assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="<?= $base_url ?>/assets/js/pages/dashboard.js"></script>
    <script src="<?= $base_url ?>/assets/js/main.js"></script>
</body>