<?php
require_once '../../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil ID absensi dari URL
$absensi_id = $_GET['id'] ?? 0;

// Ambil data absensi
$stmt = $pdo->prepare("SELECT a.*, m.nis, m.nama_lengkap, k.nama_kelas, 
                              mp.nama_mapel, j.hari, j.jam_mulai, j.jam_selesai
                       FROM absensi a
                       JOIN murid m ON a.murid_id = m.murid_id
                       JOIN kelas k ON m.kelas_id = k.kelas_id
                       JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
                       JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
                       WHERE a.absensi_id = ? AND a.guru_id = ?");
$stmt->execute([$absensi_id, $_SESSION['user_id']]);
$absensi = $stmt->fetch();

// Jika tidak ditemukan atau bukan milik guru ini
if (!$absensi) {
    $_SESSION['error'] = "Data absensi tidak ditemukan atau Anda tidak memiliki akses.";
    header('Location: index.php');
    exit;
}

// Proses form edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $keterangan = $_POST['keterangan'];

    // Validasi input
    if (!in_array($status, ['hadir', 'sakit', 'izin', 'alpha'])) {
        $_SESSION['error'] = "Status absensi tidak valid.";
    } else {
        // Update data absensi
        $stmt = $pdo->prepare("UPDATE absensi SET status = ?, keterangan = ? 
                              WHERE absensi_id = ? AND guru_id = ?");
        $stmt->execute([$status, $keterangan, $absensi_id, $_SESSION['user_id']]);

        $_SESSION['success'] = "Data absensi berhasil diperbarui.";
        header('Location: index.php');
        exit;
    }
}
?>


<?php include '../../../includes/header.php'; ?>

<body>
    <div id="app">
        <!-- Sidebar start -->

        <?php include '../../../includes/navigation/guru.php'; ?>

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

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-info-circle me-2"></i>Informasi Absensi
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-3 fw-bold">Tanggal</div>
                                <div class="col-md-9"><?= date('d/m/Y', strtotime($absensi['tanggal'])) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3 fw-bold">Hari/Jam</div>
                                <div class="col-md-9"><?= $absensi['hari'] ?>, <?= date('H:i', strtotime($absensi['jam_mulai'])) ?>-<?= date('H:i', strtotime($absensi['jam_selesai'])) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3 fw-bold">Mata Pelajaran</div>
                                <div class="col-md-9"><?= htmlspecialchars($absensi['nama_mapel']) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3 fw-bold">Kelas</div>
                                <div class="col-md-9"><?= htmlspecialchars($absensi['nama_kelas']) ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3 fw-bold">Siswa</div>
                                <div class="col-md-9"><?= htmlspecialchars($absensi['nis']) ?> - <?= htmlspecialchars($absensi['nama_lengkap']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-edit me-2"></i>Form Edit
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status Absensi</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="hadir" <?= $absensi['status'] == 'hadir' ? 'selected' : '' ?>>Hadir</option>
                                        <option value="sakit" <?= $absensi['status'] == 'sakit' ? 'selected' : '' ?>>Sakit</option>
                                        <option value="izin" <?= $absensi['status'] == 'izin' ? 'selected' : '' ?>>Izin</option>
                                        <option value="alpha" <?= $absensi['status'] == 'alpha' ? 'selected' : '' ?>>Alpha</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="keterangan" class="form-label">Keterangan</label>
                                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= htmlspecialchars($absensi['keterangan']) ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Simpan Perubahan
                                    </button>
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