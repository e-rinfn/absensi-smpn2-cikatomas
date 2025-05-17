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

// Parameter filter
$murid_id = $_GET['murid_id'] ?? $anak[0]['murid_id'];
$bulan = $_GET['bulan'] ?? date('Y-m');
$mapel_id = $_GET['mapel_id'] ?? 'all';

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

// Ambil data absensi dengan filter
$query = "SELECT a.*, mp.nama_mapel, j.hari, u.full_name as guru 
          FROM absensi a
          JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
          JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
          JOIN users u ON a.guru_id = u.user_id
          WHERE a.murid_id = ? 
          AND DATE_FORMAT(a.tanggal, '%Y-%m') = ?";

$params = [$murid_id, $bulan];

if ($mapel_id !== 'all') {
    $query .= " AND j.mapel_id = ?";
    $params[] = $mapel_id;
}

$query .= " ORDER BY a.tanggal DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$absensi = $stmt->fetchAll();

// Ambil data murid yang dipilih
$stmt = $pdo->prepare("SELECT m.*, k.nama_kelas 
                      FROM murid m
                      JOIN kelas k ON m.kelas_id = k.kelas_id
                      WHERE m.murid_id = ?");
$stmt->execute([$murid_id]);
$murid_selected = $stmt->fetch();

// Ambil daftar mata pelajaran untuk filter
$stmt = $pdo->prepare("SELECT DISTINCT mp.mapel_id, mp.nama_mapel
                      FROM absensi a
                      JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
                      JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
                      WHERE a.murid_id = ?");
$stmt->execute([$murid_id]);
$mapel_list = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>
<?php include '../../includes/navigation/wali.php'; ?>

<div class="container">
    <h1>Absensi Murid</h1>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label>Pilih Murid:</label>
                    <select id="select-murid" class="form-select">
                        <?php foreach ($anak as $a): ?>
                            <option value="<?= $a['murid_id'] ?>" <?= $a['murid_id'] == $murid_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nama_lengkap']) ?> (<?= htmlspecialchars($a['nis']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Pilih Bulan:</label>
                    <input type="month" id="select-bulan" value="<?= $bulan ?>" class="form-control">
                </div>

                <div class="col-md-3">
                    <label>Pilih Mata Pelajaran:</label>
                    <select id="select-mapel" class="form-select">
                        <option value="all">Semua Mapel</option>
                        <?php foreach ($mapel_list as $mapel): ?>
                            <option value="<?= $mapel['mapel_id'] ?>" <?= $mapel_id == $mapel['mapel_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mapel['nama_mapel']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Absensi <?= htmlspecialchars($murid_selected['nama_lengkap']) ?> - <?= htmlspecialchars($murid_selected['nama_kelas']) ?></h5>
            <p class="mb-0">Bulan: <?= date('F Y', strtotime($bulan . '-01')) ?></p>
        </div>

        <div class="card-body">
            <?php if (empty($absensi)): ?>
                <div class="alert alert-info">Tidak ada data absensi untuk filter yang dipilih.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($absensi as $a): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($a['tanggal'])) ?></td>
                                    <td><?= $a['hari'] ?></td>
                                    <td><?= htmlspecialchars($a['nama_mapel']) ?></td>
                                    <td><?= htmlspecialchars($a['guru']) ?></td>
                                    <td>
                                        <span class="badge 
                                        <?= $a['status'] == 'hadir' ? 'bg-success' : ($a['status'] == 'sakit' ? 'bg-info' : ($a['status'] == 'izin' ? 'bg-warning' : 'bg-danger')) ?>">
                                            <?= ucfirst($a['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($a['keterangan']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Cetak Laporan
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fungsi untuk apply filter
        function applyFilter() {
            const murid_id = document.getElementById('select-murid').value;
            const bulan = document.getElementById('select-bulan').value;
            const mapel_id = document.getElementById('select-mapel').value;

            let url = `absensi.php?murid_id=${murid_id}&bulan=${bulan}`;
            if (mapel_id !== 'all') {
                url += `&mapel_id=${mapel_id}`;
            }

            window.location.href = url;
        }

        // Event listener untuk tombol filter
        document.getElementById('btn-filter').addEventListener('click', applyFilter);

        // Event listener untuk enter di input
        document.getElementById('select-bulan').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilter();
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>