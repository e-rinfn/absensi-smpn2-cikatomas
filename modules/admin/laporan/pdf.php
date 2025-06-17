<?php
require_once '../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

function namaBulanIndonesia($bulanInggris)
{
    $bulan = [
        'January'   => 'Januari',
        'February'  => 'Februari',
        'March'     => 'Maret',
        'April'     => 'April',
        'May'       => 'Mei',
        'June'      => 'Juni',
        'July'      => 'Juli',
        'August'    => 'Agustus',
        'September' => 'September',
        'October'   => 'Oktober',
        'November'  => 'November',
        'December'  => 'Desember'
    ];

    return $bulan[$bulanInggris] ?? $bulanInggris;
}



// Load TCPDF library
require_once '../../../vendor/autoload.php';

// Parameter filter
$kelas_id = $_GET['kelas_id'] ?? null;
$mapel_id = $_GET['mapel_id'] ?? null;
$bulan = $_GET['bulan'] ?? date('Y-m');
$tipe_laporan = $_GET['tipe'] ?? 'harian';

// Query data (sama seperti sebelumnya)
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

// Perbaikan query dengan join yang benar
if ($tipe_laporan == 'harian') {
    $sql = "SELECT a.tanggal, 
                   COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                   COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
                   COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
                   COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
                   COUNT(*) as total
            FROM absensi a
            JOIN murid m ON a.murid_id = m.murid_id
            JOIN kelas k ON m.kelas_id = k.kelas_id
            JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
            $where_clause
            GROUP BY a.tanggal
            ORDER BY a.tanggal DESC";
} elseif ($tipe_laporan == 'mapel') {
    $sql = "SELECT mp.nama_mapel,
                   COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as hadir,
                   COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as sakit,
                   COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as izin,
                   COUNT(CASE WHEN a.status = 'alpha' THEN 1 END) as alpha,
                   COUNT(*) as total
            FROM absensi a
            JOIN jadwal_pelajaran j ON a.jadwal_id = j.jadwal_id
            JOIN mata_pelajaran mp ON j.mapel_id = mp.mapel_id
            JOIN murid m ON a.murid_id = m.murid_id
            $where_clause
            GROUP BY mp.mapel_id
            ORDER BY mp.nama_mapel";
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
}
$filename = "laporan_kelas_{$bulan}.pdf";
$title = "Laporan Per Kelas Bulan " . date('m Y', strtotime($bulan));

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();


// Path ke logo sekolah - gunakan path absolut server
$logo_path = __DIR__ . '/Logo.png';

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Sistem Absensi SMP');
$pdf->SetAuthor('Sistem Absensi SMP');
$pdf->SetTitle($title);
$pdf->SetSubject('Laporan Absensi');
$pdf->SetKeywords('Absensi, SMP, Laporan');

// Remove default header/footer
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);

// Set header data dengan logo
$pdf->SetHeaderData($logo_path, 15, '', '', array(0, 0, 0), array(255, 255, 255));
$pdf->setHeaderFont(array('helvetica', '', 10));

// Set margins
$pdf->SetMargins(15, 30, 15); // Margin atas diperbesar untuk logo
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Judul Laporan
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN ABSENSI SISWA', 0, 1, 'C');
$pdf->Cell(0, 5, strtoupper($title), 0, 1, 'C'); // Judul spesifik
$pdf->Ln(5); // Spasi

// Informasi Sekolah
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'SMP NEGERI 2 CIKATOMAS', 0, 1, 'C');
$pdf->Cell(0, 5, 'Jl. Pendidikan No. 123, Kec. Cikatomas, Kab. Tasikmalaya', 0, 1, 'C');
$pdf->Cell(0, 5, 'Telp. (0265) 123456 | Email: smpn2cikatomas@sch.id', 0, 1, 'C');
$pdf->Ln(8); // Spasi

// Garis pembatas
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(8); // Spasi

// Informasi Filter
$pdf->SetFont('helvetica', '', 10);
$filter_info = "";

if ($kelas_id) {
    $stmt = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE kelas_id = ?");
    $stmt->execute([$kelas_id]);
    $kelas = $stmt->fetch();
    $filter_info .= "Kelas: " . $kelas['nama_kelas'] . " | ";
}

if ($mapel_id) {
    $stmt = $pdo->prepare("SELECT nama_mapel FROM mata_pelajaran WHERE mapel_id = ?");
    $stmt->execute([$mapel_id]);
    $mapel = $stmt->fetch();
    $filter_info .= "Mata Pelajaran: " . $mapel['nama_mapel'] . " | ";
}

$timestamp = strtotime($bulan); // contoh $bulan = '2025-06'
$namaBulan = namaBulanIndonesia(date('F', $timestamp));
$tahun = date('Y', $timestamp);
$filter_info .= "Periode: " . $namaBulan . " " . $tahun;

