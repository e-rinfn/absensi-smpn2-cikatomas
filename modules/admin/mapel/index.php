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
if (!empty($search)) {
    $where = "WHERE kode_mapel LIKE :search OR nama_mapel LIKE :search";
}

// Get total mapel for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM mata_pelajaran $where");
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->execute();
$total_mapel = $stmt->fetchColumn();
$total_pages = ceil($total_mapel / $limit);

// Get mapel data
$stmt = $pdo->prepare("SELECT * FROM mata_pelajaran $where ORDER BY nama_mapel ASC LIMIT :limit OFFSET :offset");
if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$mapel_list = $stmt->fetchAll();
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
                <h3>DAFTAR MATA PELAJARAN</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success']; ?>
                            <?php unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error']; ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>


                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Daftar Mata Pelajaran</span>
                            <a href="tambah.php" class="btn btn-primary btn-sm">
                                Tambah Mata Pelajaran
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Nama Mata Pelajaran</th>
                                            <th>Deskripsi</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($mapel_list)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">Tidak ada data mata pelajaran</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($mapel_list as $mapel): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($mapel['kode_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($mapel['nama_mapel']) ?></td>
                                                    <td><?= htmlspecialchars(substr($mapel['deskripsi'], 0, 50)) ?><?= strlen($mapel['deskripsi']) > 50 ? '...' : '' ?></td>
                                                    <td>
                                                        <a href="edit.php?id=<?= $mapel['mapel_id'] ?>" class="btn btn-sm btn-warning">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="hapus.php?id=<?= $mapel['mapel_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
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