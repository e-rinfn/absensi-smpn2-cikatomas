<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

$mapel_id = $_GET['id'] ?? 0;

// Cek apakah mata pelajaran ada
$stmt = $pdo->prepare("SELECT * FROM mata_pelajaran WHERE mapel_id = ?");
$stmt->execute([$mapel_id]);
$mapel = $stmt->fetch();

if (!$mapel) {
    $_SESSION['error'] = "Mata pelajaran tidak ditemukan!";
    header('Location: index.php');
    exit;
}

// Cek apakah mata pelajaran digunakan di jadwal
$stmt = $pdo->prepare("SELECT COUNT(*) FROM jadwal_pelajaran WHERE mapel_id = ?");
$stmt->execute([$mapel_id]);
$used_in_jadwal = $stmt->fetchColumn() > 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM guru_mapel WHERE mapel_id = ?");
$stmt->execute([$mapel_id]);
$used_in_guru_mapel = $stmt->fetchColumn() > 0;

if ($used_in_jadwal || $used_in_guru_mapel) {
    $_SESSION['error'] = "Mata pelajaran tidak dapat dihapus karena masih digunakan di data lain!";
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("DELETE FROM mata_pelajaran WHERE mapel_id = ?");
        $stmt->execute([$mapel_id]);

        $_SESSION['success'] = "Mata pelajaran berhasil dihapus!";
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus mata pelajaran: " . $e->getMessage();
        header('Location: index.php');
        exit;
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
                <h3>Judul Halaman!</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <div class="alert alert-warning">
                        <p>Anda yakin ingin menghapus mata pelajaran berikut?</p>
                        <ul>
                            <li>Kode: <?= htmlspecialchars($mapel['kode_mapel']) ?></li>
                            <li>Nama: <?= htmlspecialchars($mapel['nama_mapel']) ?></li>
                        </ul>
                    </div>

                    <form method="POST">
                        <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                    </form>

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