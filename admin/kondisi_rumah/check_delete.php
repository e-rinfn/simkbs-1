<?php
// Tambahkan ini di paling atas file
error_reporting(0); // Nonaktifkan error reporting untuk mencegah output HTML
ini_set('display_errors', 0);

// Set header JSON FIRST sebelum apapun
header('Content-Type: application/json; charset=utf-8');

// Cegah output apapun sebelum JSON
ob_start();

require_once '../../config/database.php';
require_once '../../config/functions.php';

// Hapus semua output buffer
ob_end_clean();

// Check for errors in database connection
if (!isset($conn) || $conn->connect_error) {
    echo json_encode([
        'can_delete' => false,
        'message' => 'Koneksi ke database gagal.'
    ]);
    exit;
}

// Validate input
$nik = isset($_GET['nik']) ? trim($_GET['nik']) : '';
if (empty($nik)) {
    echo json_encode([
        'can_delete' => false,
        'message' => 'NIK tidak boleh kosong.'
    ]);
    exit;
}

// Periksa apakah NIK hanya mengandung angka dan maksimal 16 digit
if (!preg_match('/^\d{1,16}$/', $nik)) {
    echo json_encode([
        'can_delete' => false,
        'message' => 'Format NIK tidak valid. Harus berupa angka (maksimal 16 digit).'
    ]);
    exit;
}

try {
    // Cek apakah data rumah ada
    $sql_check = "SELECT 1 FROM tabel_rumah WHERE NIK = ? LIMIT 1";
    $stmt = $conn->prepare($sql_check);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'can_delete' => false,
            'message' => 'Data kondisi rumah tidak ditemukan.'
        ]);
        exit;
    }

    $stmt->close();

    // Untuk data rumah biasanya tidak ada constraint yang ketat
    echo json_encode([
        'can_delete' => true,
        'message' => 'Data kondisi rumah dapat dihapus.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'can_delete' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}

exit;
