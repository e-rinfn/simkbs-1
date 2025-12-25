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

// Ambil ID dari parameter URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$jenis_surat = isset($_GET['jenis']) ? $_GET['jenis'] : 'MASUK'; // MASUK atau KELUAR

if ($id <= 0) {
    $_SESSION['error'] = "ID surat tidak valid!";
    header("Location: list.php");
    exit();
}

// Data untuk dropdown
$sifat_surat_options = ['BIASA', 'PENTING', 'RAHASIA', 'SANGAT RAHASIA'];
$status_masuk_options = ['BARU', 'DIPROSES', 'SELESAI', 'ARSIP'];
$status_keluar_options = ['DRAFT', 'TERBIT', 'TERKIRIM', 'ARSIP'];

$error = '';
$success = '';
$form_data = [];

// Ambil data surat dari database berdasarkan jenis surat
if ($jenis_surat == 'MASUK') {
    $sql = "SELECT * FROM tabel_surat_masuk WHERE id = ?";
} else {
    $sql = "SELECT * FROM tabel_surat_keluar WHERE id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Surat tidak ditemukan!";
    header("Location: list.php");
    exit();
}

$surat = $result->fetch_assoc();
$stmt->close();

// Inisialisasi form data dari database
$form_data = [
    'id' => $surat['id'],
    'jenis_surat' => $jenis_surat,
    'nomor_surat' => $surat['nomor_surat'],
    'tanggal_surat' => $surat['tanggal_surat'],
    'perihal' => $surat['perihal'],
    'sifat_surat' => $surat['sifat_surat'],
    'keterangan' => $surat['keterangan'] ?? '',
    'file_surat' => $surat['file_surat'] ?? '',
    'status' => $surat['status']
];

// Set data khusus berdasarkan jenis surat
if ($jenis_surat == 'MASUK') {
    $form_data['tanggal_diterima'] = $surat['tanggal_diterima'];
    $form_data['pengirim'] = $surat['pengirim'];
    $form_data['tujuan'] = $surat['tujuan'] ?? '';
} else {
    $form_data['tujuan'] = $surat['tujuan'];
    $form_data['pengirim'] = $surat['pengirim'] ?? '';
    $form_data['tanggal_diterima'] = $surat['tanggal_diterima'] ?? '';
}

