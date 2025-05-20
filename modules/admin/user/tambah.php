<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);

    // Validasi
    if (empty($username) || empty($password) || empty($email) || empty($role) || empty($full_name)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $password_confirm) {
        $error = 'Password tidak sama!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek username/email sudah ada
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username atau email sudah digunakan!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert ke database
            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, full_name, phone) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $email, $role, $full_name, $phone]);

            $_SESSION['success'] = 'User berhasil ditambahkan!';
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
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0">Form Tambah Murid</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3 mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Username</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <select name="role" class="form-select" required>
                                            <option value="">Pilih Role</option>
                                            <option value="admin">Admin</option>
                                            <option value="guru">Guru</option>
                                            <option value="wali_murid">Wali Murid</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Konfirmasi Password</label>
                                        <input type="password" name="password_confirm" class="form-control" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" name="full_name" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nomor Telepon</label>
                                    <input type="text" name="phone" class="form-control">
                                </div>

                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Simpan</button>
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