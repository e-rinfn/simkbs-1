<?php
require_once '../includes/header.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Validasi NIK
if (!isset($_GET['nik']) || empty($_GET['nik'])) {
    $_SESSION['error'] = "NIK tidak valid";
    header("Location: list.php");
    exit();
}

$nik = $conn->real_escape_string($_GET['nik']);

// Cek apakah penduduk ada di database
$penduduk = query("SELECT * FROM tabel_kependudukan WHERE NIK = '$nik'");
if (empty($penduduk)) {
    $_SESSION['error'] = "Data penduduk tidak ditemukan";
    header("Location: list.php");
    exit();
}

$nama_penduduk = $penduduk[0]['NAMA_LGKP'];

// Cek relasi dengan tabel lain
$cek_keluarga = query("SELECT 1 FROM tabel_keluarga WHERE NIK_KEPALA = '$nik' LIMIT 1");
$cek_bantuan = query("SELECT 1 FROM tabel_bantuan WHERE NIK = '$nik' AND status = 'AKTIF' LIMIT 1");
$cek_ibu_hamil = query("SELECT 1 FROM tabel_ibu_hamil WHERE NIK = '$nik' LIMIT 1");

if (!empty($cek_keluarga) || !empty($cek_bantuan) || !empty($cek_ibu_hamil)) {
    $error_msg = "Data penduduk <strong>$nama_penduduk</strong> tidak dapat dihapus karena: ";
    $reasons = [];

    if (!empty($cek_keluarga)) $reasons[] = "masih tercatat sebagai kepala keluarga";
    if (!empty($cek_bantuan)) $reasons[] = "masih menerima bantuan aktif";
    if (!empty($cek_ibu_hamil)) $reasons[] = "masih dalam data ibu hamil";

    $_SESSION['error'] = $error_msg . implode(", ", $reasons) . '.';
    header("Location: list.php");
    exit();
}

// Mulai transaksi
$conn->begin_transaction();

try {
    // Hapus dari semua tabel terkait (dengan CASCADE atau manual)

    // 1. Hapus data bantuan (non-aktif dulu jika ada)
    $sql_bantuan = "DELETE FROM tabel_bantuan WHERE NIK = '$nik'";
    $conn->query($sql_bantuan);

    // 2. Hapus data ibu hamil
    $sql_ibu_hamil = "DELETE FROM tabel_ibu_hamil WHERE NIK = '$nik'";
    $conn->query($sql_ibu_hamil);

    // 3. Hapus data pendidikan history
    $sql_pendidikan = "DELETE FROM tabel_pendidikan_history WHERE NIK = '$nik'";
    $conn->query($sql_pendidikan);

    // 4. Hapus data konsumsi
    $sql_konsumsi = "DELETE FROM tabel_konsumsi WHERE NIK = '$nik'";
    $conn->query($sql_konsumsi);

    // 5. Hapus data pakaian
    $sql_pakaian = "DELETE FROM tabel_pakaian WHERE NIK = '$nik'";
    $conn->query($sql_pakaian);

    // 6. Hapus data kesehatan
    $sql_kesehatan = "DELETE FROM tabel_kesehatan WHERE NIK = '$nik'";
    $conn->query($sql_kesehatan);

    // 7. Hapus data tabungan
    $sql_tabungan = "DELETE FROM tabel_tabungan WHERE NIK = '$nik'";
    $conn->query($sql_tabungan);

    // 8. Hapus data rumah
    $sql_rumah = "DELETE FROM tabel_rumah WHERE NIK = '$nik'";
    $conn->query($sql_rumah);

    // 9. Hapus data pekerjaan
    $sql_pekerjaan = "DELETE FROM tabel_pekerjaan WHERE NIK = '$nik'";
    $conn->query($sql_pekerjaan);

    // 10. Update tabel_keluarga jika anggota keluarga dihapus
    // Hitung jumlah anggota keluarga setelah penghapusan
    $no_kk = $penduduk[0]['NO_KK'];
    $sql_count_anggota = "SELECT COUNT(*) as jumlah FROM tabel_kependudukan WHERE NO_KK = '$no_kk'";
    $result_count = query($sql_count_anggota);
    $jumlah_anggota = $result_count[0]['jumlah'] - 1; // Kurangi 1 karena akan dihapus

    // Update jumlah anggota di tabel_keluarga
    if ($jumlah_anggota > 0) {
        $sql_update_keluarga = "UPDATE tabel_keluarga SET jumlah_anggota = $jumlah_anggota WHERE NO_KK = '$no_kk'";
        $conn->query($sql_update_keluarga);
    } else {
        // Jika tidak ada anggota lagi, hapus dari tabel_keluarga
        $sql_hapus_keluarga = "DELETE FROM tabel_keluarga WHERE NO_KK = '$no_kk'";
        $conn->query($sql_hapus_keluarga);
    }

    // 11. Hapus data kependudukan (harus terakhir karena ada foreign key constraints)
    $sql_kependudukan = "DELETE FROM tabel_kependudukan WHERE NIK = '$nik'";
    if (!$conn->query($sql_kependudukan)) {
        throw new Exception("Gagal menghapus data kependudukan: " . $conn->error);
    }

    // Commit transaksi
    $conn->commit();

    // Log aktivitas
    $aktivitas = "Menghapus data penduduk: $nama_penduduk (NIK: $nik)";
    log_aktivitas($_SESSION['user_id'] ?? null, $aktivitas, 'penduduk', 'DELETE', $nik);

    $_SESSION['success'] = "Data penduduk <strong>$nama_penduduk</strong> berhasil dihapus";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Gagal menghapus data penduduk: " . $e->getMessage();
}

header("Location: list.php");
exit();