// Konfigurasi upload file
$upload_dir = __DIR__ . '/../../uploads/surat/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
$max_file_size = 5 * 1024 * 1024; // 5MB

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        $jenis_surat = $conn->real_escape_string($_POST['jenis_surat'] ?? 'MASUK');
        $nomor_surat = $conn->real_escape_string($_POST['nomor_surat'] ?? '');
        $tanggal_surat = $conn->real_escape_string($_POST['tanggal_surat'] ?? '');
        $perihal = $conn->real_escape_string($_POST['perihal'] ?? '');
        $sifat_surat = $conn->real_escape_string($_POST['sifat_surat'] ?? 'BIASA');
        $keterangan = $conn->real_escape_string($_POST['keterangan'] ?? '');

        // Data khusus surat masuk
        if ($jenis_surat == 'MASUK') {
            $tanggal_diterima = $conn->real_escape_string($_POST['tanggal_diterima'] ?? '');
            $pengirim = $conn->real_escape_string($_POST['pengirim'] ?? '');
            $status = $conn->real_escape_string($_POST['status_masuk'] ?? 'BARU');
        }
        // Data khusus surat keluar
        else {
            $tujuan = $conn->real_escape_string($_POST['tujuan'] ?? '');
            $status = $conn->real_escape_string($_POST['status_keluar'] ?? 'DRAFT');
        }

        // Validasi data wajib
        if (empty($nomor_surat)) {
            throw new Exception("Nomor surat wajib diisi!");
        }

        if (empty($tanggal_surat)) {
            throw new Exception("Tanggal surat wajib diisi!");
        }

        if (empty($perihal)) {
            throw new Exception("Perihal wajib diisi!");
        }

        if ($jenis_surat == 'MASUK') {
            if (empty($tanggal_diterima)) {
                throw new Exception("Tanggal diterima wajib diisi!");
            }
            if (empty($pengirim)) {
                throw new Exception("Pengirim wajib diisi!");
            }
        } else {
            if (empty($tujuan)) {
                throw new Exception("Tujuan wajib diisi!");
            }
        }

        // Cek apakah nomor surat sudah ada (kecuali untuk surat ini)
        if ($jenis_surat == 'MASUK') {
            $check_query = "SELECT id FROM tabel_surat_masuk WHERE nomor_surat = '$nomor_surat' AND id != $id";
        } else {
            $check_query = "SELECT id FROM tabel_surat_keluar WHERE nomor_surat = '$nomor_surat' AND id != $id";
        }

        $check_result = query($check_query);
        if (!empty($check_result)) {
            throw new Exception("Nomor surat '$nomor_surat' sudah terdaftar untuk surat lain!");
        }

        // Handle upload file baru
        $file_name = $form_data['file_surat']; // default ke file lama
        if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0) {
            $file = $_FILES['file_surat'];
            $file_tmp = $file['tmp_name'];
            $original_file_name = basename($file['name']);
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));

            // Validasi ekstensi file
            if (!in_array($file_ext, $allowed_extensions)) {
                throw new Exception("Format file tidak didukung. Hanya PDF, DOC, DOCX, JPG, JPEG, PNG yang diizinkan.");
            }

            // Validasi ukuran file
            if ($file_size > $max_file_size) {
                throw new Exception("Ukuran file terlalu besar. Maksimal 5MB.");
            }

            // Generate unique filename
            $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;

            // Upload file baru
            if (!move_uploaded_file($file_tmp, $file_path)) {
                throw new Exception("Gagal mengupload file.");
            }

            // Hapus file lama jika ada dan bukan file default
            if (!empty($form_data['file_surat']) && $form_data['file_surat'] != $new_file_name) {
                $old_file_path = $upload_dir . $form_data['file_surat'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }

            $file_name = $new_file_name;
        }

        // Update ke database berdasarkan jenis surat
        if ($jenis_surat == 'MASUK') {
            $sql = "UPDATE tabel_surat_masuk SET
                nomor_surat = '$nomor_surat', 
                tanggal_surat = '$tanggal_surat', 
                tanggal_diterima = '$tanggal_diterima', 
                pengirim = '$pengirim', 
                perihal = '$perihal', 
                sifat_surat = '$sifat_surat', 
                file_surat = " . ($file_name ? "'$file_name'" : "NULL") . ", 
                status = '$status', 
                keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . ",
                updated_at = NOW(),
                updated_by = '" . $_SESSION['user_id'] . "'
            WHERE id = $id";

            $redirect_page = 'list.php?tab=masuk';
        } else {
            $sql = "UPDATE tabel_surat_keluar SET
                nomor_surat = '$nomor_surat', 
                tanggal_surat = '$tanggal_surat', 
                tujuan = '$tujuan', 
                perihal = '$perihal', 
                sifat_surat = '$sifat_surat', 
                file_surat = " . ($file_name ? "'$file_name'" : "NULL") . ", 
                status = '$status', 
                keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . ",
                updated_at = NOW(),
                updated_by = '" . $_SESSION['user_id'] . "'
            WHERE id = $id";

            $redirect_page = 'list.php?tab=keluar';
        }

        if (!$conn->query($sql)) {
            throw new Exception("Gagal mengupdate data surat: " . $conn->error);
        }

        $_SESSION['success'] = "Surat $jenis_surat berhasil diperbarui!";
        header("Location: $redirect_page");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();

        // Simpan data yang diinput untuk ditampilkan kembali
        $form_data = [
            'id' => $id,
            'jenis_surat' => $_POST['jenis_surat'] ?? 'MASUK',
            'nomor_surat' => $_POST['nomor_surat'] ?? '',
            'tanggal_surat' => $_POST['tanggal_surat'] ?? date('Y-m-d'),
            'tanggal_diterima' => $_POST['tanggal_diterima'] ?? date('Y-m-d'),
            'pengirim' => $_POST['pengirim'] ?? '',
            'tujuan' => $_POST['tujuan'] ?? '',
            'perihal' => $_POST['perihal'] ?? '',
            'sifat_surat' => $_POST['sifat_surat'] ?? 'BIASA',
            'status' => $jenis_surat == 'MASUK' ? ($_POST['status_masuk'] ?? 'BARU') : ($_POST['status_keluar'] ?? 'DRAFT'),
            'keterangan' => $_POST['keterangan'] ?? ''
        ];
    }
}