$pdf->Cell(0, 6, $filter_info, 0, 1);
$pdf->Ln(5); // Spasi

$pdf->SetFont('helvetica', 'B', 10);

// Header Tabel
$header = [];
$widths = [];
$aligns = [];

if ($tipe_laporan == 'harian') {
    $header = ['No', 'Tanggal', 'Hadir', 'Sakit', 'Izin', 'Alpha', 'Total', '% Hadir'];
    $widths = [10, 75, 15, 15, 15, 15, 15, 20];
    $aligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C'];
} elseif ($tipe_laporan == 'mapel') {
    $header = ['No', 'Mata Pelajaran', 'Hadir', 'Sakit', 'Izin', 'Alpha', 'Total', '% Hadir'];
    $widths = [10, 75, 15, 15, 15, 15, 15, 20];
    $aligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C'];
} elseif ($tipe_laporan == 'kelas') {
    $header = ['No', 'Kelas', 'Hadir', 'Sakit', 'Izin', 'Alpha', 'Total', '% Hadir'];
    $widths = [10, 75, 15, 15, 15, 15, 15, 20];
    $aligns = ['C', 'L', 'C', 'C', 'C', 'C', 'C', 'C'];
}

// Warna header tabel
$pdf->SetFillColor(220, 220, 220);
$pdf->SetTextColor(0);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);

// Cetak Header
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Data
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(255, 255, 255);

$no = 1;
foreach ($laporan as $row) {
    // Kolom No
    $pdf->Cell($widths[0], 6, $no++, 'LR', 0, $aligns[0]);

    // Kolom pertama (dinamis)
    if ($tipe_laporan == 'harian') {
        $pdf->Cell($widths[1], 6, date('d/m/Y', strtotime($row['tanggal'])), 'LR', 0, $aligns[1]);
    } elseif ($tipe_laporan == 'mapel') {
        $pdf->Cell($widths[1], 6, $row['nama_mapel'], 'LR', 0, $aligns[1]);
    } elseif ($tipe_laporan == 'kelas') {
        $pdf->Cell($widths[1], 6, $row['nama_kelas'], 'LR', 0, $aligns[1]);
    }

    // Kolom data
    $pdf->Cell($widths[2], 6, $row['hadir'], 'LR', 0, $aligns[2]);
    $pdf->Cell($widths[3], 6, $row['sakit'], 'LR', 0, $aligns[3]);
    $pdf->Cell($widths[4], 6, $row['izin'], 'LR', 0, $aligns[4]);
    $pdf->Cell($widths[5], 6, $row['alpha'], 'LR', 0, $aligns[5]);
    $pdf->Cell($widths[6], 6, $row['total'], 'LR', 0, $aligns[6]);

    $persen = $row['total'] > 0 ? round(($row['hadir'] / $row['total']) * 100, 2) : 0;
    $pdf->Cell($widths[7], 6, $persen . '%', 'LR', 0, $aligns[7]);

    $pdf->Ln();
}

// Closing line
$pdf->Cell(array_sum($widths), 0, '', 'T');
$pdf->Ln(10);

// Statistik Keseluruhan
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'REKAPITULASI KEHADIRAN', 0, 1);
$pdf->SetFont('helvetica', '', 10);

$total_hadir = array_sum(array_column($laporan, 'hadir'));
$total_sakit = array_sum(array_column($laporan, 'sakit'));
$total_izin = array_sum(array_column($laporan, 'izin'));
$total_alpha = array_sum(array_column($laporan, 'alpha'));
$grand_total = $total_hadir + $total_sakit + $total_izin + $total_alpha;
$persen_hadir = $grand_total > 0 ? round(($total_hadir / $grand_total) * 100, 2) : 0;

$pdf->Cell(0, 6, "Jumlah Hadir: $total_hadir siswa ($persen_hadir%)", 0, 1);
$pdf->Cell(0, 6, "Jumlah Sakit: $total_sakit siswa", 0, 1);
$pdf->Cell(0, 6, "Jumlah Izin: $total_izin siswa", 0, 1);
$pdf->Cell(0, 6, "Jumlah Alpha: $total_alpha siswa", 0, 1);
$pdf->Cell(0, 6, "Total Siswa: $grand_total siswa", 0, 1);
$pdf->Ln(10);

// Tanda tangan
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Mengetahui,', 0, 1, 'R');
$pdf->Ln(15);
$pdf->Cell(0, 5, '(__________________________)', 0, 1, 'R');
$pdf->Cell(0, 5, 'Kepala Sekolah', 0, 1, 'R');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'SMP NEGERI 2 CIKATOMAS', 0, 1, 'R');

// Output PDF
$pdf->Output($filename, 'I');
