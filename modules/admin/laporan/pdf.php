<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Load TCPDF library
require_once '../../../vendor/autoload.php';



// Parameter filter
$kelas_id = $_GET['kelas_id'] ?? null;
$mapel_id = $_GET['mapel_id'] ?? null;
$bulan = $_GET['bulan'] ?? date('Y-m');
$tipe_laporan = $_GET['tipe'] ?? 'harian';

// Query data
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

    $filename = "laporan_harian_{$bulan}.pdf";
    $title = "Laporan Harian Bulan " . date('F Y', strtotime($bulan));
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

    $filename = "laporan_mapel_{$bulan}.pdf";
    $title = "Laporan Per Mata Pelajaran Bulan " . date('F Y', strtotime($bulan));
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

    $filename = "laporan_kelas_{$bulan}.pdf";
    $title = "Laporan Per Kelas Bulan " . date('F Y', strtotime($bulan));
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Sistem Absensi SMP');
$pdf->SetAuthor('Sistem Absensi SMP');
$pdf->SetTitle($title);
$pdf->SetSubject('Laporan Absensi');
$pdf->SetKeywords('Absensi, SMP, Laporan');

// Set default header data
$pdf->SetHeaderData('', 0, 'SMP NEGERI 1 CONTOH', 'Jl. Pendidikan No. 123, Kota Contoh');

// Set header and footer fonts
$pdf->setHeaderFont(['helvetica', '', 10]);
$pdf->setFooterFont(['helvetica', '', 8]);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont('courier');

// Set margins
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(true, 15);

// Set image scale factor
$pdf->setImageScale(1.25);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, $title, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);

// Tambahkan informasi filter
$filter_info = "";
if ($kelas_id) {
    $stmt = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE kelas_id = ?");
    $stmt->execute([$kelas_id]);
    $kelas = $stmt->fetch();
    $filter_info .= "Kelas: " . $kelas['nama_kelas'] . "\n";
}
if ($mapel_id) {
    $stmt = $pdo->prepare("SELECT nama_mapel FROM mata_pelajaran WHERE mapel_id = ?");
    $stmt->execute([$mapel_id]);
    $mapel = $stmt->fetch();
    $filter_info .= "Mata Pelajaran: " . $mapel['nama_mapel'] . "\n";
}
$filter_info .= "Periode: " . date('F Y', strtotime($bulan));

$pdf->MultiCell(0, 10, $filter_info, 0, 'L');

// Create table header
$header = [];
$widths = [];
if ($tipe_laporan == 'harian') {
    $header = ['Tanggal', 'Hadir', 'Sakit', 'Izin', 'Alpha', 'Total', 'Persentase Hadir'];
    $widths = [30, 20, 20, 20, 20, 20, 30];
} elseif ($tipe_laporan == 'mapel') {
    $header = ['Mata Pelajaran', 'Hadir', 'Sakit', 'Izin', 'Alpha', 'Total', 'Persentase Hadir'];
    $widths = [60, 20, 20, 20, 20, 20, 30];
} elseif ($tipe_laporan == 'kelas') {
    $header = ['Kelas', 'Hadir', 'Sakit', 'Izin', 'Alpha', 'Total', 'Persentase Hadir'];
    $widths = [40, 20, 20, 20, 20, 20, 30];
}

// Set table header style
$pdf->SetFillColor(220, 220, 220);
$pdf->SetTextColor(0);
$pdf->SetFont('', 'B');

// Header table
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Data table
$pdf->SetFont('');
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0);

foreach ($laporan as $row) {
    if ($tipe_laporan == 'harian') {
        $pdf->Cell($widths[0], 6, date('d/m/Y', strtotime($row['tanggal'])), 'LR', 0, 'L');
    } elseif ($tipe_laporan == 'mapel') {
        $pdf->Cell($widths[0], 6, $row['nama_mapel'], 'LR', 0, 'L');
    } elseif ($tipe_laporan == 'kelas') {
        $pdf->Cell($widths[0], 6, $row['nama_kelas'], 'LR', 0, 'L');
    }

    $pdf->Cell($widths[1], 6, $row['hadir'], 'LR', 0, 'C');
    $pdf->Cell($widths[2], 6, $row['sakit'], 'LR', 0, 'C');
    $pdf->Cell($widths[3], 6, $row['izin'], 'LR', 0, 'C');
    $pdf->Cell($widths[4], 6, $row['alpha'], 'LR', 0, 'C');
    $pdf->Cell($widths[5], 6, $row['total'], 'LR', 0, 'C');

    $persen = $row['total'] > 0 ? round(($row['hadir'] / $row['total']) * 100, 2) : 0;
    $pdf->Cell($widths[6], 6, $persen . '%', 'LR', 0, 'C');

    $pdf->Ln();
}

// Closing line
$pdf->Cell(array_sum($widths), 0, '', 'T');

// Tambahkan statistik
$pdf->Ln(10);
$pdf->SetFont('', 'B');
$pdf->Cell(0, 6, 'Statistik Keseluruhan:', 0, 1);

$total_hadir = array_sum(array_column($laporan, 'hadir'));
$total_sakit = array_sum(array_column($laporan, 'sakit'));
$total_izin = array_sum(array_column($laporan, 'izin'));
$total_alpha = array_sum(array_column($laporan, 'alpha'));
$grand_total = $total_hadir + $total_sakit + $total_izin + $total_alpha;
$persen_hadir = $grand_total > 0 ? round(($total_hadir / $grand_total) * 100, 2) : 0;

$pdf->SetFont('');
$pdf->Cell(0, 6, "Total Hadir: $total_hadir ($persen_hadir%)", 0, 1);
$pdf->Cell(0, 6, "Total Sakit: $total_sakit", 0, 1);
$pdf->Cell(0, 6, "Total Izin: $total_izin", 0, 1);
$pdf->Cell(0, 6, "Total Alpha: $total_alpha", 0, 1);

// Output PDF
$pdf->Output($filename, 'I');
