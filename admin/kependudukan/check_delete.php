<?php
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Set proper headers first
header('Content-Type: application/json');

// Check for errors in database connection
if ($conn->connect_error) {
    echo json_encode([
        'can_delete' => false,
        'message' => 'Koneksi ke database gagal.'
    ]);
    exit;
}

// Validate input
$nik = isset($_GET['nik']) ? trim($_GET['nik']) : '';
if (empty($nik) || strlen($nik) !== 16) {
    echo json_encode([
        'can_delete' => false,
        'message' => 'NIK tidak valid.'
    ]);
    exit;
}

try {
    // Daftar relasi yang akan dicek (key = pesan untuk user)
    $relations = [
        'Kepala Keluarga' => "SELECT 1 FROM tabel_keluarga WHERE NIK_KEPALA = ? LIMIT 1",
        'Data Bantuan' => "SELECT 1 FROM tabel_bantuan WHERE NIK = ? AND status = 'AKTIF' LIMIT 1",
        'Data Ibu Hamil' => "SELECT 1 FROM tabel_ibu_hamil WHERE NIK = ? LIMIT 1",
    ];

    $reasons = [];

    foreach ($relations as $friendlyName => $sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $nik);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reasons[] = $friendlyName;
        }
        $stmt->close();
    }

    if (!empty($reasons)) {
        echo json_encode([
            'can_delete' => false,
            'message' => 'Data penduduk ini tidak bisa dihapus karena masih memiliki relasi dengan: ' . implode(', ', $reasons) . '.'
        ]);
    } else {
        echo json_encode([
            'can_delete' => true,
            'message' => ''
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'can_delete' => false,
        'message' => 'Terjadi kesalahan saat memeriksa relasi data penduduk: ' . $e->getMessage()
    ]);
}
