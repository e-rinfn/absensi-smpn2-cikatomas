<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

$errors = [];
$success = '';

// Ambil data kelas untuk dropdown
$kelas = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

// Ambil data wali murid untuk dropdown
$wali = $pdo->query("SELECT * FROM users WHERE role = 'wali_murid' ORDER BY full_name")->fetchAll();

// Proses form tambah murid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $alamat = trim($_POST['alamat']);
    $kelas_id = $_POST['kelas_id'];
    $wali_murid_id = $_POST['wali_murid_id'] ?: null;

    // Validasi
    if (empty($nis)) {
        $errors['nis'] = 'NIS wajib diisi';
    } elseif (!preg_match('/^[0-9]+$/', $nis)) {
        $errors['nis'] = 'NIS hanya boleh berisi angka';
    } else {
        // Cek NIS sudah ada
        $stmt = $pdo->prepare("SELECT * FROM murid WHERE nis = ?");
        $stmt->execute([$nis]);
        if ($stmt->fetch()) {
            $errors['nis'] = 'NIS sudah terdaftar';
        }
    }

    if (empty($nama_lengkap)) {
        $errors['nama_lengkap'] = 'Nama lengkap wajib diisi';
    }

    if (empty($kelas_id)) {
        $errors['kelas_id'] = 'Kelas wajib dipilih';
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO murid 
                                  (nis, nama_lengkap, jenis_kelamin, tanggal_lahir, alamat, kelas_id, wali_murid_id) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $nis,
                $nama_lengkap,
                $jenis_kelamin,
                $tanggal_lahir,
                $alamat,
                $kelas_id,
                $wali_murid_id
            ]);

            $_SESSION['success'] = "Murid berhasil ditambahkan!";
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Gagal menambahkan murid: ' . $e->getMessage();
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

                    <?php if (!empty($errors['database'])): ?>
                        <div class="alert alert-danger"><?= $errors['database'] ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0">Form Tambah Murid</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3 mt-3">
                                    <label for="nis" class="form-label">NIS</label>
                                    <input type="text" class="form-control <?= isset($errors['nis']) ? 'is-invalid' : '' ?>"
                                        id="nis" name="nis" value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>">
                                    <?php if (isset($errors['nis'])): ?>
                                        <div class="invalid-feedback"><?= $errors['nis'] ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control <?= isset($errors['nama_lengkap']) ? 'is-invalid' : '' ?>"
                                        id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>">
                                    <?php if (isset($errors['nama_lengkap'])): ?>
                                        <div class="invalid-feedback"><?= $errors['nama_lengkap'] ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jenis_kelamin" id="laki" value="L"
                                                    <?= ($_POST['jenis_kelamin'] ?? 'L') == 'L' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="laki">Laki-laki</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jenis_kelamin" id="perempuan" value="P"
                                                    <?= ($_POST['jenis_kelamin'] ?? '') == 'P' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="perempuan">Perempuan</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir"
                                            value="<?= htmlspecialchars($_POST['tanggal_lahir'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="2"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="kelas_id" class="form-label">Kelas</label>
                                        <select class="form-select <?= isset($errors['kelas_id']) ? 'is-invalid' : '' ?>" id="kelas_id" name="kelas_id" required>
                                            <option value="">Pilih Kelas</option>
                                            <?php foreach ($kelas as $k): ?>
                                                <option value="<?= $k['kelas_id'] ?>" <?= ($_POST['kelas_id'] ?? '') == $k['kelas_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($k['nama_kelas']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['kelas_id'])): ?>
                                            <div class="invalid-feedback"><?= $errors['kelas_id'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="wali_murid_id" class="form-label">Wali Murid (Opsional)</label>
                                        <select class="form-select" id="wali_murid_id" name="wali_murid_id">
                                            <option value="">Pilih Wali Murid</option>
                                            <?php foreach ($wali as $w): ?>
                                                <option value="<?= $w['user_id'] ?>" <?= ($_POST['wali_murid_id'] ?? '') == $w['user_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($w['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </div>
                        </div>
                    </form>
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