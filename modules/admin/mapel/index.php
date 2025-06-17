<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

$search_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '%' . $search_raw . '%';

// Query untuk mengambil data mata pelajaran dengan kondisi search
$sql = "SELECT * 
        FROM mata_pelajaran
        WHERE nama_mapel LIKE :search1 
           OR kode_mapel LIKE :search2
        ORDER BY nama_mapel";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'search1' => $search_sql,
    'search2' => $search_sql
]);
$mata_pelajaran = $stmt->fetchAll();

// Hitung jumlah kelas yang menggunakan mata pelajaran ini (jika diperlukan)
$jumlah_penggunaan = [];
foreach ($mata_pelajaran as $mapel) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mata_pelajaran WHERE mapel_id = ?");
    $stmt->execute([$mapel['mapel_id']]);
    $jumlah_penggunaan[$mapel['mapel_id']] = $stmt->fetchColumn();
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
                            <div class="d-flex align-items-center">
                                <form method="get" class="d-flex me-2">
                                    <input type="text" name="search" class="form-control form-control-sm me-2"
                                        value="<?= htmlspecialchars($search_raw) ?>" placeholder="Cari mata pelajaran...">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Cari</button>
                                </form>
                                <a href="index.php" class="btn btn-secondary btn-sm">
                                    Reset
                                </a>
                                <a href="tambah.php" class="btn btn-primary btn-sm ms-3">
                                    Tambah Mata Pelajaran
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Deskripsi</th>
                                            <!-- <th>Digunakan di Kelas</th> -->
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($mata_pelajaran)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada data mata pelajaran</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($mata_pelajaran as $i => $mapel): ?>
                                                <tr>
                                                    <td><?= $i + 1 ?></td>
                                                    <td><?= htmlspecialchars($mapel['kode_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($mapel['nama_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($mapel['deskripsi']) ?></td>
                                                    <!-- <td><?= $jumlah_penggunaan[$mapel['mapel_id']] ?? 0 ?></td> -->
                                                    <td>
                                                        <a href="edit.php?id=<?= $mapel['mapel_id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="hapus.php?id=<?= $mapel['mapel_id'] ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus mata pelajaran ini?')">
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