<?php
require_once 'database.php';

date_default_timezone_set('Asia/Jakarta');

// Bersihkan lock yang expired di awal setiap request
// cleanupExpiredLocks();

// Atau buat cron job untuk membersihkan lock yang expired
// 0 * * * * php /path/to/cleanup_locks.php

/***************************************
 * Fungsi: dateIndo
 * Deskripsi:
 *   Mengubah format tanggal dari format
 *   standar (YYYY-MM-DD) menjadi format
 *   tanggal Indonesia (DD NamaBulan YYYY).
 *
 * Parameter:
 *   - $tanggal : Tanggal dalam format apa pun
 *                yang dikenali oleh strtotime().
 *
 * Return:
 *   String tanggal dalam format Indonesia,
 *   contoh: "25 Desember 2025".
 ***************************************/
function dateIndo($tanggal)
{
    // Array nama bulan dalam bahasa Indonesia
    $bulanIndo = [
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
    ];

    // Normalisasi tanggal ke format YYYY-MM-DD
    $tanggal = date('Y-m-d', strtotime($tanggal));

    // Memecah tanggal menjadi bagian tahun, bulan, dan hari
    $pecah = explode('-', $tanggal);

    // Mengembalikan format tanggal Indonesia
    return $pecah[2] . ' ' . $bulanIndo[(int)$pecah[1]] . ' ' . $pecah[0];
}



/******************************************************
 * Fungsi: isLoggedIn()
 * Deskripsi:
 *   Memeriksa apakah pengguna saat ini telah login.
 *   Pemeriksaan dilakukan dengan memastikan variabel
 *   sesi 'user_id' sudah diset ketika proses login
 *   berhasil.
 *
 * Return:
 *   - true  : Jika user_id ada dalam session.
 *   - false : Jika user_id tidak ditemukan.
 ******************************************************/
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}



/******************************************************
 * Fungsi: redirectIfNotLoggedIn()
 * Deskripsi:
 *   Digunakan untuk membatasi akses halaman yang
 *   membutuhkan autentikasi login. Jika pengguna belum
 *   login (isLoggedIn() mengembalikan false), maka
 *   pengguna akan langsung diarahkan ke halaman login.
 *
 * Catatan:
 *   - exit() digunakan untuk menghentikan eksekusi PHP
 *     setelah melakukan redirect.
 ******************************************************/
function redirectIfNotLoggedIn()
{
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}



/******************************************************
 * Fungsi: checkRole($requiredRole)
 * Deskripsi:
 *   Memvalidasi apakah pengguna memiliki role yang sesuai
 *   untuk mengakses halaman tertentu. Jika role pengguna
 *   tidak cocok dengan role yang diperlukan, maka pengguna
 *   akan diarahkan ke halaman utama.
 *
 * Parameter:
 *   - $requiredRole : Peran (role) yang wajib dimiliki
 *                     untuk mengakses halaman.
 ******************************************************/
function checkRole($requiredRole)
{
    if ($_SESSION['role'] != $requiredRole) {
        header("Location: ../index.php");
        exit();
    }
}



/******************************************************
 * Fungsi: formatRupiah($angka)
 * Deskripsi:
 *   Mengubah nilai numerik menjadi format rupiah dengan
 *   penulisan standar Indonesia (Rp xxx.xxx).
 *
 * Parameter:
 *   - $angka : Angka yang ingin diformat.
 *
 * Return:
 *   - string : Nilai rupiah dalam format "Rp 1.000.000".
 ******************************************************/
function formatRupiah($angka = 0)
{
    $angka = is_numeric($angka) ? (float)$angka : 0;
    return 'Rp ' . number_format($angka, 0, ',', '.');
}




/** 
 * ******************************************************************
 *  Fungsi: loadEnv
 *  Deskripsi:
 *      Fungsi ini digunakan untuk memuat dan membaca variabel-variabel
 *      lingkungan (environment variables) dari sebuah file `.env`.
 *      Setiap baris yang berisi pasangan KEY=VALUE akan diproses dan
 *      dimasukkan ke dalam array global $_ENV.
 *
 *  Parameter:
 *      - string $path
 *          Lokasi file `.env` yang akan dibaca.
 *          Secara default mengarah ke folder induk dari file ini.
 *
 *  Catatan:
 *      - Baris yang diawali dengan tanda `#` akan dianggap sebagai komentar
 *        dan dilewati.
 *      - Nilai VALUE yang menggunakan tanda kutip ("" atau '') akan
 *        dihilangkan kutipnya secara otomatis.
 *
 *  Return:
 *      Tidak mengembalikan nilai apa pun. Namun, variabel lingkungan akan
 *      tersimpan di dalam $_ENV.
 * ******************************************************************
 */
function loadEnv($path = __DIR__ . '/../.env')
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Lewati komentar
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Pisahkan KEY dan VALUE
        list($name, $value) = array_map('trim', explode('=', $line, 2));

        // Hilangkan tanda kutip jika ada
        $value = trim($value, "\"'");

        // Simpan ke variabel lingkungan
        $_ENV[$name] = $value;
    }
}


/**
 * Format No. KK dan NIK dengan spasi setiap 4 digit
 */
