<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Ambil parameter yang sama dengan index.php
$kelas_id = $_GET['kelas_id'] ?? null;
$mapel_id = $_GET['mapel_id'] ?? null;
$bulan = $_GET['bulan'] ?? date('Y-m');
$tipe_laporan = $_GET['tipe'] ?? 'harian';

// Query data (sama dengan index.php)
$where = [];
$params = [];

if ($kelas_id) {
    $where[] = "m.kelas_id = ?";
    $params[] = $kelas_id;
}

if ($mapel_id) {
    $where[] = "j.mapel_id = ?";
    $params[] = $mapel_id;
}

$where[] = "DATE_FORMAT(a.tanggal, '%Y-%m') = ?";
$params[] = $bulan;

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

if ($tipe_laporan == 'harian') {
    $sql = "SELECT a.tanggal, 
                   COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                   COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
                   COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
                   COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
                   COUNT(*) as total
            FROM absensi a
            JOIN murid m ON a.murid_id = m.murid_id
            JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
            $where_clause
            GROUP BY a.tanggal
            ORDER BY a.tanggal DESC";

    $filename = "laporan_harian_{$bulan}.xls";
} elseif ($tipe_laporan == 'mapel') {
    $sql = "SELECT m.nama_mapel,
                   COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                   COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
                   COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
                   COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
                   COUNT(*) as total
            FROM absensi a
            JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
            JOIN mata_pelajaran m ON j.mapel_id = m.mapel_id
            $where_clause
            GROUP BY m.mapel_id
            ORDER BY m.nama_mapel";

    $filename = "laporan_mapel_{$bulan}.xls";
} elseif ($tipe_laporan == 'kelas') {
    $sql = "SELECT k.nama_kelas,
                   COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                   COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
                   COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
                   COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
                   COUNT(*) as total
            FROM absensi a
            JOIN murid m ON a.murid_id = m.murid_id
            JOIN kelas k ON m.kelas_id = k.kelas_id
            $where_clause
            GROUP BY k.kelas_id
            ORDER BY k.nama_kelas";

    $filename = "laporan_kelas_{$bulan}.xls";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// Header untuk download file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Output data
echo "Laporan Absensi " . ucfirst($tipe_laporan) . " - Bulan " . date('F Y', strtotime($bulan)) . "\n\n";

if ($tipe_laporan == 'harian') {
    echo "Tanggal\tHadir\tSakit\tIzin\tAlpha\tTotal\tPersentase Hadir\n";
    foreach ($laporan as $row) {
        $persen = $row['total'] > 0 ? round(($row['hadir'] / $row['total']) * 100, 2) : 0;
        echo date('d/m/Y', strtotime($row['tanggal'])) . "\t";
        echo $row['hadir'] . "\t";
        echo $row['sakit'] . "\t";
        echo $row['izin'] . "\t";
        echo $row['alpha'] . "\t";
        echo $row['total'] . "\t";
        echo $persen . "%\n";
    }
} elseif ($tipe_laporan == 'mapel') {
    echo "Mata Pelajaran\tHadir\tSakit\tIzin\tAlpha\tTotal\tPersentase Hadir\n";
    foreach ($laporan as $row) {
        $persen = $row['total'] > 0 ? round(($row['hadir'] / $row['total']) * 100, 2) : 0;
        echo $row['nama_mapel'] . "\t";
        echo $row['hadir'] . "\t";
        echo $row['sakit'] . "\t";
        echo $row['izin'] . "\t";
        echo $row['alpha'] . "\t";
        echo $row['total'] . "\t";
        echo $persen . "%\n";
    }
} elseif ($tipe_laporan == 'kelas') {
    echo "Kelas\tHadir\tSakit\tIzin\tAlpha\tTotal\tPersentase Hadir\n";
    foreach ($laporan as $row) {
        $persen = $row['total'] > 0 ? round(($row['hadir'] / $row['total']) * 100, 2) : 0;
        echo $row['nama_kelas'] . "\t";
        echo $row['hadir'] . "\t";
        echo $row['sakit'] . "\t";
        echo $row['izin'] . "\t";
        echo $row['alpha'] . "\t";
        echo $row['total'] . "\t";
        echo $persen . "%\n";
    }
}
exit;
