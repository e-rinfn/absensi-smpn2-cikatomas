<?php

/**
 * Fungsi helper untuk sistem absensi
 */

// Format tanggal Indonesia
function indonesian_date($date)
{
    $months = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $months[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
}

// Redirect dengan pesan flash
function redirect_with_message($url, $message, $is_error = false)
{
    if ($is_error) {
        $_SESSION['error'] = $message;
    } else {
        $_SESSION['success'] = $message;
    }
    header("Location: $url");
    exit;
}

// Cek apakah request adalah AJAX
function is_ajax_request()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Sanitasi input
function sanitize_input($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Hash password
function hash_password($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}

// Generate NIS otomatis
function generate_nis($tahun_masuk)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT MAX(nis) as last_nis FROM murid WHERE nis LIKE ?");
    $stmt->execute([$tahun_masuk . '%']);
    $result = $stmt->fetch();

    $last_nis = $result['last_nis'];

    if ($last_nis) {
        $sequence = (int) substr($last_nis, -4) + 1;
    } else {
        $sequence = 1;
    }

    return $tahun_masuk . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}

// File: includes/functions.php

/**
 * Mengatur zona waktu default ke WIB (Jakarta, Indonesia)
 */
date_default_timezone_set('Asia/Jakarta');

/**
 * Fungsi untuk mendapatkan waktu sekarang dalam format tertentu
 * 
 * @param string $format Format tanggal (default: 'Y-m-d H:i:s')
 * @return string Tanggal dan waktu yang diformat
 */
function now($format = 'Y-m-d H:i:s')
{
    return date($format);
}

/**
 * Fungsi untuk memformat tanggal Indonesia
 * 
 * @param string $date Tanggal yang akan diformat (format Y-m-d)
 * @return string Tanggal dalam format Indonesia (contoh: 12 Januari 2023)
 */
function formatTanggalIndonesia($date)
{
    if (empty($date)) return '-';

    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    $pecah = explode('-', $date);
    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

/**
 * Fungsi untuk memformat tanggal dan waktu Indonesia
 * 
 * @param string $datetime Tanggal dan waktu (format Y-m-d H:i:s)
 * @return string Format tanggal dan waktu Indonesia
 */
function formatWaktuIndonesia($datetime)
{
    if (empty($datetime)) return '-';

    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    $pecah = explode(' ', $datetime);
    $tanggal = explode('-', $pecah[0]);
    $waktu = substr($pecah[1], 0, 5); // Ambil jam dan menit saja

    return $tanggal[2] . ' ' . $bulan[(int)$tanggal[1]] . ' ' . $tanggal[0] . ' ' . $waktu . ' WIB';
}

/**
 * Fungsi untuk menghitung umur berdasarkan tanggal lahir
 * 
 * @param string $tanggal_lahir Format Y-m-d
 * @return int Umur dalam tahun
 */
function hitungUmur($tanggal_lahir)
{
    if (empty($tanggal_lahir)) return 0;

    $lahir = new DateTime($tanggal_lahir);
    $hari_ini = new DateTime();
    $umur = $hari_ini->diff($lahir);
    return $umur->y;
}

/**
 * Fungsi untuk redirect dengan pesan flash
 * 
 * @param string $url URL tujuan
 * @param string $type Tipe pesan (success, error, warning, info)
 * @param string $message Pesan yang akan ditampilkan
 */
function redirectWithMessage($url, $type, $message)
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    header("Location: $url");
    exit();
}

/**
 * Fungsi untuk menampilkan pesan flash
 * 
 * @return string HTML alert message
 */
function displayFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);

        $alertClass = '';
        switch ($message['type']) {
            case 'success':
                $alertClass = 'alert-success';
                break;
            case 'error':
                $alertClass = 'alert-danger';
                break;
            case 'warning':
                $alertClass = 'alert-warning';
                break;
            case 'info':
                $alertClass = 'alert-info';
                break;
            default:
                $alertClass = 'alert-primary';
        }

        return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
    }
    return '';
}

/**
 * Fungsi untuk memeriksa apakah request adalah AJAX
 * 
 * @return bool True jika request AJAX
 */
function isAjaxRequest()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Fungsi untuk memformat nomor telepon Indonesia
 * 
 * @param string $phone Nomor telepon
 * @return string Nomor telepon yang diformat
 */
function formatPhoneIndonesia($phone)
{
    if (empty($phone)) return '-';

    // Hilangkan semua karakter non-digit
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Cek jika nomor sudah termasuk kode negara
    if (substr($phone, 0, 2) === '62') {
        $phone = '+' . $phone;
    } elseif (substr($phone, 0, 1) === '0') {
        $phone = '+62' . substr($phone, 1);
    }

    return $phone;
}

/**
 * Fungsi untuk mendapatkan hari dalam bahasa Indonesia
 * 
 * @param string $date Tanggal (format Y-m-d)
 * @return string Nama hari dalam bahasa Indonesia
 */
function getHariIndonesia($date)
{
    $hari = array(
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    );

    $day = date('l', strtotime($date));
    return $hari[$day];
}
