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
                <h3>EDIT ABSENSI</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="card mb-3 shadow-sm">
                        <div class="card-header d-flex align-items-center">
                            <span>Informasi Absensi</span>
                        </div>
                        <div class="card-body p-3">
                            <dl class="row mb-0">
                                <dt class="col-4 col-md-3 fw-semibold">Tanggal</dt>
                                <dd class="col-8 col-md-9"><?= date('d/m/Y', strtotime($absensi['tanggal'])) ?></dd>

                                <dt class="col-4 col-md-3 fw-semibold">Hari / Jam</dt>
                                <dd class="col-8 col-md-9"><?= htmlspecialchars($absensi['hari']) ?>, <?= date('H:i', strtotime($absensi['jam_mulai'])) ?> - <?= date('H:i', strtotime($absensi['jam_selesai'])) ?></dd>

                                <dt class="col-4 col-md-3 fw-semibold">Mata Pelajaran</dt>
                                <dd class="col-8 col-md-9"><?= htmlspecialchars($absensi['nama_mapel']) ?></dd>

                                <dt class="col-4 col-md-3 fw-semibold">Kelas</dt>
                                <dd class="col-8 col-md-9"><?= htmlspecialchars($absensi['nama_kelas']) ?></dd>

                                <dt class="col-4 col-md-3 fw-semibold">Siswa</dt>
                                <dd class="col-8 col-md-9"><?= htmlspecialchars($absensi['nis']) ?> - <?= htmlspecialchars($absensi['nama_lengkap']) ?></dd>
                            </dl>
                        </div>
                    </div>


                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header fw-semibold">
                            <i class="fas fa-edit me-2"></i>Edit Absensi
                        </div>
                        <div class="card-body py-3">
                            <form method="POST">
                                <div class="mb-2">
                                    <label for="status" class="form-label mb-1">Status</label>
                                    <select class="form-select form-select-sm" id="status" name="status" required>
                                        <option value="hadir" <?= $absensi['status'] == 'hadir' ? 'selected' : '' ?>>Hadir</option>
                                        <option value="sakit" <?= $absensi['status'] == 'sakit' ? 'selected' : '' ?>>Sakit</option>
                                        <option value="izin" <?= $absensi['status'] == 'izin' ? 'selected' : '' ?>>Izin</option>
                                        <option value="alpha" <?= $absensi['status'] == 'alpha' ? 'selected' : '' ?>>Alpha</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="keterangan" class="form-label mb-1">Keterangan</label>
                                    <textarea class="form-control form-control-sm" id="keterangan" name="keterangan" rows="2"><?= htmlspecialchars($absensi['keterangan']) ?></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Kembali
                                    </a>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-save me-1"></i> Simpan
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