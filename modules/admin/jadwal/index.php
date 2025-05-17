<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil data jadwal beserta relasinya
$stmt = $pdo->prepare("SELECT j.*, k.nama_kelas, m.nama_mapel, u.full_name AS nama_guru 
                      FROM jadwal_pelajaran j
                      JOIN kelas k ON j.kelas_id = k.kelas_id
                      JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                      JOIN users u ON j.guru_id = u.user_id
                      ORDER BY j.hari, j.jam_mulai");
$stmt->execute();
$jadwal = $stmt->fetchAll();

// Urutan hari untuk sorting
$urutan_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
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

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Daftar Jadwal</span>
                            <a href="tambah.php" class="btn btn-primary btn-sm">Tambah Jadwal</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Hari</th>
                                            <th>Mapel</th>
                                            <th>Kelas</th>
                                            <th>Guru</th>
                                            <th>Jam</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($urutan_hari as $hari): ?>
                                            <?php foreach ($jadwal as $j): ?>
                                                <?php if ($j['hari'] == $hari): ?>
                                                    <tr>
                                                        <td><?= $j['hari'] ?></td>
                                                        <td><?= $j['nama_mapel'] ?></td>
                                                        <td><?= $j['nama_kelas'] ?></td>
                                                        <td><?= $j['nama_guru'] ?></td>
                                                        <td>
                                                            <?= date('H:i', strtotime($j['jam_mulai'])) ?> -
                                                            <?= date('H:i', strtotime($j['jam_selesai'])) ?>
                                                        </td>
                                                        <td>
                                                            <a href="edit.php?id=<?= $j['jadwal_id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <a href="hapus.php?id=<?= $j['jadwal_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus jadwal ini?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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