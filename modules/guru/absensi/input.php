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

// Ambil data murid di kelas
$stmt = $pdo->prepare("SELECT * FROM murid WHERE kelas_id = ? ORDER BY nama_lengkap");
$stmt->execute([$jadwal['kelas_id']]);
$murid = $stmt->fetchAll();

// Ambil data absensi yang sudah ada
$stmt = $pdo->prepare("SELECT * FROM absensi WHERE jadwal_id = ? AND tanggal = ?");
$stmt->execute([$jadwal_id, $tanggal]);
$absensi_terdaftar = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

// Proses form input absensi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];

    foreach ($_POST['absensi'] as $murid_id => $status) {
        $keterangan = $_POST['keterangan'][$murid_id] ?? '';

        if (isset($absensi_terdaftar[$murid_id])) {
            // Update jika sudah ada
            $stmt = $pdo->prepare("UPDATE absensi SET status = ?, keterangan = ? 
                                   WHERE murid_id = ? AND jadwal_id = ? AND tanggal = ?");
            $stmt->execute([$status, $keterangan, $murid_id, $jadwal_id, $tanggal]);
        } else {
            // Insert jika belum ada
            $stmt = $pdo->prepare("INSERT INTO absensi 
                                  (murid_id, jadwal_id, tanggal, status, keterangan, guru_id) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$murid_id, $jadwal_id, $tanggal, $status, $keterangan, $_SESSION['user_id']]);
        }
    }

    $_SESSION['success'] = "Absensi berhasil disimpan!";
    header("Location: rekap.php?jadwal_id=$jadwal_id&tanggal=$tanggal");
    exit;
}
?>

<?php include '../../../includes/header.php'; ?>
<?php include '../../../includes/navigation/guru.php'; ?>

<div class="container py-4">
    <h1 class="mb-4">Input Absensi</h1>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4><?= htmlspecialchars($jadwal['nama_mapel']) ?> - <?= htmlspecialchars($jadwal['nama_kelas']) ?></h4>
        </div>
        <div class="card-body">
            <p><strong>Hari:</strong> <?= $jadwal['hari'] ?></p>
            <p><strong>Jam:</strong> <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?></p>
        </div>
    </div>

    <form method="POST" class="mb-4">
        <div class="form-group row">
            <label for="tanggal" class="col-sm-2 col-form-label">Tanggal</label>
            <div class="col-sm-4">
                <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= $tanggal ?>" required>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5>Daftar Murid</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="10%">NIS</th>
                                <th width="30%">Nama Murid</th>
                                <th width="20%">Status</th>
                                <th width="40%">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($murid as $m):
                                $absensi = $absensi_terdaftar[$m['murid_id']] ?? null;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['nis']) ?></td>
                                    <td><?= htmlspecialchars($m['nama_lengkap']) ?></td>
                                    <td>
                                        <select name="absensi[<?= $m['murid_id'] ?>]" class="form-control" required>
                                            <option value="hadir" <?= ($absensi && $absensi['status'] == 'hadir') ? 'selected' : '' ?>>Hadir</option>
                                            <option value="sakit" <?= ($absensi && $absensi['status'] == 'sakit') ? 'selected' : '' ?>>Sakit</option>
                                            <option value="izin" <?= ($absensi && $absensi['status'] == 'izin') ? 'selected' : '' ?>>Izin</option>
                                            <option value="alpha" <?= ($absensi && $absensi['status'] == 'alpha') ? 'selected' : '' ?>>Alpha</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="keterangan[<?= $m['murid_id'] ?>]"
                                            class="form-control"
                                            value="<?= $absensi ? htmlspecialchars($absensi['keterangan']) : '' ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="form-group mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Absensi
            </button>
            <a href="../jadwal/" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </form>
</div>

<?php include '../../../includes/footer.php'; ?>