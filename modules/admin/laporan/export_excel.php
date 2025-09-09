<?php
require_once __DIR__ . '/../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil parameter dari URL
$tanggal = $_GET['tanggal'] ?? '';
$mapel   = $_GET['mapel'] ?? '';
$kelas   = $_GET['kelas'] ?? '';
$jam     = $_GET['jam'] ?? '';

// Pecah jam jadi jam_mulai & jam_selesai
list($jam_mulai, $jam_selesai) = array_map('trim', explode('-', $jam));

// Query ulang data detail absensi
$sql = "SELECT mu.nis, mu.nama_lengkap, a.status, a.keterangan
        FROM absensi a
        JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
        JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
        JOIN kelas k ON j.kelas_id = k.kelas_id
        JOIN murid mu ON a.murid_id = mu.murid_id
        WHERE a.tanggal = ? AND m.nama_mapel = ? 
              AND k.nama_kelas = ? 
              AND j.jam_mulai = ? AND j.jam_selesai = ?
        ORDER BY mu.nama_lengkap ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$tanggal, $mapel, $kelas, $jam_mulai, $jam_selesai]);
$data = $stmt->fetchAll();

// Header untuk download file Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="laporan_absensi_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Output content
echo '<table border="1">';
echo '<tr><th colspan="5" style="text-align:center;font-size:16px;background-color:#e0e0e0;">LAPORAN ABSENSI</th></tr>';
echo '<tr><th colspan="5" style="text-align:center;">Mata Pelajaran: ' . $mapel . '</th></tr>';
echo '<tr><th colspan="5" style="text-align:center;">Kelas: ' . $kelas . ' | Tanggal: ' . $tanggal . ' | Jam: ' . $jam . '</th></tr>';
echo '<tr><th colspan="5"></th></tr>';
echo '<tr style="background-color:#f0f0f0;">';
echo '<th>No</th>';
echo '<th>NIS</th>';
echo '<th>Nama Murid</th>';
echo '<th>Status</th>';
echo '<th>Keterangan</th>';
echo '</tr>';

foreach ($data as $index => $row) {
    echo '<tr>';
    echo '<td>' . ($index + 1) . '</td>';
    echo '<td>' . $row['nis'] . '</td>';
    echo '<td>' . $row['nama_lengkap'] . '</td>';
    echo '<td>' . strtoupper($row['status']) . '</td>';
    echo '<td>' . ($row['keterangan'] ?: '-') . '</td>';
    echo '</tr>';
}

echo '</table>';
exit;
