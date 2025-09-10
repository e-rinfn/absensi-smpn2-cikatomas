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
$semester = $_GET['semester'] ?? ''; // Pindahkan deklarasi semester ke sini

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

// Hapus filter bulan
// if (!empty($bulan)) {
//     $where[] = "DATE_FORMAT(a.tanggal, '%Y-%m') = ?";
//     $params[] = $bulan;
// }

// Filter semester
if (!empty($semester)) {
    if ($semester == '1') {
        // Semester 1: Januari - Juni
        $where[] = "MONTH(a.tanggal) BETWEEN 1 AND 6";
    } elseif ($semester == '2') {
        // Semester 2: Juli - Desember
        $where[] = "MONTH(a.tanggal) BETWEEN 7 AND 12";
    }
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Query untuk rekap absensi (ringkasan) - admin melihat semua (HAPUS filter guru_id)
$query = "SELECT
        a.tanggal,
        m.nama_mapel,
        k.nama_kelas,
        j.jam_mulai,
        j.jam_selesai,
        COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
        COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
        COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
        COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
        COUNT(*) as total
        FROM absensi a
        JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
        JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
        JOIN kelas k ON j.kelas_id = k.kelas_id
        $where_clause
        GROUP BY a.tanggal, m.nama_mapel, k.nama_kelas, j.jam_mulai, j.jam_selesai
        ORDER BY a.tanggal DESC, j.jam_mulai ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rekap = $stmt->fetchAll();

// Query untuk detail absensi per hari - admin melihat semua (HAPUS filter guru_id)
$query_detail = "SELECT
                a.tanggal,
                m.nama_mapel,
                k.nama_kelas,
                j.jam_mulai,
                j.jam_selesai,
                mu.nis,
                mu.nama_lengkap,
                a.status,
                a.keterangan
                FROM absensi a
                JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
                JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
                JOIN kelas k ON j.kelas_id = k.kelas_id
                JOIN murid mu ON a.murid_id = mu.murid_id
                $where_clause
                ORDER BY a.tanggal DESC, j.jam_mulai ASC, mu.nama_lengkap ASC";

$stmt_detail = $pdo->prepare($query_detail);
$stmt_detail->execute($params);
$detail_absensi = $stmt_detail->fetchAll();

// Organisasi data detail per tanggal, mapel, kelas, dan JAM
$absensi_per_tanggal = [];
foreach ($detail_absensi as $detail) {
    // Update key untuk menyertakan jam
    $key = $detail['tanggal'] . '_' . $detail['nama_mapel'] . '_' . $detail['nama_kelas'] . '_' . $detail['jam_mulai'] . '_' . $detail['jam_selesai'];
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

                                <!-- Hapus input bulan -->
                                <!-- <div class="col-md-3">
                                    <label for="bulan" class="form-label">Bulan</label>
                                    <input type="month" name="bulan" id="bulan" class="form-control" value="<?= $bulan ?>">
                                </div> -->

                                <div class="col-md-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <select name="semester" id="semester" class="form-select">
                                        <option value="">Semua Semester</option>
                                        <option value="1" <?= ($semester == '1') ? 'selected' : '' ?>>Semester 1 (Jan - Jun)</option>
                                        <option value="2" <?= ($semester == '2') ? 'selected' : '' ?>>Semester 2 (Jul - Des)</option>
                                    </select>
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
                                                <th>Jam</th>
                                                <th>Mapel</th>
                                                <th>Kelas</th>
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
                                                // Update key untuk menyertakan jam
                                                $key = $r['tanggal'] . '_' . $r['nama_mapel'] . '_' . $r['nama_kelas'] . '_' . $r['jam_mulai'] . '_' . $r['jam_selesai'];
                                                $has_detail = isset($absensi_per_tanggal[$key]) && !empty($absensi_per_tanggal[$key]);
                                            ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                                                    <td><?= date('H:i', strtotime($r['jam_mulai'])) ?> - <?= date('H:i', strtotime($r['jam_selesai'])) ?></td>
                                                    <td><?= htmlspecialchars($r['nama_mapel']) ?></td>
                                                    <td><?= htmlspecialchars($r['nama_kelas']) ?></td>
                                                    <td><?= $r['hadir'] ?></td>
                                                    <td><?= $r['sakit'] ?></td>
                                                    <td><?= $r['izin'] ?></td>
                                                    <td><?= $r['alpha'] ?></td>
                                                    <td><strong><?= $r['total'] ?></strong></td>
                                                    <td>
                                                        <?php if ($has_detail): ?>
                                                            <button class="btn btn-sm btn-primary btn-detail" data-key="<?= htmlspecialchars($key) ?>"
                                                                data-tanggal="<?= $r['tanggal'] ?>"
                                                                data-mapel="<?= htmlspecialchars($r['nama_mapel']) ?>"
                                                                data-kelas="<?= htmlspecialchars($r['nama_kelas']) ?>"
                                                                data-jam="<?= date('H:i', strtotime($r['jam_mulai'])) ?> - <?= date('H:i', strtotime($r['jam_selesai'])) ?>">
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
                                    <!-- Filter status akan ditambahkan di sini -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="searchStudent" placeholder="Cari nama atau NIS...">
                                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="form-select" id="statusFilter">
                                                <option value="all">Semua Status</option>
                                                <option value="hadir">Hadir</option>
                                                <option value="sakit">Sakit</option>
                                                <option value="izin">Izin</option>
                                                <option value="alpha">Alpha</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Konten tabel akan diisi oleh JavaScript -->
                                    <div id="tableContainer"></div>
                                </div>
                                <div class="modal-footer">
                                    <!-- Tombol untuk cetak PDF dan export Excel -->
                                    <div class="me-auto">
                                        <button type="button" class="btn btn-success btn-sm" id="btnExportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Export Excel
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" id="btnCetakPDF">
                                            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        // Konversi data PHP ke JavaScript
                        const absensiDetail = <?= json_encode($absensi_per_tanggal) ?>;
                        let currentKey = ''; // Untuk menyimpan key data yang sedang dilihat
                        let currentData = []; // Untuk menyimpan data yang sedang ditampilkan

                        document.querySelectorAll('.btn-detail').forEach(button => {
                            button.addEventListener('click', function() {
                                const key = this.getAttribute('data-key');
                                const tanggal = this.getAttribute('data-tanggal');
                                const mapel = this.getAttribute('data-mapel');
                                const kelas = this.getAttribute('data-kelas');
                                const jam = this.getAttribute('data-jam');

                                // Simpan data untuk digunakan oleh fungsi export
                                currentKey = key;
                                document.getElementById('btnExportExcel').setAttribute('data-tanggal', tanggal);
                                document.getElementById('btnExportExcel').setAttribute('data-mapel', mapel);
                                document.getElementById('btnExportExcel').setAttribute('data-kelas', kelas);
                                document.getElementById('btnExportExcel').setAttribute('data-jam', jam);

                                document.getElementById('btnCetakPDF').setAttribute('data-tanggal', tanggal);
                                document.getElementById('btnCetakPDF').setAttribute('data-mapel', mapel);
                                document.getElementById('btnCetakPDF').setAttribute('data-kelas', kelas);
                                document.getElementById('btnCetakPDF').setAttribute('data-jam', jam);

                                const modalTitle = document.getElementById('modalTitle');
                                const tableContainer = document.getElementById('tableContainer');

                                // Set judul modal dengan informasi jam
                                modalTitle.textContent = `Detail Absensi - ${mapel} - ${kelas} - ${tanggal} (${jam})`;

                                if (absensiDetail[key]) {
                                    // Simpan data untuk filtering
                                    currentData = absensiDetail[key];

                                    // Render tabel dengan data lengkap
                                    renderTable(currentData);

                                    // Reset filter
                                    document.getElementById('searchStudent').value = '';
                                    document.getElementById('statusFilter').value = 'all';

                                    // Tampilkan modal
                                    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                                    modal.show();
                                }
                            });
                        });

                        // Fungsi untuk merender tabel berdasarkan data
                        function renderTable(data) {
                            if (data.length === 0) {
                                document.getElementById('tableContainer').innerHTML = `
            <div class="alert alert-info text-center">
                Tidak ada data absensi.
            </div>
        `;
                                return;
                            }

                            // Hitung jumlah status
                            let countHadir = 0,
                                countSakit = 0,
                                countIzin = 0,
                                countAlpha = 0;

                            let tableContent = `
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>NIS</th>
                        <th>Nama Murid</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>`;

                            // Tambahkan baris untuk setiap murid dengan nomor urut
                            data.forEach((detail, index) => {
                                let statusClass = '';
                                switch (detail.status) {
                                    case 'hadir':
                                        statusClass = 'text-success fw-bold';
                                        countHadir++;
                                        break;
                                    case 'sakit':
                                        statusClass = 'text-warning fw-bold';
                                        countSakit++;
                                        break;
                                    case 'izin':
                                        statusClass = 'text-info fw-bold';
                                        countIzin++;
                                        break;
                                    case 'alpha':
                                        statusClass = 'text-danger fw-bold';
                                        countAlpha++;
                                        break;
                                }

                                tableContent += `
            <tr>
                <td>${index + 1}</td>
                <td>${detail.nis}</td>
                <td>${detail.nama_lengkap}</td>
                <td class="${statusClass}">${detail.status.toUpperCase()}</td>
                <td>${detail.keterangan || '-'}</td>
            </tr>`;
                            });

                            tableContent += `
                </tbody>
            </table>
        </div>
        <div class="mt-2">
            <strong>Ringkasan:</strong> 
            Hadir: <span class="text-success">${countHadir}</span>, 
            Sakit: <span class="text-warning">${countSakit}</span>, 
            Izin: <span class="text-info">${countIzin}</span>, 
            Alpha: <span class="text-danger">${countAlpha}</span>
        </div>
    `;

                            document.getElementById('tableContainer').innerHTML = tableContent;
                        }


                        // Fungsi untuk memfilter data
                        function filterData() {
                            const searchText = document.getElementById('searchStudent').value.toLowerCase();
                            const statusFilter = document.getElementById('statusFilter').value;

                            let filteredData = [...currentData];

                            // Filter berdasarkan pencarian teks
                            if (searchText) {
                                filteredData = filteredData.filter(item =>
                                    item.nama_lengkap.toLowerCase().includes(searchText) ||
                                    item.nis.toLowerCase().includes(searchText)
                                );
                            }

                            // Filter berdasarkan status
                            if (statusFilter !== 'all') {
                                filteredData = filteredData.filter(item => item.status === statusFilter);
                            }

                            // Render tabel dengan data yang sudah difilter
                            renderTable(filteredData);
                        }

                        // Event listener untuk filter
                        document.getElementById('searchStudent').addEventListener('input', filterData);
                        document.getElementById('statusFilter').addEventListener('change', filterData);

                        // Event listener untuk clear search
                        document.getElementById('clearSearch').addEventListener('click', function() {
                            document.getElementById('searchStudent').value = '';
                            filterData();
                        });

                        // Fungsi untuk export Excel
                        document.getElementById('btnExportExcel').addEventListener('click', function() {
                            const tanggal = this.getAttribute('data-tanggal');
                            const mapel = this.getAttribute('data-mapel');
                            const kelas = this.getAttribute('data-kelas');
                            const jam = this.getAttribute('data-jam');

                            const params = new URLSearchParams({
                                tanggal: tanggal,
                                mapel: mapel,
                                kelas: kelas,
                                jam: jam
                            });

                            window.open('export_excel.php?' + params.toString(), '_blank');
                        });

                        // Fungsi untuk cetak PDF
                        document.getElementById('btnCetakPDF').addEventListener('click', function() {
                            const tanggal = this.getAttribute('data-tanggal');
                            const mapel = this.getAttribute('data-mapel');
                            const kelas = this.getAttribute('data-kelas');
                            const jam = this.getAttribute('data-jam');

                            const params = new URLSearchParams({
                                tanggal: tanggal,
                                mapel: mapel,
                                kelas: kelas,
                                jam: jam
                            });

                            window.open('cetak_pdf.php?' + params.toString(), '_blank');
                        });
                    </script>

                    <!-- Main content end -->
                </section>
            </div>
        </div>

        <!-- Main end -->
    </div>


    <!-- Javascript template mazer start -->
    <script src="<?= $base_url ?>/assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="<?= $base_url ?>/assets/js/bootstrap.bundle.min.js"></script>

    <script src="<?= $base_url ?>/assets/vendors/apexcharts/apexcharts.js"></script>
    <script src="<?= $base_url ?>/assets/js/pages/dashboard.js"></script>

    <script src="<?= $base_url ?>/assets/js/main.js"></script>
    <!-- Javascrip template mazer end -->
</body>