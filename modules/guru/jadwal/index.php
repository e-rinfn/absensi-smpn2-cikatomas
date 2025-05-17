<?php
// require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../../includes/auth.php';

if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil semua jadwal mengajar guru ini
$stmt = $pdo->prepare("SELECT j.*, k.nama_kelas, m.nama_mapel 
                      FROM jadwal_pelajaran j
                      JOIN kelas k ON j.kelas_id = k.kelas_id
                      JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                      WHERE j.guru_id = ?
                      ORDER BY 
                        FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'),
                        j.jam_mulai");
$stmt->execute([$_SESSION['user_id']]);
$jadwal = $stmt->fetchAll();

// Kelompokkan jadwal per hari untuk tampilan yang lebih baik
$jadwal_per_hari = [];
foreach ($jadwal as $j) {
    $jadwal_per_hari[$j['hari']][] = $j;
}
?>

<?php include __DIR__ . '/../../../includes/header.php'; ?>
<?php include  __DIR__ . '/../../../includes/navigation/guru.php'; ?>

<div class="container py-4">
    <h1 class="mb-4">Jadwal Mengajar</h1>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4>Seluruh Jadwal Mengajar</h4>
        </div>
        <div class="card-body">
            <?php if (empty($jadwal_per_hari)): ?>
                <div class="alert alert-info">Anda belum memiliki jadwal mengajar.</div>
            <?php else: ?>
                <div class="accordion" id="jadwalAccordion">
                    <?php foreach ($jadwal_per_hari as $hari => $jadwal_hari): ?>
                        <div class="card">
                            <div class="card-header" id="heading<?= $hari ?>">
                                <h2 class="mb-0">
                                    <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse<?= $hari ?>" aria-expanded="true" aria-controls="collapse<?= $hari ?>">
                                        <?= $hari ?>
                                    </button>
                                </h2>
                            </div>

                            <div id="collapse<?= $hari ?>" class="collapse show" aria-labelledby="heading<?= $hari ?>" data-parent="#jadwalAccordion">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Jam</th>
                                                    <th>Mata Pelajaran</th>
                                                    <th>Kelas</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($jadwal_hari as $j): ?>
                                                    <tr>
                                                        <td><?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?></td>
                                                        <td><?= htmlspecialchars($j['nama_mapel']) ?></td>
                                                        <td><?= htmlspecialchars($j['nama_kelas']) ?></td>
                                                        <td>
                                                            <a href="../absensi/input.php?jadwal_id=<?= $j['jadwal_id'] ?>"
                                                                class="btn btn-sm btn-primary">
                                                                <i class="fas fa-clipboard-check"></i> Absensi
                                                            </a>
                                                            <a href="../absensi/rekap.php?jadwal_id=<?= $j['jadwal_id'] ?>"
                                                                class="btn btn-sm btn-secondary">
                                                                <i class="fas fa-chart-bar"></i> Rekap
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>