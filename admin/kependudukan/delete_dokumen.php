<?php
// delete_dokumen.php
include_once __DIR__ . '/../../config/config.php';
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['dokumen_id']) || !isset($_POST['penduduk_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$dokumen_id = intval($_POST['dokumen_id']);
$penduduk_id = intval($_POST['penduduk_id']);

// Start transaction
$conn->begin_transaction();

try {
    // Get file path before deleting
    $sql = "SELECT path FROM tabel_dokumen_penduduk WHERE id = ? AND penduduk_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $dokumen_id, $penduduk_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dokumen = $result->fetch_assoc();

    if (!$dokumen) {
        throw new Exception('Dokumen tidak ditemukan');
    }

    // Delete from database
    $sql = "DELETE FROM tabel_dokumen_penduduk WHERE id = ? AND penduduk_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $dokumen_id, $penduduk_id);

    if (!$stmt->execute()) {
        throw new Exception('Gagal menghapus dari database');
    }

    // Delete physical file
    $file_path = realpath(__DIR__ . '/../../' . $dokumen['path']);
    if ($file_path && file_exists($file_path)) {
        if (!unlink($file_path)) {
            throw new Exception('Gagal menghapus file fisik');
        }
    }

    // Commit transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Dokumen berhasil dihapus']);
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