// Cek apakah ada file yang sudah diupload
$file_exists = false;
$file_path = '';
if (!empty($form_data['file_surat'])) {
    $file_path = $upload_dir . $form_data['file_surat'];
    $file_exists = file_exists($file_path);
}

?>

<style>
    .swal2-container {
        z-index: 99999 !important;
    }

    .form-section {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #0d6efd;
    }

    .form-section h5 {
        color: #0d6efd;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }

    .form-required:after {
        content: " *";
        color: #dc3545;
    }

    .tab-content {
        padding: 20px 0;
    }

    .nav-tabs .nav-link {
        color: #495057;
        border: 1px solid transparent;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
    }

    .nav-tabs .nav-link.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }

    .nav-tabs .nav-link:hover:not(.active) {
        border-color: #dee2e6 #dee2e6 #0d6efd;
        color: #0d6efd;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    .file-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        background-color: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .file-upload-area:hover {
        border-color: #0d6efd;
        background-color: #e8f4fd;
    }

    .file-upload-area.drag-over {
        border-color: #0d6efd;
        background-color: #e8f4fd;
    }

    .file-upload-icon {
        font-size: 3rem;
        color: #6c757d;
        margin-bottom: 15px;
    }

    .file-preview {
        margin-top: 15px;
        padding: 10px;
        background-color: white;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }

    .file-preview.show {
        display: block;
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .file-icon {
        font-size: 1.5rem;
        color: #0d6efd;
    }

    .file-details {
        flex: 1;
    }

    .file-name {
        font-weight: 500;
        margin-bottom: 5px;
    }

    .file-size {
        font-size: 0.875em;
        color: #6c757d;
    }

    .file-actions {
        display: flex;
        gap: 10px;
    }

    .btn-file-action {
        padding: 5px 10px;
        font-size: 0.875rem;
    }

    .form-note {
        font-size: 0.875em;
        color: #6c757d;
        margin-top: 5px;
    }

    .form-note.error {
        color: #dc3545;
    }

    .info-box {
        background-color: #e8f4fd;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .info-box h6 {
        color: #0d6efd;
        margin-bottom: 10px;
    }

    .info-box ul {
        margin-bottom: 0;
        padding-left: 20px;
    }

    .info-box li {
        margin-bottom: 5px;
        font-size: 0.9em;
    }

    .current-file {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        border: 1px solid #dee2e6;
    }

    .current-file-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .current-file-title {
        font-weight: 600;
        color: #495057;
    }

    .current-file-details {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .file-status {
        font-size: 0.75rem;
        padding: 3px 8px;
        border-radius: 12px;
    }

    .status-exists {
        background-color: #d1e7dd;
        color: #0f5132;
    }

    .status-missing {
        background-color: #f8d7da;
        color: #842029;
    }

    .btn-delete-file {
        color: #dc3545;
        background: none;
        border: 1px solid #dc3545;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-delete-file:hover {
        background-color: #dc3545;
        color: white;
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
                    <h2>Edit Surat <?= $jenis_surat == 'MASUK' ? 'Masuk' : 'Keluar' ?></h2>
                    <div>
                        <a href="list.php?tab=<?= strtolower($jenis_surat) ?>" class="btn btn-secondary">
                            <i class="ti ti-arrow-back"></i> Kembali ke List
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error!</strong> <?= htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Sukses!</strong> <?= $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="info-box">
                    <h6><i class="ti ti-info-circle"></i> Informasi Edit Surat</h6>
                    <ul>
                        <li>Anda sedang mengedit surat <?= $jenis_surat == 'MASUK' ? 'masuk' : 'keluar' ?> dengan ID: <strong><?= $id ?></strong></li>
                        <li>Field dengan tanda <span class="form-required">*</span> wajib diisi</li>
                        <li>Format file yang diperbolehkan: PDF, DOC, DOCX, JPG, JPEG, PNG (maks. 5MB)</li>
                        <li>Upload file baru akan menggantikan file lama</li>
                        <li>Status surat: <strong><?= $form_data['status'] ?></strong></li>
                    </ul>
                </div>

                <div class="card p-4 shadow-sm">
                    <!-- Tab hanya untuk display, tidak bisa diubah -->
                    <div class="mb-4">
                        <div class="badge bg-primary p-2">
                            <i class="ti ti-mail me-1"></i>
                            Surat <?= $jenis_surat == 'MASUK' ? 'Masuk' : 'Keluar' ?>
                        </div>
                        <small class="text-muted ms-2">
                            ID: <?= $id ?> | Dibuat: <?= date('d/m/Y H:i', strtotime($surat['created_at'])) ?>
                        </small>
                    </div>

                    <form method="post" id="formSurat" enctype="multipart/form-data">
                        <input type="hidden" name="jenis_surat" id="jenis_surat" value="<?= $jenis_surat ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">

                        <!-- TAB SURAT MASUK -->
                        <?php if ($jenis_surat == 'MASUK'): ?>
                            <div class="form-section">
                                <h5><i class="ti ti-mail"></i> Informasi Surat Masuk</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nomor_surat" class="form-label form-required">Nomor Surat</label>
                                        <input type="text" id="nomor_surat" name="nomor_surat" class="form-control" required
                                            placeholder="Contoh: 001/SM/DPRD/V/2024"
                                            value="<?= htmlspecialchars($form_data['nomor_surat']) ?>">
                                        <small class="form-note">Format: [Nomor]/[Kode]/[Bulan]/[Tahun]</small>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="tanggal_surat" class="form-label form-required">Tanggal Surat</label>
                                        <input type="date" id="tanggal_surat" name="tanggal_surat" class="form-control" required
                                            max="<?= date('Y-m-d'); ?>"
                                            value="<?= htmlspecialchars($form_data['tanggal_surat']) ?>">
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="tanggal_diterima" class="form-label form-required">Tanggal Diterima</label>
                                        <input type="date" id="tanggal_diterima" name="tanggal_diterima" class="form-control" required
                                            max="<?= date('Y-m-d'); ?>"
                                            value="<?= htmlspecialchars($form_data['tanggal_diterima']) ?>">
                                    </div>

                                    <div class="col-md-8 mb-3">
                                        <label for="pengirim" class="form-label form-required">Pengirim</label>
                                        <input type="text" id="pengirim" name="pengirim" class="form-control" required
                                            placeholder="Nama instansi/pihak pengirim"
                                            value="<?= htmlspecialchars($form_data['pengirim']) ?>">
                                        <small class="form-note">Contoh: Dinas Pendidikan Kota, PT. ABC, dll.</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="status_masuk" class="form-label">Status Surat</label>
                                        <select id="status_masuk" name="status_masuk" class="form-select">
                                            <?php foreach ($status_masuk_options as $status): ?>
                                                <option value="<?= $status ?>" <?= $form_data['status'] == $status ? 'selected' : '' ?>>
                                                    <?= $status ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label for="perihal" class="form-label form-required">Perihal</label>
                                        <textarea id="perihal" name="perihal" class="form-control" rows="3" required
                                            placeholder="Isi perihal surat secara lengkap"><?= htmlspecialchars($form_data['perihal']) ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB SURAT KELUAR -->
                        <?php else: ?>
                            <div class="form-section">
                                <h5><i class="ti ti-mail-forward"></i> Informasi Surat Keluar</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nomor_surat" class="form-label form-required">Nomor Surat</label>
                                        <input type="text" id="nomor_surat" name="nomor_surat" class="form-control" required
                                            placeholder="Contoh: 001/SK/KEL/V/2024"
                                            value="<?= htmlspecialchars($form_data['nomor_surat']) ?>">
                                        <small class="form-note">Format: [Nomor]/[Kode]/[Bulan]/[Tahun]</small>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="tanggal_surat" class="form-label form-required">Tanggal Surat</label>
                                        <input type="date" id="tanggal_surat" name="tanggal_surat" class="form-control" required
                                            max="<?= date('Y-m-d'); ?>"
                                            value="<?= htmlspecialchars($form_data['tanggal_surat']) ?>">
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="status_keluar" class="form-label">Status Surat</label>
                                        <select id="status_keluar" name="status_keluar" class="form-select">
                                            <?php foreach ($status_keluar_options as $status): ?>
                                                <option value="<?= $status ?>" <?= $form_data['status'] == $status ? 'selected' : '' ?>>
                                                    <?= $status ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-8 mb-3">
                                        <label for="tujuan" class="form-label form-required">Tujuan</label>
                                        <input type="text" id="tujuan" name="tujuan" class="form-control" required
                                            placeholder="Nama instansi/pihak tujuan"
                                            value="<?= htmlspecialchars($form_data['tujuan']) ?>">
                                        <small class="form-note">Contoh: Dinas Pendidikan Kota, PT. ABC, dll.</small>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label for="perihal" class="form-label form-required">Perihal</label>
                                        <textarea id="perihal" name="perihal" class="form-control" rows="3" required
                                            placeholder="Isi perihal surat secara lengkap"><?= htmlspecialchars($form_data['perihal']) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Klasifikasi dan Keterangan -->
                        <div class="form-section">
                            <h5><i class="ti ti-category"></i> Klasifikasi Surat</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="sifat_surat" class="form-label">Sifat Surat</label>
                                    <select id="sifat_surat" name="sifat_surat" class="form-select">
                                        <?php foreach ($sifat_surat_options as $sifat): ?>
                                            <option value="<?= $sifat ?>" <?= $form_data['sifat_surat'] == $sifat ? 'selected' : '' ?>>
                                                <?= $sifat ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-note">Pilih tingkat kerahasiaan surat</small>
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="keterangan" class="form-label">Keterangan Tambahan</label>
                                    <textarea id="keterangan" name="keterangan" class="form-control" rows="2"
                                        placeholder="Keterangan tambahan jika diperlukan"><?= htmlspecialchars($form_data['keterangan']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- File Surat Saat Ini -->
                        <?php if (!empty($form_data['file_surat'])): ?>
                            <div class="form-section">
                                <h5><i class="ti ti-file"></i> File Surat Saat Ini</h5>
                                <div class="current-file">
                                    <div class="current-file-header">
                                        <div class="current-file-title">
                                            <i class="ti ti-paperclip me-1"></i>File Terlampir
                                        </div>
                                        <div class="file-status <?= $file_exists ? 'status-exists' : 'status-missing' ?>">
                                            <?= $file_exists ? 'File Tersedia' : 'File Hilang' ?>
                                        </div>
                                    </div>

                                    <div class="current-file-details">
                                        <div class="file-icon">
                                            <i class="ti ti-file-text"></i>
                                        </div>
                                        <div class="file-details">
                                            <div class="file-name"><?= htmlspecialchars($form_data['file_surat']) ?></div>
                                            <?php if ($file_exists): ?>
                                                <div class="file-size"><?= formatFileSize(filesize($file_path)) ?></div>
                                            <?php else: ?>
                                                <div class="file-size text-danger">File tidak ditemukan di server</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="file-actions">
                                            <?php if ($file_exists): ?>
                                                <a href="<?= $base_url . '/uploads/surat/' . $form_data['file_surat'] ?>"
                                                    target="_blank"
                                                    class="btn btn-info btn-sm btn-file-action">
                                                    <i class="ti ti-eye"></i> Lihat
                                                </a>
                                                <a href="<?= $base_url . '/uploads/surat/' . $form_data['file_surat'] ?>"
                                                    download
                                                    class="btn btn-primary btn-sm btn-file-action">
                                                    <i class="ti ti-download"></i> Download
                                                </a>
                                            <?php endif; ?>
                                            <button type="button"
                                                class="btn btn-danger btn-sm btn-file-action btn-delete-file"
                                                onclick="confirmDeleteFile()">
                                                <i class="ti ti-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="ti ti-info-circle"></i>
                                        Upload file baru akan menggantikan file yang ada di atas.
                                        Kosongkan jika tidak ingin mengubah file.
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Upload File Baru -->
                        <div class="form-section">
                            <h5><i class="ti ti-cloud-upload"></i> <?= empty($form_data['file_surat']) ? 'Upload' : 'Ganti' ?> File Surat</h5>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <div class="file-upload-area" id="fileUploadArea">
                                        <div class="file-upload-icon">
                                            <i class="ti ti-cloud-upload"></i>
                                        </div>
                                        <h5>Drag & Drop file di sini</h5>
                                        <p class="text-muted">atau klik untuk memilih file</p>
                                        <p class="text-muted">
                                            <small>Format: PDF, DOC, DOCX, JPG, JPEG, PNG (Maks. 5MB)</small>
                                        </p>
                                        <input type="file" id="file_surat" name="file_surat" class="d-none" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                    </div>

                                    <div class="file-preview" id="filePreview" style="display: none;">
                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="ti ti-file-text"></i>
                                            </div>
                                            <div class="file-details">
                                                <div class="file-name" id="fileName"></div>
                                                <div class="file-size" id="fileSize"></div>
                                            </div>
                                            <button type="button" class="btn btn-danger btn-sm" id="removeFile">
                                                <i class="ti ti-x"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <div>
                                <a href="list.php?tab=<?= strtolower($jenis_surat) ?>" class="btn btn-danger">
                                    <i class="ti ti-x"></i> Batal
                                </a>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="ti ti-refresh"></i> Reset Form
                                </button>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success">
                                    <i class="ti ti-check"></i> Update Surat
                                </button>
                            </div>
                        </div>
                    </form>
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
        // Elemen DOM
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('file_surat');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFileBtn = document.getElementById('removeFile');
        const form = document.getElementById('formSurat');

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            if (fileUploadArea) {
                fileUploadArea.addEventListener(eventName, preventDefaults, false);
            }
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            if (fileUploadArea) {
                fileUploadArea.addEventListener(eventName, highlight, false);
            }
        });

        ['dragleave', 'drop'].forEach(eventName => {
            if (fileUploadArea) {
                fileUploadArea.addEventListener(eventName, unhighlight, false);
            }
        });

        function highlight() {
            fileUploadArea.classList.add('drag-over');
        }

        function unhighlight() {
            fileUploadArea.classList.remove('drag-over');
        }

        // Handle drop
        if (fileUploadArea) {
            fileUploadArea.addEventListener('drop', handleDrop, false);
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        }

        // Click to select file
        if (fileUploadArea) {
            fileUploadArea.addEventListener('click', function() {
                fileInput.click();
            });
        }

        // Handle file selection
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    handleFileSelect(this.files[0]);
                }
            });
        }

        // Handle file selection
        function handleFileSelect(file) {
            // Validasi ukuran file (5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File terlalu besar',
                    text: 'Ukuran file maksimal 5MB',
                    confirmButtonColor: '#d33'
                });
                fileInput.value = '';
                return;
            }

            // Validasi ekstensi file
            const allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            const fileExtension = file.name.split('.').pop().toLowerCase();

            if (!allowedExtensions.includes(fileExtension)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Format file tidak didukung',
                    text: 'Hanya file PDF, DOC, DOCX, JPG, JPEG, PNG yang diperbolehkan',
                    confirmButtonColor: '#d33'
                });
                fileInput.value = '';
                return;
            }

            // Tampilkan preview
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            filePreview.style.display = 'block';
        }

        // Format ukuran file
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Remove file
        if (removeFileBtn) {
            removeFileBtn.addEventListener('click', function() {
                fileInput.value = '';
                filePreview.style.display = 'none';
            });
        }

        // Form validation before submit
        if (form) {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                let errorMessage = '';

                // Validasi data umum
                const nomorSurat = document.getElementById('nomor_surat').value.trim();
                const tanggalSurat = document.getElementById('tanggal_surat').value;
                const perihal = document.getElementById('perihal').value.trim();

                if (!nomorSurat) {
                    errorMessage = 'Nomor surat wajib diisi';
                    isValid = false;
                } else if (!tanggalSurat) {
                    errorMessage = 'Tanggal surat wajib diisi';
                    isValid = false;
                } else if (!perihal) {
                    errorMessage = 'Perihal wajib diisi';
                    isValid = false;
                }

                // Validasi data khusus
                const jenisSurat = document.getElementById('jenis_surat').value;
                if (jenisSurat === 'MASUK') {
                    const tanggalDiterima = document.getElementById('tanggal_diterima').value;
                    const pengirim = document.getElementById('pengirim').value.trim();

                    if (!tanggalDiterima) {
                        errorMessage = 'Tanggal diterima wajib diisi';
                        isValid = false;
                    } else if (!pengirim) {
                        errorMessage = 'Pengirim wajib diisi';
                        isValid = false;
                    }
                } else {
                    const tujuan = document.getElementById('tujuan').value.trim();
                    if (!tujuan) {
                        errorMessage = 'Tujuan wajib diisi';
                        isValid = false;
                    }
                }

                // Validasi file (opsional)
                if (fileInput && fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    const maxSize = 5 * 1024 * 1024;

                    if (file.size > maxSize) {
                        errorMessage = 'Ukuran file maksimal 5MB';
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Data belum lengkap',
                        text: errorMessage,
                        confirmButtonColor: '#d33'
                    });
                    return false;
                }

                // Konfirmasi sebelum submit
                e.preventDefault();
                const jenis = jenisSurat === 'MASUK' ? 'masuk' : 'keluar';

                Swal.fire({
                    title: 'Update Surat?',
                    html: `Apakah Anda yakin ingin memperbarui surat ${jenis} ini?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Update',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        }

        // Reset form
        const resetBtn = document.querySelector('button[type="reset"]');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Reset Form?',
                    text: 'Semua perubahan yang belum disimpan akan hilang',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Reset',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.reset();
                        if (filePreview) {
                            filePreview.style.display = 'none';
                        }
                        // Kembalikan ke nilai awal dari database
                        // (Diimplementasikan di PHP)
                        location.reload();
                    }
                });
            });
        }
    });

    // Fungsi untuk konfirmasi hapus file
    function confirmDeleteFile() {
        Swal.fire({
            title: 'Hapus File?',
            text: 'File yang dihapus tidak dapat dikembalikan. Anda masih bisa upload file baru.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Kirim request untuk hapus file
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'delete_file.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: 'File berhasil dihapus',
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: response.message
                            });
                        }
                    }
                };
                xhr.send('id=<?= $id ?>&jenis=<?= $jenis_surat ?>');
            }
        });
    }
</script>

</html>
<?php
// Helper function untuk format file size
function formatFileSize($bytes)
{
    if ($bytes == 0) return "0 Bytes";
    $k = 1024;
    $sizes = ["Bytes", "KB", "MB", "GB", "TB"];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), 2) . " " . $sizes[$i];
}
?>