<?php
require_once __DIR__ . '/../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil semua kelas (admin bisa melihat semua kelas)
$stmt = $pdo->query("SELECT kelas_id, nama_kelas FROM kelas ORDER BY nama_kelas");
$kelas = $stmt->fetchAll();

// Ambil semua mata pelajaran (admin bisa melihat semua mapel)
$stmt = $pdo->query("SELECT mapel_id, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel");
$mapel = $stmt->fetchAll();

// Proses filter laporan
$kelas_id = $_GET['kelas_id'] ?? '';
$mapel_id = $_GET['mapel_id'] ?? '';
$bulan = $_GET['bulan'] ?? date('Y-m');

$where = [];
$params = [];

// Hanya tambahkan filter jika dipilih (admin bisa melihat semua)
if (!empty($kelas_id)) {
    $where[] = "j.kelas_id = ?";
    $params[] = $kelas_id;
}

if (!empty($mapel_id)) {
    $where[] = "j.mapel_id = ?";
    $params[] = $mapel_id;
}

// Tambahkan filter bulan
$where[] = "DATE_FORMAT(a.tanggal, '%Y-%m') = ?";
$params[] = $bulan;

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Query untuk rekap absensi (ringkasan) - admin melihat semua
$query = "SELECT
        a.tanggal,
        m.nama_mapel,
        k.nama_kelas,
        u.full_name as nama_guru,
        COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
        COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
        COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
        COUNT(*) as total
        FROM absensi a
        JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
        JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
        JOIN kelas k ON j.kelas_id = k.kelas_id
        JOIN users u ON j.guru_id = u.user_id
        $where_clause
        GROUP BY a.tanggal, m.nama_mapel, k.nama_kelas, u.full_name
        ORDER BY a.tanggal DESC";


$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rekap = $stmt->fetchAll();

// Query untuk detail absensi per hari - admin melihat semua
$query_detail = "SELECT
                a.tanggal,
                m.nama_mapel,
                k.nama_kelas,
                u.full_name as nama_guru,
                mu.nis,
                mu.nama_lengkap,
                a.status,
                a.keterangan
                FROM absensi a
                JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
                JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                JOIN kelas k ON j.kelas_id = k.kelas_id
                JOIN users u ON j.guru_id = u.user_id
                JOIN murid mu ON a.murid_id = mu.murid_id
                $where_clause
                ORDER BY a.tanggal DESC, mu.nama_lengkap ASC";


$stmt_detail = $pdo->prepare($query_detail);
$stmt_detail->execute($params);
$detail_absensi = $stmt_detail->fetchAll();

// Organisasi data detail per tanggal, mapel, dan kelas
$absensi_per_tanggal = [];
foreach ($detail_absensi as $detail) {
    $key = $detail['tanggal'] . '_' . $detail['nama_mapel'] . '_' . $detail['nama_kelas'] . '_' . $detail['nama_guru'];
    if (!isset($absensi_per_tanggal[$key])) {
        $absensi_per_tanggal[$key] = [];
    }
    $absensi_per_tanggal[$key][] = $detail;
}

// Hitung total
$total_hadir = 0;
$total_sakit = 0;
$total_izin = 0;
$total_alpha = 0;
foreach ($rekap as $r) {
    $total_hadir += $r['hadir'];
    $total_sakit += $r['sakit'];
    $total_izin += $r['izin'];
    $total_alpha += $r['alpha'];
}
?>

<?php include '../../../includes/header.php'; ?>

