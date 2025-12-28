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

if ($_SESSION['role'] !== 'kepala_desa') {
    ob_end_clean();
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Parameter filter
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$rt_filter = isset($_GET['rt']) ? $_GET['rt'] : '';
$rw_filter = isset($_GET['rw']) ? $_GET['rw'] : '';

// Query data penduduk dengan filter (sama seperti di klasifikasi.php)
$where_conditions = [];
$params = [];

if (!empty($dusun_filter)) {
    $where_conditions[] = "k.DSN = ?";
    $params[] = $dusun_filter;
}

if (!empty($rt_filter)) {
    $where_conditions[] = "k.rt = ?";
    $params[] = $rt_filter;
}

if (!empty($rw_filter)) {
    $where_conditions[] = "k.rw = ?";
    $params[] = $rw_filter;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query data penduduk lengkap
$sql = "SELECT 
            k.*,
            d.dusun,
            YEAR(CURDATE()) - YEAR(k.TGL_LHR) - 
            (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(k.TGL_LHR, '%m%d')) as usia
        FROM tabel_kependudukan k
        LEFT JOIN tabel_dusun d ON k.DSN = d.id
        $where_sql
        ORDER BY d.dusun, k.rw, k.rt, k.JK";

// Eksekusi query
if (!empty($params)) {
    $data_penduduk = query($sql, $params);
} else {
    $data_penduduk = query($sql);
}

// Clear output buffer
ob_end_clean();

// Buat nama file
$filename = 'klasifikasi_penduduk_' . date('Ymd_His') . '.xls';

// Set header untuk Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output HTML table untuk Excel
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Klasifikasi Penduduk</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4F81BD; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; }
        td { padding: 6px; border: 1px solid #ddd; }
        .total-row { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>';

// Header laporan
echo '<h2>KLASIFIKASI DATA PENDUDUK</h2>';
echo '<h4>Desa Kurniabakti</h4>';

// Info filter
if (!empty($dusun_filter) || !empty($rt_filter) || !empty($rw_filter)) {
    echo '<p><strong>Filter:</strong> ';
    $filter_info = [];

    if (!empty($dusun_filter)) {
        $sql_dusun = "SELECT dusun FROM tabel_dusun WHERE id = ?";
        $dusun_data = query($sql_dusun, [$dusun_filter]);
        $nama_dusun = $dusun_data[0]['dusun'] ?? 'Dusun tidak diketahui';
        $filter_info[] = "Dusun: $nama_dusun";
    }

    if (!empty($rt_filter)) $filter_info[] = "RT: $rt_filter";
    if (!empty($rw_filter)) $filter_info[] = "RW: $rw_filter";

    echo implode(', ', $filter_info) . '</p>';
}

echo '<p><strong>Tanggal Cetak:</strong> ' . date('d/m/Y H:i:s') . '</p>';
echo '<p><strong>Total Data:</strong> ' . count($data_penduduk) . ' penduduk</p>';

// TABEL 1: Klasifikasi RT/RW dan Jenis Kelamin
echo '<h3>1. Klasifikasi Berdasarkan RT/RW dan Jenis Kelamin</h3>';
echo '<table border="1">
    <tr>
        <th>No</th>
        <th>Dusun</th>
        <th>RT</th>
        <th>RW</th>
        <th>Jenis Kelamin</th>
        <th>Jumlah</th>
    </tr>';

// Hitung data untuk tabel 1
$klasifikasi_data = [];
foreach ($data_penduduk as $penduduk) {
    $key = $penduduk['dusun'] . '_' . $penduduk['rt'] . '_' . $penduduk['rw'] . '_' . $penduduk['JK'];

    if (!isset($klasifikasi_data[$key])) {
        $klasifikasi_data[$key] = [
            'dusun' => $penduduk['dusun'] ?? 'Tidak diketahui',
            'rt' => $penduduk['rt'] ?? '0',
            'rw' => $penduduk['rw'] ?? '0',
            'jk' => $penduduk['JK'],
            'jumlah' => 0
        ];
    }
    $klasifikasi_data[$key]['jumlah']++;
}

// Tampilkan data
$no = 1;
$grand_total = 0;
$current_dusun = '';
$subtotal_dusun = 0;

foreach ($klasifikasi_data as $data) {
    if ($current_dusun != $data['dusun'] && $current_dusun != '') {
        // Subtotal dusun
        echo '<tr class="total-row">
            <td colspan="5" align="right"><strong>Subtotal Dusun ' . $current_dusun . ':</strong></td>
            <td align="center"><strong>' . number_format($subtotal_dusun) . '</strong></td>
        </tr>';
        $subtotal_dusun = 0;
    }

    $current_dusun = $data['dusun'];
    $subtotal_dusun += $data['jumlah'];
    $grand_total += $data['jumlah'];

    echo '<tr>
        <td align="center">' . $no++ . '</td>
        <td>' . $data['dusun'] . '</td>
        <td align="center">' . $data['rt'] . '</td>
        <td align="center">' . $data['rw'] . '</td>
        <td>' . ($data['jk'] == 'L' ? 'Laki-laki' : 'Perempuan') . '</td>
        <td align="center">' . number_format($data['jumlah']) . '</td>
    </tr>';
}

// Subtotal dusun terakhir
if ($current_dusun != '') {
    echo '<tr class="total-row">
        <td colspan="5" align="right"><strong>Subtotal Dusun ' . $current_dusun . ':</strong></td>
        <td align="center"><strong>' . number_format($subtotal_dusun) . '</strong></td>
    </tr>';
}

// Grand total
echo '<tr class="total-row">
    <td colspan="5" align="right"><strong>GRAND TOTAL:</strong></td>
    <td align="center"><strong>' . number_format($grand_total) . '</strong></td>
</tr>';
echo '</table>';

// TABEL 2: Rentang Umur
echo '<h3 style="margin-top: 30px;">2. Distribusi Berdasarkan Rentang Usia</h3>';
echo '<table border="1">
    <tr>
        <th>No</th>
        <th>Dusun</th>
        <th>0-5</th>
        <th>6-10</th>
        <th>11-15</th>
        <th>16-20</th>
        <th>21-25</th>
        <th>26-30</th>
        <th>31-35</th>
        <th>36-40</th>
        <th>41-45</th>
        <th>46-50</th>
        <th>51-55</th>
        <th>56-60</th>
        <th>60+</th>
        <th>Jumlah</th>
    </tr>';

// Rentang umur
$rentang_labels = [
    '0-5' => [0, 5],
    '6-10' => [6, 10],
    '11-15' => [11, 15],
    '16-20' => [16, 20],
    '21-25' => [21, 25],
    '26-30' => [26, 30],
    '31-35' => [31, 35],
    '36-40' => [36, 40],
    '41-45' => [41, 45],
    '46-50' => [46, 50],
    '51-55' => [51, 55],
    '56-60' => [56, 60],
    '60+' => [61, 200]
];

// Kelompokkan per dusun
$rentang_umur = [];
foreach ($data_penduduk as $penduduk) {
    $dusun = $penduduk['dusun'] ?? 'Tidak diketahui';
    $usia = (int)$penduduk['usia'];

    if (!isset($rentang_umur[$dusun])) {
        $rentang_umur[$dusun] = [
            'dusun' => $dusun,
            'total' => 0
        ];
        foreach ($rentang_labels as $label => $range) {
            $rentang_umur[$dusun][$label] = 0;
        }
    }

    $rentang_umur[$dusun]['total']++;

    foreach ($rentang_labels as $label => $range) {
        if ($label == '60+') {
            if ($usia >= $range[0]) {
                $rentang_umur[$dusun][$label]++;
                break;
            }
        } else {
            if ($usia >= $range[0] && $usia <= $range[1]) {
                $rentang_umur[$dusun][$label]++;
                break;
            }
        }
    }
}

// Tampilkan data rentang umur
$no = 1;
$total_per_rentang = array_fill_keys(array_keys($rentang_labels), 0);
$grand_total_umur = 0;

foreach ($rentang_umur as $data) {
    echo '<tr>
        <td align="center">' . $no++ . '</td>
        <td>' . $data['dusun'] . '</td>';

    foreach ($rentang_labels as $label => $range) {
        $jumlah = $data[$label] ?? 0;
        $total_per_rentang[$label] += $jumlah;
        echo '<td align="center">' . number_format($jumlah) . '</td>';
    }

    echo '<td align="center"><strong>' . number_format($data['total']) . '</strong></td>
    </tr>';

    $grand_total_umur += $data['total'];
}

// Total per rentang
echo '<tr class="total-row">
    <td colspan="2" align="right"><strong>TOTAL:</strong></td>';
foreach ($rentang_labels as $label => $range) {
    echo '<td align="center"><strong>' . number_format($total_per_rentang[$label]) . '</strong></td>';
}
echo '<td align="center"><strong>' . number_format($grand_total_umur) . '</strong></td>
</tr>';
echo '</table>';

// TABEL 3: Pekerjaan
echo '<h3 style="margin-top: 30px;">3. Distribusi Pekerjaan Utama</h3>';
echo '<table border="1">
    <tr>
        <th>No</th>
        <th>Dusun</th>
        <th>Pekerjaan</th>
        <th>Jumlah</th>
    </tr>';

// Daftar pekerjaan
$daftar_pekerjaan = ['PNS', 'TNI/POLRI', 'SWASTA', 'WIRAUSAHA', 'PETANI', 'NELAYAN', 'BURUH', 'PENSIUNAN', 'TIDAK BEKERJA', 'LAINNYA'];

// Kelompokkan pekerjaan
$pekerjaan_data = [];
foreach ($data_penduduk as $penduduk) {
    $dusun = $penduduk['dusun'] ?? 'Tidak diketahui';
    $pekerjaan = $penduduk['PEKERJAAN'] ?? 'TIDAK BEKERJA';

    $pekerjaan_normalized = 'LAINNYA';
    foreach ($daftar_pekerjaan as $p) {
        if (stripos($pekerjaan, $p) !== false || $pekerjaan == $p) {
            $pekerjaan_normalized = $p;
            break;
        }
    }

    $key = $dusun . '_' . $pekerjaan_normalized;

    if (!isset($pekerjaan_data[$key])) {
        $pekerjaan_data[$key] = [
            'dusun' => $dusun,
            'pekerjaan' => $pekerjaan_normalized,
            'jumlah' => 0
        ];
    }

    $pekerjaan_data[$key]['jumlah']++;
}

// Tampilkan data pekerjaan
$no = 1;
$current_dusun = '';
$subtotal_pekerjaan = 0;
$grand_total_pekerjaan = 0;

foreach ($pekerjaan_data as $data) {
    if ($current_dusun != $data['dusun'] && $current_dusun != '') {
        // Subtotal dusun
        echo '<tr class="total-row">
            <td colspan="3" align="right"><strong>Subtotal Dusun ' . $current_dusun . ':</strong></td>
            <td align="center"><strong>' . number_format($subtotal_pekerjaan) . '</strong></td>
        </tr>';
        $subtotal_pekerjaan = 0;
    }

    $current_dusun = $data['dusun'];
    $subtotal_pekerjaan += $data['jumlah'];
    $grand_total_pekerjaan += $data['jumlah'];

    echo '<tr>
        <td align="center">' . $no++ . '</td>
        <td>' . $data['dusun'] . '</td>
        <td>' . $data['pekerjaan'] . '</td>
        <td align="center">' . number_format($data['jumlah']) . '</td>
    </tr>';
}

// Subtotal dusun terakhir
if ($current_dusun != '') {
    echo '<tr class="total-row">
        <td colspan="3" align="right"><strong>Subtotal Dusun ' . $current_dusun . ':</strong></td>
        <td align="center"><strong>' . number_format($subtotal_pekerjaan) . '</strong></td>
    </tr>';
}

// Grand total
echo '<tr class="total-row">
    <td colspan="3" align="right"><strong>GRAND TOTAL:</strong></td>
    <td align="center"><strong>' . number_format($grand_total_pekerjaan) . '</strong></td>
</tr>';
echo '</table>';

// Footer
echo '<div style="margin-top: 30px; font-style: italic; color: #666;">
    <p>Data dihitung berdasarkan ' . count($data_penduduk) . ' penduduk</p>
    <p>Dicetak pada: ' . date('d F Y H:i:s') . '</p>
</div>';

echo '</body></html>';
exit;
