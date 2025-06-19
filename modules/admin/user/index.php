<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Search functionality
$search_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '%' . $search_raw . '%';

// Query untuk mengambil data pengguna dengan kondisi search
$sql = "SELECT u.* 
        FROM users u
        WHERE u.username LIKE :search1 
           OR u.full_name LIKE :search2 
           OR u.email LIKE :search3 
           OR u.role LIKE :search4
        ORDER BY u.role, u.full_name";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'search1' => $search_sql,
    'search2' => $search_sql,
    'search3' => $search_sql,
    'search4' => $search_sql
]);
$users = $stmt->fetchAll();

// Jika ingin menampilkan jumlah data terkait (misalnya jumlah kelas untuk wali kelas)
$related_counts = [];
foreach ($users as $user) {
    if ($user['role'] == 'wali_murid') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM murid WHERE wali_murid_id = ?");
        $stmt->execute([$user['user_id']]);
        $related_counts[$user['user_id']] = $stmt->fetchColumn();
    } elseif ($user['role'] == 'wali_kelas') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE wali_kelas_id = ?");
        $stmt->execute([$user['user_id']]);
        $related_counts[$user['user_id']] = $stmt->fetchColumn();
    }
}
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
                <h3>DAFTAR PENGGUNA</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'];
                                                            unset($_SESSION['success']); ?></div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Daftar Pengguna</span>
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
                                    Tambah Pengguna
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Username</th>
                                            <th>Nama Lengkap</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <!-- <th>Jumlah Terkait</th> -->
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Tidak ada data pengguna</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $i => $user): ?>
                                                <tr>
                                                    <td><?= $i + 1 ?></td>
                                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><?= htmlspecialchars($user['role']) ?></td>
                                                    <!-- <td>
                                                        <?php if (isset($related_counts[$user['user_id']])): ?>
                                                            <?= $related_counts[$user['user_id']] ?>
                                                        <?php else: ?>
                                                            0
                                                        <?php endif; ?>
                                                    </td> -->
                                                    <td>
                                                        <a href="edit.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="hapus.php?id=<?= $user['user_id'] ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus murid ini?')">
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