<?php
include_once __DIR__ . '/../../config/config.php';
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

// Cek role user
$allowed_roles = ['admin', 'kades', 'sekretaris'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: {$base_url}auth/role_tidak_cocok.php");
    exit();
}

// Parameter filter
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Inisialisasi variabel
$errors = [];
$success = false;

// Jika form tambah dusun disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dusun'])) {
    $dusun = trim($_POST['dusun'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validasi
    if (empty($dusun)) {
        $errors['dusun'] = 'Nama dusun wajib diisi';
    } elseif (strlen($dusun) > 100) {
        $errors['dusun'] = 'Nama dusun maksimal 100 karakter';
    }

    if (empty($errors)) {
        try {
            // Cek apakah dusun sudah ada
            $check_sql = "SELECT id FROM tabel_dusun WHERE dusun = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $dusun);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $errors['dusun'] = 'Dusun sudah terdaftar!';
            } else {
                // Insert data
                $sql = "INSERT INTO tabel_dusun (dusun, keterangan) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $dusun, $keterangan);

                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Dusun berhasil ditambahkan!';
                    header("Location: list.php");
                    exit();
                } else {
                    $errors['database'] = 'Gagal menambahkan dusun: ' . $stmt->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        } catch (Exception $e) {
            $errors['database'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Jika form edit dusun disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dusun'])) {
    $id = intval($_POST['id'] ?? 0);
    $dusun = trim($_POST['dusun'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    // Validasi
    if (empty($dusun)) {
        $errors['edit_dusun'] = 'Nama dusun wajib diisi';
    } elseif (strlen($dusun) > 100) {
        $errors['edit_dusun'] = 'Nama dusun maksimal 100 karakter';
    }

    if (empty($errors)) {
        try {
            // Cek apakah dusun sudah ada (kecuali untuk dusun ini)
            $check_sql = "SELECT id FROM tabel_dusun WHERE dusun = ? AND id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("si", $dusun, $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $errors['edit_dusun'] = 'Dusun sudah terdaftar!';
            } else {
                // Update data
                $sql = "UPDATE tabel_dusun SET dusun = ?, keterangan = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $dusun, $keterangan, $id);

                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Dusun berhasil diperbarui!';
                    header("Location: list.php");
                    exit();
                } else {
                    $errors['database'] = 'Gagal memperbarui dusun: ' . $stmt->error;
                }
                $stmt->close();
            }
            $check_stmt->close();
        } catch (Exception $e) {
            $errors['database'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Jika hapus dusun
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Cek apakah ada data terkait sebelum menghapus
    // Anda bisa menambahkan pengecekan di sini jika dusun terkait dengan tabel lain

    try {
        $sql = "DELETE FROM tabel_dusun WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Dusun berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus dusun!';
        }
        $stmt->close();

        header("Location: list.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
        header("Location: list.php");
        exit();
    }
}

// Query data dusun dengan filter
$where_conditions = [];
$params = [];
$params_types = '';

if (!empty($search)) {
    $where_conditions[] = "(dusun LIKE ? OR keterangan LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params_types .= 'ss';
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query utama
$sql = "SELECT * FROM tabel_dusun 
        $where_sql 
        ORDER BY dusun ASC";

// Eksekusi query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($params_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data_dusun = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $data_dusun = [];
    }
} else {
    $result = mysqli_query($conn, $sql);
    $data_dusun = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Hitung total data
$total_data = count($data_dusun);

// Ambil data untuk modal edit
$edit_data = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_sql = "SELECT * FROM tabel_dusun WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_data = $edit_result->fetch_assoc();
    $edit_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Dusun - Sistem Administrasi Desa</title>
    <style>
        .required:after {
            content: " *";
            color: red;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .card-header {
            background-color: #0d6efd;
            color: white;
        }

        .btn-back {
            margin-right: 10px;
        }

        .badge-count {
            background-color: #0d6efd;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9rem;
        }

        .table-responsive {
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
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

        .search-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .keterangan-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .keterangan-text:hover {
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

        .modal-header {
            background-color: #0d6efd;
            color: white;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #0d6efd;
        }

        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #0d6efd;
            margin: 5px 0;
        }

        .stat-card .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>

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

                <div class="col-md-4 mb-4">



                    <!-- PROFIL USER & DESA -->
                    <div class="d-flex align-items-center justify-content-between">

                        <div class="d-flex align-items-center gap-3">
                            <!-- Foto User -->
                            <img
                                src="<?= $base_url ?>/assets/img/user.png"
                                alt="User"
                                class="rounded-circle border"
                                width="48"
                                height="48">

                            <!-- Info User -->
                            <div>
                                <h6 class="mb-0">
                                    <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']) ?>
                                </h6>
                                <small class="text-muted">
                                    <?= htmlspecialchars($_SESSION['role'] ?? 'Pengguna') ?>
                                </small>
                            </div>
                        </div>

                        <!-- Tombol Edit -->
                        <a href="../profile/index.php"
                            class="btn btn-sm btn-outline-primary"
                            title="Edit Profil">
                            <i class="ti ti-edit"></i>
                        </a>

                    </div>

                    <!-- Info Desa -->
                    <div class="mt-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary me-1">
                            <i class="ti ti-building-community me-1"></i>
                            Kantor Kepala Desa
                        </span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                            Desa Kurniabakti
                        </span>
                    </div>


                    <!-- PETA -->
                    <h6 class="mt-3 fw-semibold">
                        <i class="ti ti-map-pin me-1 text-primary"></i>
                        Lokasi Kantor Desa
                    </h6>
                    <small class="text-muted">Desa Kurniabakti</small>
                    <div class="ratio ratio-4x3">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d7969.021430837158!2d108.14291600000001!3d-7.156297!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f4eaa8785249d%3A0x17bdb61a5a113357!2sKantor%20Kepala%20Desa%20Kurniabakti!5e1!3m2!1sid!2sid!4v1766535822837!5m2!1sid!2sid"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                        </iframe>
                    </div>


                </div>




                <div class="col-8">
                    <div class="card">
                        <div class="card-body">

                            <!-- Statistik -->
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-label">Total Dusun</div>
                                        <div class="stat-value"><?= number_format($total_data) ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Search dan Tambah Data -->
                            <div class="search-container">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <form method="GET" action="" class="row g-2">
                                            <div class="col-md-8">
                                                <input type="text"
                                                    name="search"
                                                    class="form-control"
                                                    placeholder="Cari nama dusun atau keterangan..."
                                                    value="<?= htmlspecialchars($search) ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <div class="btn-group">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="ti ti-search"></i> Cari
                                                    </button>
                                                    <a href="list.php" class="btn btn-secondary">
                                                        <i class="ti ti-refresh"></i> Reset
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                                            <i class="ti ti-plus"></i> Tambah Dusun
                                        </button>
                                    </div>
                                </div>

                                <!-- Info Filter -->
                                <?php if (!empty($search)): ?>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="ti ti-info-circle"></i>
                                            Menampilkan <?= $total_data ?> dusun dengan kata kunci: "<?= htmlspecialchars($search) ?>"
                                            <span class="badge-count ms-2">Total: <?= number_format($total_data) ?> dusun</span>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tampilkan pesan error atau success -->
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    <strong>Error!</strong> <?= $_SESSION['error'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>

                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="ti ti-circle-check me-2"></i>
                                    <strong>Sukses!</strong> <?= $_SESSION['success'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>

                            <!-- Table Data -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;" class="text-center">No</th>
                                            <th style="width: 25%;">Nama Dusun</th>
                                            <th style="width: 50%;">Keterangan</th>
                                            <th style="width: 15%;">Tanggal Dibuat</th>
                                            <th style="width: 10%;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($data_dusun)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="no-data">
                                                        <i class="ti ti-map-pin-off"></i>
                                                        <h5 class="mt-2 mb-3">Tidak ada data dusun</h5>
                                                        <?php if (!empty($search)): ?>
                                                            <p class="text-muted mb-0">Coba reset filter atau ubah kata kunci pencarian</p>
                                                            <a href="list.php" class="btn btn-outline-primary mt-2">
                                                                <i class="ti ti-refresh"></i> Reset Filter
                                                            </a>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addModal">
                                                                <i class="ti ti-plus"></i> Tambah Dusun Pertama
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $no = 1; ?>
                                            <?php foreach ($data_dusun as $dusun): ?>
                                                <?php
                                                // Format tanggal
                                                $created_at = !empty($dusun['created_at']) ? date('d/m/Y H:i', strtotime($dusun['created_at'])) : '-';
                                                ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++; ?></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($dusun['dusun']) ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="keterangan-text" title="<?= htmlspecialchars($dusun['keterangan']) ?>">
                                                            <?= !empty($dusun['keterangan']) ? htmlspecialchars($dusun['keterangan']) : '<span class="text-muted">-</span>' ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?= $created_at ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="action-buttons">
                                                            <a href="?edit=<?= $dusun['id'] ?>"
                                                                class="btn btn-primary btn-sm"
                                                                title="Edit"
                                                                data-bs-toggle="tooltip"
                                                                onclick="return false;">
                                                                <i class="ti ti-edit"></i>
                                                            </a>
                                                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'kades'): ?>
                                                                <a href="?delete=<?= $dusun['id'] ?>"
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
            <!-- [ Main Content ] end -->
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <!-- Modal Tambah Dusun -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">
                        <i class="ti ti-plus me-2"></i>Tambah Dusun Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if (!empty($errors['database'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="ti ti-alert-circle me-2"></i>
                                <?= htmlspecialchars($errors['database']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="dusun" class="form-label required">Nama Dusun</label>
                            <input type="text"
                                class="form-control <?= isset($errors['dusun']) ? 'is-invalid' : '' ?>"
                                id="dusun"
                                name="dusun"
                                value="<?= isset($_POST['dusun']) ? htmlspecialchars($_POST['dusun']) : '' ?>"
                                placeholder="Masukkan nama dusun"
                                required>
                            <?php if (isset($errors['dusun'])): ?>
                                <div class="error-message"><?= $errors['dusun'] ?></div>
                            <?php endif; ?>
                            <div class="form-text">
                                Maksimal 100 karakter. Nama dusun harus unik.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control"
                                id="keterangan"
                                name="keterangan"
                                rows="3"
                                placeholder="Tambahan informasi tentang dusun"><?= isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : '' ?></textarea>
                            <div class="form-text">
                                Informasi tambahan seperti batas wilayah, jumlah penduduk, dll.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-1"></i>Batal
                        </button>
                        <button type="submit" name="add_dusun" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Dusun -->
    <?php if ($edit_data): ?>
        <div class="modal fade show" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="false" style="display: block; padding-right: 17px;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">
                            <i class="ti ti-edit me-2"></i>Edit Dusun
                        </h5>
                        <button type="button" class="btn-close btn-close-white" onclick="window.location.href='list.php'" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <div class="modal-body">
                            <?php if (!empty($errors['database'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    <?= htmlspecialchars($errors['database']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="edit_dusun" class="form-label required">Nama Dusun</label>
                                <input type="text"
                                    class="form-control <?= isset($errors['edit_dusun']) ? 'is-invalid' : '' ?>"
                                    id="edit_dusun"
                                    name="dusun"
                                    value="<?= isset($_POST['dusun']) ? htmlspecialchars($_POST['dusun']) : htmlspecialchars($edit_data['dusun']) ?>"
                                    placeholder="Masukkan nama dusun"
                                    required>
                                <?php if (isset($errors['edit_dusun'])): ?>
                                    <div class="error-message"><?= $errors['edit_dusun'] ?></div>
                                <?php endif; ?>
                                <div class="form-text">
                                    Maksimal 100 karakter. Nama dusun harus unik.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_keterangan" class="form-label">Keterangan</label>
                                <textarea class="form-control"
                                    id="edit_keterangan"
                                    name="keterangan"
                                    rows="3"
                                    placeholder="Tambahan informasi tentang dusun"><?= isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : htmlspecialchars($edit_data['keterangan']) ?></textarea>
                                <div class="form-text">
                                    Informasi tambahan seperti batas wilayah, jumlah penduduk, dll.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="list.php" class="btn btn-secondary">
                                <i class="ti ti-x me-1"></i>Batal
                            </a>
                            <button type="submit" name="edit_dusun" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i>Perbarui
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <?php include_once '../includes/footer.php'; ?>

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
            <?php if (isset($_SESSION['success']) && !isset($_GET['edit'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses!',
                    text: '<?= addslashes($_SESSION['success']) ?>',
                    confirmButtonColor: '#3085d6',
                    timer: 3000,
                    timerProgressBar: true
                });
            <?php endif; ?>

            <?php if (isset($_SESSION['error']) && !isset($_GET['edit'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?= addslashes($_SESSION['error']) ?>',
                    confirmButtonColor: '#d33'
                });
            <?php endif; ?>
        });

        // Confirm delete dengan SweetAlert
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                const dusunName = this.closest('tr').querySelector('td:nth-child(2) strong').textContent;

                Swal.fire({
                    title: 'Hapus Dusun?',
                    html: `Apakah Anda yakin ingin menghapus dusun <strong>"${dusunName}"</strong>?<br><br>
                          <small class="text-danger">Tindakan ini tidak dapat dibatalkan!</small>`,
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

        // Auto focus pada modal tambah
        document.addEventListener('DOMContentLoaded', function() {
            const addModal = document.getElementById('addModal');
            if (addModal) {
                addModal.addEventListener('shown.bs.modal', function() {
                    document.getElementById('dusun').focus();
                });
            }

            // Auto focus pada modal edit jika terbuka
            const editModal = document.getElementById('editModal');
            if (editModal) {
                document.getElementById('edit_dusun').focus();
            }
        });

        // Validasi form sebelum submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const dusunInput = this.querySelector('input[name="dusun"]');
                if (dusunInput && dusunInput.value.trim().length > 100) {
                    e.preventDefault();
                    alert('Nama dusun maksimal 100 karakter!');
                    dusunInput.focus();
                }
            });
        });
    </script>
</body>

</html>