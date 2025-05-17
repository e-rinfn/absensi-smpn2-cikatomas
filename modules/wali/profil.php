<?php
require_once '../../includes/auth.php';
if (!isWali()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil data murid yang menjadi tanggung jawab wali ini
$stmt = $pdo->prepare("SELECT * FROM murid WHERE wali_murid_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$anak = $stmt->fetchAll();

if (empty($anak)) {
    die("Anda tidak memiliki murid yang diawasi.");
}

// Parameter murid_id
$murid_id = $_GET['murid_id'] ?? $anak[0]['murid_id'];

// Validasi murid_id
$valid_murid = false;
foreach ($anak as $a) {
    if ($a['murid_id'] == $murid_id) {
        $valid_murid = true;
        break;
    }
}

if (!$valid_murid) {
    $murid_id = $anak[0]['murid_id'];
}

// Ambil data murid yang dipilih
$stmt = $pdo->prepare("SELECT m.*, k.nama_kelas, u.full_name as wali_kelas
                      FROM murid m
                      JOIN kelas k ON m.kelas_id = k.kelas_id
                      JOIN users u ON k.wali_kelas_id = u.user_id
                      WHERE m.murid_id = ?");
$stmt->execute([$murid_id]);
$murid = $stmt->fetch();

// Hitung statistik absensi
$stmt = $pdo->prepare("SELECT 
                      COUNT(CASE WHEN status = 'hadir' THEN 1 END) as hadir,
                      COUNT(CASE WHEN status = 'sakit' THEN 1 END) as sakit,
                      COUNT(CASE WHEN status = 'izin' THEN 1 END) as izin,
                      COUNT(CASE WHEN status = 'alpha' THEN 1 END) as alpha
                      FROM absensi 
                      WHERE murid_id = ?");
$stmt->execute([$murid_id]);
$stats = $stmt->fetch();

// Ambil 5 mata pelajaran dengan alpha terbanyak
$stmt = $pdo->prepare("SELECT mp.nama_mapel, COUNT(a.absensi_id) as total_alpha
                      FROM absensi a
                      JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
                      JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
                      WHERE a.murid_id = ? AND a.status = 'alpha'
                      GROUP BY mp.mapel_id
                      ORDER BY total_alpha DESC
                      LIMIT 5");
$stmt->execute([$murid_id]);
$mapel_alpha = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navigation/wali.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5>Profil Murid</h5>
                </div>
                <div class="card-body text-center">
                    <img src="../../assets/images/student.png" alt="Foto Murid" class="img-thumbnail mb-3" style="max-width: 200px;">

                    <h4><?= htmlspecialchars($murid['nama_lengkap']) ?></h4>
                    <p class="text-muted">NIS: <?= htmlspecialchars($murid['nis']) ?></p>

                    <hr>

                    <div class="text-start">
                        <p><strong>Kelas:</strong> <?= htmlspecialchars($murid['nama_kelas']) ?></p>
                        <p><strong>Wali Kelas:</strong> <?= htmlspecialchars($murid['wali_kelas']) ?></p>
                        <p><strong>Jenis Kelamin:</strong> <?= $murid['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
                        <p><strong>Tanggal Lahir:</strong> <?= date('d/m/Y', strtotime($murid['tanggal_lahir'])) ?></p>
                        <p><strong>Alamat:</strong> <?= htmlspecialchars($murid['alamat']) ?></p>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5>Pilih Murid Lain</h5>
                </div>
                <div class="card-body">
                    <select id="select-murid" class="form-select">
                        <?php foreach ($anak as $a): ?>
                            <option value="<?= $a['murid_id'] ?>" <?= $a['murid_id'] == $murid_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nama_lengkap']) ?> (<?= htmlspecialchars($a['nis']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Statistik Absensi</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="stat-card bg-success">
                                <h3><?= $stats['hadir'] ?></h3>
                                <p>Hadir</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stat-card bg-info">
                                <h3><?= $stats['sakit'] ?></h3>
                                <p>Sakit</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stat-card bg-warning">
                                <h3><?= $stats['izin'] ?></h3>
                                <p>Izin</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="stat-card bg-danger">
                                <h3><?= $stats['alpha'] ?></h3>
                                <p>Alpha</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <canvas id="absensiChart" height="150"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>Mata Pelajaran dengan Alpha Terbanyak</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($mapel_alpha)): ?>
                        <div class="alert alert-info">Tidak ada data alpha untuk mata pelajaran.</div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($mapel_alpha as $mapel): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($mapel['nama_mapel']) ?>
                                    <span class="badge bg-danger rounded-pill"><?= $mapel['total_alpha'] ?> alpha</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ganti murid
        document.getElementById('select-murid').addEventListener('change', function() {
            window.location.href = `profil.php?murid_id=${this.value}`;
        });

        // Chart absensi
        const ctx = document.getElementById('absensiChart').getContext('2d');
        const absensiChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Sakit', 'Izin', 'Alpha'],
                datasets: [{
                    data: [
                        <?= $stats['hadir'] ?>,
                        <?= $stats['sakit'] ?>,
                        <?= $stats['izin'] ?>,
                        <?= $stats['alpha'] ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#17a2b8',
                        '#ffc107',
                        '#dc3545'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>