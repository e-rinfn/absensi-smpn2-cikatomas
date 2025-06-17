<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

$search_raw = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = '%' . $search_raw . '%';

// Query untuk mengambil data kelas dengan kondisi search
$sql = "SELECT k.*, u.full_name AS wali_kelas 
        FROM kelas k
        LEFT JOIN users u ON k.wali_kelas_id = u.user_id
        WHERE k.nama_kelas LIKE :search1 
           OR k.tingkat LIKE :search2 
           OR k.tahun_ajaran LIKE :search3 
           OR u.full_name LIKE :search4
        ORDER BY k.tingkat, k.nama_kelas";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'search1' => $search_sql,
    'search2' => $search_sql,
    'search3' => $search_sql,
    'search4' => $search_sql
]);
$kelas = $stmt->fetchAll();

// Hitung jumlah murid per kelas
$jumlah_murid = [];
foreach ($kelas as $k) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM murid WHERE kelas_id = ?");
    $stmt->execute([$k['kelas_id']]);
    $jumlah_murid[$k['kelas_id']] = $stmt->fetchColumn();
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
                <h3>DAFTAR KELAS</h3>
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
                            <span>Daftar Kelas</span>
                            <div class="d-flex align-items-center">
                                <form method="get" class="d-flex me-2">
                                    <input type="text" name="search" class="form-control form-control-sm me-2"
                                        value="<?= htmlspecialchars($search_raw) ?>" placeholder="Cari kelas...">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Cari</button>
                                </form>
                                <a href="index.php" class="btn btn-secondary btn-sm">
                                    Reset
                                </a>
                                <a href="tambah.php" class="btn btn-primary btn-sm ms-3">
                                    Tambah Kelas
                                </a>
                            </div>
                        </div>

                        <div class="card-body">
                            <?php if ($search_raw && empty($kelas)): ?>
                                <div class="alert alert-warning">Tidak ditemukan kelas dengan kata kunci "<?= htmlspecialchars($search_raw) ?>"</div>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Kelas</th>
                                            <th>Tingkat</th>
                                            <th>Tahun Ajaran</th>
                                            <th>Wali Kelas</th>
                                            <th>Jumlah Murid</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($kelas)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Belum ada data kelas</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($kelas as $i => $k): ?>
                                                <tr>
                                                    <td><?= $i + 1 ?></td>
                                                    <td><?= htmlspecialchars($k['nama_kelas']) ?></td>
                                                    <td><?= htmlspecialchars($k['tingkat']) ?></td>
                                                    <td><?= htmlspecialchars($k['tahun_ajaran']) ?></td>
                                                    <td><?= $k['wali_kelas'] ? htmlspecialchars($k['wali_kelas']) : '-' ?></td>
                                                    <td><?= $jumlah_murid[$k['kelas_id']] ?></td>
                                                    <td>
                                                        <a href="edit.php?id=<?= $k['kelas_id'] ?>" class="btn btn-sm btn-warning">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </a>
                                                        <a href="hapus.php?id=<?= $k['kelas_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kelas ini?')">
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
    <script>
        // Fokuskan input search saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
    <!-- Javascript add end -->

    <!-- Javascript template mazer start -->
    <script src="<?= $base_url ?>/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= $base_url ?>/assets/js/bootstrap.bundle.min.js"></script>

    <script src="<?= $base_url ?>/assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="<?= $base_url ?>/assets/js/pages/dashboard.js"></script>

    <script src="<?= $base_url ?>/assets/js/main.js"></script>
    <!-- Javascrip template mazer end -->
</body>