<?php
// Mulai output buffering
ob_start();

include_once __DIR__ . '/../../config/config.php';
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    ob_end_clean();
    header("Location: {$base_url}auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades' && $_SESSION['role'] !== 'sekretaris') {
    ob_end_clean();
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Parameter filter
$bulan_filter = isset($_GET['bulan_masuk']) ? $_GET['bulan_masuk'] : '';
$tahun_filter = isset($_GET['tahun_masuk']) ? $_GET['tahun_masuk'] : date('Y');
$sifat_filter = isset($_GET['sifat_masuk']) ? $_GET['sifat_masuk'] : '';
$status_filter = isset($_GET['status_masuk']) ? $_GET['status_masuk'] : '';
$search = isset($_GET['search_masuk']) ? $_GET['search_masuk'] : '';

// Query data dengan filter
$where_conditions = [];
$params = [];
$params_types = '';

if (!empty($bulan_filter) && !empty($tahun_filter)) {
    $where_conditions[] = "DATE_FORMAT(tanggal_surat, '%Y-%m') = ?";
    $params[] = $tahun_filter . '-' . $bulan_filter;
    $params_types .= 's';
} elseif (!empty($tahun_filter)) {
    $where_conditions[] = "YEAR(tanggal_surat) = ?";
    $params[] = $tahun_filter;
    $params_types .= 's';
}

if (!empty($sifat_filter)) {
    $where_conditions[] = "sifat_surat = ?";
    $params[] = $sifat_filter;
    $params_types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $params_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(nomor_surat LIKE ? OR pengirim LIKE ? OR perihal LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params_types .= 'sss';
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT * FROM tabel_surat_masuk 
        $where_sql 
        ORDER BY tanggal_surat DESC";

// Eksekusi query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($params_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data_surat = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} else {
    $result = mysqli_query($conn, $sql);
    $data_surat = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Clear output buffer
ob_end_clean();

// Buat nama file
$filename = 'arsip_surat_masuk_' . date('Ymd_His') . '.xls';

// Set header untuk Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output HTML table untuk Excel
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Arsip Surat Masuk</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4F81BD; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; }
        td { padding: 6px; border: 1px solid #ddd; }
        .total-row { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>';

// Header laporan
echo '<h2>ARSIP SURAT MASUK</h2>';
echo '<h4>Desa Kurniabakti, Kecamatan Cineam, Kabupaten Tasikmalaya</h4>';

// Info filter
$filter_info = [];
if (!empty($bulan_filter) && !empty($tahun_filter)) {
    $bulan_labels = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];
    $filter_info[] = $bulan_labels[$bulan_filter] . ' ' . $tahun_filter;
} elseif (!empty($tahun_filter)) {
    $filter_info[] = 'Tahun ' . $tahun_filter;
}
if (!empty($sifat_filter)) $filter_info[] = 'Sifat: ' . $sifat_filter;
if (!empty($status_filter)) $filter_info[] = 'Status: ' . $status_filter;
if (!empty($search)) $filter_info[] = 'Kata kunci: "' . $search . '"';

if (!empty($filter_info)) {
    echo '<p><strong>Filter:</strong> ' . implode(', ', $filter_info) . '</p>';
}

echo '<p><strong>Tanggal Cetak:</strong> ' . date('d/m/Y H:i:s') . '</p>';
echo '<p><strong>Total Data:</strong> ' . count($data_surat) . ' surat</p>';

// Tabel data
echo '<table border="1">
    <tr>
        <th>No</th>
        <th>Nomor Surat</th>
        <th>Tanggal Surat</th>
        <th>Tanggal Diterima</th>
        <th>Pengirim</th>
        <th>Perihal</th>
        <th>Sifat Surat</th>
        <th>Status</th>
        <th>Keterangan</th>
    </tr>';

// Data rows
if (count($data_surat) > 0) {
    $no = 1;
    foreach ($data_surat as $surat) {
        $tanggal_surat = !empty($surat['tanggal_surat']) ? date('d/m/Y', strtotime($surat['tanggal_surat'])) : '-';
        $tanggal_diterima = !empty($surat['tanggal_diterima']) ? date('d/m/Y', strtotime($surat['tanggal_diterima'])) : '-';

        echo '<tr>
            <td align="center">' . $no++ . '</td>
            <td>' . htmlspecialchars($surat['nomor_surat']) . '</td>
            <td>' . $tanggal_surat . '</td>
            <td>' . $tanggal_diterima . '</td>
            <td>' . htmlspecialchars($surat['pengirim']) . '</td>
            <td>' . htmlspecialchars($surat['perihal']) . '</td>
            <td>' . htmlspecialchars($surat['sifat_surat']) . '</td>
            <td>' . htmlspecialchars($surat['status']) . '</td>
            <td>' . htmlspecialchars($surat['keterangan'] ?? '-') . '</td>
        </tr>';
    }
} else {
    echo '<tr><td colspan="9" align="center">Tidak ada data surat masuk</td></tr>';
}

echo '</table>';

// Footer
echo '<div style="margin-top: 30px; font-style: italic; color: #666;">
    <p>Dicetak oleh: ' . htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']) . '</p>
    <p>Jabatan: ' . htmlspecialchars($_SESSION['role']) . '</p>
</div>';

echo '</body></html>';
exit;
