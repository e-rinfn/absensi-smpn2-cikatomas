<?php
require_once '../../../includes/auth.php';

// Cek apakah user admin
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Ambil parameter filter
$kelasFilter = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$waliFilter = isset($_GET['wali']) ? $_GET['wali'] : '';

// Bangun kondisi WHERE dan parameter binding
$where = [];
$params = [];

if (!empty($kelasFilter)) {
    $where[] = "m.kelas_id = :kelas_id";
    $params[':kelas_id'] = $kelasFilter;
}

if (!empty($waliFilter)) {
    $where[] = "m.wali_murid_id = :wali_id";
    $params[':wali_id'] = $waliFilter;
}

// Susun SQL WHERE
$whereSQL = '';
if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

// Query utama untuk mengambil data murid dengan limit dan offset
$query = "SELECT m.*, k.nama_kelas, u.full_name AS nama_wali 
          FROM murid m 
          JOIN kelas k ON m.kelas_id = k.kelas_id 
          LEFT JOIN users u ON m.wali_murid_id = u.user_id 
          $whereSQL 
          ORDER BY m.nama_lengkap 
          LIMIT :limit OFFSET :offset";

// Tambahkan limit dan offset ke parameter
$params[':limit'] = (int)$limit;
$params[':offset'] = (int)$offset;

// Prepare dan execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$murid = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Query untuk hitung total data (untuk pagination)
$countQuery = "SELECT COUNT(*) 
               FROM murid m 
               JOIN kelas k ON m.kelas_id = k.kelas_id 
               LEFT JOIN users u ON m.wali_murid_id = u.user_id 
               $whereSQL";

$stmt = $pdo->prepare($countQuery);

// Buat array params tanpa limit & offset untuk count
$paramsCount = $params;
unset($paramsCount[':limit'], $paramsCount[':offset']);

$stmt->execute($paramsCount);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Ambil data kelas untuk dropdown filter
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll(PDO::FETCH_ASSOC);

// Ambil data wali murid untuk dropdown filter
$wali = $pdo->query("SELECT * FROM users WHERE role = 'wali_murid' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
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
                <h3>DAFTAR MURID</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Daftar Murid</span>
                            <a href="tambah.php" class="btn btn-primary btn-sm">
                                Tambah Murid
                            </a>
                        </div>
                        <div class="card-body">
                            <form method="get" class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <select name="kelas" class="form-control">
                                        <option value="">Semua Kelas</option>
                                        <?php foreach ($kelas as $k): ?>
                                            <option value="<?= $k['kelas_id'] ?>" <?= $kelasFilter == $k['kelas_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($k['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select name="wali" class="form-control">
                                        <option value="">Semua Wali</option>
                                        <?php foreach ($wali as $w): ?>
                                            <option value="<?= $w['user_id'] ?>" <?= $waliFilter == $w['user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($w['full_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                    <a href="<?= basename($_SERVER['PHP_SELF']) ?>" class="btn btn-secondary w-100">Reset Filter</a>
                                </div>
                            </form>


                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>NIS</th>
                                            <th>Nama Lengkap</th>
                                            <th>Kelas</th>
                                            <th>Wali Murid</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($murid)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada data murid</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($murid as $m): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($m['nis']) ?></td>
                                                    <td><?= htmlspecialchars($m['nama_lengkap']) ?></td>
                                                    <td><?= htmlspecialchars($m['nama_kelas']) ?></td>
                                                    <td><?= $m['nama_wali'] ? htmlspecialchars($m['nama_wali']) : '-' ?></td>
                                                    <td>
                                                        <a href="edit.php?id=<?= $m['murid_id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="hapus.php?id=<?= $m['murid_id'] ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus murid ini?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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