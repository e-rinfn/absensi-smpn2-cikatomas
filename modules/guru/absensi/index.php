<?php
require_once '../../../includes/auth.php';
if (!isGuru()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil parameter filter
$mapel_id = $_GET['mapel_id'] ?? null;
$kelas_id = $_GET['kelas_id'] ?? null;
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-t');

// Query untuk mendapatkan daftar absensi
$sql = "SELECT a.*, m.nis, m.nama_lengkap, k.nama_kelas, mp.nama_mapel, j.hari, u.full_name AS guru_pengampu
        FROM absensi a
        JOIN murid m ON a.murid_id = m.murid_id
        JOIN kelas k ON m.kelas_id = k.kelas_id
        JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
        JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
        JOIN users u ON a.guru_id = u.user_id
        WHERE a.guru_id = :guru_id";

$params = [':guru_id' => $_SESSION['user_id']];

// Tambahkan filter jika ada
if ($mapel_id) {
    $sql .= " AND j.mapel_id = :mapel_id";
    $params[':mapel_id'] = $mapel_id;
}

if ($kelas_id) {
    $sql .= " AND m.kelas_id = :kelas_id";
    $params[':kelas_id'] = $kelas_id;
}

$sql .= " AND a.tanggal BETWEEN :tgl_mulai AND :tgl_selesai
          ORDER BY a.tanggal DESC, j.hari, mp.nama_mapel, m.nama_lengkap";

$params[':tgl_mulai'] = $tanggal_mulai;
$params[':tgl_selesai'] = $tanggal_selesai;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$absensi = $stmt->fetchAll();

// Ambil daftar mata pelajaran yang diajar guru ini untuk filter
$stmt = $pdo->prepare("SELECT DISTINCT mp.mapel_id, mp.nama_mapel 
                       FROM jadwal_pelajaran j
                       JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
                       WHERE j.guru_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$mapel_options = $stmt->fetchAll();

// Ambil daftar kelas yang diajar guru ini untuk filter
$stmt = $pdo->prepare("SELECT DISTINCT k.kelas_id, k.nama_kelas 
                       FROM jadwal_pelajaran j
                       JOIN kelas k ON j.kelas_id = k.kelas_id
                       WHERE j.guru_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$kelas_options = $stmt->fetchAll();
?>

<?php include '../../../includes/header.php'; ?>
<?php include '../../../includes/navigation/guru.php'; ?>

<div class="container py-4">
    <h1 class="mb-4">Daftar Absensi</h1>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-filter me-2"></i>Filter Absensi
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="mapel_id" class="form-label">Mata Pelajaran</label>
                    <select id="mapel_id" name="mapel_id" class="form-select">
                        <option value="">Semua Mapel</option>
                        <?php foreach ($mapel_options as $mapel): ?>
                            <option value="<?= $mapel['mapel_id'] ?>" <?= $mapel_id == $mapel['mapel_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mapel['nama_mapel']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="kelas_id" class="form-label">Kelas</label>
                    <select id="kelas_id" name="kelas_id" class="form-select">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($kelas_options as $kelas): ?>
                            <option value="<?= $kelas['kelas_id'] ?>" <?= $kelas_id == $kelas['kelas_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kelas['nama_kelas']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai"
                        value="<?= htmlspecialchars($tanggal_mulai) ?>" class="form-control">
                </div>

                <div class="col-md-2">
                    <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai"
                        value="<?= htmlspecialchars($tanggal_selesai) ?>" class="form-control">
                </div>

                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Absensi -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>Data Absensi</span>
            <a href="input.php" class="btn btn-light btn-sm">
                <i class="fas fa-plus me-1"></i> Input Baru
            </a>
        </div>

        <div class="card-body">
            <?php if (empty($absensi)): ?>
                <div class="alert alert-info">Tidak ada data absensi untuk filter yang dipilih.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Mapel</th>
                                <th>Kelas</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($absensi as $a): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($a['tanggal'])) ?></td>
                                    <td><?= $a['hari'] ?></td>
                                    <td><?= htmlspecialchars($a['nama_mapel']) ?></td>
                                    <td><?= htmlspecialchars($a['nama_kelas']) ?></td>
                                    <td><?= htmlspecialchars($a['nis']) ?></td>
                                    <td><?= htmlspecialchars($a['nama_lengkap']) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'hadir' => 'bg-success',
                                            'sakit' => 'bg-info',
                                            'izin'  => 'bg-warning',
                                            'alpha' => 'bg-danger'
                                        ];
                                        ?>
                                        <span class="badge <?= $badge_class[$a['status']] ?>">
                                            <?= ucfirst($a['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($a['keterangan']) ?></td>
                                    <td>
                                        <a href="edit.php?id=<?= $a['absensi_id'] ?>"
                                            class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../../includes/footer.php'; ?>