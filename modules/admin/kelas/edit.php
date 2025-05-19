<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$kelas_id = $_GET['id'];

// Ambil data kelas
$stmt = $pdo->prepare("SELECT * FROM kelas WHERE kelas_id = ?");
$stmt->execute([$kelas_id]);
$kelas = $stmt->fetch();

if (!$kelas) {
    $_SESSION['error'] = 'Kelas tidak ditemukan';
    header('Location: index.php');
    exit;
}

// Ambil daftar guru untuk dropdown wali kelas
$stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE role = 'guru' ORDER BY full_name");
$stmt->execute();
$guru = $stmt->fetchAll();

$errors = [];
$data = [
    'nama_kelas' => $kelas['nama_kelas'],
    'tingkat' => $kelas['tingkat'],
    'tahun_ajaran' => $kelas['tahun_ajaran'],
    'wali_kelas_id' => $kelas['wali_kelas_id']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nama_kelas' => trim($_POST['nama_kelas']),
        'tingkat' => trim($_POST['tingkat']),
        'tahun_ajaran' => trim($_POST['tahun_ajaran']),
        'wali_kelas_id' => $_POST['wali_kelas_id'] ?: null
    ];

    // Validasi
    if (empty($data['nama_kelas'])) {
        $errors['nama_kelas'] = 'Nama kelas harus diisi';
    }

    if (empty($data['tingkat'])) {
        $errors['tingkat'] = 'Tingkat kelas harus diisi';
    }

    if (empty($data['tahun_ajaran'])) {
        $errors['tahun_ajaran'] = 'Tahun ajaran harus diisi';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE kelas SET 
                                 nama_kelas = ?, 
                                 tingkat = ?, 
                                 tahun_ajaran = ?, 
                                 wali_kelas_id = ? 
                                 WHERE kelas_id = ?");
            $stmt->execute([
                $data['nama_kelas'],
                $data['tingkat'],
                $data['tahun_ajaran'],
                $data['wali_kelas_id'],
                $kelas_id
            ]);

            $_SESSION['success'] = 'Data kelas berhasil diperbarui';
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $errors['database'] = 'Gagal memperbarui kelas: ' . $e->getMessage();
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
                                <h5 class="mb-0">Form Edit Data Kelas</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3 mt-3">
                                    <label for="nama_kelas" class="form-label">Nama Kelas</label>
                                    <input type="text" class="form-control <?= isset($errors['nama_kelas']) ? 'is-invalid' : '' ?>"
                                        id="nama_kelas" name="nama_kelas" value="<?= htmlspecialchars($data['nama_kelas']) ?>" required>
                                    <?php if (isset($errors['nama_kelas'])): ?>
                                        <div class="invalid-feedback"><?= $errors['nama_kelas'] ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="tingkat" class="form-label">Tingkat</label>
                                    <select class="form-select <?= isset($errors['tingkat']) ? 'is-invalid' : '' ?>"
                                        id="tingkat" name="tingkat" required>
                                        <option value="">Pilih Tingkat</option>
                                        <option value="7" <?= $data['tingkat'] === '7' ? 'selected' : '' ?>>Kelas 7</option>
                                        <option value="8" <?= $data['tingkat'] === '8' ? 'selected' : '' ?>>Kelas 8</option>
                                        <option value="9" <?= $data['tingkat'] === '9' ? 'selected' : '' ?>>Kelas 9</option>
                                    </select>
                                    <?php if (isset($errors['tingkat'])): ?>
                                        <div class="invalid-feedback"><?= $errors['tingkat'] ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                                    <input type="text" class="form-control <?= isset($errors['tahun_ajaran']) ? 'is-invalid' : '' ?>"
                                        id="tahun_ajaran" name="tahun_ajaran"
                                        placeholder="Contoh: 2023/2024" value="<?= htmlspecialchars($data['tahun_ajaran']) ?>" required>
                                    <?php if (isset($errors['tahun_ajaran'])): ?>
                                        <div class="invalid-feedback"><?= $errors['tahun_ajaran'] ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="wali_kelas_id" class="form-label">Wali Kelas (Opsional)</label>
                                    <select class="form-select" id="wali_kelas_id" name="wali_kelas_id">
                                        <option value="">-- Pilih Wali Kelas --</option>
                                        <?php foreach ($guru as $g): ?>
                                            <option value="<?= $g['user_id'] ?>" <?= $data['wali_kelas_id'] == $g['user_id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($g['full_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="index.php" class="btn btn-secondary">Batal</a>
                            </div>
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