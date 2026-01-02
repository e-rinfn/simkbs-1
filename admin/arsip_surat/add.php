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

// Data untuk dropdown
$sifat_surat_options = ['BIASA', 'PENTING', 'RAHASIA', 'SANGAT RAHASIA'];
$status_masuk_options = ['BARU', 'DIPROSES', 'SELESAI', 'ARSIP'];
$status_keluar_options = ['DRAFT', 'TERBIT', 'TERKIRIM', 'ARSIP'];

$error = '';
$success = '';

// Inisialisasi variabel untuk menyimpan data form
$form_data = [
    'jenis_surat' => 'MASUK',
    'nomor_surat' => '',
    'tanggal_surat' => date('Y-m-d'),
    'tanggal_diterima' => date('Y-m-d'),
    'pengirim' => '',
    'tujuan' => '',
    'perihal' => '',
    'sifat_surat' => 'BIASA',
    'status' => 'BARU',
    'keterangan' => ''
];

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

        // Cek apakah nomor surat sudah ada
        if ($jenis_surat == 'MASUK') {
            $check_query = "SELECT id FROM tabel_surat_masuk WHERE nomor_surat = '$nomor_surat'";
        } else {
            $check_query = "SELECT id FROM tabel_surat_keluar WHERE nomor_surat = '$nomor_surat'";
        }

        $check_result = query($check_query);
        if (!empty($check_result)) {
            throw new Exception("Nomor surat '$nomor_surat' sudah terdaftar!");
        }

        // Handle upload file
        $file_name = null;
        if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] == 0) {
            $file = $_FILES['file_surat'];
            $file_tmp = $file['tmp_name'];
            $file_name = basename($file['name']);
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validasi ekstensi file
            if (!in_array($file_ext, $allowed_extensions)) {
                throw new Exception("Format file tidak didukung. Hanya PDF, DOC, DOCX, JPG, JPEG, PNG yang diizinkan.");
            }

            // Validasi ukuran file
            if ($file_size > $max_file_size) {
                throw new Exception("Ukuran file terlalu besar. Maksimal 5MB.");
            }

            // Generate filename dengan format: nomor_surat_nama_asli
            // Sanitasi nomor surat (hapus karakter khusus)
            $sanitized_number = preg_replace('/[^a-zA-Z0-9]/', '', $nomor_surat);
            // Sanitasi nama file asli (hapus karakter khusus kecuali - dan _)
            $original_name = pathinfo($file_name, PATHINFO_FILENAME);
            $sanitized_name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $original_name);
            // Format: nomor_surat_nama_asli.ext
            $new_file_name = $sanitized_number . '_' . $sanitized_name . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;

            // Jika file dengan nama yang sama sudah ada, tambahkan timestamp
            $counter = 1;
            while (file_exists($file_path)) {
                $new_file_name = $sanitized_number . '_' . $sanitized_name . '_' . $counter . '.' . $file_ext;
                $file_path = $upload_dir . $new_file_name;
                $counter++;
            }

            // // Upload file
            // if (!move_uploaded_file($file_tmp, $file_path)) {
            //     throw new Exception("Gagal mengupload file.");
            // }

            // Dengan ini:
            if (!move_uploaded_file($file_tmp, $file_path)) {
                // Debug detail error
                $error_details = [
                    'tmp_file_exists' => file_exists($file_tmp),
                    'tmp_file_size' => filesize($file_tmp),
                    'target_path' => $file_path,
                    'target_dir_writable' => is_writable(dirname($file_path)),
                    'upload_error' => $_FILES['file_surat']['error'],
                    'disk_free_space' => disk_free_space(dirname($file_path)),
                    'last_php_error' => error_get_last()
                ];

                error_log("UPLOAD DEBUG: " . print_r($error_details, true));

                $error_message = "Gagal mengupload file. Detail: " . print_r($error_details, true);
                throw new Exception($error_message);
            }


            $file_name = $new_file_name;
        }

        // Insert ke database berdasarkan jenis surat
        if ($jenis_surat == 'MASUK') {
            $sql = "INSERT INTO tabel_surat_masuk (
                nomor_surat, 
                tanggal_surat, 
                tanggal_diterima, 
                pengirim, 
                perihal, 
                sifat_surat, 
                file_surat, 
                status, 
                keterangan
            ) VALUES (
                '$nomor_surat', 
                '$tanggal_surat', 
                '$tanggal_diterima', 
                '$pengirim', 
                '$perihal', 
                '$sifat_surat', 
                " . ($file_name ? "'$file_name'" : "NULL") . ", 
                '$status', 
                " . ($keterangan ? "'$keterangan'" : "NULL") . "
            )";

            $redirect_page = 'list.php';
        } else {
            $sql = "INSERT INTO tabel_surat_keluar (
                nomor_surat, 
                tanggal_surat, 
                tujuan, 
                perihal, 
                sifat_surat, 
                file_surat, 
                status, 
                keterangan
            ) VALUES (
                '$nomor_surat', 
                '$tanggal_surat', 
                '$tujuan', 
                '$perihal', 
                '$sifat_surat', 
                " . ($file_name ? "'$file_name'" : "NULL") . ", 
                '$status', 
                " . ($keterangan ? "'$keterangan'" : "NULL") . "
            )";

            $redirect_page = 'list.php';
        }

        if (!$conn->query($sql)) {
            // Hapus file jika upload berhasil tapi database gagal
            if ($file_name && file_exists($upload_dir . $file_name)) {
                unlink($upload_dir . $file_name);
            }
            throw new Exception("Gagal menyimpan data surat: " . $conn->error);
        }

        $_SESSION['success'] = "Surat $jenis_surat berhasil ditambahkan!";
        header("Location: $redirect_page");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();

        // Simpan data yang diinput untuk ditampilkan kembali
        $form_data = [
            'jenis_surat' => $_POST['jenis_surat'] ?? 'MASUK',
            'nomor_surat' => $_POST['nomor_surat'] ?? '',
            'tanggal_surat' => $_POST['tanggal_surat'] ?? date('Y-m-d'),
            'tanggal_diterima' => $_POST['tanggal_diterima'] ?? date('Y-m-d'),
            'pengirim' => $_POST['pengirim'] ?? '',
            'tujuan' => $_POST['tujuan'] ?? '',
            'perihal' => $_POST['perihal'] ?? '',
            'sifat_surat' => $_POST['sifat_surat'] ?? 'BIASA',
            'status' => $_POST['status_masuk'] ?? 'BARU',
            'keterangan' => $_POST['keterangan'] ?? ''
        ];
    }
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
        display: none;
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

    .btn-remove-file {
        color: #dc3545;
        background: none;
        border: none;
        font-size: 1.2rem;
        padding: 5px;
        cursor: pointer;
    }

    .btn-remove-file:hover {
        color: #bd2130;
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
                    <h2>Tambah Surat</h2>
                    <div>
                        <a href="list.php" class="btn btn-secondary">
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

                <div class="info-box">
                    <h6><i class="ti ti-info-circle"></i> Informasi</h6>
                    <ul>
                        <li>Pilih jenis surat yang akan ditambahkan: <strong>Surat Masuk</strong> atau <strong>Surat Keluar</strong></li>
                        <li>Field dengan tanda <span class="form-required">*</span> wajib diisi</li>
                        <li>Format file yang diperbolehkan: PDF, DOC, DOCX, JPG, JPEG, PNG (maks. 5MB)</li>
                        <li>Untuk surat masuk: isi <strong>Pengirim</strong> dan <strong>Tanggal Diterima</strong></li>
                        <li>Untuk surat keluar: isi <strong>Tujuan</strong></li>
                    </ul>
                </div>

                <div class="card p-4 shadow-sm">
                    <ul class="nav nav-tabs mb-4" id="suratTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="masuk-tab" data-bs-toggle="tab" data-bs-target="#masuk" type="button" role="tab">
                                <i class="ti ti-mail"></i> Surat Masuk
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="keluar-tab" data-bs-toggle="tab" data-bs-target="#keluar" type="button" role="tab">
                                <i class="ti ti-mail-forward"></i> Surat Keluar
                            </button>
                        </li>
                    </ul>

                    <form method="post" id="formSurat" enctype="multipart/form-data">
                        <input type="hidden" name="jenis_surat" id="jenis_surat" value="MASUK">

                        <div class="tab-content" id="suratTabContent">
                            <!-- TAB 1: SURAT MASUK -->
                            <div class="tab-pane fade show active" id="masuk" role="tabpanel">
                                <div class="form-section">
                                    <h5><i class="ti ti-mail"></i> Informasi Surat</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nomor_surat_masuk" class="form-label form-required">Nomor Surat</label>
                                            <input type="text" id="nomor_surat_masuk" name="nomor_surat" class="form-control" required
                                                placeholder="Contoh: 001/SM/DPRD/V/2024"
                                                value="<?= htmlspecialchars($form_data['nomor_surat']) ?>">
                                            <small class="form-note">Format: [Nomor]/[Kode]/[Bulan]/[Tahun]</small>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="tanggal_surat_masuk" class="form-label form-required">Tanggal Surat</label>
                                            <input type="date" id="tanggal_surat_masuk" name="tanggal_surat" class="form-control" required
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
                                            <label for="perihal_masuk" class="form-label form-required">Perihal</label>
                                            <textarea id="perihal_masuk" name="perihal" class="form-control" rows="3" required
                                                placeholder="Isi perihal surat secara lengkap"><?= htmlspecialchars($form_data['perihal']) ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: SURAT KELUAR -->
                            <div class="tab-pane fade" id="keluar" role="tabpanel">
                                <div class="form-section">
                                    <h5><i class="ti ti-mail-forward"></i> Informasi Surat</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nomor_surat_keluar" class="form-label form-required">Nomor Surat</label>
                                            <input type="text" id="nomor_surat_keluar" name="nomor_surat" class="form-control" required
                                                placeholder="Contoh: 001/SK/KEL/V/2024"
                                                value="<?= htmlspecialchars($form_data['nomor_surat']) ?>">
                                            <small class="form-note">Format: [Nomor]/[Kode]/[Bulan]/[Tahun]</small>
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="tanggal_surat_keluar" class="form-label form-required">Tanggal Surat</label>
                                            <input type="date" id="tanggal_surat_keluar" name="tanggal_surat" class="form-control" required
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
                                            <label for="perihal_keluar" class="form-label form-required">Perihal</label>
                                            <textarea id="perihal_keluar" name="perihal" class="form-control" rows="3" required
                                                placeholder="Isi perihal surat secara lengkap"><?= htmlspecialchars($form_data['perihal']) ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Klasifikasi dan Keterangan (Umum untuk kedua jenis surat) -->
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

                        <!-- Upload File -->
                        <div class="form-section">
                            <h5><i class="ti ti-paperclip"></i> File Surat</h5>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="file_surat" class="form-label">Upload File Surat</label>
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

                                    <div class="file-preview" id="filePreview">
                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="ti ti-file-text"></i>
                                            </div>
                                            <div class="file-details">
                                                <div class="file-name" id="fileName"></div>
                                                <div class="file-size" id="fileSize"></div>
                                            </div>
                                            <button type="button" class="btn-remove-file" id="removeFile">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <div>
                                <a href="list.php" class="btn btn-danger">
                                    <i class="ti ti-x"></i> Batal
                                </a>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success">
                                    <i class="ti ti-check"></i> Simpan Surat
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
        const masukTab = document.getElementById('masuk-tab');
        const keluarTab = document.getElementById('keluar-tab');
        const jenisSuratInput = document.getElementById('jenis_surat');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('file_surat');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const removeFileBtn = document.getElementById('removeFile');
        const form = document.getElementById('formSurat');

        // Event untuk tab surat masuk
        masukTab.addEventListener('click', function() {
            jenisSuratInput.value = 'MASUK';
            updateFormValidation('masuk');
        });

        // Event untuk tab surat keluar
        keluarTab.addEventListener('click', function() {
            jenisSuratInput.value = 'KELUAR';
            updateFormValidation('keluar');
        });

        // Fungsi untuk update validasi form berdasarkan tab aktif
        function updateFormValidation(activeTab) {
            // Reset semua input required
            const allRequiredInputs = form.querySelectorAll('[required]');
            allRequiredInputs.forEach(input => {
                input.removeAttribute('required');
            });

            // Set required berdasarkan tab aktif
            if (activeTab === 'masuk') {
                document.getElementById('nomor_surat_masuk').setAttribute('required', '');
                document.getElementById('tanggal_surat_masuk').setAttribute('required', '');
                document.getElementById('tanggal_diterima').setAttribute('required', '');
                document.getElementById('pengirim').setAttribute('required', '');
                document.getElementById('perihal_masuk').setAttribute('required', '');
            } else {
                document.getElementById('nomor_surat_keluar').setAttribute('required', '');
                document.getElementById('tanggal_surat_keluar').setAttribute('required', '');
                document.getElementById('tujuan').setAttribute('required', '');
                document.getElementById('perihal_keluar').setAttribute('required', '');
            }
        }

        // Initial validation setup
        updateFormValidation('masuk');

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            fileUploadArea.classList.add('drag-over');
        }

        function unhighlight() {
            fileUploadArea.classList.remove('drag-over');
        }

        // Handle drop
        fileUploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(files[0]);
            }
        }

        // Click to select file
        fileUploadArea.addEventListener('click', function() {
            fileInput.click();
        });

        // Handle file selection
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                handleFileSelect(this.files[0]);
            }
        });

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
            filePreview.classList.add('show');
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
        removeFileBtn.addEventListener('click', function() {
            fileInput.value = '';
            filePreview.classList.remove('show');
        });

        // Form validation before submit
        form.addEventListener('submit', function(e) {
            const activeTab = document.querySelector('#suratTab .nav-link.active').id;
            let isValid = true;
            let errorMessage = '';

            // Validasi berdasarkan tab aktif
            if (activeTab === 'masuk-tab') {
                const nomorSurat = document.getElementById('nomor_surat_masuk').value.trim();
                const tanggalSurat = document.getElementById('tanggal_surat_masuk').value;
                const tanggalDiterima = document.getElementById('tanggal_diterima').value;
                const pengirim = document.getElementById('pengirim').value.trim();
                const perihal = document.getElementById('perihal_masuk').value.trim();

                if (!nomorSurat) {
                    errorMessage = 'Nomor surat wajib diisi';
                    isValid = false;
                } else if (!tanggalSurat) {
                    errorMessage = 'Tanggal surat wajib diisi';
                    isValid = false;
                } else if (!tanggalDiterima) {
                    errorMessage = 'Tanggal diterima wajib diisi';
                    isValid = false;
                } else if (!pengirim) {
                    errorMessage = 'Pengirim wajib diisi';
                    isValid = false;
                } else if (!perihal) {
                    errorMessage = 'Perihal wajib diisi';
                    isValid = false;
                }
            } else {
                const nomorSurat = document.getElementById('nomor_surat_keluar').value.trim();
                const tanggalSurat = document.getElementById('tanggal_surat_keluar').value;
                const tujuan = document.getElementById('tujuan').value.trim();
                const perihal = document.getElementById('perihal_keluar').value.trim();

                if (!nomorSurat) {
                    errorMessage = 'Nomor surat wajib diisi';
                    isValid = false;
                } else if (!tanggalSurat) {
                    errorMessage = 'Tanggal surat wajib diisi';
                    isValid = false;
                } else if (!tujuan) {
                    errorMessage = 'Tujuan wajib diisi';
                    isValid = false;
                } else if (!perihal) {
                    errorMessage = 'Perihal wajib diisi';
                    isValid = false;
                }
            }

            // Validasi file (opsional)
            if (fileInput.files.length > 0) {
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
            const jenis = activeTab === 'masuk-tab' ? 'masuk' : 'keluar';

            Swal.fire({
                title: 'Simpan Surat?',
                html: `Apakah Anda yakin ingin menyimpan surat ${jenis} ini?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Copy data antara tab jika ada perubahan
        document.getElementById('nomor_surat_masuk').addEventListener('input', function() {
            if (jenisSuratInput.value === 'MASUK') {
                document.getElementById('nomor_surat_keluar').value = this.value;
            }
        });

        document.getElementById('nomor_surat_keluar').addEventListener('input', function() {
            if (jenisSuratInput.value === 'KELUAR') {
                document.getElementById('nomor_surat_masuk').value = this.value;
            }
        });

        document.getElementById('perihal_masuk').addEventListener('input', function() {
            if (jenisSuratInput.value === 'MASUK') {
                document.getElementById('perihal_keluar').value = this.value;
            }
        });

        document.getElementById('perihal_keluar').addEventListener('input', function() {
            if (jenisSuratInput.value === 'KELUAR') {
                document.getElementById('perihal_masuk').value = this.value;
            }
        });
    });
</script>

</html>