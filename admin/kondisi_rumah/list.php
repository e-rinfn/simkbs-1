<?php
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

// Jika diperlukan role tertentu (admin/kades), sesuaikan dengan kebutuhan
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades') {
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Parameter filter
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$jenis_lantai_filter = isset($_GET['jenis_lantai']) ? $_GET['jenis_lantai'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Ambil data dusun untuk filter
$sql_dusun = "SELECT * FROM tabel_dusun ORDER BY dusun";
$data_dusun = query($sql_dusun);

// Data untuk dropdown filter
$jenis_lantai_options = ['KERAMIK', 'SEMEN', 'TANAH', 'KAYU', 'LAINNYA'];

// Query data kondisi rumah dengan filter (tanpa parameter binding)
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

// Query utama dengan JOIN ke tabel kondisi rumah
$sql = "SELECT 
            k.id,
            k.NO_KK,
            k.NIK,
            k.NAMA_LGKP,
            k.NAMA_PANGGILAN,
            k.JK,
            k.DSN,
            k.rt,
            k.rw,
            d.dusun,
            r.id as rumah_id,
            r.status_tempat_tinggal,
            r.luas_lantai,
            r.jenis_lantai,
            r.jenis_dinding,
            r.fasilitas_bab,
            r.sumber_penerangan,
            r.sumber_air_minum,
            r.bahan_bakar_memasak,
            r.kondisi_rumah,
            r.created_at as rumah_created_at,
            r.updated_at as rumah_updated_at
        FROM tabel_kependudukan k
        LEFT JOIN tabel_dusun d ON k.DSN = d.id
        LEFT JOIN tabel_rumah r ON k.NIK = r.NIK
        $where_sql
        ORDER BY k.NAMA_LGKP";

// Debug: uncomment untuk melihat query
// echo "<pre>SQL: " . $sql . "</pre>";

// Eksekusi query
try {
    $data_rumah = query($sql);
} catch (Exception $e) {
    // Tangani error dengan lebih baik
    error_log("Error query: " . $e->getMessage());
    $data_rumah = [];
    $_SESSION['error'] = "Terjadi kesalahan dalam mengambil data. Silakan coba lagi.";
    echo "Error Debug: " . $e->getMessage(); // Hapus ini di production
}

// Hitung total data
$total_data = count($data_rumah);

// Hitung statistik
$total_berfasilitas = 0;
$total_tanpa_fasilitas = 0;
$total_listrik_pln = 0;
$total_air_pdam = 0;

foreach ($data_rumah as $rumah) {
    if ($rumah['fasilitas_bab'] == 'JAMBAN SENDIRI') {
        $total_berfasilitas++;
    } elseif ($rumah['fasilitas_bab'] == 'TIDAK ADA') {
        $total_tanpa_fasilitas++;
    }

    if ($rumah['sumber_penerangan'] == 'PLN') {
        $total_listrik_pln++;
    }

    if ($rumah['sumber_air_minum'] == 'PDAM') {
        $total_air_pdam++;
    }
}
?>

<style>
    /* Paksa SweetAlert berada di atas segalanya */
    .swal2-container {
        z-index: 99999 !important;
    }

    .badge-jk-l {
        background-color: #0d6efd;
        color: white;
    }

    .badge-jk-p {
        background-color: #dc3545;
        color: white;
    }

    .badge-kondisi {
        font-size: 0.8em;
        padding: 3px 8px;
        border-radius: 12px;
    }

    .badge-layak {
        background-color: #20c997;
        color: white;
    }

    .badge-rusak-ringan {
        background-color: #fd7e14;
        color: white;
    }

    .badge-rusak-berat {
        background-color: #dc3545;
        color: white;
    }

    .badge-fasilitas {
        background-color: #6f42c1;
        color: white;
    }

    .badge-tanpa-fasilitas {
        background-color: #6c757d;
        color: white;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }

    .filter-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
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

    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border-left: 4px solid #0d6efd;
    }

    .stat-card h6 {
        color: #495057;
        margin-bottom: 10px;
        font-size: 0.9rem;
    }

    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #0d6efd;
    }

    .stat-card .stat-percent {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .fasilitas-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .fasilitas-icon.bathroom {
        background-color: #17a2b8;
        color: white;
    }

    .fasilitas-icon.electricity {
        background-color: #ffc107;
        color: #212529;
    }

    .fasilitas-icon.water {
        background-color: #0d6efd;
        color: white;
    }

    .status-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .status-layak {
        background-color: #20c997;
    }

    .status-rusak-ringan {
        background-color: #fd7e14;
    }

    .status-rusak-berat {
        background-color: #dc3545;
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
                    <h2>Data Kondisi Rumah</h2>
                </div>

                <!-- Statistik Section -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="fasilitas-icon bathroom">
                                    <i class="ti ti-droplet"></i>
                                </div>
                                <div>
                                    <h6>MCK Sendiri</h6>
                                    <div class="stat-value"><?= number_format($total_berfasilitas) ?></div>
                                    <div class="stat-percent">
                                        <?= $total_data > 0 ? number_format(($total_berfasilitas / $total_data) * 100, 1) : 0 ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="fasilitas-icon electricity">
                                    <i class="ti ti-bolt"></i>
                                </div>
                                <div>
                                    <h6>Listrik PLN</h6>
                                    <div class="stat-value"><?= number_format($total_listrik_pln) ?></div>
                                    <div class="stat-percent">
                                        <?= $total_data > 0 ? number_format(($total_listrik_pln / $total_data) * 100, 1) : 0 ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="fasilitas-icon water">
                                    <i class="ti ti-droplet"></i>
                                </div>
                                <div>
                                    <h6>Air PDAM</h6>
                                    <div class="stat-value"><?= number_format($total_air_pdam) ?></div>
                                    <div class="stat-percent">
                                        <?= $total_data > 0 ? number_format(($total_air_pdam / $total_data) * 100, 1) : 0 ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="d-flex align-items-center">
                                <div class="fasilitas-icon" style="background-color: #6c757d; color: white;">
                                    <i class="ti ti-home"></i>
                                </div>
                                <div>
                                    <h6>Total Data</h6>
                                    <div class="stat-value"><?= number_format($total_data) ?></div>
                                    <div class="stat-percent">Rumah</div>
                                </div>
                            </div>
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
                            <label for="jenis_lantai" class="form-label">Jenis Lantai</label>
                            <select name="jenis_lantai" id="jenis_lantai" class="form-select">
                                <option value="">Semua Jenis</option>
                                <?php foreach ($jenis_lantai_options as $jenis): ?>
                                    <option value="<?= $jenis ?>" <?= $jenis_lantai_filter == $jenis ? 'selected' : '' ?>>
                                        <?= $jenis ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="search" class="form-label">Cari (KK/NIK/Nama)</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Masukkan No. KK, NIK, atau Nama"
                                value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-filter"></i> Filter
                                </button>
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="ti ti-refresh"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Statistik Filter -->
                    <?php if (!empty($dusun_filter) || !empty($jenis_lantai_filter) || !empty($search)): ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="ti ti-info-circle"></i>
                                Menampilkan <?= $total_data ?> data dengan filter:
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
                                if (!empty($jenis_lantai_filter)) {
                                    $filter_info[] = "Lantai: $jenis_lantai_filter";
                                }
                                if (!empty($search)) {
                                    $filter_info[] = "Kata kunci: \"$search\"";
                                }
                                echo implode(', ', $filter_info);
                                ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card p-3">
                    <!-- Tampilkan pesan error atau success -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <!-- /Tampilkan pesan error atau success -->

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="text-muted">Total Data: <strong><?= number_format($total_data, 0, ',', '.') ?></strong> rumah</span>
                        </div>
                        <div class="btn-group">
                            <?php if ($total_data > 0): ?>
                                <a href="export_excel_simple.php?<?= http_build_query($_GET) ?>" class="btn btn-success">
                                    <i class="ti ti-file-spreadsheet"></i> Excel
                                </a>
                                <a href="export_pdf_rumah.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-danger">
                                    <i class="ti ti-file-text"></i> PDF
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTable">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 3%;">No</th>
                                    <th style="width: 12%;">No. KK</th>
                                    <th style="width: 12%;">NIK</th>
                                    <th style="width: 15%;">Nama</th>
                                    <th style="width: 8%;">Luas Lantai</th>
                                    <th style="width: 8%;">Jenis Lantai</th>
                                    <th style="width: 8%;">Jenis Dinding</th>
                                    <th style="width: 10%;">Fasilitas MCK</th>
                                    <th style="width: 8%;">Penerangan</th>
                                    <th style="width: 8%;">Sumber Air</th>
                                    <th style="width: 8%;">Bahan Bakar</th>
                                    <th style="width: 10%;">Kondisi</th>
                                    <th style="width: 5%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($data_rumah)): ?>
                                    <tr>
                                        <td colspan="13" class="text-center py-4">
                                            <div class="text-muted mb-2">
                                                <i class="ti ti-home-off fs-1"></i>
                                            </div>
                                            Tidak ada data kondisi rumah
                                            <?php if (!empty($dusun_filter) || !empty($jenis_lantai_filter) || !empty($search)): ?>
                                                <br>
                                                <small class="text-muted">Coba reset filter atau ubah kriteria pencarian</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($data_rumah as $rumah): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td class="text-center"><?= formatKKNIK($rumah['NO_KK']); ?></td>
                                            <td class="text-center"><?= formatKKNIK($rumah['NIK']); ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <strong><?= htmlspecialchars($rumah['NAMA_LGKP']); ?></strong>
                                                    <?php if ($rumah['NAMA_PANGGILAN']): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($rumah['NAMA_PANGGILAN']); ?></small>
                                                    <?php endif; ?>
                                                    <small class="text-muted">
                                                        <?= $rumah['dusun'] ? 'Dusun ' . htmlspecialchars($rumah['dusun']) : '' ?>
                                                        <?= $rumah['rt'] ? ', RT ' . $rumah['rt'] : '' ?>
                                                        <?= $rumah['rw'] ? '/RW ' . $rumah['rw'] : '' ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?= $rumah['luas_lantai'] ? number_format($rumah['luas_lantai'], 1) . ' m²' : '-' ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rumah['jenis_lantai']): ?>
                                                    <?php
                                                    $badge_color = '';
                                                    switch ($rumah['jenis_lantai']) {
                                                        case 'KERAMIK':
                                                            $badge_color = 'bg-primary';
                                                            break;
                                                        case 'SEMEN':
                                                            $badge_color = 'bg-secondary';
                                                            break;
                                                        case 'KAYU':
                                                            $badge_color = 'bg-warning text-dark';
                                                            break;
                                                        case 'TANAH':
                                                            $badge_color = 'bg-danger';
                                                            break;
                                                        default:
                                                            $badge_color = 'bg-info';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badge_color ?>"><?= $rumah['jenis_lantai'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rumah['jenis_dinding']): ?>
                                                    <?php
                                                    $badge_color = '';
                                                    switch ($rumah['jenis_dinding']) {
                                                        case 'TEMBOK':
                                                            $badge_color = 'bg-success';
                                                            break;
                                                        case 'KAYU':
                                                            $badge_color = 'bg-warning text-dark';
                                                            break;
                                                        case 'BAMBU':
                                                            $badge_color = 'bg-info';
                                                            break;
                                                        default:
                                                            $badge_color = 'bg-secondary';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badge_color ?>"><?= $rumah['jenis_dinding'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rumah['fasilitas_bab']): ?>
                                                    <?php if ($rumah['fasilitas_bab'] == 'JAMBAN SENDIRI'): ?>
                                                        <span class="badge badge-fasilitas">
                                                            <i class="ti ti-check"></i> Sendiri
                                                        </span>
                                                    <?php elseif ($rumah['fasilitas_bab'] == 'JAMBAN BERSAMA'): ?>
                                                        <span class="badge bg-info">
                                                            <i class="ti ti-users"></i> Bersama
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-tanpa-fasilitas">
                                                            <i class="ti ti-x"></i> Tidak Ada
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rumah['sumber_penerangan']): ?>
                                                    <?php if ($rumah['sumber_penerangan'] == 'PLN'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="ti ti-bolt"></i> PLN
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark"><?= $rumah['sumber_penerangan'] ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rumah['sumber_air_minum']): ?>
                                                    <?php if ($rumah['sumber_air_minum'] == 'PDAM'): ?>
                                                        <span class="badge bg-primary">
                                                            <i class="ti ti-droplet"></i> PDAM
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info"><?= $rumah['sumber_air_minum'] ?></span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rumah['bahan_bakar_memasak']): ?>
                                                    <?php
                                                    $badge_color = '';
                                                    switch ($rumah['bahan_bakar_memasak']) {
                                                        case 'GAS':
                                                            $badge_color = 'bg-success';
                                                            break;
                                                        case 'LISTRIK':
                                                            $badge_color = 'bg-warning text-dark';
                                                            break;
                                                        case 'KAYU BAKAR':
                                                            $badge_color = 'bg-danger';
                                                            break;
                                                        case 'MINYAK TANAH':
                                                            $badge_color = 'bg-secondary';
                                                            break;
                                                        default:
                                                            $badge_color = 'bg-info';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badge_color ?>"><?= $rumah['bahan_bakar_memasak'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($rumah['kondisi_rumah']): ?>
                                                    <?php
                                                    $badge_class = '';
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch ($rumah['kondisi_rumah']) {
                                                        case 'LAYAK HUNI':
                                                            $badge_class = 'badge-layak';
                                                            $status_class = 'status-layak';
                                                            $status_text = 'Layak';
                                                            break;
                                                        case 'RUSAK RINGAN':
                                                            $badge_class = 'badge-rusak-ringan';
                                                            $status_class = 'status-rusak-ringan';
                                                            $status_text = 'Rusak Ringan';
                                                            break;
                                                        case 'RUSAK BERAT':
                                                            $badge_class = 'badge-rusak-berat';
                                                            $status_class = 'status-rusak-berat';
                                                            $status_text = 'Rusak Berat';
                                                            break;
                                                        default:
                                                            $badge_class = 'bg-secondary';
                                                            $status_text = $rumah['kondisi_rumah'];
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badge_class ?> badge-kondisi">
                                                        <span class="status-indicator <?= $status_class ?>"></span>
                                                        <?= $status_text ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view.php?nik=<?= $rumah['NIK']; ?>"
                                                        class="btn btn-info"
                                                        title="Detail"
                                                        data-bs-toggle="tooltip">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                    <a href="edit.php?nik=<?= $rumah['NIK']; ?>"
                                                        class="btn btn-primary"
                                                        title="Edit"
                                                        data-bs-toggle="tooltip">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                                        <?php if ($rumah['rumah_id']): ?>
                                                            <a href="delete.php?nik=<?= $rumah['NIK']; ?>"
                                                                class="btn btn-danger btn-delete"
                                                                title="Hapus"
                                                                data-bs-toggle="tooltip"
                                                                data-id="<?= $rumah['rumah_id']; ?>"
                                                                data-nama="<?= htmlspecialchars($rumah['NAMA_LGKP']); ?>">
                                                                <i class="ti ti-trash"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="add.php?nik=<?= $rumah['NIK']; ?>"
                                                                class="btn btn-success"
                                                                title="Tambah Data Rumah"
                                                                data-bs-toggle="tooltip">
                                                                <i class="ti ti-plus"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination atau info jika data banyak -->
                    <?php if ($total_data > 50): ?>
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="ti ti-info-circle"></i>
                                Menampilkan <?= min($total_data, 50) ?> data pertama. Gunakan filter untuk pencarian spesifik.
                            </small>
                        </div>
                    <?php endif; ?>
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
    // Fungsi untuk konfirmasi hapus data rumah
    function confirmDeleteRumah(nik, nama) {
        // Pastikan NIK valid sebelum melanjutkan
        if (!nik || nik.length === 0) {
            alert('NIK tidak valid!');
            return false;
        }

        // Encode parameter untuk URL
        const encodedNik = encodeURIComponent(nik);
        const encodedNama = encodeURIComponent(nama);

        // Tampilkan konfirmasi sebelum cek relasi
        if (confirm(`Apakah Anda yakin ingin menghapus data kondisi rumah milik ${nama}?`)) {
            // Tampilkan loading
            showLoading();

            // Cek apakah bisa dihapus
            fetch(`check_delete_rumah.php?nik=${encodedNik}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    hideLoading();

                    if (data.can_delete) {
                        // Jika bisa dihapus, tampilkan konfirmasi final
                        if (confirm(`${data.message}\n\nTekan OK untuk melanjutkan penghapusan.`)) {
                            window.location.href = `delete_rumah.php?nik=${encodedNik}`;
                        }
                    } else {
                        // Jika tidak bisa dihapus, tampilkan pesan error
                        alert(data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memeriksa data. Silakan coba lagi.');
                });
        }
        return false;
    }

    // Fungsi untuk menampilkan loading
    function showLoading() {
        // Buat overlay loading jika belum ada
        if (!document.getElementById('loading-overlay')) {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;

            const spinner = document.createElement('div');
            spinner.style.cssText = `
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        `;

            overlay.appendChild(spinner);
            document.body.appendChild(overlay);

            // Tambahkan CSS untuk animasi
            const style = document.createElement('style');
            style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
            document.head.appendChild(style);
        }
    }

    // Fungsi untuk menyembunyikan loading
    function hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    // Function to print table
    function printTable() {
        var printContents = document.getElementById('dataTable').outerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML =
            '<html><head><title>Data Kondisi Rumah</title>' +
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">' +
            '<style>@media print { .no-print { display: none; } }</style></head>' +
            '<body>' +
            '<div class="container mt-4">' +
            '<h3 class="text-center mb-3">Data Kondisi Rumah</h3>' +
            '<small class="text-muted mb-3 d-block">' + new Date().toLocaleDateString('id-ID') + '</small>' +
            printContents +
            '</div>' +
            '</body></html>';

        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }

    // Auto submit form on filter change (optional)
    // document.getElementById('dusun').addEventListener('change', function() {
    //     if (this.value) {
    //         this.form.submit();
    //     }
    // });
</script>

</html>