<body>
    <div id="app">
        <!-- Sidebar start -->

        <?php include '../../../includes/navigation/admin.php'; ?>

        <!-- Sidebar end -->

        <!-- Main start -->

        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <h3>LAPORAN</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <!-- Main content start -->

                    <div class="card mb-4 shadow-sm">
                        <div class="card-header">
                            <h6 class="mb-0">Filter Laporan</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-2 align-items-end">
                                <!-- Hapus hidden dari filter kelas dan mapel -->
                                <div class="col-md-3">
                                    <label for="kelas_id" class="form-label">Kelas</label>
                                    <select name="kelas_id" id="kelas_id" class="form-select">
                                        <option value="">Semua Kelas</option>
                                        <?php foreach ($kelas as $k): ?>
                                            <option value="<?= $k['kelas_id'] ?>" <?= ($k['kelas_id'] == $kelas_id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($k['nama_kelas']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="mapel_id" class="form-label">Mata Pelajaran</label>
                                    <select name="mapel_id" id="mapel_id" class="form-select">
                                        <option value="">Semua Mapel</option>
                                        <?php foreach ($mapel as $m): ?>
                                            <option value="<?= $m['mapel_id'] ?>" <?= ($m['mapel_id'] == $mapel_id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['nama_mapel']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="bulan" class="form-label">Bulan</label>
                                    <input type="month" name="bulan" id="bulan" class="form-control" value="<?= $bulan ?>">
                                </div>

                                <div class="col-md-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <a href="index.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-sync-alt me-1"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Rekap Absensi</h5>
                        </div>
                        <div class="card-body p-3">
                            <?php if (empty($rekap)): ?>
                                <div class="alert alert-warning mb-0">Tidak ada data absensi untuk filter yang dipilih.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-hover align-middle text-center mb-2">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Mapel</th>
                                                <th>Kelas</th>
                                                <th>Guru</th>
                                                <th class="text-success">Hadir</th>
                                                <th class="text-warning">Sakit</th>
                                                <th class="text-info">Izin</th>
                                                <th class="text-danger">Alpha</th>
                                                <th>Total</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rekap as $r):
                                                $key = $r['tanggal'] . '_' . $r['nama_mapel'] . '_' . $r['nama_kelas'] . '_' . $r['nama_guru'];
                                                $has_detail = isset($absensi_per_tanggal[$key]) && !empty($absensi_per_tanggal[$key]);
                                            ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                                                    <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($r['nama_kelas']) ?></td>
                                                    <td><?= htmlspecialchars($r['nama_guru']) ?></td>
                                                    <td><?= $r['hadir'] ?></td>
                                                    <td><?= $r['sakit'] ?></td>
                                                    <td><?= $r['izin'] ?></td>
                                                    <td><?= $r['alpha'] ?></td>
                                                    <td><strong><?= $r['total'] ?></strong></td>
                                                    <td>
                                                        <?php if ($has_detail): ?>
                                                            <button class="btn btn-sm btn-primary btn-detail" data-key="<?= htmlspecialchars($key) ?>">
                                                                <i class="fas fa-eye me-1"></i> Detail
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>



                    <!-- Modal untuk detail absensi -->
                    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalTitle">Detail Absensi</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="modalBody">
                                    <!-- Konten akan diisi oleh JavaScript -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        // Konversi data PHP ke JavaScript
                        const absensiDetail = <?= json_encode($absensi_per_tanggal) ?>;
                        const rekapData = <?= json_encode($rekap) ?>;

                        document.querySelectorAll('.btn-detail').forEach(button => {
                            button.addEventListener('click', function() {
                                const key = this.getAttribute('data-key');
                                const modalTitle = document.getElementById('modalTitle');
                                const modalBody = document.getElementById('modalBody');

                                // Cari data rekap yang sesuai
                                const rekapItem = rekapData.find(item =>
                                    (item.tanggal + '_' + item.nama_mapel + '_' + item.nama_kelas + '_' + item.nama_guru) === key
                                );

                                if (rekapItem && absensiDetail[key]) {
                                    // Set judul modal
                                    modalTitle.textContent = `Detail Absensi - ${rekapItem.nama_mapel} - ${rekapItem.nama_kelas} - ${rekapItem.nama_guru} - ${rekapItem.tanggal}`;

                                    // Buat konten tabel
                                    let tableContent = `
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama Murid</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>`;

                                    // Tambahkan baris untuk setiap murid
                                    absensiDetail[key].forEach(detail => {
                                        let statusClass = '';
                                        switch (detail.status) {
                                            case 'hadir':
                                                statusClass = 'text-success fw-bold';
                                                break;
                                            case 'sakit':
                                                statusClass = 'text-warning fw-bold';
                                                break;
                                            case 'izin':
                                                statusClass = 'text-info fw-bold';
                                                break;
                                            case 'alpha':
                                                statusClass = 'text-danger fw-bold';
                                                break;
                                        }

                                        tableContent += `
                        <tr>
                            <td>${detail.nis}</td>
                            <td>${detail.nama_lengkap}</td>
                            <td class="${statusClass}">${detail.status.toUpperCase()}</td>
                            <td>${detail.keterangan || '-'}</td>
                        </tr>`;
                                    });

                                    tableContent += `</tbody></table></div>`;
                                    modalBody.innerHTML = tableContent;

                                    // Tampilkan modal
                                    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                                    modal.show();
                                }
                            });
                        });
                    </script>

                    <!-- Main content end -->
                </section>
            </div>
        </div>

        <!-- Main end -->
    </div>


    <!-- Javascript add start -->

    <!-- your javascript code here -->

    <!-- Javascript add end -->

    <!-- Javascript template mazer start -->
    <script src="<?= $base_url ?>/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= $base_url ?>/assets/js/bootstrap.bundle.min.js"></script>

    <script src="<?= $base_url ?>/assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="<?= $base_url ?>/assets/js/pages/dashboard.js"></script>

    <script src="<?= $base_url ?>/assets/js/main.js"></script>
    <!-- Javascrip template mazer end -->
</body>