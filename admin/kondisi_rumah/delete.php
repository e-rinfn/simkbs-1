<?php
require_once '../includes/header.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades') {
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Validasi NIK
if (!isset($_GET['nik']) || empty(trim($_GET['nik']))) {
    $_SESSION['error'] = "NIK tidak valid atau kosong";
    header("Location: list.php");
    exit();
}

$nik_input = trim($_GET['nik']);

// Validasi format NIK (harus angka)
if (!preg_match('/^\d+$/', $nik_input)) {
    $_SESSION['error'] = "Format NIK tidak valid. Harus berupa angka.";
    header("Location: list.php");
    exit();
}

// Escape input untuk keamanan
$nik = $conn->real_escape_string($nik_input);

// Cek apakah data rumah ada di database
$rumah = query("SELECT * FROM tabel_rumah WHERE NIK = '$nik'");
if (empty($rumah)) {
    $_SESSION['error'] = "Data kondisi rumah dengan NIK $nik_input tidak ditemukan";
    header("Location: list.php");
    exit();
}

$nama_pemilik = $rumah[0]['nama_pemilik'];
$no_kk = $rumah[0]['NO_KK'];

// Mulai transaksi
$conn->begin_transaction();

try {
    // Ambil informasi penduduk untuk log (opsional)
    $penduduk_info = query("SELECT NAMA_LGKP FROM tabel_kependudukan WHERE NIK = '$nik'");
    $nama_penduduk = !empty($penduduk_info) ? $penduduk_info[0]['NAMA_LGKP'] : $nama_pemilik;

    // Hapus data dari tabel rumah
    $sql_delete = "DELETE FROM tabel_rumah WHERE NIK = '$nik'";

    if (!$conn->query($sql_delete)) {
        // throw new Exception("Gagal menghapus data kondisi rumah: " . $conn->error);
    }

    // Commit transaksi
    $conn->commit();

    // Log aktivitas
    $aktivitas = "Menghapus data kondisi rumah milik: $nama_penduduk (NIK: $nik, No. KK: $no_kk)";
    log_aktivitas($_SESSION['user_id'] ?? null, $aktivitas, 'rumah', 'DELETE', $nik);

    $_SESSION['success'] = "Data kondisi rumah milik <strong>$nama_penduduk</strong> berhasil dihapus";
} catch (Exception $e) {
    $conn->rollback();
    // $_SESSION['error'] = "Gagal menghapus data kondisi rumah: " . $e->getMessage();
}

header("Location: list.php");
exit();