function formatKKNIK($number)
{
    $number = preg_replace('/[^0-9]/', '', $number);
    return chunk_split($number, 4, ' ');
}

/**
 * Get age badge class based on age
 */
function getAgeBadgeClass($age)
{
    if ($age < 5) return 'age-child';
    if ($age >= 5 && $age < 18) return 'age-teen';
    if ($age >= 18 && $age < 60) return 'age-adult';
    return 'age-elder';
}

/**
 * Format relationship
 */
function formatHubungan($hbkel)
{
    $map = [
        'KEPALA KELUARGA' => 'Kepala',
        'ISTRI' => 'Istri',
        'ANAK' => 'Anak',
        'FAMILI LAIN' => 'Famili'
    ];
    return $map[$hbkel] ?? $hbkel;
}

/**
 * Log aktivitas user
 */
function log_aktivitas($user_id, $aktivitas, $modul, $aksi, $data_id = null)
{
    global $conn;

    if (!$conn) {
        return false;
    }

    $user_id = $conn->real_escape_string($user_id);
    $aktivitas = $conn->real_escape_string($aktivitas);
    $modul = $conn->real_escape_string($modul);
    $aksi = $conn->real_escape_string($aksi);
    $data_id = $data_id ? $conn->real_escape_string($data_id) : null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $sql = "INSERT INTO tabel_log_aktivitas (
        user_id, aktivitas, modul, aksi, data_id, ip_address, user_agent
    ) VALUES (
        '$user_id', '$aktivitas', '$modul', '$aksi', " .
        ($data_id ? "'$data_id'" : "NULL") . ", 
        '$ip_address', '$user_agent'
    )";

    return $conn->query($sql);
}

/**
 * Fungsi untuk memotong string dengan menghormati kata
 * @param string $string String yang akan dipotong
 * @param int $length Panjang maksimum
 * @param string $ellipsis Karakter elipsis
 * @return string String yang sudah dipotong
 */
function smartTruncate($string, $length = 50, $ellipsis = '...')
{
    if (strlen($string) <= $length) {
        return $string;
    }

    // Coba potong pada spasi terdekat
    $truncated = substr($string, 0, $length);
    $lastSpace = strrpos($truncated, ' ');

    if ($lastSpace !== false) {
        return substr($truncated, 0, $lastSpace) . $ellipsis;
    }

    // Jika tidak ada spasi, potong langsung
    return $truncated . $ellipsis;
}

/**
 * Fungsi khusus untuk memotong dusun tanpa memotong kata
 * @param string $dusun Nama dusun
 * @param int $maxLength Panjang maksimum
 * @return string Nama dusun yang sudah dipotong
 */
function truncateDusun($dusun, $maxLength = 10)
{
    if (strlen($dusun) <= $maxLength) {
        return $dusun;
    }

    // Untuk dusun, kita prioritaskan menjaga kata lengkap
    $words = explode(' ', $dusun);
    $result = '';

    foreach ($words as $word) {
        if (strlen($result . ' ' . $word) <= $maxLength) {
            $result .= ($result ? ' ' : '') . $word;
        } else {
            break;
        }
    }

    // Jika masih terlalu panjang setelah menjaga kata, potong dengan smart truncate
    if (strlen($result) > $maxLength || empty($result)) {
        return smartTruncate($dusun, $maxLength, '');
    }

    return $result;
}

/**
 * Fungsi khusus untuk memotong hubungan keluarga
 * @param string $hubkel Hubungan keluarga
 * @param int $maxLength Panjang maksimum
 * @return string Hubungan keluarga yang dipendekkan
 */
function truncateHubKeluarga($hubkel, $maxLength = 8)
{
    // Mapping untuk singkatan
    $mapping = [
        'KEPALA KELUARGA' => 'Kepala',
        'SUAMI' => 'Suami',
        'ISTRI' => 'Istri',
        'ANAK' => 'Anak',
        'MENANTU' => 'Menantu',
        'CUCU' => 'Cucu',
        'ORANGTUA' => 'Ortu',
        'MERTUA' => 'Mertua',
        'FAMILI LAIN' => 'Famili',
        'PEMBANTU' => 'Pembantu',
        'LAINNYA' => 'Lain'
    ];

    // Cek apakah ada di mapping
    if (isset($mapping[$hubkel])) {
        return $mapping[$hubkel];
    }

    // Jika tidak, gunakan smart truncate
    return smartTruncate($hubkel, $maxLength, '');
}

/**
 * Format KK/NIK untuk PDF
 * @param string $string KK atau NIK
 * @return string Format dengan spasi setiap 4 digit
 */
function formatKKNIKPDF($string)
{
    if (empty($string)) return '';
    // Hapus semua karakter non-digit
    $string = preg_replace('/[^0-9]/', '', $string);
    // Format dengan spasi setiap 4 digit
    return chunk_split($string, 4, ' ');
}

/**
 * Format tanggal Indonesia untuk PDF
 * @param string $date Tanggal dalam format Y-m-d
 * @return string Tanggal dalam format d/m/Y
 */

// Muat variabel lingkungan dari file .env
loadEnv();
