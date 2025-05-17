<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_mapel = trim($_POST['kode_mapel']);
    $nama_mapel = trim($_POST['nama_mapel']);
    $deskripsi = trim($_POST['deskripsi']);

    // Validasi
    if (empty($kode_mapel) || empty($nama_mapel)) {
        $error = 'Kode dan Nama Mata Pelajaran wajib diisi!';
    } else {
        // Cek kode mapel sudah ada
        $stmt = $pdo->prepare("SELECT * FROM mata_pelajaran WHERE kode_mapel = ?");
        $stmt->execute([$kode_mapel]);
        if ($stmt->fetch()) {
            $error = 'Kode mata pelajaran sudah digunakan!';
        } else {
            // Insert ke database
            $stmt = $pdo->prepare("INSERT INTO mata_pelajaran (kode_mapel, nama_mapel, deskripsi) 
                                  VALUES (?, ?, ?)");
            $stmt->execute([$kode_mapel, $nama_mapel, $deskripsi]);

            $_SESSION['success'] = 'Mata pelajaran berhasil ditambahkan!';
            header('Location: index.php');
            exit;
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

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Kode Mata Pelajaran</label>
                            <input type="text" name="kode_mapel" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Mata Pelajaran</label>
                            <input type="text" name="nama_mapel" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan</button>
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