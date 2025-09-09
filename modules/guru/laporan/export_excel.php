<?php
require_once __DIR__ . '/../../../includes/auth.php';


// Ambil parameter dari URL
$tanggal = $_GET['tanggal'] ?? '';
$mapel = urldecode($_GET['mapel'] ?? '');
$kelas = urldecode($_GET['kelas'] ?? '');
$jam = urldecode($_GET['jam'] ?? '');
$data = json_decode(urldecode($_GET['data'] ?? '[]'), true);

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
