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

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    if ($id > 0) {
        // Cek apakah data ada
        $check = query("SELECT id FROM tabel_kependudukan WHERE id = $id");
        if (!empty($check)) {
            $delete = $conn->query("DELETE FROM tabel_kependudukan WHERE id = $id");
            if ($delete) {
                $_SESSION['success'] = "Data berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus data: " . $conn->error;
            }
        } else {
            $_SESSION['error'] = "Data tidak ditemukan!";
        }
    }
    header("Location: list.php");
    exit();
}

// Ambil data dusun untuk dropdown filter
$sql_dusun = "SELECT * FROM tabel_dusun ORDER BY dusun";
$dusun_options = query($sql_dusun);

// Pagination setup
$limit = 10; // Jumlah data per halaman
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Filter functionality
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$dusun_filter = isset($_GET['dusun']) ? intval($_GET['dusun']) : 0;
$jk_filter = isset($_GET['jk']) ? $conn->real_escape_string($_GET['jk']) : '';

$where_conditions = [];
$params = [];

// Build WHERE conditions
if ($search) {
    $where_conditions[] = "(NIK LIKE '%$search%' OR NO_KK LIKE '%$search%' OR NAMA_LGKP LIKE '%$search%' OR NAMA_PANGGILAN LIKE '%$search%' OR ALAMAT LIKE '%$search%')";
}

if ($dusun_filter > 0) {
    $where_conditions[] = "k.DSN = $dusun_filter";
}

if ($jk_filter && in_array($jk_filter, ['L', 'P'])) {
    $where_conditions[] = "k.JK = '$jk_filter'";
}

// Combine WHERE conditions
$where = '';
if (!empty($where_conditions)) {
    $where = "WHERE " . implode(" AND ", $where_conditions);
}

// Get total records
$total_query = "SELECT COUNT(*) as total 
                FROM tabel_kependudukan k 
                LEFT JOIN tabel_dusun d ON k.DSN = d.id 
                $where";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Get data with pagination
$sql = "SELECT k.*, d.dusun as nama_dusun 
        FROM tabel_kependudukan k 
        LEFT JOIN tabel_dusun d ON k.DSN = d.id 
        $where 
        ORDER BY k.created_at DESC 
        LIMIT $limit OFFSET $offset";

$data = query($sql);

