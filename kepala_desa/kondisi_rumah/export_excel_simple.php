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

if ($_SESSION['role'] !== 'kepala_desa') {
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Ambil parameter filter
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$jenis_lantai_filter = isset($_GET['jenis_lantai']) ? $_GET['jenis_lantai'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query data
$where_conditions = [];

if (!empty($dusun_filter)) {
    $dusun_filter_safe = mysqli_real_escape_string($conn, $dusun_filter);
    $where_conditions[] = "k.DSN = '$dusun_filter_safe'";
}

if (!empty($jenis_lantai_filter)) {
    $jenis_lantai_filter_safe = mysqli_real_escape_string($conn, $jenis_lantai_filter);
    $where_conditions[] = "r.jenis_lantai = '$jenis_lantai_filter_safe'";
}

if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(k.NO_KK LIKE '%$search_safe%' OR k.NIK LIKE '%$search_safe%' OR k.NAMA_LGKP LIKE '%$search_safe%')";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT 
            k.NO_KK,
            k.NIK,
            k.NAMA_LGKP,
            k.NAMA_PANGGILAN,
            k.JK,
            d.dusun,
            k.rt,
            k.rw,
            r.luas_lantai,
            r.jenis_lantai,
            r.jenis_dinding,
            r.fasilitas_bab,
            r.sumber_penerangan,
            r.sumber_air_minum,
            r.bahan_bakar_memasak,
            r.kondisi_rumah,
            DATE(r.created_at) as tanggal_input
        FROM tabel_kependudukan k
        LEFT JOIN tabel_dusun d ON k.DSN = d.id
        LEFT JOIN tabel_rumah r ON k.NIK = r.NIK
        $where_sql
        ORDER BY k.NAMA_LGKP";

$result = mysqli_query($conn, $sql);

// Set header untuk download CSV
$filename = 'data_kondisi_rumah_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output CSV
$output = fopen('php://output', 'w');

// Header CSV
$headers = [
    'No. KK',
    'NIK',
    'Nama Lengkap',
    'Nama Panggilan',
    'Jenis Kelamin',
    'Dusun',
    'RT',
    'RW',
    'Luas Lantai (m²)',
    'Jenis Lantai',
    'Jenis Dinding',
    'Fasilitas MCK',
    'Sumber Penerangan',
    'Sumber Air Minum',
    'Bahan Bakar Memasak',
    'Kondisi Rumah',
    'Tanggal Input'
];

fputcsv($output, $headers);

// Data
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data = [
            $row['NO_KK'],
            $row['NIK'],
            $row['NAMA_LGKP'],
            $row['NAMA_PANGGILAN'],
            $row['JK'],
            $row['dusun'] ?? '',
            $row['rt'],
            $row['rw'],
            $row['luas_lantai'] ? number_format($row['luas_lantai'], 1) : '',
            $row['jenis_lantai'] ?? '',
            $row['jenis_dinding'] ?? '',
            $row['fasilitas_bab'] ?? '',
            $row['sumber_penerangan'] ?? '',
            $row['sumber_air_minum'] ?? '',
            $row['bahan_bakar_memasak'] ?? '',
            $row['kondisi_rumah'] ?? '',
            $row['tanggal_input'] ?? ''
        ];
        fputcsv($output, $data);
    }
}

fclose($output);
exit;
