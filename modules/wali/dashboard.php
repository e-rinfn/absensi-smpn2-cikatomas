<?php
require_once '../../includes/auth.php';
if (!isWali()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil data murid yang menjadi tanggung jawab wali ini
$stmt = $pdo->prepare("SELECT m.*, k.nama_kelas 
                      FROM murid m
                      JOIN kelas k ON m.kelas_id = k.kelas_id
                      WHERE m.wali_murid_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$anak = $stmt->fetchAll();

if (empty($anak)) {
    die("Anda tidak memiliki murid yang diawasi.");
}

// Hitung statistik absensi
$stats = [];
foreach ($anak as $a) {
    $stmt = $pdo->prepare("SELECT 
                          COUNT(CASE WHEN status = 'hadir' THEN 1 END) as hadir,
                          COUNT(CASE WHEN status = 'sakit' THEN 1 END) as sakit,
                          COUNT(CASE WHEN status = 'izin' THEN 1 END) as izin,
                          COUNT(CASE WHEN status = 'alpha' THEN 1 END) as alpha
                          FROM absensi 
                          WHERE murid_id = ? 
                          AND tanggal BETWEEN ? AND ?");
    $awal_bulan = date('Y-m-01');
    $akhir_bulan = date('Y-m-t');
    $stmt->execute([$a['murid_id'], $awal_bulan, $akhir_bulan]);
    $stats[$a['murid_id']] = $stmt->fetch();
}
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navigation/wali.php'; ?>


<div class="container-fluid px-3 py-3">
    <h1 class="h4 mb-3">Dashboard Wali Murid</h1>
    <p class="lead mb-4">Selamat datang, <?= htmlspecialchars($user['full_name']) ?></p>

    <div class="row g-3">
        <?php foreach ($anak as $a): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-primary text-white py-2">
                        <h5 class="h6 mb-1 text-white"><?= htmlspecialchars($a['nama_lengkap']) ?></h5>
                        <p class="small mb-0"><?= htmlspecialchars($a['nama_kelas']) ?></p>
                    </div>
                    <div class="card-body p-3">
                        <div class="text-center mb-2">
                            <img src="../../assets/images/student.png" alt="Foto Murid" class="img-thumbnail rounded-circle" style="max-width: 100px;">
                        </div>


                        <!-- Stats Grid -->
                        <div class="row g-1 text-center small mb-2">
                            <div class="col-3">
                                <div class="bg-success-light p-1 rounded">
                                    <div class="fw-bold"><?= $stats[$a['murid_id']]['hadir'] ?></div>
                                    <div>Hadir</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-info-light p-1 rounded">
                                    <div class="fw-bold"><?= $stats[$a['murid_id']]['sakit'] ?></div>
                                    <div>Sakit</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-warning-light p-1 rounded">
                                    <div class="fw-bold"><?= $stats[$a['murid_id']]['izin'] ?></div>
                                    <div>Izin</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="bg-danger-light p-1 rounded">
                                    <div class="fw-bold"><?= $stats[$a['murid_id']]['alpha'] ?></div>
                                    <div>Alpha</div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-2">
                            <a href="absensi.php?murid_id=<?= $a['murid_id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
                                <i class="bi bi-clipboard-check me-1"></i> Absensi
                            </a>
                            <a href="profil.php?murid_id=<?= $a['murid_id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                <i class="bi bi-person me-1"></i> Profil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Absensi -->
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-light py-2">
            <h5 class="h6 mb-0">Absensi Terbaru</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="small table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_absensi)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-3">Belum ada data absensi</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_absensi as $abs): ?>
                                <tr data-bs-toggle="collapse" data-bs-target="#detail-<?= $abs['absensi_id'] ?>" style="cursor: pointer;">
                                    <td class="small"><?= htmlspecialchars($abs['nama_lengkap']) ?></td>
                                    <td class="small"><?= date('d/m/y', strtotime($abs['tanggal'])) ?></td>
                                    <td>
                                        <span class="badge <?= $abs['status'] == 'hadir' ? 'bg-success' : ($abs['status'] == 'sakit' ? 'bg-info' : ($abs['status'] == 'izin' ? 'bg-warning' : 'bg-danger')) ?>">
                                            <?= ucfirst($abs['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr class="collapse" id="detail-<?= $abs['absensi_id'] ?>">
                                    <td colspan="3" class="small bg-light">
                                        <div><strong>Mapel:</strong> <?= htmlspecialchars($abs['nama_mapel']) ?></div>
                                        <div><strong>Keterangan:</strong> <?= htmlspecialchars($abs['keterangan']) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-success-light {
        background-color: #d1e7dd;
    }

    .bg-info-light {
        background-color: #cff4fc;
    }

    .bg-warning-light {
        background-color: #fff3cd;
    }

    .bg-danger-light {
        background-color: #f8d7da;
    }

    .progress-bar {
        min-width: 20px;
    }

    /* Memastikan progress bar tetap terlihat */
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tooltip untuk progress bar
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>