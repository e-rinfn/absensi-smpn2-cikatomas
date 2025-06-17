<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Search functionality
$search_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '%' . $search_raw . '%';

// Query untuk mengambil data murid dengan kondisi search
$sql = "SELECT m.*, k.nama_kelas, u.full_name AS nama_wali 
        FROM murid m
        JOIN kelas k ON m.kelas_id = k.kelas_id
        LEFT JOIN users u ON m.wali_murid_id = u.user_id
        WHERE m.nis LIKE :search1 
           OR m.nama_lengkap LIKE :search2 
           OR k.nama_kelas LIKE :search3 
           OR u.full_name LIKE :search4
        ORDER BY m.nama_lengkap";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':search1' => $search_sql,
    ':search2' => $search_sql,
    ':search3' => $search_sql,
    ':search4' => $search_sql
]);
$murid = $stmt->fetchAll();

// Pagination can be added here if needed
// $limit = 10;
// $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
// $offset = ($page - 1) * $limit;
// Then add LIMIT :limit OFFSET :offset to the query
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
                            <div class="d-flex align-items-center">
                                <form method="get" class="d-flex me-2">
                                    <input type="text" name="search" class="form-control form-control-sm me-2"
                                        value="<?= htmlspecialchars($search_raw) ?>" placeholder="Cari murid...">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Cari</button>
                                </form>
                                <a href="index.php" class="btn btn-secondary btn-sm">
                                    Reset
                                </a>
                                <a href="tambah.php" class="btn btn-primary btn-sm ms-3">
                                    Tambah Murid
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
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
                                            <?php foreach ($murid as $i => $m): ?>
                                                <tr>
                                                    <td><?= $i + 1 ?></td>
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