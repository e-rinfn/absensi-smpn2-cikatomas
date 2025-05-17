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

<div class="container">
    <h1>Dashboard Wali Murid</h1>
    <p class="lead">Selamat datang, <?= htmlspecialchars($user['full_name']) ?></p>

    <div class="row">
        <?php foreach ($anak as $a): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><?= htmlspecialchars($a['nama_lengkap']) ?></h5>
                        <p class="mb-0"><?= htmlspecialchars($a['nama_kelas']) ?></p>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="../../assets/images/student.png" alt="Foto Murid" class="img-thumbnail" style="max-width: 150px;">
                        </div>

                        <div class="progress mb-2">
                            <div class="progress-bar bg-success"
                                style="width: <?= $stats[$a['murid_id']]['hadir'] ?>%">
                                Hadir
                            </div>
                            <div class="progress-bar bg-warning"
                                style="width: <?= $stats[$a['murid_id']]['izin'] ?>%">
                                Izin
                            </div>
                            <div class="progress-bar bg-info"
                                style="width: <?= $stats[$a['murid_id']]['sakit'] ?>%">
                                Sakit
                            </div>
                            <div class="progress-bar bg-danger"
                                style="width: <?= $stats[$a['murid_id']]['alpha'] ?>%">
                                Alpha
                            </div>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-item bg-success">
                                <span>Hadir</span>
                                <strong><?= $stats[$a['murid_id']]['hadir'] ?></strong>
                            </div>
                            <div class="stat-item bg-info">
                                <span>Sakit</span>
                                <strong><?= $stats[$a['murid_id']]['sakit'] ?></strong>
                            </div>
                            <div class="stat-item bg-warning">
                                <span>Izin</span>
                                <strong><?= $stats[$a['murid_id']]['izin'] ?></strong>
                            </div>
                            <div class="stat-item bg-danger">
                                <span>Alpha</span>
                                <strong><?= $stats[$a['murid_id']]['alpha'] ?></strong>
                            </div>
                        </div>

                        <div class="mt-3">
                            <a href="absensi.php?murid_id=<?= $a['murid_id'] ?>" class="btn btn-primary btn-sm">
                                Lihat Absensi
                            </a>
                            <a href="profil.php?murid_id=<?= $a['murid_id'] ?>" class="btn btn-secondary btn-sm">
                                Lihat Profil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Absensi Terbaru</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tanggal</th>
                        <th>Mapel</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("SELECT a.*, m.nama_lengkap, mp.nama_mapel 
                                          FROM absensi a
                                          JOIN murid m ON a.murid_id = m.murid_id
                                          JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
                                          JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
                                          WHERE m.wali_murid_id = ?
                                          ORDER BY a.tanggal DESC
                                          LIMIT 5");
                    $stmt->execute([$_SESSION['user_id']]);
                    $recent_absensi = $stmt->fetchAll();

                    if (empty($recent_absensi)): ?>
                        <tr>
                            <td colspan="5">Belum ada data absensi</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_absensi as $abs): ?>
                            <tr>
                                <td><?= htmlspecialchars($abs['nama_lengkap']) ?></td>
                                <td><?= date('d/m/Y', strtotime($abs['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($abs['nama_mapel']) ?></td>
                                <td>
                                    <span class="badge 
                                    <?= $abs['status'] == 'hadir' ? 'bg-success' : ($abs['status'] == 'sakit' ? 'bg-info' : ($abs['status'] == 'izin' ? 'bg-warning' : 'bg-danger')) ?>">
                                        <?= ucfirst($abs['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($abs['keterangan']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>