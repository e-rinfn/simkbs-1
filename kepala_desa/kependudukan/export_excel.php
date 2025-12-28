<?php


// Aktifkan error reporting
error_reporting(error_level: E_ALL);
ini_set('display_errors', 1);

require_once '../../vendor/autoload.php';

session_start();
include_once __DIR__ . '/../../config/config.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}/auth/login.php");
    exit;
}


// Jika diperlukan role tertentu (admin/kades), sesuaikan dengan kebutuhan
if ($_SESSION['role'] !== 'kepala_desa') {
    ob_end_clean();
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}


// Parameter filter yang sama dengan list.php
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$jk_filter = isset($_GET['jk']) ? $_GET['jk'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query data kependudukan dengan filter yang sama
$where_conditions = [];
$params = [];
$params_types = '';

if (!empty($dusun_filter)) {
    $where_conditions[] = "k.DSN = ?";
    $params[] = $dusun_filter;
    $params_types .= 'i';
}

if (!empty($jk_filter)) {
    $where_conditions[] = "k.JK = ?";
    $params[] = $jk_filter;
    $params_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(k.NO_KK LIKE ? OR k.NIK LIKE ? OR k.NAMA_LGKP LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params_types .= 'sss';
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query utama
$sql = "SELECT 
            k.NO_KK,
            k.NIK,
            k.NAMA_LGKP,
            k.NAMA_PANGGILAN,
            k.HBKEL,
            k.JK,
            k.TMPT_LHR,
            k.TGL_LHR,
            TIMESTAMPDIFF(YEAR, k.TGL_LHR, CURDATE()) as usia,
            k.AGAMA,
            k.STATUS_KAWIN,
            k.PENDIDIKAN,
            k.PEKERJAAN,
            k.DSN,
            k.rt,
            k.rw,
            d.dusun,
            k.ALAMAT,
            k.GOL_DARAH,
            k.KEWARGANEGARAAN,
            k.STATUS_TINGGAL,
            k.DISABILITAS,
            p.jenis_pekerjaan,
            p.penghasilan_per_bulan,
            p.status_pekerjaan
        FROM tabel_kependudukan k
        LEFT JOIN tabel_dusun d ON k.DSN = d.id
        LEFT JOIN tabel_pekerjaan p ON k.NIK = p.NIK
        $where_sql
        ORDER BY k.NAMA_LGKP";

// Eksekusi query dengan prepared statement
global $conn;
$data_kependudukan = [];

try {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($params_types)) {
                $stmt->bind_param($params_types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $data_kependudukan = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $data_kependudukan = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
        }
    }
} catch (Exception $e) {
    error_log("Database error in Excel export: " . $e->getMessage());
}

// Clear semua output buffer
ob_end_clean();

// **PERBAIKAN: Gunakan file Excel langsung dengan header yang benar**
$filename = 'data_kependudukan_' . date('Ymd_His') . '.xls';

// Set header untuk Excel (bukan CSV)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output HTML table untuk Excel
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Data Kependudukan</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background-color: #4F81BD;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
        }
        td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>';

echo '<h2>LAPORAN DATA KEPENDUDUKAN</h2>';
echo '<h4>Desa Kurniabakti, Kecamatan Cineam, Kabupaten Tasikmalaya</h4>';

// Informasi filter
$filter_info = [];
if (!empty($dusun_filter)) {
    $sql_dusun = "SELECT dusun FROM tabel_dusun WHERE id = ?";
    $stmt = $conn->prepare($sql_dusun);
    $stmt->bind_param("i", $dusun_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    $dusun_data = $result->fetch_assoc();
    $nama_dusun = $dusun_data['dusun'] ?? 'Dusun tidak diketahui';
    $filter_info[] = "Dusun: $nama_dusun";
    $stmt->close();
}

if (!empty($jk_filter)) {
    $jk_text = ($jk_filter == 'L') ? 'Laki-laki' : 'Perempuan';
    $filter_info[] = "Jenis Kelamin: $jk_text";
}

if (!empty($search)) {
    $filter_info[] = "Kata kunci: \"$search\"";
}

$filter_text = !empty($filter_info) ? 'Filter: ' . implode(', ', $filter_info) : 'Semua Data';
echo '<p><strong>' . $filter_text . '</strong></p>';
echo '<p><strong>Tanggal Cetak:</strong> ' . date('d/m/Y H:i:s') . '</p>';
echo '<p><strong>Total Data:</strong> ' . count($data_kependudukan) . ' penduduk</p>';

// Table header
echo '<table border="1">
    <thead>
        <tr>
            <th>No</th>
            <th>No. KK</th>
            <th>NIK</th>
            <th>Nama Lengkap</th>
            <th>Nama Panggilan</th>
            <th>Hubungan Keluarga</th>
            <th>Jenis Kelamin</th>
            <th>Tempat Lahir</th>
            <th>Tanggal Lahir</th>
            <th>Usia (Tahun)</th>
            <th>Agama</th>
            <th>Status Perkawinan</th>
            <th>Pendidikan</th>
            <th>Pekerjaan (KTP)</th>
            <th>Dusun</th>
            <th>RT</th>
            <th>RW</th>
            <th>Alamat Lengkap</th>
            <th>Golongan Darah</th>
            <th>Kewarganegaraan</th>
            <th>Status Tinggal</th>
            <th>Disabilitas</th>
            <th>Jenis Pekerjaan</th>
            <th>Penghasilan per Bulan</th>
            <th>Status Pekerjaan</th>
        </tr>
    </thead>
    <tbody>';

// Data rows
if (count($data_kependudukan) > 0) {
    $no = 1;
    foreach ($data_kependudukan as $penduduk) {
        echo '<tr>';
        echo '<td align="center">' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['NO_KK']) . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['NIK']) . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['NAMA_LGKP']) . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['NAMA_PANGGILAN'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['HBKEL']) . '</td>';
        echo '<td align="center">' . ($penduduk['JK'] == 'L' ? 'Laki-laki' : 'Perempuan') . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['TMPT_LHR']) . '</td>';
        echo '<td>' . (!empty($penduduk['TGL_LHR']) ? date('d/m/Y', strtotime($penduduk['TGL_LHR'])) : '') . '</td>';
        echo '<td align="center">' . ($penduduk['usia'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['AGAMA']) . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['STATUS_KAWIN']) . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['PENDIDIKAN']) . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['PEKERJAAN'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['dusun'] ?? '') . '</td>';
        echo '<td align="center">' . $penduduk['rt'] . '</td>';
        echo '<td align="center">' . $penduduk['rw'] . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['ALAMAT'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['GOL_DARAH'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['KEWARGANEGARAAN']) . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['STATUS_TINGGAL']) . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['DISABILITAS'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['jenis_pekerjaan'] ?? '') . '</td>';
        echo '<td align="right">' . ($penduduk['penghasilan_per_bulan'] > 0 ? number_format($penduduk['penghasilan_per_bulan'], 0, ',', '.') : '') . '</td>';
        echo '<td>' . htmlspecialchars($penduduk['status_pekerjaan'] ?? '') . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="25" align="center">Tidak ada data ditemukan</td></tr>';
}

echo '</tbody></table>';
echo '</body></html>';
exit;
