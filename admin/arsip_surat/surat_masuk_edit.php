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

// Cek apakah ada parameter ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID surat tidak valid!';
    header("Location: list.php?tab=masuk");
    exit();
}

$id = intval($_GET['id']);

// Array untuk pilihan dropdown
$sifat_surat_options = ['BIASA', 'PENTING', 'RAHASIA', 'SANGAT RAHASIA'];
$status_options = ['BARU', 'DIPROSES', 'SELESAI', 'ARSIP'];

// Ambil data surat masuk berdasarkan ID
$sql = "SELECT * FROM tabel_surat_masuk WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error'] = 'Terjadi kesalahan database!';
    header("Location: list.php?tab=masuk");
    exit();
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$surat = $result->fetch_assoc();
$stmt->close();

// Cek apakah data ditemukan
if (!$surat) {
    $_SESSION['error'] = 'Surat tidak ditemukan!';
    header("Location: list.php?tab=masuk");
    exit();
}

// Inisialisasi variabel
$errors = [];
$success = false;

// Data default untuk form
$form_data = [
    'nomor_surat' => $surat['nomor_surat'],
    'tanggal_surat' => $surat['tanggal_surat'],
    'tanggal_diterima' => $surat['tanggal_diterima'],
    'pengirim' => $surat['pengirim'],
    'perihal' => $surat['perihal'],
    'sifat_surat' => $surat['sifat_surat'],
    'status' => $surat['status'],
    'keterangan' => $surat['keterangan'],
    'file_surat' => $surat['file_surat']
];

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $form_data['nomor_surat'] = trim($_POST['nomor_surat'] ?? '');
    $form_data['tanggal_surat'] = $_POST['tanggal_surat'] ?? '';
    $form_data['tanggal_diterima'] = $_POST['tanggal_diterima'] ?? '';
    $form_data['pengirim'] = trim($_POST['pengirim'] ?? '');
    $form_data['perihal'] = trim($_POST['perihal'] ?? '');
    $form_data['sifat_surat'] = $_POST['sifat_surat'] ?? 'BIASA';
    $form_data['status'] = $_POST['status'] ?? 'BARU';
    $form_data['keterangan'] = trim($_POST['keterangan'] ?? '');

    // Validasi input
    if (empty($form_data['nomor_surat'])) {
        $errors['nomor_surat'] = 'Nomor surat wajib diisi';
    } elseif (strlen($form_data['nomor_surat']) > 100) {
        $errors['nomor_surat'] = 'Nomor surat maksimal 100 karakter';
    } else {
        // Cek apakah nomor surat sudah digunakan (kecuali untuk surat ini)
        $check_sql = "SELECT id FROM tabel_surat_masuk WHERE nomor_surat = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $form_data['nomor_surat'], $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $errors['nomor_surat'] = 'Nomor surat sudah digunakan!';
        }
        $check_stmt->close();
    }

    if (empty($form_data['tanggal_surat'])) {
        $errors['tanggal_surat'] = 'Tanggal surat wajib diisi';
    }

    if (empty($form_data['tanggal_diterima'])) {
        $errors['tanggal_diterima'] = 'Tanggal diterima wajib diisi';
    }

    if (empty($form_data['pengirim'])) {
        $errors['pengirim'] = 'Pengirim wajib diisi';
    } elseif (strlen($form_data['pengirim']) > 200) {
        $errors['pengirim'] = 'Pengirim maksimal 200 karakter';
    }

    if (empty($form_data['perihal'])) {
        $errors['perihal'] = 'Perihal wajib diisi';
    } elseif (strlen($form_data['perihal']) > 500) {
        $errors['perihal'] = 'Perihal maksimal 500 karakter';
    }

    if (!in_array($form_data['sifat_surat'], $sifat_surat_options)) {
        $errors['sifat_surat'] = 'Sifat surat tidak valid';
    }

    if (!in_array($form_data['status'], $status_options)) {
        $errors['status'] = 'Status tidak valid';
    }

    // Validasi file upload
    $file_surat = $_FILES['file_surat'] ?? null;
    $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $max_file_size = 5 * 1024 * 1024; // 5MB

    if ($file_surat && $file_surat['error'] === 0) {
        // Validasi ukuran file
        if ($file_surat['size'] > $max_file_size) {
            $errors['file_surat'] = 'Ukuran file maksimal 5MB';
        }

        // Validasi ekstensi file
        $file_extension = strtolower(pathinfo($file_surat['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors['file_surat'] = 'Format file tidak didukung. Gunakan PDF, DOC, DOCX, JPG, JPEG, atau PNG';
        }
    }

    // Jika tidak ada error, proses data
    if (empty($errors)) {
        try {
            // Handle file upload jika ada file baru
            $uploaded_file_name = $surat['file_surat']; // Default ke file lama
            
            if ($file_surat && $file_surat['error'] === 0) {
                $upload_dir = __DIR__ . '/../../uploads/surat/';
                
                // Buat folder jika belum ada
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Hapus file lama jika ada dan bukan file default
                if ($surat['file_surat'] && file_exists($upload_dir . $surat['file_surat'])) {
                    unlink($upload_dir . $surat['file_surat']);
                }

                // Generate nama file unik
                $file_extension = strtolower(pathinfo($file_surat['name'], PATHINFO_EXTENSION));
                $uploaded_file_name = 'surat_masuk_' . time() . '_' . uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $uploaded_file_name;

                // Upload file
                if (!move_uploaded_file($file_surat['tmp_name'], $target_file)) {
                    throw new Exception('Gagal mengupload file');
                }
            }

            // Query untuk update data
            $sql = "UPDATE tabel_surat_masuk SET 
                    nomor_surat = ?,
                    tanggal_surat = ?,
                    tanggal_diterima = ?,
                    pengirim = ?,
                    perihal = ?,
                    sifat_surat = ?,
                    status = ?,
                    keterangan = ?,
                    file_surat = ?,
                    updated_at = NOW()
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            
            $stmt->bind_param(
                "sssssssssi",
                $form_data['nomor_surat'],
                $form_data['tanggal_surat'],
                $form_data['tanggal_diterima'],
                $form_data['pengirim'],
                $form_data['perihal'],
                $form_data['sifat_surat'],
                $form_data['status'],
                $form_data['keterangan'],
                $uploaded_file_name,
                $id
            );

            if ($stmt->execute()) {
                $success = true;
                
                // Set session success message
                $_SESSION['success'] = 'Surat masuk berhasil diperbarui!';
                
                // Redirect ke halaman list
                header("Location: list.php?tab=masuk");
                exit();
            } else {
                $errors['database'] = 'Gagal memperbarui data: ' . $stmt->error;
                
                // Hapus file baru jika upload berhasil tetapi database gagal
                if ($file_surat && $file_surat['error'] === 0 && file_exists($upload_dir . $uploaded_file_name)) {
                    unlink($upload_dir . $uploaded_file_name);
                }
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errors['database'] = 'Terjadi kesalahan: ' . $e->getMessage();
            
            // Hapus file baru jika ada error
            if ($file_surat && $file_surat['error'] === 0 && isset($upload_dir) && file_exists($upload_dir . $uploaded_file_name)) {
                unlink($upload_dir . $uploaded_file_name);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Surat Masuk - Sistem Administrasi Desa</title>
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
        
        .file-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
        
        .card-header {
            background-color: #0d6efd;
            color: white;
        }
        
        .sifat-options .form-check {
            margin-bottom: 5px;
        }
        
        .sifat-biasa {
            color: #6c757d;
        }
        
        .sifat-penting {
            color: #fd7e14;
        }
        
        .sifat-rahasia {
            color: #dc3545;
        }
        
        .sifat-sangat-rahasia {
            color: #6f42c1;
        }
        
        .preview-area {
            border: 2px dashed #dee2e6;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
        }
        
        .preview-area:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        
        .file-info {
            margin-top: 10px;
            font-size: 0.9em;
            color: #6c757d;
        }
        
        .btn-back {
            margin-right: 10px;
        }
        
        .current-file {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #0d6efd;
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
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="ti ti-edit me-2"></i>Edit Surat Masuk</h5>
                        </div>
                        <div class="card-body">
                            <!-- Breadcrumb -->
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="list.php?tab=masuk">Arsip Surat</a></li>
                                    <li class="breadcrumb-item active">Edit Surat Masuk</li>
                                </ol>
                            </nav>

                            <?php if (!empty($errors['database'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    <?= htmlspecialchars($errors['database']) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" enctype="multipart/form-data" novalidate>
                                <div class="row">
                                    <!-- Kolom Kiri -->
                                    <div class="col-md-6">
                                        <!-- Nomor Surat -->
                                        <div class="mb-3">
                                            <label for="nomor_surat" class="form-label required">Nomor Surat</label>
                                            <input type="text" 
                                                   class="form-control <?= isset($errors['nomor_surat']) ? 'is-invalid' : '' ?>" 
                                                   id="nomor_surat" 
                                                   name="nomor_surat" 
                                                   value="<?= htmlspecialchars($form_data['nomor_surat']) ?>"
                                                   placeholder="Contoh: 005/001/SM/VI/2024"
                                                   required>
                                            <?php if (isset($errors['nomor_surat'])): ?>
                                                <div class="error-message"><?= $errors['nomor_surat'] ?></div>
                                            <?php endif; ?>
                                            <div class="form-text">
                                                Format: Nomor/Kode/Jenis/Bulan/Tahun
                                            </div>
                                        </div>

                                        <!-- Tanggal Surat -->
                                        <div class="mb-3">
                                            <label for="tanggal_surat" class="form-label required">Tanggal Surat</label>
                                            <input type="date" 
                                                   class="form-control <?= isset($errors['tanggal_surat']) ? 'is-invalid' : '' ?>" 
                                                   id="tanggal_surat" 
                                                   name="tanggal_surat" 
                                                   value="<?= $form_data['tanggal_surat'] ?>"
                                                   required>
                                            <?php if (isset($errors['tanggal_surat'])): ?>
                                                <div class="error-message"><?= $errors['tanggal_surat'] ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Tanggal Diterima -->
                                        <div class="mb-3">
                                            <label for="tanggal_diterima" class="form-label required">Tanggal Diterima</label>
                                            <input type="date" 
                                                   class="form-control <?= isset($errors['tanggal_diterima']) ? 'is-invalid' : '' ?>" 
                                                   id="tanggal_diterima" 
                                                   name="tanggal_diterima" 
                                                   value="<?= $form_data['tanggal_diterima'] ?>"
                                                   required>
                                            <?php if (isset($errors['tanggal_diterima'])): ?>
                                                <div class="error-message"><?= $errors['tanggal_diterima'] ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Pengirim -->
                                        <div class="mb-3">
                                            <label for="pengirim" class="form-label required">Pengirim</label>
                                            <input type="text" 
                                                   class="form-control <?= isset($errors['pengirim']) ? 'is-invalid' : '' ?>" 
                                                   id="pengirim" 
                                                   name="pengirim" 
                                                   value="<?= htmlspecialchars($form_data['pengirim']) ?>"
                                                   placeholder="Nama instansi/pengirim"
                                                   required>
                                            <?php if (isset($errors['pengirim'])): ?>
                                                <div class="error-message"><?= $errors['pengirim'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Kolom Kanan -->
                                    <div class="col-md-6">
                                        <!-- Sifat Surat -->
                                        <div class="mb-3">
                                            <label class="form-label required">Sifat Surat</label>
                                            <div class="sifat-options">
                                                <?php foreach ($sifat_surat_options as $sifat): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" 
                                                               type="radio" 
                                                               name="sifat_surat" 
                                                               id="sifat_<?= strtolower($sifat) ?>" 
                                                               value="<?= $sifat ?>"
                                                               <?= $form_data['sifat_surat'] === $sifat ? 'checked' : '' ?>
                                                               required>
                                                        <label class="form-check-label sifat-<?= strtolower(str_replace(' ', '-', $sifat)) ?>"
                                                               for="sifat_<?= strtolower($sifat) ?>">
                                                            <?= $sifat ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if (isset($errors['sifat_surat'])): ?>
                                                <div class="error-message"><?= $errors['sifat_surat'] ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Status -->
                                        <div class="mb-3">
                                            <label for="status" class="form-label required">Status</label>
                                            <select class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>" 
                                                    id="status" 
                                                    name="status"
                                                    required>
                                                <?php foreach ($status_options as $status): ?>
                                                    <option value="<?= $status ?>" 
                                                            <?= $form_data['status'] === $status ? 'selected' : '' ?>>
                                                        <?= $status ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (isset($errors['status'])): ?>
                                                <div class="error-message"><?= $errors['status'] ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- File Surat -->
                                        <div class="mb-3">
                                            <label for="file_surat" class="form-label">File Surat</label>
                                            
                                            <!-- Tampilkan file saat ini -->
                                            <?php if ($surat['file_surat']): ?>
                                                <?php
                                                $file_path = __DIR__ . '/../../uploads/surat/' . $surat['file_surat'];
                                                $file_url = $base_url . '/uploads/surat/' . $surat['file_surat'];
                                                $file_exists = file_exists($file_path);
                                                ?>
                                                <div class="current-file mb-3">
                                                    <strong>File Saat Ini:</strong><br>
                                                    <?php if ($file_exists): ?>
                                                        <a href="<?= $file_url ?>" target="_blank" class="file-link">
                                                            <i class="ti ti-file-text me-1"></i><?= $surat['file_surat'] ?>
                                                        </a>
                                                        <small class="text-muted d-block mt-1">
                                                            <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="ti ti-download"></i> Download
                                                            </a>
                                                            <a href="<?= $file_url ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                                <i class="ti ti-eye"></i> Lihat
                                                            </a>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-danger">
                                                            <i class="ti ti-file-off me-1"></i>File tidak ditemukan di server
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted d-block mb-2">Unggah file baru untuk mengganti file saat ini</small>
                                            <?php endif; ?>
                                            
                                            <div class="preview-area" onclick="document.getElementById('file_surat').click()">
                                                <i class="ti ti-upload" style="font-size: 48px; color: #6c757d;"></i>
                                                <p class="mt-2 mb-1">Klik untuk mengupload file baru</p>
                                                <p class="file-info">Format: PDF, DOC, DOCX, JPG, JPEG, PNG (Maks. 5MB)</p>
                                            </div>
                                            <input type="file" 
                                                   class="form-control d-none" 
                                                   id="file_surat" 
                                                   name="file_surat"
                                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                                   onchange="previewFile()">
                                            <?php if (isset($errors['file_surat'])): ?>
                                                <div class="error-message"><?= $errors['file_surat'] ?></div>
                                            <?php endif; ?>
                                            <div id="filePreview" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Perihal -->
                                <div class="mb-3">
                                    <label for="perihal" class="form-label required">Perihal</label>
                                    <textarea class="form-control <?= isset($errors['perihal']) ? 'is-invalid' : '' ?>" 
                                              id="perihal" 
                                              name="perihal" 
                                              rows="3"
                                              placeholder="Isi perihal surat secara lengkap"
                                              required><?= htmlspecialchars($form_data['perihal']) ?></textarea>
                                    <?php if (isset($errors['perihal'])): ?>
                                        <div class="error-message"><?= $errors['perihal'] ?></div>
                                    <?php endif; ?>
                                    <div class="form-text">
                                        Maksimal 500 karakter. Saat ini: <span id="charCount"><?= strlen($form_data['perihal']) ?></span>/500
                                    </div>
                                </div>

                                <!-- Keterangan -->
                                <div class="mb-3">
                                    <label for="keterangan" class="form-label">Keterangan</label>
                                    <textarea class="form-control" 
                                              id="keterangan" 
                                              name="keterangan" 
                                              rows="2"
                                              placeholder="Tambahan informasi terkait surat"><?= htmlspecialchars($form_data['keterangan']) ?></textarea>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between mt-4">
                                    <div>
                                        <a href="list.php?tab=masuk" class="btn btn-secondary btn-back">
                                            <i class="ti ti-arrow-left me-1"></i>Kembali
                                        </a>
                                        <button type="reset" class="btn btn-outline-secondary">
                                            <i class="ti ti-refresh me-1"></i>Reset
                                        </button>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-check me-1"></i>Perbarui Surat
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ Main Content ] end -->
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include_once '../includes/footer.php'; ?>

    <script>
        // Character counter untuk perihal
        document.getElementById('perihal').addEventListener('input', function() {
            const charCount = this.value.length;
            document.getElementById('charCount').textContent = charCount;
            
            if (charCount > 500) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // File preview function
        function previewFile() {
            const fileInput = document.getElementById('file_surat');
            const previewArea = document.querySelector('.preview-area');
            const fileInfo = document.querySelector('.file-info');
            const filePreview = document.getElementById('filePreview');
            
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // Convert to MB
                const fileType = file.type;
                
                // Update preview area
                previewArea.innerHTML = `
                    <i class="ti ti-file-text" style="font-size: 48px; color: #0d6efd;"></i>
                    <p class="mt-2 mb-1"><strong>${fileName}</strong></p>
                    <p class="file-info">${fileSize} MB - ${fileType}</p>
                `;
                
                // Preview image if it's an image
                if (fileType.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        filePreview.innerHTML = `
                            <img src="${e.target.result}" class="img-thumbnail file-preview" style="display: block;">
                        `;
                        filePreview.querySelector('.file-preview').style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    // For non-image files, show icon based on file type
                    let iconClass = 'ti-file-text';
                    let iconColor = '#0d6efd';
                    
                    if (fileName.endsWith('.pdf')) {
                        iconClass = 'ti-file-text';
                        iconColor = '#dc3545';
                    } else if (fileName.endsWith('.doc') || fileName.endsWith('.docx')) {
                        iconClass = 'ti-file-word';
                        iconColor = '#0d6efd';
                    } else if (fileName.endsWith('.jpg') || fileName.endsWith('.jpeg') || fileName.endsWith('.png')) {
                        iconClass = 'ti-photo';
                        iconColor = '#198754';
                    }
                    
                    filePreview.innerHTML = `
                        <div class="text-center">
                            <i class="ti ${iconClass}" style="font-size: 64px; color: ${iconColor};"></i>
                            <p class="mt-2">${fileName}</p>
                        </div>
                    `;
                }
            }
        }

        // Form validation sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Reset previous error states
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Check required fields
            const requiredFields = document.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            // Check file size if exists
            const fileInput = document.getElementById('file_surat');
            if (fileInput.files.length > 0) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (fileInput.files[0].size > maxSize) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB.');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Harap lengkapi semua field yang wajib diisi.');
            }
        });
    </script>
</body>
</html>