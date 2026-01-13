<?php
// list.php
include_once __DIR__ . '/../../config/config.php';
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'kepala_desa') {
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

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penduduk</title>
    <?php include_once '../includes/css.php'; ?>
    <style>
        .table th,
        .table td {
            vertical-align: middle;
        }

        .badge-jk-L {
            background-color: #0d6efd;
        }

        .badge-jk-P {
            background-color: #dc3545;
        }

        .badge-disabilitas-YA {
            background-color: #ffc107;
            color: #000;
        }

        .badge-disabilitas-TIDAK {
            background-color: #198754;
        }

        .action-buttons {
            white-space: nowrap;
        }

        .pagination .page-link {
            color: #0d6efd;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .filter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .filter-section h6 {
            color: #0d6efd;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }

        .filter-active {
            background-color: #0d6efd !important;
            color: white !important;
            border-color: #0d6efd !important;
        }

        .filter-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <?php include_once '../includes/sidebar.php'; ?>
    <?php include_once '../includes/navbar.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <div class="row">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Data Kependudukan</h2>
                    <!-- <div>
                        <a href="add.php" class="btn btn-success">
                            <i class="ti ti-user-plus"></i> Tambah Penduduk
                        </a>
                    </div> -->
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
                                                        <span class="badge badge-jk-<?= $row['JK'] ?>">
                                                            <?= $row['JK'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                                        </span>
                                                        <br><small><?= $usia ?> tahun</small>
                                                    </td>
                                                    <td>
                                                        <?= date('d/m/Y', strtotime($row['TGL_LHR'])) ?><br>
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
    <?php include_once '../includes/js.php'; ?>

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
</body>

</html>