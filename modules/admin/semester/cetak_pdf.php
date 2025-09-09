<?php
require_once __DIR__ . '/../../../includes/auth.php';
if (!isAdmin()) {
    header('Location: /sis-absensi-smp/login.php');
    exit;
}

// Load library TCPDF
require_once __DIR__ . '/../../../vendor/tecnickcom/tcpdf/tcpdf.php';

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

// Format tanggal Indonesia
function formatTanggalIndonesia($tanggal)
{
    $bulan = array(
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    );

    $tanggal_arr = explode('-', $tanggal);
    $tahun = $tanggal_arr[0];
    $bulan_num = (int)$tanggal_arr[1];
    $hari = (int)$tanggal_arr[2];

    return $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

$tanggal_formatted = formatTanggalIndonesia($tanggal);

// Buat class custom PDF
class ABSENSI_PDF extends TCPDF
{
    public function Header()
    {
        $this->SetFont('times', '', 10);
        $image_file = __DIR__ . '/../../../assets/images/Logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 20, '', 'PNG');
        }

        $this->SetXY(40, 10);
        $this->SetFont('times', 'B', 12);
        $this->SetX(66);
        $this->Cell(0, 5, 'PEMERINTAH KOTA TASIKMALAYA', 0, 1, 'L');
        $this->Cell(0, 5, 'DINAS PENDIDIKAN', 0, 1, 'C');
        $this->SetFont('times', 'B', 14);
        $this->Cell(0, 6, 'SMP TERPADU BUGELAN', 0, 1, 'C');
        $this->SetFont('times', '', 10);
        $this->Cell(0, 5, 'Jl. Raya Bugelan No. 123, Kota Tasikmalaya, Jawa Barat', 0, 1, 'C');
        $this->Cell(0, 5, 'Telp. (0265) 7654321 | Email: smpterpadubugelan@example.com', 0, 1, 'C');
        $this->Line(10, 42, 200, 42);
        $this->Ln(5);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->getAliasNumPage() . ' dari ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// Create new PDF document
$pdf = new ABSENSI_PDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Sistem Absensi SMP');
$pdf->SetAuthor('Administrator');
$pdf->SetTitle('Laporan Absensi - ' . $mapel);
$pdf->SetSubject('Laporan Detail Absensi');

// Set margin
$pdf->SetMargins(15, 50, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

// Judul laporan
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN ABSENSI SISWA', 0, 1, 'C');
$pdf->SetFont('times', '', 11);
$pdf->Cell(0, 6, 'Periode: ' . $tanggal_formatted, 0, 1, 'C');
$pdf->Ln(3);

// Informasi detail
$pdf->SetFont('times', '', 10);
$pdf->Cell(40, 5, 'Mata Pelajaran', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, $mapel, 0, 1);
$pdf->Cell(40, 5, 'Kelas', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, $kelas, 0, 1);
$pdf->Cell(40, 5, 'Jam Pelajaran', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, $jam, 0, 1);
$pdf->Cell(40, 5, 'Jumlah Siswa', 0, 0);
$pdf->Cell(5, 5, ':', 0, 0);
$pdf->Cell(0, 5, count($data) . ' orang', 0, 1);
$pdf->Ln(5);

// Tabel data absensi
$html = '<table border="1" cellpadding="4" width="100%">
    <thead>
        <tr style="background-color:#f2f2f2;text-align:center;font-weight:bold;">
            <th width="5%">No</th>
            <th width="15%">NIS</th>
            <th width="40%">Nama Siswa</th>
            <th width="15%">Status</th>
            <th width="25%">Keterangan</th>
        </tr>
    </thead>
    <tbody>';

if (empty($data)) {
    $html .= '<tr><td colspan="5" style="text-align:center;">Tidak ada data absensi</td></tr>';
} else {
    foreach ($data as $key => $item) {
        $statusClass = '';
        switch ($item['status']) {
            case 'hadir':
                $statusClass = 'color: #2e7d32; font-weight: bold;';
                break;
            case 'sakit':
                $statusClass = 'color: #ff8f00; font-weight: bold;';
                break;
            case 'izin':
                $statusClass = 'color: #0277bd; font-weight: bold;';
                break;
            case 'alpha':
                $statusClass = 'color: #c62828; font-weight: bold;';
                break;
        }

        $html .= '<tr>
            <td width="5%" style="text-align:center;">' . ($key + 1) . '</td>
            <td width="15%" style="text-align:center;">' . $item['nis'] . '</td>
            <td width="40%">' . $item['nama_lengkap'] . '</td>
            <td width="15%" style="text-align:center; ' . $statusClass . '">' . strtoupper($item['status']) . '</td>
            <td width="25%">' . ($item['keterangan'] ?: '-') . '</td>
        </tr>';
    }
}

$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(10);

// Tanda tangan
// $pdf->SetFont('times', '', 10);
// $pdf->Cell(0, 0, 'Tasikmalaya, ' . date('d F Y'), 0, 1, 'R');
// $pdf->Ln(10);
// $pdf->SetFont('times', 'B', 10);
// $pdf->Cell(0, 0, 'Guru Mata Pelajaran', 0, 1, 'R');
// $pdf->Ln(15);
// $pdf->SetFont('times', 'BU', 10);
// $pdf->Cell(0, 0, 'Nama Guru', 0, 1, 'R');
// $pdf->Ln(5);
// $pdf->SetFont('times', '', 10);
// $pdf->Cell(0, 0, 'NIP. 1234567890', 0, 1, 'R');

// Output PDF
$filename = "Laporan_Absensi_" . $mapel . "_" . $tanggal . ".pdf";
$pdf->Output($filename, 'I');
exit;
