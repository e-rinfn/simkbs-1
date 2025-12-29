<?php

include_once __DIR__ . '/../../config/config.php';
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades' && $_SESSION['role'] !== 'sekretaris') {
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Parameter filter untuk surat masuk
$bulan_filter_masuk = isset($_GET['bulan_masuk']) ? $_GET['bulan_masuk'] : '';
$tahun_filter_masuk = isset($_GET['tahun_masuk']) ? $_GET['tahun_masuk'] : date('Y');
$sifat_filter_masuk = isset($_GET['sifat_masuk']) ? $_GET['sifat_masuk'] : '';
$status_filter_masuk = isset($_GET['status_masuk']) ? $_GET['status_masuk'] : '';
$search_masuk = isset($_GET['search_masuk']) ? $_GET['search_masuk'] : '';

// Parameter filter untuk surat keluar
$bulan_filter_keluar = isset($_GET['bulan_keluar']) ? $_GET['bulan_keluar'] : '';
$tahun_filter_keluar = isset($_GET['tahun_keluar']) ? $_GET['tahun_keluar'] : date('Y');
$sifat_filter_keluar = isset($_GET['sifat_keluar']) ? $_GET['sifat_keluar'] : '';
$status_filter_keluar = isset($_GET['status_keluar']) ? $_GET['status_keluar'] : '';
$search_keluar = isset($_GET['search_keluar']) ? $_GET['search_keluar'] : '';

// Aktif tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'masuk';

// Data untuk dropdown filter
$sifat_surat_options = ['BIASA', 'PENTING', 'RAHASIA', 'SANGAT RAHASIA'];
$status_options_masuk = ['BARU', 'DIPROSES', 'SELESAI', 'ARSIP'];
$status_options_keluar = ['DRAFT', 'DIPROSES', 'TERKIRIM', 'ARSIP'];

// Generate tahun options (5 tahun terakhir)
$tahun_options = [];
$current_year = date('Y');
for ($i = $current_year; $i >= $current_year - 5; $i--) {
    $tahun_options[] = $i;
}

// Generate bulan options
$bulan_options = [
    '' => 'Semua Bulan',
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

// FUNGSI UNTUK SURAT MASUK
function getSuratMasukData($conn, $bulan_filter, $tahun_filter, $sifat_filter, $status_filter, $search)
{
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
            ORDER BY tanggal_surat DESC, created_at DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($params_types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        }
    }

    global $conn;
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// FUNGSI UNTUK SURAT KELUAR
function getSuratKeluarData($conn, $bulan_filter, $tahun_filter, $sifat_filter, $status_filter, $search)
{
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
        $where_conditions[] = "(nomor_surat LIKE ? OR tujuan LIKE ? OR perihal LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params_types .= 'sss';
    }

    $where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    $sql = "SELECT * FROM tabel_surat_keluar 
            $where_sql 
            ORDER BY tanggal_surat DESC, created_at DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($params_types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        }
    }

    global $conn;
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// PERBAIKAN: Ambil data UNTUK KEDUA TAB secara terpisah
$data_surat_masuk = getSuratMasukData($conn, $bulan_filter_masuk, $tahun_filter_masuk, $sifat_filter_masuk, $status_filter_masuk, $search_masuk);
$data_surat_keluar = getSuratKeluarData($conn, $bulan_filter_keluar, $tahun_filter_keluar, $sifat_filter_keluar, $status_filter_keluar, $search_keluar);

// Hitung statistik surat masuk (tanpa filter untuk statistik total)
$data_masuk_total = getSuratMasukData($conn, '', date('Y'), '', '', '');
$total_masuk = count($data_masuk_total);
$total_masuk_baru = 0;
$total_masuk_diproses = 0;
$total_masuk_selesai = 0;
$total_masuk_arsip = 0;

foreach ($data_masuk_total as $surat) {
    switch ($surat['status']) {
        case 'BARU':
            $total_masuk_baru++;
            break;
        case 'DIPROSES':
            $total_masuk_diproses++;
            break;
        case 'SELESAI':
            $total_masuk_selesai++;
            break;
        case 'ARSIP':
            $total_masuk_arsip++;
            break;
    }
}

// Hitung statistik surat keluar (tanpa filter untuk statistik total)
$data_keluar_total = getSuratKeluarData($conn, '', date('Y'), '', '', '');
$total_keluar = count($data_keluar_total);
$total_keluar_draft = 0;
$total_keluar_diproses = 0;
$total_keluar_terkirim = 0;
$total_keluar_arsip = 0;