// Get total count for statistics
$stat_total = query("SELECT COUNT(*) as total FROM tabel_kependudukan")[0]['total'];
$stat_laki = query("SELECT COUNT(*) as total FROM tabel_kependudukan WHERE JK = 'L'")[0]['total'];
$stat_perempuan = query("SELECT COUNT(*) as total FROM tabel_kependudukan WHERE JK = 'P'")[0]['total'];

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

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Data Kependudukan</h2>
                    <div>
                        <a href="add.php" class="btn btn-success">
                            <i class="ti ti-user-plus"></i> Tambah Penduduk
                        </a>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="col-12">
                    <div class="filter-section">
                        <h6><i class="ti ti-filter"></i> Filter Data</h6>
                        <form method="get" id="filterForm">
                            <div class="row">
                                <!-- Search -->
                                <div class="col-md-4 mb-3">
                                    <label for="search" class="form-label">Pencarian</label>
                                    <input type="text" id="search" name="search" class="form-control"
                                        placeholder="Cari NIK, Nama, Alamat..."
                                        value="<?= htmlspecialchars($search) ?>">
                                </div>

                                <!-- Filter Dusun -->
                                <div class="col-md-3 mb-3">
                                    <label for="dusun" class="form-label">Dusun</label>
                                    <select id="dusun" name="dusun" class="form-select">
                                        <option value="0">Semua Dusun</option>
                                        <?php foreach ($dusun_options as $dusun): ?>
                                            <option value="<?= $dusun['id'] ?>"
                                                <?= $dusun_filter == $dusun['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dusun['dusun']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Filter Jenis Kelamin -->
                                <div class="col-md-3 mb-3">
                                    <label class="form-label d-block">Jenis Kelamin</label>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary filter-jk <?= $jk_filter == '' ? 'filter-active' : '' ?>"
                                            onclick="setJkFilter('')">
                                            <i class="ti ti-users"></i> Semua
                                        </button>
                                        <button type="button" class="btn btn-outline-primary filter-jk <?= $jk_filter == 'L' ? 'filter-active' : '' ?>"
                                            onclick="setJkFilter('L')">
                                            <i class="ti ti-gender-male"></i> Laki-laki
                                        </button>
                                        <button type="button" class="btn btn-outline-primary filter-jk <?= $jk_filter == 'P' ? 'filter-active' : '' ?>"
                                            onclick="setJkFilter('P')">
                                            <i class="ti ti-gender-female"></i> Perempuan
                                        </button>
                                    </div>
                                    <input type="hidden" id="jk" name="jk" value="<?= htmlspecialchars($jk_filter) ?>">
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    <div class="d-grid gap-2 w-100">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-filter"></i> Terapkan Filter
                                        </button>
                                        <?php if ($search || $dusun_filter || $jk_filter): ?>
                                            <a href="list.php" class="btn btn-secondary">
                                                <i class="ti ti-x"></i> Reset
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Active Filters -->
                        <?php if ($search || $dusun_filter || $jk_filter): ?>
                            <div class="mt-3">
                                <small class="text-muted">Filter Aktif:</small>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <?php if ($search): ?>
                                        <span class="badge bg-info d-flex align-items-center">
                                            Pencarian: "<?= htmlspecialchars($search) ?>"
                                            <button type="button" class="btn-close btn-close-white ms-2"
                                                style="font-size: 10px;" onclick="removeFilter('search')"></button>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($dusun_filter > 0):
                                        $selected_dusun = array_filter($dusun_options, function ($d) use ($dusun_filter) {
                                            return $d['id'] == $dusun_filter;
                                        });
                                        $selected_dusun = reset($selected_dusun);
                                    ?>
                                        <span class="badge bg-info d-flex align-items-center">
                                            Dusun: <?= htmlspecialchars($selected_dusun['dusun']) ?>
                                            <button type="button" class="btn-close btn-close-white ms-2"
                                                style="font-size: 10px;" onclick="removeFilter('dusun')"></button>
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($jk_filter): ?>
                                        <span class="badge bg-info d-flex align-items-center">
                                            Jenis Kelamin: <?= $jk_filter == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                            <button type="button" class="btn-close btn-close-white ms-2"
                                                style="font-size: 10px;" onclick="removeFilter('jk')"></button>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar bg-light-primary">
                                        <i class="ti ti-users fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Total Data</h6>
                                    <h5 class="mb-0"><?= number_format($total_records) ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar bg-light-info">
                                        <i class="ti ti-gender-male fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Laki-laki</h6>
                                    <h5 class="mb-0"><?= number_format($stat_laki) ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar bg-light-danger">
                                        <i class="ti ti-gender-female fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0">Perempuan</h6>
                                    <h5 class="mb-0"><?= number_format($stat_perempuan) ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Daftar Penduduk</h5>
                            <div class="float-end">
                                <div class="btn-group">
                                    <a href="export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success">
                                        <i class="ti ti-file-spreadsheet"></i> Excel
                                    </a>
                                    <a href="export_pdf.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-danger">
                                        <i class="ti ti-file-text"></i> PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Sukses!</strong> <?= $_SESSION['success']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> <?= $_SESSION['error']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>

                            <!-- Data Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>NIK</th>
                                            <th>Nama</th>
                                            <th>JK</th>
                                            <th>Tgl Lahir</th>
                                            <th>Alamat</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">
                                                    <div class="py-4">
                                                        <i class="ti ti-database-off fs-1 text-muted"></i>
                                                        <p class="mt-2">Tidak ada data ditemukan</p>
                                                        <?php if ($search || $dusun_filter || $jk_filter): ?>
                                                            <a href="list.php" class="btn btn-sm btn-primary">
                                                                <i class="ti ti-refresh"></i> Tampilkan Semua Data
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $no = $offset + 1; ?>
                                            <?php foreach ($data as $row): ?>
                                                <?php
                                                // Hitung usia
                                                $tgl_lahir = new DateTime($row['TGL_LHR']);
                                                $today = new DateTime();
                                                $usia = $today->diff($tgl_lahir)->y;
                                                ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td>
                                                        <strong><?= $row['NIK'] ?></strong><br>
                                                        <small class="text-muted">KK: <?= $row['NO_KK'] ?></small>
                                                    </td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($row['NAMA_LGKP']) ?></strong>
                                                        <?php if ($row['NAMA_PANGGILAN']): ?>
                                                            <br><small class="text-muted">(<?= htmlspecialchars($row['NAMA_PANGGILAN']) ?>)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning text-dark">
                                                            <?= $row['JK'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                                        </span>
                                                        <br>
                                                        <small><?= $usia ?> tahun</small>
                                                    </td>
                                                    <td>
                                                        <?= dateIndo($row['TGL_LHR']) ?><br>
                                                        <small><?= htmlspecialchars($row['TMPT_LHR']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($row['ALAMAT']) ?><br>
                                                        <small class="text-muted">
                                                            Dusun <?= htmlspecialchars($row['nama_dusun'] ?? '-') ?>
                                                            <?php if ($row['rt']): ?>RT <?= $row['rt'] ?><?php endif; ?>
                                                            <?php if ($row['rw']): ?>RW <?= $row['rw'] ?><?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <!-- Status Tinggal -->
                                                        <span class="badge bg-secondary mb-1 d-inline-block">
                                                            <?= htmlspecialchars($row['STATUS_TINGGAL']) ?>
                                                        </span>
                                                        <br>

                                                        <!-- Status Kawin -->
                                                        <span class="badge bg-primary mb-1 d-inline-block">
                                                            <?= htmlspecialchars($row['STATUS_KAWIN']) ?>
                                                        </span>
                                                        <br>

                                                        <!-- Agama -->
                                                        <span class="badge bg-info mb-1 d-inline-block">
                                                            <?= htmlspecialchars($row['AGAMA']) ?>
                                                        </span>
                                                        <br>

                                                        <!-- Disabilitas -->
                                                        <span class="badge <?= $row['DISABILITAS'] === 'Ya' ? 'bg-warning text-dark' : 'bg-success' ?>">
                                                            Disabilitas: <?= htmlspecialchars($row['DISABILITAS']) ?>
                                                        </span>
                                                    </td>

                                                    <td class="action-buttons">
                                                        <a href="detail.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-sm btn-info"
                                                            title="Detail">
                                                            <i class="ti ti-eye"></i>
                                                        </a>
                                                        <a href="edit.php?id=<?= $row['id'] ?>"
                                                            class="btn btn-sm btn-warning"
                                                            title="Edit">
                                                            <i class="ti ti-edit"></i>
                                                        </a>
                                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                                            <button type="button"
                                                                class="btn btn-sm btn-danger btn-delete"
                                                                title="Hapus"
                                                                data-id="<?= $row['id']; ?>"
                                                                data-nama="<?= htmlspecialchars($row['NAMA_LGKP']); ?>"
                                                                data-nik="<?= htmlspecialchars($row['NIK']); ?>">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?<?=
                                                                        http_build_query(array_merge($_GET, ['page' => $page - 1]))
                                                                        ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>

                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?<?=
                                                                                http_build_query(array_merge($_GET, ['page' => $i]))
                                                                                ?>">
                                                        <?= $i ?>
                                                    </a>
                                                </li>
                                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                                <li class="page-item disabled">
                                                    <span class="page-link">...</span>
                                                </li>
                                            <?php endif; ?>
                                        <?php endfor; ?>

                                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?<?=
                                                                        http_build_query(array_merge($_GET, ['page' => $page + 1]))
                                                                        ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                            <div class="text-muted text-center mt-3">
                                Menampilkan <?= min($limit, count($data)) ?> dari <?= number_format($total_records) ?> data
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
    document.addEventListener('DOMContentLoaded', function() {
        // Delete confirmation
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Anda akan menghapus data ${name}`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `list.php?delete=${id}&<?=
                                                                        http_build_query(array_diff_key($_GET, ['delete' => '']))
                                                                        ?>`;
                    }
                });
            });
        });

        // Quick search with debounce
        let searchTimeout;
        const searchInput = document.getElementById('search');

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length === 0 || this.value.length >= 2) {
                        document.getElementById('filterForm').submit();
                    }
                }, 500);
            });
        }

        // Export to Excel
        document.getElementById('exportBtn')?.addEventListener('click', function() {
            Swal.fire({
                title: 'Ekspor Data',
                text: 'Pilih format ekspor:',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Excel',
                cancelButtonText: 'PDF',
                showDenyButton: true,
                denyButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const params = new URLSearchParams(window.location.search);
                    window.location.href = `export.php?type=excel&${params.toString()}`;
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    const params = new URLSearchParams(window.location.search);
                    window.location.href = `export.php?type=pdf&${params.toString()}`;
                }
            });
        });

        // Auto submit on dusun select change
        document.getElementById('dusun').addEventListener('change', function() {
            if (this.value == 0 || this.value != '<?= $dusun_filter ?>') {
                document.getElementById('filterForm').submit();
            }
        });
    });

    // Set jenis kelamin filter
    function setJkFilter(value) {
        document.getElementById('jk').value = value;
        document.querySelectorAll('.filter-jk').forEach(btn => {
            btn.classList.remove('filter-active');
        });
        event.target.classList.add('filter-active');
        document.getElementById('filterForm').submit();
    }

    // Remove specific filter
    function removeFilter(filterName) {
        const url = new URL(window.location.href);
        url.searchParams.delete(filterName);
        url.searchParams.delete('page'); // Reset to page 1
        window.location.href = url.toString();
    }

    // Print data
    function printData() {
        const params = new URLSearchParams(window.location.search);
        window.open(`print.php?${params.toString()}`, '_blank');
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete confirmation - VERSION TERBARU
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                const nik = this.getAttribute('data-nik');

                Swal.fire({
                    title: 'Hapus Data Penduduk?',
                    html: `<div class="text-start">
                         <p>Anda akan menghapus data:</p>
                         <ul class="ps-3">
                           <li><strong>NIK:</strong> ${nik}</li>
                           <li><strong>Nama:</strong> ${nama}</li>
                         </ul>
                         <p class="text-danger"><i class="ti ti-alert-triangle"></i> Data yang dihapus tidak dapat dikembalikan!</p>
                       </div>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="ti ti-trash"></i> Ya, Hapus!',
                    cancelButtonText: '<i class="ti ti-x"></i> Batal',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Buat URL dengan parameter delete
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('delete', id);
                        currentUrl.searchParams.delete('page'); // Kembali ke halaman 1 setelah delete

                        // Redirect ke URL dengan parameter delete
                        window.location.href = currentUrl.toString();
                    }
                });
            });
        });

        // Quick search with debounce
        let searchTimeout;
        const searchInput = document.getElementById('search');

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length === 0 || this.value.length >= 2) {
                        document.getElementById('filterForm').submit();
                    }
                }, 500);
            });
        }

        // Auto submit on dusun select change
        const dusunSelect = document.getElementById('dusun');
        if (dusunSelect) {
            dusunSelect.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        }
    });

    // Set jenis kelamin filter
    function setJkFilter(value) {
        document.getElementById('jk').value = value;
        document.querySelectorAll('.filter-jk').forEach(btn => {
            btn.classList.remove('filter-active');
        });
        event.target.classList.add('filter-active');
        document.getElementById('filterForm').submit();
    }

    // Remove specific filter
    function removeFilter(filterName) {
        const url = new URL(window.location.href);
        url.searchParams.delete(filterName);
        url.searchParams.delete('page'); // Reset to page 1
        window.location.href = url.toString();
    }
</script>

</html>