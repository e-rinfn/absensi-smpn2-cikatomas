<?php
require_once __DIR__ . '/../../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil data admin yang sedang login
$admin_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

// Inisialisasi variabel
$errors = [];
$success = false;

// Proses form update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($full_name)) {
        $errors['full_name'] = "Nama lengkap harus diisi";
    }

    if (empty($email)) {
        $errors['email'] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format email tidak valid";
    }

    // Validasi password jika diisi
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors['current_password'] = "Password saat ini harus diisi";
        } elseif (!password_verify($current_password, $admin['password'])) {
            $errors['current_password'] = "Password saat ini salah";
        }

        if (strlen($new_password) < 8) {
            $errors['new_password'] = "Password minimal 8 karakter";
        } elseif ($new_password !== $confirm_password) {
            $errors['confirm_password'] = "Konfirmasi password tidak cocok";
        }
    }

    // Jika tidak ada error, update data
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Siapkan query update
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?";
            $params = [$full_name, $email, $phone];

            // Jika password diubah
            if (!empty($new_password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE user_id = ?";
            $params[] = $admin_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $pdo->commit();
            $success = true;

            // Refresh data admin
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch();

            // Update session jika nama berubah
            $_SESSION['full_name'] = $admin['full_name'];
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['database'] = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
        }
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
                <h3>PROFIL</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->


                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0 text-white">
                                <i class="fas fa-user-cog me-2"></i>Profil Guru
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show mt-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Profil berhasil diperbarui!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($errors['database'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?= htmlspecialchars($errors['database']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3 mt-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username"
                                        value="<?= htmlspecialchars($admin['username']) ?>" readonly>
                                    <small class="text-muted">Username tidak dapat diubah</small>
                                </div>

                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                                        id="full_name" name="full_name" required
                                        value="<?= htmlspecialchars($admin['full_name']) ?>">
                                    <?php if (isset($errors['full_name'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['full_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                        id="email" name="email" required
                                        value="<?= htmlspecialchars($admin['email']) ?>">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?= htmlspecialchars($admin['phone']) ?>">
                                </div>

                                <hr class="my-4">

                                <h5 class="mb-3">Ubah Password</h5>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Kosongkan kolom password jika tidak ingin mengubah password
                                </div>

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Password Saat Ini</label>
                                    <input type="password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                                        id="current_password" name="current_password">
                                    <?php if (isset($errors['current_password'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['current_password']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Password Baru</label>
                                    <input type="password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                                        id="new_password" name="new_password">
                                    <?php if (isset($errors['new_password'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['new_password']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">Minimal 8 karakter</small>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                                        id="confirm_password" name="confirm_password">
                                    <?php if (isset($errors['confirm_password'])): ?>
                                        <div class="invalid-feedback">
                                            <?= htmlspecialchars($errors['confirm_password']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                                    </button>
                                    <a href="<?= $base_url ?>modules/admin/dashboard.php" class="btn btn-secondary px-4">
                                        <i class="fas fa-times me-2"></i>Batal
                                    </a>
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