<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

$mapel_id = $_GET['id'] ?? 0;

// Ambil data mata pelajaran
$stmt = $pdo->prepare("SELECT * FROM mata_pelajaran WHERE mapel_id = ?");
$stmt->execute([$mapel_id]);
$mapel = $stmt->fetch();

if (!$mapel) {
    $_SESSION['error'] = "Mata pelajaran tidak ditemukan!";
    header('Location: index.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_mapel = trim($_POST['kode_mapel']);
    $nama_mapel = trim($_POST['nama_mapel']);
    $deskripsi = trim($_POST['deskripsi']);

    // Validasi
    if (empty($kode_mapel)) {
        $errors[] = "Kode mata pelajaran harus diisi";
    }

    if (empty($nama_mapel)) {
        $errors[] = "Nama mata pelajaran harus diisi";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE mata_pelajaran SET kode_mapel = ?, nama_mapel = ?, deskripsi = ? WHERE mapel_id = ?");
            $stmt->execute([$kode_mapel, $nama_mapel, $deskripsi, $mapel_id]);

            $_SESSION['success'] = "Data mata pelajaran berhasil diperbarui!";
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Gagal memperbarui mata pelajaran: " . $e->getMessage();
        }
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

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0">Form Edit Mata Pelajaran</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3 mt-3">
                                    <div class="form-group">
                                        <label>Kode Mata Pelajaran</label>
                                        <input type="text" name="kode_mapel" class="form-control" value="<?= htmlspecialchars($mapel['kode_mapel']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-group">
                                        <label>Nama Mata Pelajaran</label>
                                        <input type="text" name="nama_mapel" class="form-control" value="<?= htmlspecialchars($mapel['nama_mapel']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <div class="form-group">
                                        <label>Deskripsi</label>
                                        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($mapel['deskripsi']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </div>
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