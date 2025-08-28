<?php
require_once __DIR__ . '/../../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Inisialisasi variabel error
$errors = [];
$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Ambil parameter
$jadwal_id = $_GET['jadwal_id'] ?? 0;
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Validasi jadwal mengajar
$stmt = $pdo->prepare("SELECT j.*, k.nama_kelas, m.nama_mapel 
                      FROM jadwal_pelajaran j
                      JOIN kelas k ON j.kelas_id = k.kelas_id
                      JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                      WHERE j.jadwal_id = ? AND j.guru_id = ?");
$stmt->execute([$jadwal_id, $_SESSION['user_id']]);
$jadwal = $stmt->fetch();

if (!$jadwal) {
    header('Location: ../jadwal/index.php');
    exit;
}

// Ambil data murid dan absensi
$stmt = $pdo->prepare("SELECT * FROM murid WHERE kelas_id = ? ORDER BY nama_lengkap");
$stmt->execute([$jadwal['kelas_id']]);
$murid = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM absensi WHERE jadwal_id = ? AND tanggal = ?");
$stmt->execute([$jadwal_id, $tanggal]);
$absensi_terdaftar = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];

    // Validasi dasar
    if (empty($tanggal)) {
        $errors['tanggal'] = "Tanggal harus diisi";
    }

    if (!isset($_POST['absensi']) || count($_POST['absensi']) !== count($murid)) {
        $errors['absensi'] = "Status kehadiran untuk semua murid harus diisi";
    }

    // Validasi tambahan jika tidak ada error dasar
    if (empty($errors)) {
        // Cek duplikat untuk murid yang belum ada di $absensi_terdaftar
        foreach ($_POST['absensi'] as $murid_id => $status) {
            if (!isset($absensi_terdaftar[$murid_id])) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM absensi 
                                      WHERE murid_id = ? AND jadwal_id = ? AND tanggal = ?");
                $stmt->execute([$murid_id, $jadwal_id, $tanggal]);
                if ($stmt->fetchColumn() > 0) {
                    $errors['database'] = "Data absensi sudah ada. " .
                        "";
                    // $errors['database'] = "Data absensi untuk beberapa murid sudah ada. " .
                    //     "Silakan muat ulang halaman dan coba lagi.";
                    break;
                }
            }
        }

        // Jika semua validasi lolos
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                foreach ($_POST['absensi'] as $murid_id => $status) {
                    $keterangan = $_POST['keterangan'][$murid_id] ?? '';

                    // Validasi status
                    if (!in_array($status, ['hadir', 'sakit', 'izin', 'alpha'])) {
                        throw new Exception("Status kehadiran tidak valid");
                    }

                    if (isset($absensi_terdaftar[$murid_id])) {
                        // Update existing
                        $stmt = $pdo->prepare("UPDATE absensi SET status = ?, keterangan = ? 
                                              WHERE murid_id = ? AND jadwal_id = ? AND tanggal = ?");
                        $stmt->execute([$status, $keterangan, $murid_id, $jadwal_id, $tanggal]);
                    } else {
                        // Insert new
                        $stmt = $pdo->prepare("INSERT INTO absensi 
                                              (murid_id, jadwal_id, tanggal, status, keterangan, guru_id) 
                                              VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$murid_id, $jadwal_id, $tanggal, $status, $keterangan, $_SESSION['user_id']]);
                    }
                }

                $pdo->commit();
                $_SESSION['success'] = "Absensi berhasil disimpan!";
                header("Location: rekap.php?jadwal_id=$jadwal_id&tanggal=$tanggal");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                if (strpos($e->getMessage(), '1062 Duplicate entry') !== false) {
                    $errors['database'] = "Data absensi untuk beberapa murid sudah ada. " .
                        "Silakan muat ulang halaman dan coba lagi.";
                } else {
                    $errors['database'] = "Terjadi kesalahan saat menyimpan data: " . $e->getMessage();
                }
            }
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
                <h3>INPUT ABSENSI</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Notifikasi -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success d-flex align-items-center justify-content-between gap-2 alert-dismissible fade show shadow-sm border-0" role="alert">
                            <div class="d-flex align-items-center">
                                <div><?= htmlspecialchars($success_message) ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>


                    <?php if (!empty($tanggal_sudah_tercatat)): ?>
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            Gagal menyimpan data: Absensi untuk guru ini pada tanggal <?= date("d F Y", strtotime($tanggal_sudah_tercatat)) ?> sudah tercatat.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>


                    <?php if (!empty($errors['database'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($errors['database']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-3 shadow-sm mx-3 w-50">
                        <div class="card-body py-2 px-3">
                            <h5 class="card-title mb-1">
                                <?= htmlspecialchars($jadwal['nama_mapel']) ?> - <?= htmlspecialchars($jadwal['nama_kelas']) ?>
                            </h5>
                            <p class="mb-1"><small><strong>Hari:</strong> <?= htmlspecialchars($jadwal['hari']) ?></small></p>
                            <p class="mb-0"><small><strong>Jam:</strong> <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?></small></p>
                        </div>
                    </div>

                    <form method="POST" class="mb-4 m-0" id="formAbsensi" novalidate>
                        <div class="mb-3 row align-items-center">
                            <label for="tanggal" class="col-sm-2 col-form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <div class="col-sm-4">
                                <input type="date"
                                    class="form-control <?= isset($errors['tanggal']) ? 'is-invalid' : '' ?>"
                                    id="tanggal" name="tanggal"
                                    value="<?= htmlspecialchars($tanggal) ?>"
                                    required>
                                <?php if (isset($errors['tanggal'])): ?>
                                    <div class="invalid-feedback">
                                        <?= htmlspecialchars($errors['tanggal']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (isset($errors['absensi'])): ?>
                            <div class="alert alert-warning py-2">
                                <?= htmlspecialchars($errors['absensi']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow-sm">
                            <div class="card-header py-2">
                                <h6 class="mb-0 fw-semibold">Daftar Murid <span class="text-danger">*</span></h6>
                            </div>
                            <div class="card-body p-2">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0 align-middle" style="font-size: .875rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%;">No.</th> <!-- Kolom nomor -->
                                                <th style="width: 10%;">NIS</th>
                                                <th style="width: 30%;">Nama Murid</th>
                                                <th style="width: 20%;">Status</th>
                                                <th style="width: 35%;">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; ?>
                                            <?php foreach ($murid as $m):
                                                $absensi = $absensi_terdaftar[$m['murid_id']] ?? null;
                                            ?>
                                                <tr>
                                                    <td><?= $no++ ?></td> <!-- Nomor urut -->
                                                    <td><?= htmlspecialchars($m['nis']) ?></td>
                                                    <td><?= htmlspecialchars($m['nama_lengkap']) ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-success absensi-btn <?= ($absensi && $absensi['status'] == 'hadir') ? 'active' : '' ?>"
                                                                data-murid-id="<?= $m['murid_id'] ?>" data-status="hadir">
                                                                Hadir
                                                            </button>
                                                            <button type="button" class="btn btn-outline-warning absensi-btn <?= ($absensi && $absensi['status'] == 'sakit') ? 'active' : '' ?>"
                                                                data-murid-id="<?= $m['murid_id'] ?>" data-status="sakit">
                                                                Sakit
                                                            </button>
                                                            <button type="button" class="btn btn-outline-info absensi-btn <?= ($absensi && $absensi['status'] == 'izin') ? 'active' : '' ?>"
                                                                data-murid-id="<?= $m['murid_id'] ?>" data-status="izin">
                                                                Izin
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger absensi-btn <?= ($absensi && $absensi['status'] == 'alpha') ? 'active' : '' ?>"
                                                                data-murid-id="<?= $m['murid_id'] ?>" data-status="alpha">
                                                                Alpha
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="absensi[<?= $m['murid_id'] ?>]" id="absensi_<?= $m['murid_id'] ?>"
                                                            value="<?= $absensi ? $absensi['status'] : '' ?>" required>
                                                    </td>

                                                    <script>
                                                        document.addEventListener('DOMContentLoaded', function() {
                                                            // Menangani klik pada tombol absensi
                                                            document.querySelectorAll('.absensi-btn').forEach(button => {
                                                                button.addEventListener('click', function() {
                                                                    const muridId = this.getAttribute('data-murid-id');
                                                                    const status = this.getAttribute('data-status');

                                                                    // Hapus kelas active dari semua tombol dalam grup yang sama
                                                                    const buttonGroup = this.closest('.btn-group');
                                                                    buttonGroup.querySelectorAll('.absensi-btn').forEach(btn => {
                                                                        btn.classList.remove('active');
                                                                    });

                                                                    // Tambahkan kelas active ke tombol yang diklik
                                                                    this.classList.add('active');

                                                                    // Update nilai input hidden
                                                                    document.getElementById('absensi_' + muridId).value = status;
                                                                });
                                                            });

                                                            // Validasi form sebelum submit
                                                            document.querySelector('form').addEventListener('submit', function(e) {
                                                                let allFilled = true;
                                                                document.querySelectorAll('input[name^="absensi["]').forEach(input => {
                                                                    if (!input.value) {
                                                                        allFilled = false;
                                                                        // Highlight baris yang belum diisi
                                                                        input.closest('tr').classList.add('table-danger');
                                                                    }
                                                                });

                                                                if (!allFilled) {
                                                                    e.preventDefault();
                                                                    alert('Harap isi status absensi untuk semua murid!');
                                                                }
                                                            });
                                                        });
                                                    </script>

                                                    <td>
                                                        <input type="text" name="keterangan[<?= $m['murid_id'] ?>]" class="form-control form-control-sm" value="<?= $absensi ? htmlspecialchars($absensi['keterangan']) : '' ?>" placeholder="Keterangan (opsional)">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i> Simpan Absensi
                            </button>
                            <a href="../jadwal/" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>


                    <!-- JavaScript untuk validasi client-side -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const form = document.getElementById('formAbsensi');
                            const statusSelects = document.querySelectorAll('.status-select');

                            form.addEventListener('submit', function(e) {
                                let isValid = true;

                                // Validasi tanggal
                                const tanggalInput = document.getElementById('tanggal');
                                if (!tanggalInput.value) {
                                    tanggalInput.classList.add('is-invalid');
                                    isValid = false;
                                } else {
                                    tanggalInput.classList.remove('is-invalid');
                                }

                                // Validasi status
                                statusSelects.forEach(select => {
                                    if (!select.value) {
                                        select.classList.add('is-invalid');
                                        isValid = false;
                                    } else {
                                        select.classList.remove('is-invalid');
                                    }
                                });

                                if (!isValid) {
                                    e.preventDefault();
                                    alert('Silakan lengkapi semua data yang wajib diisi!');
                                }
                            });
                        });
                    </script>
                </section>
            </div>
        </div>
        <!-- Main end -->
    </div>

    <!-- Javascript -->
    <script src="<?= $base_url ?>/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= $base_url ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $base_url ?>/assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="<?= $base_url ?>/assets/js/pages/dashboard.js"></script>
    <script src="<?= $base_url ?>/assets/js/main.js"></script>
</body>