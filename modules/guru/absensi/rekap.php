<?php
require_once __DIR__ . '/../../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil jadwal yang diajar oleh guru ini
$jadwal_id = $_GET['jadwal_id'] ?? 0;
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Validasi apakah guru mengajar jadwal ini
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

// Ambil rekap absensi
$stmt = $pdo->prepare("SELECT a.*, m.nis, m.nama_lengkap 
                      FROM absensi a
                      JOIN murid m ON a.murid_id = m.murid_id
                      WHERE a.jadwal_id = ? AND a.tanggal = ?
                      ORDER BY m.nama_lengkap");
$stmt->execute([$jadwal_id, $tanggal]);
$absensi = $stmt->fetchAll();

// Hitung statistik
$total_murid = count($absensi);
$hadir = 0;
$sakit = 0;
$izin = 0;
$alpha = 0;

foreach ($absensi as $a) {
    switch ($a['status']) {
        case 'hadir':
            $hadir++;
            break;
        case 'sakit':
            $sakit++;
            break;
        case 'izin':
            $izin++;
            break;
        case 'alpha':
            $alpha++;
            break;
    }
}
?>

<?php include '../../../includes/header.php'; ?>
<?php include '../../../includes/navigation/guru.php'; ?>

<div class="container py-4">
    <h1 class="mb-4">Rekap Absensi</h1>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4><?= htmlspecialchars($jadwal['nama_mapel']) ?> - <?= htmlspecialchars($jadwal['nama_kelas']) ?></h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($tanggal)) ?></p>
                    <p><strong>Hari:</strong> <?= $jadwal['hari'] ?></p>
                    <p><strong>Jam:</strong> <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?></p>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <h5>Statistik Kehadiran</h5>
                        <p>Hadir: <?= $hadir ?> murid</p>
                        <p>Sakit: <?= $sakit ?> murid</p>
                        <p>Izin: <?= $izin ?> murid</p>
                        <p>Alpha: <?= $alpha ?> murid</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5>Daftar Absensi Murid</h5>
        </div>
        <div class="card-body">
            <?php if (empty($absensi)): ?>
                <div class="alert alert-warning">Belum ada data absensi untuk tanggal ini.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="10%">NIS</th>
                                <th width="30%">Nama Murid</th>
                                <th width="15%">Status</th>
                                <th width="45%">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($absensi as $a):
                                $status_class = '';
                                if ($a['status'] == 'hadir') $status_class = 'text-success font-weight-bold';
                                elseif ($a['status'] == 'alpha') $status_class = 'text-danger font-weight-bold';
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['nis']) ?></td>
                                    <td><?= htmlspecialchars($a['nama_lengkap']) ?></td>
                                    <td class="<?= $status_class ?>"><?= ucfirst($a['status']) ?></td>
                                    <td><?= htmlspecialchars($a['keterangan']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3">
        <a href="input.php?jadwal_id=<?= $jadwal_id ?>&tanggal=<?= $tanggal ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Absensi
        </a>
        <a href="../jadwal/" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Jadwal
        </a>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>