foreach ($data_keluar_total as $surat) {
    switch ($surat['status']) {
        case 'DRAFT':
            $total_keluar_draft++;
            break;
        case 'DIPROSES':
            $total_keluar_diproses++;
            break;
        case 'TERKIRIM':
            $total_keluar_terkirim++;
            break;
        case 'ARSIP':
            $total_keluar_arsip++;
            break;
    }
}

// Hitung total data untuk masing-masing tab (dengan filter)
$total_masuk_filtered = count($data_surat_masuk);
$total_keluar_filtered = count($data_surat_keluar);

// Path untuk file upload
$base_upload_path_surat = __DIR__ . '/../../uploads/surat/';
$base_url_path_surat = $base_url . '/uploads/surat/';

?>

<style>
    .swal2-container {
        z-index: 99999 !important;
    }

    .tab-content {
        margin-top: 20px;
    }

    .nav-tabs .nav-link {
        font-weight: 500;
        padding: 10px 20px;
        border: none;
        color: #6c757d;
        position: relative;
    }

    .nav-tabs .nav-link.active {
        color: #0d6efd;
        background-color: transparent;
        border-bottom: 3px solid #0d6efd;
    }

    .nav-tabs .nav-link:hover {
        color: #0d6efd;
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

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        text-align: center;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-card.masuk {
        border-left: 4px solid #0d6efd;
    }

    .stat-card.keluar {
        border-left: 4px solid #198754;
    }

    .stat-card .stat-value {
        font-size: 1.8rem;
        font-weight: bold;
        margin: 5px 0;
    }

    .stat-card.masuk .stat-value {
        color: #0d6efd;
    }

    .stat-card.keluar .stat-value {
        color: #198754;
    }

    .stat-card .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .badge-status {
        font-size: 0.85em;
        padding: 5px 10px;
        border-radius: 20px;
    }

    /* Status untuk surat masuk */
    .badge-baru {
        background-color: #0d6efd;
        color: white;
    }

    .badge-diproses {
        background-color: #ffc107;
        color: #212529;
    }

    .badge-selesai {
        background-color: #198754;
        color: white;
    }

    .badge-arsip {
        background-color: #6c757d;
        color: white;
    }

    /* Status untuk surat keluar */
    .badge-draft {
        background-color: #6c757dff;
        color: white;
    }

    .badge-terbit {
        background-color: #6c757dff;
        color: white;
    }

    .badge-terkirim {
        background-color: #198754;
        color: white;
    }

    .badge-sifat {
        font-size: 0.8em;
        padding: 4px 8px;
        border-radius: 15px;
    }

    .badge-biasa {
        background-color: #6c757d;
        color: white;
    }

    .badge-penting {
        background-color: #fd7e14;
        color: white;
    }

    .badge-rahasia {
        background-color: #dc3545;
        color: white;
    }

    .badge-sangat-rahasia {
        background-color: #6f42c1;
        color: white;
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

    .file-icon {
        color: #0d6efd;
        font-size: 1.2em;
    }

    .file-link {
        text-decoration: none;
        color: #0d6efd;
    }

    .file-link:hover {
        text-decoration: underline;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: nowrap;
    }

    .action-buttons .btn {
        padding: 4px 8px;
        font-size: 0.875rem;
    }

    .perihal-text {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .perihal-text:hover {
        overflow: visible;
        white-space: normal;
        position: absolute;
        background: white;
        padding: 10px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        max-width: 400px;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }

    .no-data i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    .info-badge {
        background-color: #6c757d;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        margin-left: 10px;
    }

    .urgent-row {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .secret-row {
        background-color: rgba(220, 53, 69, 0.05);
    }

    .tab-pane {
        animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .tab-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .tab-title {
        margin: 0;
    }

    .tab-actions {
        display: flex;
        gap: 10px;
        align-items: center;
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
                <!-- Statistik Overview -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card masuk" onclick="switchTab('masuk')" style="cursor: pointer;">
                            <div class="stat-label">Total Surat Masuk</div>
                            <div class="stat-value"><?= number_format($total_masuk) ?></div>
                            <div class="row mt-2">
                                <div class="col-3">
                                    <small class="text-primary">Baru: <?= $total_masuk_baru ?></small>
                                </div>
                                <div class="col-3">
                                    <small class="text-warning">Proses: <?= $total_masuk_diproses ?></small>
                                </div>
                                <div class="col-3">
                                    <small class="text-success">Selesai: <?= $total_masuk_selesai ?></small>
                                </div>
                                <div class="col-3">
                                    <small class="text-secondary">Arsip: <?= $total_masuk_arsip ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card keluar" onclick="switchTab('keluar')" style="cursor: pointer;">
                            <div class="stat-label">Total Surat Keluar</div>
                            <div class="stat-value"><?= number_format($total_keluar) ?></div>
                            <div class="row mt-2">
                                <div class="col-3">
                                    <small class="text-secondary">Draft: <?= $total_keluar_draft ?></small>
                                </div>
                                <div class="col-3">
                                    <small class="text-warning">Proses: <?= $total_keluar_diproses ?></small>
                                </div>
                                <div class="col-3">
                                    <small class="text-success">Terkirim: <?= $total_keluar_terkirim ?></small>
                                </div>
                                <div class="col-3">
                                    <small class="text-secondary">Arsip: <?= $total_keluar_arsip ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-label">Total Keseluruhan</div>
                            <div class="stat-value"><?= number_format($total_masuk + $total_keluar) ?></div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small>Surat Masuk: <?= $total_masuk ?></small>
                                </div>
                                <div class="col-6">
                                    <small>Surat Keluar: <?= $total_keluar ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tampilkan pesan error atau success -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Sukses!</strong> <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs" id="suratTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'masuk' ? 'active' : '' ?>" id="masuk-tab"
                            data-bs-toggle="tab" data-bs-target="#masuk-content" type="button"
                            role="tab" aria-controls="masuk" aria-selected="<?= $active_tab === 'masuk' ? 'true' : 'false' ?>">
                            <i class="ti ti-mail-in"></i> Surat Masuk
                            <span class="badge bg-primary ms-1"><?= $total_masuk ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $active_tab === 'keluar' ? 'active' : '' ?>" id="keluar-tab"
                            data-bs-toggle="tab" data-bs-target="#keluar-content" type="button"
                            role="tab" aria-controls="keluar" aria-selected="<?= $active_tab === 'keluar' ? 'true' : 'false' ?>">
                            <i class="ti ti-mail-out"></i> Surat Keluar
                            <span class="badge bg-success ms-1"><?= $total_keluar ?></span>
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="suratTabContent">
                    <!-- Tab Surat Masuk -->
                    <div class="tab-pane fade <?= $active_tab === 'masuk' ? 'show active' : '' ?>"
                        id="masuk-content" role="tabpanel" aria-labelledby="masuk-tab">

                        <!-- Header Surat Masuk -->
                        <div class="tab-header">
                            <h3 class="tab-title">Arsip Surat Masuk</h3>
                            <div class="tab-actions">
                                <a href="add.php" class="btn btn-success">
                                    <i class="ti ti-plus"></i> Tambah Surat Masuk
                                </a>
                            </div>
                        </div>

                        <!-- Filter Surat Masuk -->
                        <div class="filter-container">
                            <form method="GET" action="" class="row g-3">
                                <input type="hidden" name="tab" value="masuk">

                                <div class="col-md-2">
                                    <label for="bulan_masuk" class="form-label">Bulan</label>
                                    <select name="bulan_masuk" id="bulan_masuk" class="form-select">
                                        <?php foreach ($bulan_options as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $bulan_filter_masuk == $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="tahun_masuk" class="form-label">Tahun</label>
                                    <select name="tahun_masuk" id="tahun_masuk" class="form-select">
                                        <?php foreach ($tahun_options as $tahun): ?>
                                            <option value="<?= $tahun ?>" <?= $tahun_filter_masuk == $tahun ? 'selected' : '' ?>>
                                                <?= $tahun ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="sifat_masuk" class="form-label">Sifat Surat</label>
                                    <select name="sifat_masuk" id="sifat_masuk" class="form-select">
                                        <option value="">Semua Sifat</option>
                                        <?php foreach ($sifat_surat_options as $sifat): ?>
                                            <option value="<?= $sifat ?>" <?= $sifat_filter_masuk == $sifat ? 'selected' : '' ?>>
                                                <?= $sifat ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="status_masuk" class="form-label">Status</label>
                                    <select name="status_masuk" id="status_masuk" class="form-select">
                                        <option value="">Semua Status</option>
                                        <?php foreach ($status_options_masuk as $status): ?>
                                            <option value="<?= $status ?>" <?= $status_filter_masuk == $status ? 'selected' : '' ?>>
                                                <?= $status ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="search_masuk" class="form-label">Cari (No. Surat/Pengirim/Perihal)</label>
                                    <input type="text" name="search_masuk" id="search_masuk" class="form-control"
                                        placeholder="Masukkan kata kunci"
                                        value="<?= htmlspecialchars($search_masuk) ?>">
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <div class="btn-group w-100">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-filter"></i>
                                        </button>
                                        <a href="?tab=masuk" class="btn btn-secondary">
                                            <i class="ti ti-refresh"></i>
                                        </a>
                                    </div>
                                </div>
                            </form>




                            <!-- Info Filter Surat Masuk -->
                            <?php if (!empty($bulan_filter_masuk) || !empty($tahun_filter_masuk) || !empty($sifat_filter_masuk) || !empty($status_filter_masuk) || !empty($search_masuk)): ?>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle"></i>
                                        Menampilkan <?= $total_masuk_filtered ?> surat masuk dengan filter:
                                        <?php
                                        $filter_info = [];
                                        if (!empty($bulan_filter_masuk) && !empty($tahun_filter_masuk)) {
                                            $filter_info[] = $bulan_options[$bulan_filter_masuk] . ' ' . $tahun_filter_masuk;
                                        } elseif (!empty($tahun_filter_masuk)) {
                                            $filter_info[] = 'Tahun ' . $tahun_filter_masuk;
                                        }
                                        if (!empty($sifat_filter_masuk)) {
                                            $filter_info[] = 'Sifat: ' . $sifat_filter_masuk;
                                        }
                                        if (!empty($status_filter_masuk)) {
                                            $filter_info[] = 'Status: ' . $status_filter_masuk;
                                        }
                                        if (!empty($search_masuk)) {
                                            $filter_info[] = 'Kata kunci: "' . $search_masuk . '"';
                                        }
                                        echo implode(', ', $filter_info);
                                        ?>
                                        <span class="info-badge">Total: <?= number_format($total_masuk_filtered) ?> surat</span>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Content Surat Masuk -->
                        <div class="card p-3">

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-muted">Total Data: <strong><?= number_format($total_masuk_filtered, 0, ',', '.') ?></strong> surat</span>
                                </div>
                                <div class="btn-group">
                                    <a href="export_surat_masuk_excel.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-success">
                                        <i class="ti ti-file-spreadsheet"></i> Excel
                                    </a>
                                    <a href="export_surat_masuk_pdf.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-danger">
                                        <i class="ti ti-file-text"></i> PDF
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTable-masuk">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 3%;" class="text-center">No</th>
                                            <th style="width: 12%;">No. Surat</th>
                                            <th style="width: 8%;">Tanggal Surat</th>
                                            <th style="width: 10%;">Tanggal Diterima</th>
                                            <th style="width: 20%;">Pengirim</th>
                                            <th style="width: 20%;">Perihal</th>
                                            <th style="width: 10%;">File</th>
                                            <th style="width: 10%;">Status</th>
                                            <th style="width: 9%;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data_surat_masuk)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <div class="no-data">
                                                        <i class="ti ti-mail-opened"></i>
                                                        <h5 class="mt-2 mb-3">Tidak ada data surat masuk</h5>
                                                        <?php if (!empty($bulan_filter_masuk) || !empty($tahun_filter_masuk) || !empty($sifat_filter_masuk) || !empty($status_filter_masuk) || !empty($search_masuk)): ?>
                                                            <p class="text-muted mb-0">Coba reset filter atau ubah kriteria pencarian</p>
                                                            <a href="?tab=masuk" class="btn btn-outline-primary mt-2">
                                                                <i class="ti ti-refresh"></i> Reset Filter
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="add.php" class="btn btn-primary mt-2">
                                                                <i class="ti ti-plus"></i> Tambah Surat Masuk Pertama
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $no = 1; ?>
                                            <?php foreach ($data_surat_masuk as $surat): ?>
                                                <?php
                                                // Tentukan class baris berdasarkan sifat surat
                                                $row_class = '';
                                                if ($surat['sifat_surat'] == 'PENTING') {
                                                    $row_class = 'urgent-row';
                                                } elseif ($surat['sifat_surat'] == 'RAHASIA' || $surat['sifat_surat'] == 'SANGAT RAHASIA') {
                                                    $row_class = 'secret-row';
                                                }

                                                // Format tanggal
                                                $tanggal_surat = !empty($surat['tanggal_surat']) ? dateIndo($surat['tanggal_surat']) : '-';
                                                $tanggal_diterima = !empty($surat['tanggal_diterima']) ? dateIndo($surat['tanggal_diterima']) : '-';

                                                // Badge sifat surat
                                                $badge_sifat_class = '';
                                                switch ($surat['sifat_surat']) {
                                                    case 'BIASA':
                                                        $badge_sifat_class = 'badge-biasa';
                                                        break;
                                                    case 'PENTING':
                                                        $badge_sifat_class = 'badge-penting';
                                                        break;
                                                    case 'RAHASIA':
                                                        $badge_sifat_class = 'badge-rahasia';
                                                        break;
                                                    case 'SANGAT RAHASIA':
                                                        $badge_sifat_class = 'badge-sangat-rahasia';
                                                        break;
                                                }

                                                // Badge status
                                                $badge_status_class = '';
                                                switch ($surat['status']) {
                                                    case 'BARU':
                                                        $badge_status_class = 'badge-baru';
                                                        break;
                                                    case 'DIPROSES':
                                                        $badge_status_class = 'badge-diproses';
                                                        break;
                                                    case 'SELESAI':
                                                        $badge_status_class = 'badge-selesai';
                                                        break;
                                                    case 'ARSIP':
                                                        $badge_status_class = 'badge-arsip';
                                                        break;
                                                }
                                                ?>
                                                <tr class="<?= $row_class ?>">
                                                    <td class="text-center"><?= $no++; ?></td>
                                                    <td>
                                                        <div>
                                                            <strong><?= htmlspecialchars($surat['nomor_surat']) ?></strong>
                                                            <br>
                                                            <span class="badge <?= $badge_sifat_class ?> badge-sifat">
                                                                <?= $surat['sifat_surat'] ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?= $tanggal_surat ?>
                                                    </td>
                                                    <td>
                                                        <?= $tanggal_diterima ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($surat['pengirim']) ?>
                                                    </td>
                                                    <td>
                                                        <div class="perihal-text" title="<?= htmlspecialchars($surat['perihal']) ?>">
                                                            <?= htmlspecialchars($surat['perihal']) ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($surat['file_surat'])): ?>
                                                            <?php
                                                            $file_path = $base_upload_path_surat . $surat['file_surat'];
                                                            $file_url = $base_url_path_surat . $surat['file_surat'];
                                                            $file_exists = file_exists($file_path);
                                                            ?>
                                                            <?php if ($file_exists): ?>
                                                                <a href="<?= $file_url ?>" target="_blank" class="file-link" title="Lihat/Download File">
                                                                    <i class="ti ti-file-text file-icon"></i>
                                                                    <small>Lihat File</small>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-danger" title="File tidak ditemukan">
                                                                    <i class="ti ti-file-off"></i>
                                                                    <small>File Hilang</small>
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?= $badge_status_class ?> badge-status">
                                                            <?= $surat['status'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="action-buttons">
                                                            <a href="surat_masuk_view.php?id=<?= $surat['id']; ?>"
                                                                class="btn btn-info btn-sm"
                                                                title="Detail"
                                                                data-bs-toggle="tooltip">
                                                                <i class="ti ti-eye"></i>
                                                            </a>
                                                            <a href="surat_masuk_edit.php?id=<?= $surat['id']; ?>"
                                                                class="btn btn-primary btn-sm"
                                                                title="Edit"
                                                                data-bs-toggle="tooltip">
                                                                <i class="ti ti-edit"></i>
                                                            </a>
                                                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kades'): ?>
                                                                <a href="surat_masuk_delete.php?id=<?= $surat['id']; ?>"
                                                                    class="btn btn-danger btn-sm btn-delete"
                                                                    title="Hapus"
                                                                    data-bs-toggle="tooltip">
                                                                    <i class="ti ti-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Surat Keluar -->
                    <div class="tab-pane fade <?= $active_tab === 'keluar' ? 'show active' : '' ?>"
                        id="keluar-content" role="tabpanel" aria-labelledby="keluar-tab">

                        <!-- Header Surat Keluar -->
                        <div class="tab-header">
                            <h3 class="tab-title">Arsip Surat Keluar</h3>
                            <div class="tab-actions">
                                <a href="add.php" class="btn btn-success">
                                    <i class="ti ti-plus"></i> Buat Surat Keluar
                                </a>
                            </div>
                        </div>

                        <!-- Filter Surat Keluar -->
                        <div class="filter-container">
                            <form method="GET" action="" class="row g-3">
                                <input type="hidden" name="tab" value="keluar">

                                <div class="col-md-2">
                                    <label for="bulan_keluar" class="form-label">Bulan</label>
                                    <select name="bulan_keluar" id="bulan_keluar" class="form-select">
                                        <?php foreach ($bulan_options as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $bulan_filter_keluar == $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="tahun_keluar" class="form-label">Tahun</label>
                                    <select name="tahun_keluar" id="tahun_keluar" class="form-select">
                                        <?php foreach ($tahun_options as $tahun): ?>
                                            <option value="<?= $tahun ?>" <?= $tahun_filter_keluar == $tahun ? 'selected' : '' ?>>
                                                <?= $tahun ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="sifat_keluar" class="form-label">Sifat Surat</label>
                                    <select name="sifat_keluar" id="sifat_keluar" class="form-select">
                                        <option value="">Semua Sifat</option>
                                        <?php foreach ($sifat_surat_options as $sifat): ?>
                                            <option value="<?= $sifat ?>" <?= $sifat_filter_keluar == $sifat ? 'selected' : '' ?>>
                                                <?= $sifat ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label for="status_keluar" class="form-label">Status</label>
                                    <select name="status_keluar" id="status_keluar" class="form-select">
                                        <option value="">Semua Status</option>
                                        <?php foreach ($status_options_keluar as $status): ?>
                                            <option value="<?= $status ?>" <?= $status_filter_keluar == $status ? 'selected' : '' ?>>
                                                <?= $status ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-3">
                                    <label for="search_keluar" class="form-label">Cari (No. Surat/Tujuan/Perihal)</label>
                                    <input type="text" name="search_keluar" id="search_keluar" class="form-control"
                                        placeholder="Masukkan kata kunci"
                                        value="<?= htmlspecialchars($search_keluar) ?>">
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <div class="btn-group w-100">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-filter"></i>
                                        </button>
                                        <a href="?tab=keluar" class="btn btn-secondary">
                                            <i class="ti ti-refresh"></i>
                                        </a>
                                    </div>
                                </div>
                            </form>

                            <!-- Info Filter Surat Keluar -->
                            <?php if (!empty($bulan_filter_keluar) || !empty($tahun_filter_keluar) || !empty($sifat_filter_keluar) || !empty($status_filter_keluar) || !empty($search_keluar)): ?>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle"></i>
                                        Menampilkan <?= $total_keluar_filtered ?> surat keluar dengan filter:
                                        <?php
                                        $filter_info = [];
                                        if (!empty($bulan_filter_keluar) && !empty($tahun_filter_keluar)) {
                                            $filter_info[] = $bulan_options[$bulan_filter_keluar] . ' ' . $tahun_filter_keluar;
                                        } elseif (!empty($tahun_filter_keluar)) {
                                            $filter_info[] = 'Tahun ' . $tahun_filter_keluar;
                                        }
                                        if (!empty($sifat_filter_keluar)) {
                                            $filter_info[] = 'Sifat: ' . $sifat_filter_keluar;
                                        }
                                        if (!empty($status_filter_keluar)) {
                                            $filter_info[] = 'Status: ' . $status_filter_keluar;
                                        }
                                        if (!empty($search_keluar)) {
                                            $filter_info[] = 'Kata kunci: "' . $search_keluar . '"';
                                        }
                                        echo implode(', ', $filter_info);
                                        ?>
                                        <span class="info-badge">Total: <?= number_format($total_keluar_filtered) ?> surat</span>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Content Surat Keluar -->
                        <div class="card p-3">
                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-muted">Total Data: <strong><?= number_format($total_keluar_filtered, 0, ',', '.') ?></strong> surat</span>
                                </div>
                                <div class="btn-group">
                                    <a href="export_surat_keluar_excel.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-success">
                                        <i class="ti ti-file-spreadsheet"></i> Excel
                                    </a>
                                    <a href="export_surat_keluar_pdf.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-danger">
                                        <i class="ti ti-file-text"></i> PDF
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTable-keluar">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 3%;" class="text-center">No</th>
                                            <th style="width: 12%;">No. Surat</th>
                                            <th style="width: 8%;">Tanggal Surat</th>
                                            <!-- <th style="width: 8%;">Tanggal Kirim</th> -->
                                            <th style="width: 15%;">Tujuan</th>
                                            <th style="width: 25%;">Perihal</th>
                                            <th style="width: 10%;">File</th>
                                            <th style="width: 10%;">Status</th>
                                            <th style="width: 9%;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data_surat_keluar)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-4">
                                                    <div class="no-data">
                                                        <i class="ti ti-mail-forward"></i>
                                                        <h5 class="mt-2 mb-3">Tidak ada data surat keluar</h5>
                                                        <?php if (!empty($bulan_filter_keluar) || !empty($tahun_filter_keluar) || !empty($sifat_filter_keluar) || !empty($status_filter_keluar) || !empty($search_keluar)): ?>
                                                            <p class="text-muted mb-0">Coba reset filter atau ubah kriteria pencarian</p>
                                                            <a href="?tab=keluar" class="btn btn-outline-primary mt-2">
                                                                <i class="ti ti-refresh"></i> Reset Filter
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="add.php" class="btn btn-primary mt-2">
                                                                <i class="ti ti-plus"></i> Buat Surat Keluar Pertama
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $no = 1; ?>
                                            <?php foreach ($data_surat_keluar as $surat): ?>
                                                <?php
                                                // Tentukan class baris berdasarkan sifat surat
                                                $row_class = '';
                                                if ($surat['sifat_surat'] == 'PENTING') {
                                                    $row_class = 'urgent-row';
                                                } elseif ($surat['sifat_surat'] == 'RAHASIA' || $surat['sifat_surat'] == 'SANGAT RAHASIA') {
                                                    $row_class = 'secret-row';
                                                }

                                                // Format tanggal
                                                $tanggal_surat = !empty($surat['tanggal_surat']) ? dateIndo($surat['tanggal_surat']) : '-';
                                                $tanggal_kirim = !empty($surat['tanggal_kirim']) ? dateIndo($surat['tanggal_kirim']) : '-';

                                                // Badge sifat surat
                                                $badge_sifat_class = '';
                                                switch ($surat['sifat_surat']) {
                                                    case 'BIASA':
                                                        $badge_sifat_class = 'badge-biasa';
                                                        break;
                                                    case 'PENTING':
                                                        $badge_sifat_class = 'badge-penting';
                                                        break;
                                                    case 'RAHASIA':
                                                        $badge_sifat_class = 'badge-rahasia';
                                                        break;
                                                    case 'SANGAT RAHASIA':
                                                        $badge_sifat_class = 'badge-sangat-rahasia';
                                                        break;
                                                }

                                                // Badge status
                                                $badge_status_class = '';
                                                switch ($surat['status']) {
                                                    case 'DRAFT':
                                                        $badge_status_class = 'badge-draft';
                                                        break;
                                                    case 'TERBIT':
                                                        $badge_status_class = 'badge-terbit';
                                                        break;
                                                    case 'DIPROSES':
                                                        $badge_status_class = 'badge-diproses';
                                                        break;
                                                    case 'TERKIRIM':
                                                        $badge_status_class = 'badge-terkirim';
                                                        break;
                                                    case 'ARSIP':
                                                        $badge_status_class = 'badge-arsip';
                                                        break;
                                                }
                                                ?>
                                                <tr class="<?= $row_class ?>">
                                                    <td class="text-center"><?= $no++; ?></td>
                                                    <td>
                                                        <div>
                                                            <strong><?= htmlspecialchars($surat['nomor_surat']) ?></strong>
                                                            <br>
                                                            <span class="badge <?= $badge_sifat_class ?> badge-sifat">
                                                                <?= $surat['sifat_surat'] ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?= $tanggal_surat ?>
                                                    </td>
                                                    <!-- <td>
                                                        <?= $tanggal_kirim ?>
                                                    </td> -->
                                                    <td>
                                                        <?= htmlspecialchars($surat['tujuan']) ?>
                                                    </td>
                                                    <td>
                                                        <div class="perihal-text" title="<?= htmlspecialchars($surat['perihal']) ?>">
                                                            <?= htmlspecialchars($surat['perihal']) ?>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if (!empty($surat['file_surat'])): ?>
                                                            <?php
                                                            $file_path = $base_upload_path_surat . $surat['file_surat'];
                                                            $file_url = $base_url_path_surat . $surat['file_surat'];
                                                            $file_exists = file_exists($file_path);
                                                            ?>
                                                            <?php if ($file_exists): ?>
                                                                <a href="<?= $file_url ?>" target="_blank" class="file-link" title="Lihat/Download File">
                                                                    <i class="ti ti-file-text file-icon"></i>
                                                                    <small>Lihat File</small>
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-danger" title="File tidak ditemukan">
                                                                    <i class="ti ti-file-off"></i>
                                                                    <small>File Hilang</small>
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?= $badge_status_class ?> badge-status">
                                                            <?= $surat['status'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="action-buttons">
                                                            <a href="surat_keluar_view.php?id=<?= $surat['id']; ?>"
                                                                class="btn btn-info btn-sm"
                                                                title="Detail"
                                                                data-bs-toggle="tooltip">
                                                                <i class="ti ti-eye"></i>
                                                            </a>
                                                            <a href="surat_keluar_edit.php?id=<?= $surat['id']; ?>"
                                                                class="btn btn-primary btn-sm"
                                                                title="Edit"
                                                                data-bs-toggle="tooltip">
                                                                <i class="ti ti-edit"></i>
                                                            </a>
                                                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kades'): ?>
                                                                <a href="surat_keluar_delete.php?id=<?= $surat['id']; ?>"
                                                                    class="btn btn-danger btn-sm btn-delete"
                                                                    title="Hapus"
                                                                    data-bs-toggle="tooltip">
                                                                    <i class="ti ti-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include_once '../includes/footer.php'; ?>

</body>
<!-- [Body] end -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Inisialisasi tooltip
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // SweetAlert untuk pesan session
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Sukses!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                confirmButtonColor: '#3085d6',
                timer: 3000,
                timerProgressBar: true
            });
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?= addslashes($_SESSION['error']) ?>',
                confirmButtonColor: '#d33'
            });
        <?php endif; ?>
    });

    // Function untuk switch tab
    function switchTab(tabName) {
        const tabButton = document.getElementById(tabName + '-tab');
        if (tabButton) {
            tabButton.click();
        }
    }

    // Function to print table berdasarkan tab aktif
    function printTable(tabType) {
        const tableId = tabType === 'masuk' ? 'dataTable-masuk' : 'dataTable-keluar';
        const title = tabType === 'masuk' ? 'Arsip Surat Masuk' : 'Arsip Surat Keluar';

        var printContents = document.getElementById(tableId).outerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML =
            '<html><head><title>' + title + '</title>' +
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">' +
            '<style>@media print { .no-print { display: none; } }</style></head>' +
            '<body>' +
            '<div class="container mt-4">' +
            '<h3 class="text-center mb-3">' + title + '</h3>' +
            '<small class="text-muted mb-3 d-block">' + new Date().toLocaleDateString('id-ID') + ' | Total: ' + (tabType === 'masuk' ? '<?= $total_masuk_filtered ?>' : '<?= $total_keluar_filtered ?>') + ' surat</small>' +
            printContents +
            '</div>' +
            '</body></html>';

        window.print();
        document.body.innerHTML = originalContents;
    }

    // Confirm delete dengan SweetAlert
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            const jenisSurat = this.closest('tr').querySelector('.badge-sifat') ? 'masuk' : 'keluar';
            const jenisText = jenisSurat === 'masuk' ? 'masuk' : 'keluar';

            Swal.fire({
                title: 'Hapus Surat ' + jenisText + '?',
                text: "Apakah Anda yakin ingin menghapus surat ini? Tindakan ini tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });

    // Auto submit form on filter change
    const filterForms = document.querySelectorAll('form[method="GET"]');
    filterForms.forEach(form => {
        const bulanSelect = form.querySelector('select[name*="bulan"]');
        const tahunSelect = form.querySelector('select[name*="tahun"]');

        if (bulanSelect) {
            bulanSelect.addEventListener('change', function() {
                form.submit();
            });
        }

        if (tahunSelect) {
            tahunSelect.addEventListener('change', function() {
                form.submit();
            });
        }
    });

    // Simpan tab aktif saat refresh
    document.addEventListener('DOMContentLoaded', function() {
        const activeTab = document.querySelector('.nav-link.active');
        if (activeTab) {
            localStorage.setItem('activeTab', activeTab.id);
        }

        const savedTab = localStorage.getItem('activeTab');
        if (savedTab) {
            const tabButton = document.querySelector(`#${savedTab}`);
            if (tabButton) {
                tabButton.click();
            }
        }
    });

    // Handle tab change
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            localStorage.setItem('activeTab', event.target.id);
        });
    });
</script>

</html>