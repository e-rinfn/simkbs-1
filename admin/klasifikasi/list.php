<?php

include_once __DIR__ . '/../../config/config.php';
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades') {
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Parameter filter
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$rt_filter = isset($_GET['rt']) ? $_GET['rt'] : '';
$rw_filter = isset($_GET['rw']) ? $_GET['rw'] : '';

// Ambil data dusun untuk filter
$sql_dusun = "SELECT * FROM tabel_dusun ORDER BY dusun";
$data_dusun = query($sql_dusun);

// Ambil data RT/RW unik untuk filter
$sql_rt_rw = "SELECT DISTINCT rt, rw FROM tabel_kependudukan WHERE rt IS NOT NULL AND rw IS NOT NULL ORDER BY rw, rt";
$data_rt_rw = query($sql_rt_rw);

// Query data penduduk dengan filter
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

// Eksekusi query dengan parameter binding
if (!empty($params)) {
    $data_penduduk = query($sql, $params);
} else {
    $data_penduduk = query($sql);
}

// Data untuk tabel pertama: Klasifikasi berdasarkan RT/RW dan Jenis Kelamin
$klasifikasi_rt_rw = [];
$total_perempuan = 0;
$total_laki_laki = 0;

foreach ($data_penduduk as $penduduk) {
    $dusun = $penduduk['dusun'] ?? 'Tidak diketahui';
    $rt = $penduduk['rt'] ?? '0';
    $rw = $penduduk['rw'] ?? '0';
    $jk = $penduduk['JK'];

    $key = $dusun . '_' . $rt . '_' . $rw . '_' . $jk;

    if (!isset($klasifikasi_rt_rw[$key])) {
        $klasifikasi_rt_rw[$key] = [
            'dusun' => $dusun,
            'rt' => $rt,
            'rw' => $rw,
            'jk' => $jk,
            'jumlah' => 0
        ];
    }

    $klasifikasi_rt_rw[$key]['jumlah']++;

    // Hitung total
    if ($jk == 'L') {
        $total_laki_laki++;
    } else {
        $total_perempuan++;
    }
}

// Data untuk tabel kedua: Rentang Umur
$rentang_umur = [];
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
    '60+' => [61, 200] // 60 tahun ke atas
];

