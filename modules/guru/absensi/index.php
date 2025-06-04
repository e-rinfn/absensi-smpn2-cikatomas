<?php
require_once '../../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil parameter filter
$mapel_id = $_GET['mapel_id'] ?? null;
$kelas_id = $_GET['kelas_id'] ?? null;
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-t');

// Query untuk mendapatkan daftar absensi
$sql = "SELECT a.*, m.nis, m.nama_lengkap, k.nama_kelas, mp.nama_mapel, j.hari, u.full_name AS guru_pengampu
        FROM absensi a
        JOIN murid m ON a.murid_id = m.murid_id
        JOIN kelas k ON m.kelas_id = k.kelas_id
        JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
        JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
        JOIN users u ON a.guru_id = u.user_id
        WHERE a.guru_id = :guru_id";

$params = [':guru_id' => $_SESSION['user_id']];

// Tambahkan filter jika ada
if ($mapel_id) {
    $sql .= " AND j.mapel_id = :mapel_id";
    $params[':mapel_id'] = $mapel_id;
}

if ($kelas_id) {
    $sql .= " AND m.kelas_id = :kelas_id";
    $params[':kelas_id'] = $kelas_id;
}

$sql .= " AND a.tanggal BETWEEN :tgl_mulai AND :tgl_selesai
          ORDER BY a.tanggal DESC, j.hari, mp.nama_mapel, m.nama_lengkap";

$params[':tgl_mulai'] = $tanggal_mulai;
$params[':tgl_selesai'] = $tanggal_selesai;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absensi = $stmt->fetchAll();

// Ambil daftar mata pelajaran yang diajar guru ini untuk filter
$stmt = $pdo->prepare("SELECT DISTINCT mp.mapel_id, mp.nama_mapel 
                       FROM jadwal_pelajaran j
                       JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
                       WHERE j.guru_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$mapel_options = $stmt->fetchAll();

// Ambil daftar kelas yang diajar guru ini untuk filter
$stmt = $pdo->prepare("SELECT DISTINCT k.kelas_id, k.nama_kelas 
                       FROM jadwal_pelajaran j
                       JOIN kelas k ON j.kelas_id = k.kelas_id
                       WHERE j.guru_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$kelas_options = $stmt->fetchAll();
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
                <h3>ABSENSI</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <!-- Filter Form -->
                    <div class="card mb-4 shadow-sm border">
                        <div class="card-header fw-bold">
                            Filter Absensi
                        </div>
                        <div class="card-body py-3">
                            <form method="get" class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label for="mapel_id" class="form-label mb-1">Mapel</label>
                                    <select id="mapel_id" name="mapel_id" class="form-select form-select-sm">
                                        <option value="">Semua Mapel</option>
                                        <?php foreach ($mapel_options as $mapel): ?>
                                            <option value="<?= $mapel['mapel_id'] ?>" <?= $mapel_id == $mapel['mapel_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($mapel['nama_mapel']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="kelas_id" class="form-label mb-1">Kelas</label>
                                    <select id="kelas_id" name="kelas_id" class="form-select form-select-sm">
                                        <option value="">Semua Kelas</option>
                                        <?php foreach ($kelas_options as $kelas): ?>
                                            <option value="<?= $kelas['kelas_id'] ?>" <?= $kelas_id == $kelas['kelas_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="tanggal_mulai" class="form-label mb-1">Mulai</label>
                                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>" class="form-control form-control-sm">
                                </div>

                                <div class="col-md-2">
                                    <label for="tanggal_selesai" class="form-label mb-1">Selesai</label>
                                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai) ?>" class="form-control form-control-sm">
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>


                    <!-- Tabel Absensi -->
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Data Absensi</h6>
                        </div>

                        <div class="card-body">
                            <?php if (empty($absensi)): ?>
                                <div class="alert alert-info mb-0">Tidak ada data absensi untuk filter yang dipilih.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-hover align-middle text-center">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>Hari</th>
                                                <th>Mapel</th>
                                                <th>Kelas</th>
                                                <th>NIS</th>
                                                <th>Nama</th>
                                                <th>Status</th>
                                                <th>Keterangan</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($absensi as $index => $a): ?>
                                                <tr>
                                                    <td><?= $index + 1 ?></td>
                                                    <td><?= date('d/m/Y', strtotime($a['tanggal'])) ?></td>
                                                    <td><?= htmlspecialchars($a['hari']) ?></td>
                                                    <td><?= htmlspecialchars($a['nama_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($a['nama_kelas']) ?></td>
                                                    <td><?= htmlspecialchars($a['nis']) ?></td>
                                                    <td><?= htmlspecialchars($a['nama_lengkap']) ?></td>
                                                    <td>
                                                        <?php
                                                        $badge_class = [
                                                            'hadir' => 'bg-success',
                                                            'sakit' => 'bg-info',
                                                            'izin'  => 'bg-warning text-dark',
                                                            'alpha' => 'bg-danger'
                                                        ];
                                                        $status = strtolower($a['status']);
                                                        ?>
                                                        <span class="badge <?= $badge_class[$status] ?? 'bg-secondary' ?>">
                                                            <?= ucfirst($status) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($a['keterangan']) ?></td>
                                                    <td>
                                                        <a href="edit.php?id=<?= $a['absensi_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
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