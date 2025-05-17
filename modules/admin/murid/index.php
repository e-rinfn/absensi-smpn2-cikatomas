<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$where = '';
$params = [];

if (!empty($search)) {
    $where = "WHERE m.nis LIKE :search OR m.nama_lengkap LIKE :search OR k.nama_kelas LIKE :search";
    $params = [':search' => "%$search%"];
}

// Query untuk mengambil data murid
$query = "SELECT m.*, k.nama_kelas, u.full_name AS nama_wali 
          FROM murid m 
          JOIN kelas k ON m.kelas_id = k.kelas_id 
          LEFT JOIN users u ON m.wali_murid_id = u.user_id 
          $where 
          ORDER BY m.nama_lengkap 
          LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$murid = $stmt->fetchAll();

// Query untuk total data (pagination)
$countQuery = "SELECT COUNT(*) FROM murid m $where";
$stmt = $pdo->prepare($countQuery);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Ambil data kelas untuk dropdown filter
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

// Ambil data wali murid untuk dropdown
$wali = $pdo->query("SELECT * FROM users WHERE role = 'wali_murid' ORDER BY full_name")->fetchAll();
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