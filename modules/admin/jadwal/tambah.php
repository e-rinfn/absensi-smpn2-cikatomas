<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil data untuk dropdown
$stmt = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas");
$kelas = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM mata_pelajaran ORDER BY nama_mapel");
$mapel = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'guru' ORDER BY full_name");
$guru = $stmt->fetchAll();

// Proses form tambah
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas_id = $_POST['kelas_id'];
    $mapel_id = $_POST['mapel_id'];
    $guru_id = $_POST['guru_id'];
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    // Validasi jam
    if (strtotime($jam_mulai) >= strtotime($jam_selesai)) {
        $_SESSION['error'] = "Jam selesai harus setelah jam mulai";
    } else {
        // Cek konflik jadwal
        $stmt = $pdo->prepare("SELECT * FROM jadwal_pelajaran 
                              WHERE kelas_id = ? AND hari = ? 
                              AND ((jam_mulai <= ? AND jam_selesai >= ?) 
                              OR (jam_mulai <= ? AND jam_selesai >= ?))");
        $stmt->execute([$kelas_id, $hari, $jam_mulai, $jam_mulai, $jam_selesai, $jam_selesai]);

        if ($stmt->fetch()) {
            $_SESSION['error'] = "Konflik jadwal dengan kelas ini";
        } else {
            // Insert ke database
            $stmt = $pdo->prepare("INSERT INTO jadwal_pelajaran 
                                  (kelas_id, mapel_id, guru_id, hari, jam_mulai, jam_selesai) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kelas_id, $mapel_id, $guru_id, $hari, $jam_mulai, $jam_selesai]);

            $_SESSION['success'] = "Jadwal berhasil ditambahkan";
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
                <h3>JADWAL PELAJARAN</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0">Form Tambah Jadwal</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3 mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Kelas</label>
                                        <select name="kelas_id" class="form-select" required>
                                            <option value="">Pilih Kelas</option>
                                            <?php foreach ($kelas as $k): ?>
                                                <option value="<?= $k['kelas_id'] ?>" <?= isset($_POST['kelas_id']) && $_POST['kelas_id'] == $k['kelas_id'] ? 'selected' : '' ?>>
                                                    <?= $k['nama_kelas'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mata Pelajaran</label>
                                        <select name="mapel_id" class="form-select" required>
                                            <option value="">Pilih Mata Pelajaran</option>
                                            <?php foreach ($mapel as $m): ?>
                                                <option value="<?= $m['mapel_id'] ?>" <?= isset($_POST['mapel_id']) && $_POST['mapel_id'] == $m['mapel_id'] ? 'selected' : '' ?>>
                                                    <?= $m['nama_mapel'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Guru Pengajar</label>
                                        <select name="guru_id" class="form-select" required>
                                            <option value="">Pilih Guru</option>
                                            <?php foreach ($guru as $g): ?>
                                                <option value="<?= $g['user_id'] ?>" <?= isset($_POST['guru_id']) && $_POST['guru_id'] == $g['user_id'] ? 'selected' : '' ?>>
                                                    <?= $g['full_name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Hari</label>
                                        <select name="hari" class="form-select" required>
                                            <option value="">Pilih Hari</option>
                                            <option value="Senin" <?= isset($_POST['hari']) && $_POST['hari'] == 'Senin' ? 'selected' : '' ?>>Senin</option>
                                            <option value="Selasa" <?= isset($_POST['hari']) && $_POST['hari'] == 'Selasa' ? 'selected' : '' ?>>Selasa</option>
                                            <option value="Rabu" <?= isset($_POST['hari']) && $_POST['hari'] == 'Rabu' ? 'selected' : '' ?>>Rabu</option>
                                            <option value="Kamis" <?= isset($_POST['hari']) && $_POST['hari'] == 'Kamis' ? 'selected' : '' ?>>Kamis</option>
                                            <option value="Jumat" <?= isset($_POST['hari']) && $_POST['hari'] == 'Jumat' ? 'selected' : '' ?>>Jumat</option>
                                            <option value="Sabtu" <?= isset($_POST['hari']) && $_POST['hari'] == 'Sabtu' ? 'selected' : '' ?>>Sabtu</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Jam Mulai</label>
                                        <input type="time" name="jam_mulai" class="form-control"
                                            value="<?= $_POST['jam_mulai'] ?? '' ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jam Selesai</label>
                                        <input type="time" name="jam_selesai" class="form-control"
                                            value="<?= $_POST['jam_selesai'] ?? '' ?>" required>
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