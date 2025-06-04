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

<div class="container-fluid px-3"> <!-- Gunakan container-fluid untuk lebar penuh -->
    <h1 class="h4 mb-3">Absensi Murid</h1> <!-- Ukuran judul lebih kecil -->

    <!-- Card Filter -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body p-2">
            <div class="row g-2"> <!-- Mengurangi gap antar kolom -->
                <!-- Select Murid -->
                <div class="col-12 col-md-4">
                    <label class="form-label small">Murid:</label>
                    <select id="select-murid" class="form-select form-select-sm">
                        <?php foreach ($anak as $a): ?>
                            <option value="<?= $a['murid_id'] ?>" <?= $a['murid_id'] == $murid_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['nama_lengkap']) ?> (<?= htmlspecialchars($a['nis']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Select Bulan -->
                <div class="col-6 col-md-3">
                    <label class="form-label small">Bulan:</label>
                    <input type="month" id="select-bulan" value="<?= $bulan ?>" class="form-control form-control-sm">
                </div>

                <!-- Select Mapel -->
                <div class="col-6 col-md-3">
                    <label class="form-label small">Mapel:</label>
                    <select id="select-mapel" class="form-select form-select-sm">
                        <option value="all">Semua Mapel</option>
                        <?php foreach ($mapel_list as $mapel): ?>
                            <option value="<?= $mapel['mapel_id'] ?>" <?= $mapel_id == $mapel['mapel_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mapel['nama_mapel']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tombol Filter -->
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button id="btn-filter" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Hasil Absensi -->
    <div class="card shadow-sm">
        <div class="card-header p-2">
            <h5 class="h6 mb-1"><?= htmlspecialchars($murid_selected['nama_lengkap']) ?></h5>
            <p class="small mb-0"><?= htmlspecialchars($murid_selected['nama_kelas']) ?> • <?= date('F Y', strtotime($bulan . '-01')) ?></p>
        </div>

        <div class="card-body p-0">
            <?php if (empty($absensi)): ?>
                <div class="alert alert-info m-2">Tidak ada data absensi</div>
            <?php else: ?>
                <!-- Tabel Responsive -->
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="100">Tanggal</th>
                                <th width="80">Hari</th>
                                <th>Mapel</th>
                                <th width="80">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($absensi as $a): ?>
                                <tr data-bs-toggle="collapse" data-bs-target="#detail-<?= $a['absensi_id'] ?>" aria-expanded="false">
                                    <td><?= date('d/m/y', strtotime($a['tanggal'])) ?></td> <!-- Format tanggal lebih pendek -->
                                    <td><?= substr($a['hari'], 0, 3) ?></td> <!-- Singkatan hari -->
                                    <td><?= htmlspecialchars($a['nama_mapel']) ?></td>
                                    <td>
                                        <span class="badge <?= $a['status'] == 'hadir' ? 'bg-success' : ($a['status'] == 'sakit' ? 'bg-info' : ($a['status'] == 'izin' ? 'bg-warning' : 'bg-danger')) ?>">
                                            <?= substr(ucfirst($a['status']), 0, 1) ?> <!-- Hanya tampilkan huruf pertama -->
                                        </span>
                                    </td>
                                </tr>
                                <!-- Detail Absensi (Collapse) -->
                                <tr class="collapse" id="detail-<?= $a['absensi_id'] ?>">
                                    <td colspan="4" class="small p-2 bg-light">
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Guru:</strong> <?= htmlspecialchars($a['guru']) ?>
                                            </div>
                                            <div class="col-6">
                                                <strong>Keterangan:</strong> <?= htmlspecialchars($a['keterangan']) ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tombol Cetak -->
                <!-- <div class="p-2 text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Cetak
                    </button>
                </div> -->
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fungsi filter
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

        // Event listeners
        document.getElementById('btn-filter').addEventListener('click', applyFilter);
        document.getElementById('select-bulan').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilter();
        });

        // Optimasi untuk mobile: tutup collapse saat klik di tempat lain
        document.body.addEventListener('click', function(e) {
            if (!e.target.closest('[data-bs-toggle="collapse"]')) {
                const openCollapses = document.querySelectorAll('.collapse.show');
                openCollapses.forEach(collapse => {
                    bootstrap.Collapse.getInstance(collapse).hide();
                });
            }
        });
    });
</script>

<style>
    /* Tambahan CSS untuk mobile */
    @media (max-width: 768px) {
        .card-header h5 {
            font-size: 1rem;
        }

        .table td,
        .table th {
            padding: 0.3rem;
            font-size: 0.85rem;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25em 0.4em;
        }
    }
</style>
<?php include '../../includes/footer.php'; ?>