// Kelompokkan data per dusun
foreach ($data_penduduk as $penduduk) {
    $dusun = $penduduk['dusun'] ?? 'Tidak diketahui';
    $usia = (int)$penduduk['usia'];

    if (!isset($rentang_umur[$dusun])) {
        // Inisialisasi semua rentang umur dengan 0
        $rentang_umur[$dusun] = [
            'dusun' => $dusun,
            'total' => 0
        ];
        foreach ($rentang_labels as $label => $range) {
            $rentang_umur[$dusun][$label] = 0;
        }
    }

    $rentang_umur[$dusun]['total']++;

    // Tentukan rentang umur
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

// Data untuk tabel ketiga: Pekerjaan Utama
$pekerjaan_utama = [];

// Daftar pekerjaan yang akan ditampilkan (bisa disesuaikan)
$daftar_pekerjaan = [
    'PNS' => 'PNS',
    'TNI/POLRI' => 'TNI/POLRI',
    'SWASTA' => 'SWASTA',
    'WIRAUSAHA' => 'WIRAUSAHA',
    'PETANI' => 'PETANI',
    'NELAYAN' => 'NELAYAN',
    'BURUH' => 'BURUH',
    'PENSIUNAN' => 'PENSIUNAN',
    'TIDAK BEKERJA' => 'TIDAK BEKERJA',
    'LAINNYA' => 'LAINNYA'
];

// Kelompokkan data per dusun dan pekerjaan
foreach ($data_penduduk as $penduduk) {
    $dusun = $penduduk['dusun'] ?? 'Tidak diketahui';
    $pekerjaan = $penduduk['PEKERJAAN'] ?? 'TIDAK BEKERJA';

    // Normalisasi pekerjaan
    $pekerjaan_normalized = 'LAINNYA';
    foreach ($daftar_pekerjaan as $key => $value) {
        if (stripos($pekerjaan, $key) !== false || $pekerjaan == $key) {
            $pekerjaan_normalized = $key;
            break;
        }
    }

    $key = $dusun . '_' . $pekerjaan_normalized;

    if (!isset($pekerjaan_utama[$key])) {
        $pekerjaan_utama[$key] = [
            'dusun' => $dusun,
            'pekerjaan' => $pekerjaan_normalized,
            'jumlah' => 0
        ];
    }

    $pekerjaan_utama[$key]['jumlah']++;
}

// Urutkan data
ksort($klasifikasi_rt_rw);
ksort($rentang_umur);
ksort($pekerjaan_utama);

// Hitung total semua penduduk
$total_semua_penduduk = count($data_penduduk);

?>

<style>
    .swal2-container {
        z-index: 99999 !important;
    }

    .filter-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .table-responsive {
        margin-bottom: 30px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
    }

    .table-title {
        background-color: #0d6efd;
        color: white;
        padding: 15px;
        margin: 0;
        font-size: 1.2rem;
    }

    .table-section {
        margin-bottom: 40px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        overflow: hidden;
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #0d6efd;
        text-align: center;
    }

    .stat-card .stat-value {
        font-size: 2rem;
        font-weight: bold;
        color: #0d6efd;
        margin: 10px 0;
    }

    .stat-card .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .badge-jk-l {
        background-color: #0d6efd;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
    }

    .badge-jk-p {
        background-color: #dc3545;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
    }

    .total-row {
        background-color: #f8f9fa !important;
        font-weight: bold;
        border-top: 2px solid #dee2e6;
    }

    .age-cell {
        text-align: center;
        font-weight: 500;
    }

    .age-cell.high {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .age-cell.medium {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .age-cell.low {
        background-color: rgba(40, 167, 69, 0.1);
    }

    .export-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .btn-excel {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
    }

    .btn-excel:hover {
        background-color: #218838;
        border-color: #1e7e34;
        color: white;
    }

    .btn-pdf {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .btn-pdf:hover {
        background-color: #c82333;
        border-color: #bd2130;
        color: white;
    }

    .btn-print {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }

    .btn-print:hover {
        background-color: #138496;
        border-color: #117a8b;
        color: white;
    }

    .info-badge {
        background-color: #6c757d;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        margin-left: 10px;
    }

    .dusun-header {
        background-color: #e9ecef;
        font-weight: bold;
        border-top: 2px solid #dee2e6;
    }
</style>

<!-- [Body] Start -->

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->

    <!-- Sidebar Start -->
    <?php include_once '../includes/sidebar.php'; ?>
    <!-- Sidebar End -->

    <?php include_once '../includes/navbar.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">

            <!-- [ Main Content ] start -->
            <div class="row">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Klasifikasi Data Penduduk</h2>
                    <div class="export-buttons">
                        <a href="export_klasifikasi_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success">
                            <i class="ti ti-file-spreadsheet"></i> Excel
                        </a>

                        <a href="export_klasifikasi_pdf.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-danger">
                            <i class="ti ti-file-text"></i> PDF
                        </a>

                    </div>
                </div>

                <!-- Statistik -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Total Penduduk</div>
                            <div class="stat-value"><?= number_format($total_semua_penduduk) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Laki-laki</div>
                            <div class="stat-value"><?= number_format($total_laki_laki) ?></div>
                            <div class="stat-label">
                                <?= $total_semua_penduduk > 0 ? number_format(($total_laki_laki / $total_semua_penduduk) * 100, 1) : 0 ?>%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Perempuan</div>
                            <div class="stat-value"><?= number_format($total_perempuan) ?></div>
                            <div class="stat-label">
                                <?= $total_semua_penduduk > 0 ? number_format(($total_perempuan / $total_semua_penduduk) * 100, 1) : 0 ?>%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-label">Jumlah Dusun</div>
                            <div class="stat-value"><?= count(array_unique(array_column($data_penduduk, 'dusun'))) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-container">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="dusun" class="form-label">Filter Dusun</label>
                            <select name="dusun" id="dusun" class="form-select">
                                <option value="">Semua Dusun</option>
                                <?php foreach ($data_dusun as $dusun): ?>
                                    <option value="<?= $dusun['id'] ?>" <?= $dusun_filter == $dusun['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dusun['dusun']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="rt" class="form-label">Filter RT</label>
                            <select name="rt" id="rt" class="form-select">
                                <option value="">Semua RT</option>
                                <?php
                                $rt_unik = array_unique(array_column($data_rt_rw, 'rt'));
                                sort($rt_unik);
                                foreach ($rt_unik as $rt): ?>
                                    <option value="<?= $rt ?>" <?= $rt_filter == $rt ? 'selected' : '' ?>>
                                        RT <?= $rt ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="rw" class="form-label">Filter RW</label>
                            <select name="rw" id="rw" class="form-select">
                                <option value="">Semua RW</option>
                                <?php
                                $rw_unik = array_unique(array_column($data_rt_rw, 'rw'));
                                sort($rw_unik);
                                foreach ($rw_unik as $rw): ?>
                                    <option value="<?= $rw ?>" <?= $rw_filter == $rw ? 'selected' : '' ?>>
                                        RW <?= $rw ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-filter"></i> Filter
                                </button>
                                <a href="klasifikasi.php" class="btn btn-secondary">
                                    <i class="ti ti-refresh"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Info Filter -->
                    <?php if (!empty($dusun_filter) || !empty($rt_filter) || !empty($rw_filter)): ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="ti ti-info-circle"></i>
                                Menampilkan data dengan filter:
                                <?php
                                $filter_info = [];
                                if (!empty($dusun_filter)) {
                                    $nama_dusun = '';
                                    foreach ($data_dusun as $dusun) {
                                        if ($dusun['id'] == $dusun_filter) {
                                            $nama_dusun = $dusun['dusun'];
                                            break;
                                        }
                                    }
                                    $filter_info[] = "Dusun: $nama_dusun";
                                }
                                if (!empty($rt_filter)) {
                                    $filter_info[] = "RT: $rt_filter";
                                }
                                if (!empty($rw_filter)) {
                                    $filter_info[] = "RW: $rw_filter";
                                }
                                echo implode(', ', $filter_info);
                                ?>
                                <span class="info-badge">Total: <?= number_format($total_semua_penduduk) ?> penduduk</span>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TABEL 1: Klasifikasi berdasarkan RT/RW dan Jenis Kelamin -->
                <div class="table-section">
                    <h4 class="table-title">
                        <i class="ti ti-users"></i> Klasifikasi Penduduk Berdasarkan RT/RW dan Jenis Kelamin
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="tabel1">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;" class="text-center">No</th>
                                    <th style="width: 20%;">Dusun</th>
                                    <th style="width: 10%;" class="text-center">RT</th>
                                    <th style="width: 10%;" class="text-center">RW</th>
                                    <th style="width: 20%;">Jenis Kelamin</th>
                                    <th style="width: 15%;" class="text-center">Jumlah</th>
                                    <th style="width: 20%;" class="text-center">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($klasifikasi_rt_rw)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted mb-2">
                                                <i class="ti ti-user-off fs-1"></i>
                                            </div>
                                            Tidak ada data penduduk
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $no = 1;
                                    $current_dusun = '';
                                    $subtotal_dusun = 0;
                                    $grand_total = 0;
                                    $is_first_row = true;
                                    ?>
                                    <?php foreach ($klasifikasi_rt_rw as $key => $data): ?>
                                        <?php
                                        // Jika pindah dusun, tampilkan subtotal dusun sebelumnya
                                        if ($current_dusun != $data['dusun'] && !$is_first_row): ?>
                                            <tr class="dusun-header">
                                                <td colspan="5" class="text-end"><strong>Subtotal Dusun <?= htmlspecialchars($current_dusun) ?>:</strong></td>
                                                <td class="text-center"><strong><?= number_format($subtotal_dusun) ?></strong></td>
                                                <td class="text-center">
                                                    <strong>
                                                        <?= $total_semua_penduduk > 0 ? number_format(($subtotal_dusun / $total_semua_penduduk) * 100, 1) : 0 ?>%
                                                    </strong>
                                                </td>
                                            </tr>
                                            <?php $subtotal_dusun = 0; ?>
                                        <?php endif; ?>

                                        <?php $current_dusun = $data['dusun']; ?>
                                        <?php $subtotal_dusun += $data['jumlah']; ?>
                                        <?php $grand_total += $data['jumlah']; ?>

                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($data['dusun']) ?></td>
                                            <td class="text-center"><?= $data['rt'] ?></td>
                                            <td class="text-center"><?= $data['rw'] ?></td>
                                            <td>
                                                <?php if ($data['jk'] == 'L'): ?>
                                                    <span class="badge-jk-l">Laki-laki</span>
                                                <?php else: ?>
                                                    <span class="badge-jk-p">Perempuan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?= number_format($data['jumlah']) ?></td>
                                            <td class="text-center">
                                                <?= $total_semua_penduduk > 0 ? number_format(($data['jumlah'] / $total_semua_penduduk) * 100, 1) : 0 ?>%
                                            </td>
                                        </tr>
                                        <?php $is_first_row = false; ?>
                                    <?php endforeach; ?>

                                    <!-- Tampilkan subtotal dusun terakhir -->
                                    <?php if (!empty($current_dusun)): ?>
                                        <tr class="dusun-header">
                                            <td colspan="5" class="text-end"><strong>Subtotal Dusun <?= htmlspecialchars($current_dusun) ?>:</strong></td>
                                            <td class="text-center"><strong><?= number_format($subtotal_dusun) ?></strong></td>
                                            <td class="text-center">
                                                <strong>
                                                    <?= $total_semua_penduduk > 0 ? number_format(($subtotal_dusun / $total_semua_penduduk) * 100, 1) : 0 ?>%
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <!-- Grand Total -->
                                    <tr class="total-row">
                                        <td colspan="5" class="text-end"><strong>GRAND TOTAL:</strong></td>
                                        <td class="text-center"><strong><?= number_format($grand_total) ?></strong></td>
                                        <td class="text-center"><strong>100%</strong></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TABEL 2: Rentang Umur -->
                <div class="table-section">
                    <h4 class="table-title">
                        <i class="ti ti-calendar"></i> Distribusi Penduduk Berdasarkan Rentang Usia
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="tabel2">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;" class="text-center">No</th>
                                    <th style="width: 15%;">Dusun</th>
                                    <th style="width: 6%;" class="text-center age-cell">0-5</th>
                                    <th style="width: 6%;" class="text-center age-cell">6-10</th>
                                    <th style="width: 6%;" class="text-center age-cell">11-15</th>
                                    <th style="width: 6%;" class="text-center age-cell">16-20</th>
                                    <th style="width: 6%;" class="text-center age-cell">21-25</th>
                                    <th style="width: 6%;" class="text-center age-cell">26-30</th>
                                    <th style="width: 6%;" class="text-center age-cell">31-35</th>
                                    <th style="width: 6%;" class="text-center age-cell">36-40</th>
                                    <th style="width: 6%;" class="text-center age-cell">41-45</th>
                                    <th style="width: 6%;" class="text-center age-cell">46-50</th>
                                    <th style="width: 6%;" class="text-center age-cell">51-55</th>
                                    <th style="width: 6%;" class="text-center age-cell">56-60</th>
                                    <th style="width: 6%;" class="text-center age-cell">60+</th>
                                    <th style="width: 5%;" class="text-center">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rentang_umur)): ?>
                                    <tr>
                                        <td colspan="16" class="text-center py-4">
                                            <div class="text-muted mb-2">
                                                <i class="ti ti-calendar-off fs-1"></i>
                                            </div>
                                            Tidak ada data usia penduduk
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $no = 1;
                                    $total_per_rentang = [];
                                    foreach ($rentang_labels as $label => $range) {
                                        $total_per_rentang[$label] = 0;
                                    }
                                    $grand_total_umur = 0;
                                    ?>
                                    <?php foreach ($rentang_umur as $data): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($data['dusun']) ?></td>
                                            <?php foreach ($rentang_labels as $label => $range): ?>
                                                <?php
                                                $jumlah = $data[$label] ?? 0;
                                                $total_per_rentang[$label] += $jumlah;

                                                // Tentukan class warna berdasarkan jumlah
                                                $cell_class = 'age-cell';
                                                if ($jumlah > 20) $cell_class .= ' high';
                                                elseif ($jumlah > 10) $cell_class .= ' medium';
                                                elseif ($jumlah > 0) $cell_class .= ' low';
                                                ?>
                                                <td class="<?= $cell_class ?>"><?= number_format($jumlah) ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-center"><strong><?= number_format($data['total']) ?></strong></td>
                                        </tr>
                                        <?php $grand_total_umur += $data['total']; ?>
                                    <?php endforeach; ?>

                                    <!-- Total per rentang umur -->
                                    <tr class="total-row">
                                        <td colspan="2" class="text-end"><strong>TOTAL:</strong></td>
                                        <?php foreach ($rentang_labels as $label => $range): ?>
                                            <td class="text-center"><strong><?= number_format($total_per_rentang[$label]) ?></strong></td>
                                        <?php endforeach; ?>
                                        <td class="text-center"><strong><?= number_format($grand_total_umur) ?></strong></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TABEL 3: Pekerjaan Utama -->
                <div class="table-section">
                    <h4 class="table-title">
                        <i class="ti ti-briefcase"></i> Distribusi Pekerjaan Utama Penduduk
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="tabel3">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;" class="text-center">No</th>
                                    <th style="width: 30%;">Dusun</th>
                                    <th style="width: 40%;">Pekerjaan Utama</th>
                                    <th style="width: 15%;" class="text-center">Jumlah</th>
                                    <th style="width: 10%;" class="text-center">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pekerjaan_utama)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted mb-2">
                                                <i class="ti ti-briefcase-off fs-1"></i>
                                            </div>
                                            Tidak ada data pekerjaan
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    $no = 1;
                                    $current_dusun = '';
                                    $subtotal_pekerjaan_dusun = 0;
                                    $total_per_pekerjaan = [];
                                    $grand_total_pekerjaan = 0;
                                    $is_first_row = true;

                                    // Inisialisasi total per pekerjaan
                                    foreach ($daftar_pekerjaan as $key => $value) {
                                        $total_per_pekerjaan[$key] = 0;
                                    }
                                    ?>
                                    <?php foreach ($pekerjaan_utama as $key => $data): ?>
                                        <?php
                                        // Jika pindah dusun, tampilkan subtotal dusun sebelumnya
                                        if ($current_dusun != $data['dusun'] && !$is_first_row): ?>
                                            <tr class="dusun-header">
                                                <td colspan="3" class="text-end"><strong>Subtotal Dusun <?= htmlspecialchars($current_dusun) ?>:</strong></td>
                                                <td class="text-center"><strong><?= number_format($subtotal_pekerjaan_dusun) ?></strong></td>
                                                <td class="text-center">
                                                    <strong>
                                                        <?= $total_semua_penduduk > 0 ? number_format(($subtotal_pekerjaan_dusun / $total_semua_penduduk) * 100, 1) : 0 ?>%
                                                    </strong>
                                                </td>
                                            </tr>
                                            <?php $subtotal_pekerjaan_dusun = 0; ?>
                                        <?php endif; ?>

                                        <?php $current_dusun = $data['dusun']; ?>
                                        <?php $subtotal_pekerjaan_dusun += $data['jumlah']; ?>
                                        <?php $total_per_pekerjaan[$data['pekerjaan']] += $data['jumlah']; ?>
                                        <?php $grand_total_pekerjaan += $data['jumlah']; ?>

                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><?= htmlspecialchars($data['dusun']) ?></td>
                                            <td><?= htmlspecialchars($data['pekerjaan']) ?></td>
                                            <td class="text-center"><?= number_format($data['jumlah']) ?></td>
                                            <td class="text-center">
                                                <?= $total_semua_penduduk > 0 ? number_format(($data['jumlah'] / $total_semua_penduduk) * 100, 1) : 0 ?>%
                                            </td>
                                        </tr>
                                        <?php $is_first_row = false; ?>
                                    <?php endforeach; ?>

                                    <!-- Tampilkan subtotal dusun terakhir -->
                                    <?php if (!empty($current_dusun)): ?>
                                        <tr class="dusun-header">
                                            <td colspan="3" class="text-end"><strong>Subtotal Dusun <?= htmlspecialchars($current_dusun) ?>:</strong></td>
                                            <td class="text-center"><strong><?= number_format($subtotal_pekerjaan_dusun) ?></strong></td>
                                            <td class="text-center">
                                                <strong>
                                                    <?= $total_semua_penduduk > 0 ? number_format(($subtotal_pekerjaan_dusun / $total_semua_penduduk) * 100, 1) : 0 ?>%
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <!-- Total per pekerjaan -->
                                    <tr class="total-row">
                                        <td colspan="3" class="text-end"><strong>GRAND TOTAL:</strong></td>
                                        <td class="text-center"><strong><?= number_format($grand_total_pekerjaan) ?></strong></td>
                                        <td class="text-center"><strong>100%</strong></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Note -->
                <div class="alert alert-info">
                    <h6><i class="ti ti-info-circle"></i> Catatan:</h6>
                    <ul class="mb-0">
                        <li>Data dihitung berdasarkan <?= number_format($total_semua_penduduk) ?> penduduk yang terdaftar</li>
                        <li>Persentase dihitung terhadap total penduduk dalam filter yang dipilih</li>
                        <li>Rentang usia 60+ termasuk usia 60 tahun ke atas</li>
                        <li>Pekerjaan "LAINNYA" termasuk pekerjaan yang tidak termasuk dalam kategori utama</li>
                        <li>Data terakhir diperbarui: <?= date('d F Y H:i:s') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include_once '../includes/footer.php'; ?>

</body>
<!-- [Body] end -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Fungsi untuk print halaman
    function printPage() {
        var originalContents = document.body.innerHTML;

        // Ambil konten yang akan dicetak
        var printContents = `
            <html>
                <head>
                    <title>Klasifikasi Data Penduduk</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        @media print {
                            .no-print { display: none; }
                            body { font-size: 12px; }
                            table { font-size: 11px; }
                            .table-title { 
                                background-color: #0d6efd !important; 
                                color: white !important;
                                -webkit-print-color-adjust: exact;
                            }
                            .total-row { 
                                background-color: #f8f9fa !important; 
                                -webkit-print-color-adjust: exact;
                            }
                        }
                        .page-break { page-break-before: always; }
                    </style>
                </head>
                <body>
                    <div class="container mt-4">
                        <h3 class="text-center mb-3">Klasifikasi Data Penduduk</h3>
                        <p class="text-center mb-4">
                            Tanggal Cetak: ${new Date().toLocaleDateString('id-ID')}<br>
                            Total Penduduk: <?= number_format($total_semua_penduduk) ?>
                        </p>
                        
                        <h4 class="table-title p-2 mb-3">Klasifikasi Berdasarkan RT/RW dan Jenis Kelamin</h4>
                        ${document.getElementById('tabel1').outerHTML}
                        
                        <div class="page-break"></div>
                        
                        <h4 class="table-title p-2 mb-3 mt-4">Distribusi Berdasarkan Rentang Usia</h4>
                        ${document.getElementById('tabel2').outerHTML}
                        
                        <div class="page-break"></div>
                        
                        <h4 class="table-title p-2 mb-3 mt-4">Distribusi Pekerjaan Utama</h4>
                        ${document.getElementById('tabel3').outerHTML}
                        
                        <div class="mt-4">
                            <small class="text-muted">
                                <i class="ti ti-info-circle"></i> Dicetak dari Sistem Informasi Kependudukan
                            </small>
                        </div>
                    </div>
                </body>
            </html>`;

        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }

    // Auto submit form on filter change (optional)
    document.getElementById('dusun').addEventListener('change', function() {
        if (this.value) {
            this.form.submit();
        }
    });
</script>

</html>