<?php
// edit.php
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

// Cek apakah parameter ID ada
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);

// Ambil data yang akan diedit
$sql = "SELECT k.*, d.dusun as nama_dusun 
        FROM tabel_kependudukan k 
        LEFT JOIN tabel_dusun d ON k.DSN = d.id 
        WHERE k.id = $id";
$result = $conn->query($sql);
$data = $result->fetch_assoc();

// Jika data tidak ditemukan
if (!$data) {
    $_SESSION['error'] = "Data tidak ditemukan!";
    header("Location: list.php");
    exit();
}

// Ambil data untuk dropdown dusun
$sql_dusun = "SELECT * FROM tabel_dusun ORDER BY dusun";
$dusun_options = query($sql_dusun);

// Ambil dokumen yang sudah diupload
$sql_dokumen = "SELECT * FROM tabel_dokumen_penduduk WHERE penduduk_id = $id ORDER BY jenis_dokumen, created_at DESC";
$dokumen_list = query($sql_dokumen);

// Data untuk dropdown
$agama_options = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU', 'LAINNYA'];
$hubungan_options = ['KEPALA KELUARGA', 'ISTRI', 'ANAK', 'FAMILI LAIN'];
$pendidikan_options = ['TIDAK/BELUM SEKOLAH', 'SD/SEDERAJAT', 'SMP/SEDERAJAT', 'SMA/SEDERAJAT', 'D1/D2/D3', 'S1', 'S2', 'S3'];
$kewarganegaraan_options = ['WNI', 'WNA'];
$status_tinggal_options = ['TETAP', 'SEMENTARA', 'PENDATANG', 'MENINGGAL', 'PINDAH'];
$gol_darah_options = ['A', 'B', 'AB', 'O', 'TIDAK TAHU'];
$disabilitas_options = ['YA', 'TIDAK'];
$status_kawin_options = ['BELUM KAWIN', 'KAWIN', 'CERAI HIDUP', 'CERAI MATI'];
$jenis_dokumen_options = ['AKTA_KEMATIAN' => 'Akta Kematian', 'AKTA_PINDAH' => 'Akta Pindah', 'BUKU_NIKAH' => 'Buku Nikah', 'LAINNYA' => 'Lainnya'];

$error = '';
$success = '';
$upload_errors = [];

// Inisialisasi form_data dengan data dari database
$form_data = [
    'NO_KK' => $data['NO_KK'],
    'NIK' => $data['NIK'],
    'NAMA_LGKP' => $data['NAMA_LGKP'],
    'NAMA_PANGGILAN' => $data['NAMA_PANGGILAN'] ?? '',
    'HBKEL' => $data['HBKEL'],
    'JK' => $data['JK'],
    'TMPT_LHR' => $data['TMPT_LHR'],
    'TGL_LHR' => $data['TGL_LHR'],
    'AGAMA' => $data['AGAMA'],
    'STATUS_KAWIN' => $data['STATUS_KAWIN'],
    'PENDIDIKAN' => $data['PENDIDIKAN'] ?? '',
    'PEKERJAAN' => $data['PEKERJAAN'] ?? '',
    'NAMA_LGKP_AYAH' => $data['NAMA_LGKP_AYAH'],
    'NAMA_LGKP_IBU' => $data['NAMA_LGKP_IBU'],
    'KECAMATAN' => $data['KECAMATAN'],
    'KELURAHAN' => $data['KELURAHAN'],
    'DSN' => $data['DSN'],
    'rt' => $data['rt'] ?? '',
    'rw' => $data['rw'] ?? '',
    'ALAMAT' => $data['ALAMAT'] ?? '',
    'GOL_DARAH' => $data['GOL_DARAH'],
    'KEWARGANEGARAAN' => $data['KEWARGANEGARAAN'],
    'STATUS_TINGGAL' => $data['STATUS_TINGGAL'],
    'DISABILITAS' => $data['DISABILITAS'],
    'JENIS_DISABILITAS' => $data['JENIS_DISABILITAS'] ?? ''
];

// Fungsi untuk upload file
function uploadFile($file, $penduduk_id, $jenis_dokumen)
{
    global $conn, $upload_errors;

    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];
    $max_file_size = 5 * 1024 * 1024; // 5MB

    $original_name = basename($file['name']);
    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $file_size = $file['size'];

    // Validasi ekstensi file
    if (!in_array($file_extension, $allowed_extensions)) {
        $upload_errors[] = "Ekstensi file tidak diperbolehkan. Hanya PDF, JPG, PNG, GIF, DOC, DOCX.";
        return false;
    }

    // Validasi ukuran file
    if ($file_size > $max_file_size) {
        $upload_errors[] = "Ukuran file terlalu besar. Maksimal 5MB.";
        return false;
    }

    // Generate unique filename
    $new_filename = 'doc_' . $penduduk_id . '_' . $jenis_dokumen . '_' . time() . '.' . $file_extension;
    $upload_path = __DIR__ . '/../../uploads/dokumen_penduduk/';

    // Create directory if not exists
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }

    $target_file = $upload_path . $new_filename;

    // Ambil data dokumen
    $nomor_dokumen = $_POST['nomor_dokumen'][$jenis_dokumen] ?? '';
    $tanggal_dokumen = $_POST['tanggal_dokumen'][$jenis_dokumen] ?? null;
    $keterangan = $_POST['keterangan'][$jenis_dokumen] ?? '';

    // Validasi tanggal dokumen wajib untuk akta kematian dan akta pindah
    if (in_array($jenis_dokumen, ['AKTA_KEMATIAN', 'AKTA_PINDAH'])) {
        if (empty($tanggal_dokumen)) {
            if ($jenis_dokumen === 'AKTA_KEMATIAN') {
                $upload_errors[] = "Mohon isi tanggal kematian.";
            } else if ($jenis_dokumen === 'AKTA_PINDAH') {
                $upload_errors[] = "Mohon isi tanggal pindah.";
            } else {
                $upload_errors[] = "Mohon isi tanggal dokumen.";
            }
            return false;
        }
    }

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Simpan ke database
        $sql = "INSERT INTO tabel_dokumen_penduduk 
                (penduduk_id, jenis_dokumen, nama_file, original_name, path, nomor_dokumen, tanggal_dokumen, keterangan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $relative_path = 'uploads/dokumen_penduduk/' . $new_filename;
        $stmt->bind_param(
            "isssssss",
            $penduduk_id,
            $jenis_dokumen,
            $new_filename,
            $original_name,
            $relative_path,
            $nomor_dokumen,
            $tanggal_dokumen,
            $keterangan
        );

        if ($stmt->execute()) {
            return true;
        } else {
            $upload_errors[] = "Gagal menyimpan data dokumen: " . $stmt->error;
            return false;
        }
    } else {
        $upload_errors[] = "Gagal mengupload file.";
        return false;
    }
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan semua data POST ke form_data
    foreach ($form_data as $key => $value) {
        if (isset($_POST[$key])) {
            $form_data[$key] = $_POST[$key];
        }
    }

    try {
        // Data kependudukan
        $NO_KK = $conn->real_escape_string($_POST['NO_KK']);
        $NIK = $conn->real_escape_string($_POST['NIK']);
        $NAMA_LGKP = $conn->real_escape_string($_POST['NAMA_LGKP']);
        $NAMA_PANGGILAN = $conn->real_escape_string($_POST['NAMA_PANGGILAN'] ?? '');
        $HBKEL = $conn->real_escape_string($_POST['HBKEL']);
        $JK = $conn->real_escape_string($_POST['JK']);
        $TMPT_LHR = $conn->real_escape_string($_POST['TMPT_LHR']);
        $TGL_LHR = $conn->real_escape_string($_POST['TGL_LHR']);
        $AGAMA = $conn->real_escape_string($_POST['AGAMA']);
        $STATUS_KAWIN = $conn->real_escape_string($_POST['STATUS_KAWIN'] ?? 'BELUM KAWIN');
        $PENDIDIKAN = $conn->real_escape_string($_POST['PENDIDIKAN'] ?? '');
        $PEKERJAAN = $conn->real_escape_string($_POST['PEKERJAAN'] ?? '');
        $NAMA_LGKP_AYAH = $conn->real_escape_string($_POST['NAMA_LGKP_AYAH']);
        $NAMA_LGKP_IBU = $conn->real_escape_string($_POST['NAMA_LGKP_IBU']);
        $KECAMATAN = $conn->real_escape_string($_POST['KECAMATAN'] ?? '');
        $KELURAHAN = $conn->real_escape_string($_POST['KELURAHAN'] ?? '');
        $DSN = $conn->real_escape_string($_POST['DSN']);
        $rt = $conn->real_escape_string($_POST['rt'] ?? '');
        $rw = $conn->real_escape_string($_POST['rw'] ?? '');
        $ALAMAT = $conn->real_escape_string($_POST['ALAMAT'] ?? '');
        $GOL_DARAH = $conn->real_escape_string($_POST['GOL_DARAH'] ?? 'TIDAK TAHU');
        $KEWARGANEGARAAN = $conn->real_escape_string($_POST['KEWARGANEGARAAN'] ?? 'WNI');
        $STATUS_TINGGAL = $conn->real_escape_string($_POST['STATUS_TINGGAL'] ?? 'TETAP');
        $DISABILITAS = $conn->real_escape_string($_POST['DISABILITAS'] ?? 'TIDAK');
        $JENIS_DISABILITAS = $conn->real_escape_string($_POST['JENIS_DISABILITAS'] ?? '');

        // Validasi NIK dan No KK
        if (strlen($NIK) !== 16 || !is_numeric($NIK)) {
            throw new Exception("NIK harus 16 digit angka!");
        }

        if (strlen($NO_KK) !== 16 || !is_numeric($NO_KK)) {
            throw new Exception("No. KK harus 16 digit angka!");
        }

        // Cek apakah NIK sudah ada (kecuali untuk data ini sendiri)
        $check_nik = query("SELECT NIK FROM tabel_kependudukan WHERE NIK = '$NIK' AND id != $id");
        if (!empty($check_nik)) {
            throw new Exception("NIK $NIK sudah terdaftar!");
        }

        // Update data kependudukan
        $sql = "UPDATE tabel_kependudukan SET
                NO_KK = '$NO_KK',
                NIK = '$NIK',
                NAMA_LGKP = '$NAMA_LGKP',
                NAMA_PANGGILAN = '$NAMA_PANGGILAN',
                HBKEL = '$HBKEL',
                JK = '$JK',
                TMPT_LHR = '$TMPT_LHR',
                TGL_LHR = '$TGL_LHR',
                AGAMA = '$AGAMA',
                STATUS_KAWIN = '$STATUS_KAWIN',
                PENDIDIKAN = '$PENDIDIKAN',
                PEKERJAAN = '$PEKERJAAN',
                NAMA_LGKP_AYAH = '$NAMA_LGKP_AYAH',
                NAMA_LGKP_IBU = '$NAMA_LGKP_IBU',
                KECAMATAN = '$KECAMATAN',
                KELURAHAN = '$KELURAHAN',
                DSN = '$DSN',
                rt = '$rt',
                rw = '$rw',
                ALAMAT = '$ALAMAT',
                GOL_DARAH = '$GOL_DARAH',
                KEWARGANEGARAAN = '$KEWARGANEGARAAN',
                STATUS_TINGGAL = '$STATUS_TINGGAL',
                DISABILITAS = '$DISABILITAS',
                JENIS_DISABILITAS = '$JENIS_DISABILITAS',
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $id";

        if ($conn->query($sql)) {
            // Handle file uploads
            if ($STATUS_KAWIN === 'KAWIN' && isset($_FILES['buku_nikah']) && $_FILES['buku_nikah']['error'] != 4) {
                uploadFile($_FILES['buku_nikah'], $id, 'BUKU_NIKAH');
            }

            if ($STATUS_TINGGAL === 'MENINGGAL' && isset($_FILES['akta_kematian']) && $_FILES['akta_kematian']['error'] != 4) {
                uploadFile($_FILES['akta_kematian'], $id, 'AKTA_KEMATIAN');
            }

            if ($STATUS_TINGGAL === 'PINDAH' && isset($_FILES['akta_pindah']) && $_FILES['akta_pindah']['error'] != 4) {
                uploadFile($_FILES['akta_pindah'], $id, 'AKTA_PINDAH');
            }

            // Handle dokumen lainnya
            if (isset($_FILES['dokumen_lainnya']) && $_FILES['dokumen_lainnya']['error'] != 4) {
                uploadFile($_FILES['dokumen_lainnya'], $id, 'LAINNYA');
            }

            if (!empty($upload_errors)) {
                $error = "Terjadi Kesalahan. " . implode(', ', $upload_errors);
            } else {
                $_SESSION['success'] = "Data penduduk berhasil diperbarui!";
                header("Location: list.php");
                exit();
            }
        } else {
            throw new Exception("Gagal memperbarui data: " . $conn->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Hitung usia dari tanggal lahir
$tgl_lahir = new DateTime($data['TGL_LHR']);
$today = new DateTime();
$usia = $today->diff($tgl_lahir)->y;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Penduduk</title>
    <?php include_once '../includes/css.php'; ?>
    <style>
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

        .conditional-field {
            display: none;
        }

        .conditional-field.show {
            display: block;
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

        .info-box {
            background-color: #e7f3ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }

        .info-box h6 {
            color: #0d6efd;
            margin-bottom: 10px;
        }

        .dokumen-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
        }

        .dokumen-card .dokumen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .dokumen-badge {
            background-color: #0d6efd;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }

        .file-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }

        .file-link {
            display: inline-block;
            margin-top: 5px;
            padding: 5px 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #0d6efd;
        }

        .file-link:hover {
            background-color: #e9ecef;
        }

        .delete-dokumen {
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2em;
        }

        .conditional-upload {
            display: none;
        }

        .conditional-upload.show {
            display: block;
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
                    <h2>Ubah Data Kependudukan</h2>
                    <div>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="ti ti-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">

                        <div class="card-body">
                            <!-- Informasi Data -->
                            <div class="info-box">
                                <h6>Informasi Data</h6>
                                <div class="row">
                                    <div class="col-md-3">
                                        <small class="text-muted">ID:</small><br>
                                        <strong>#<?= $id ?></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">NIK:</small><br>
                                        <strong><?= $data['NIK'] ?></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Nama:</small><br>
                                        <strong><?= htmlspecialchars($data['NAMA_LGKP']) ?></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Usia:</small><br>
                                        <strong><?= $usia ?> tahun</strong>
                                    </div>
                                </div>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> <?= htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Berhasil!</strong> <?= htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="post" id="formEditPenduduk" enctype="multipart/form-data">
                                <div class="row">
                                    <!-- Data Pribadi -->
                                    <div class="col-md-12">
                                        <div class="form-section">
                                            <h5><i class="ti ti-user"></i> Data Pribadi</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="NO_KK" class="form-label form-required">No. Kartu Keluarga</label>
                                                    <input type="text" id="NO_KK" name="NO_KK" class="form-control"
                                                        pattern="[0-9]{16}" maxlength="16" required
                                                        placeholder="16 digit angka"
                                                        value="<?= htmlspecialchars($form_data['NO_KK']) ?>">
                                                    <small class="text-muted">16 digit angka tanpa spasi</small>
                                                    <div id="error-no_kk" class="invalid-feedback"></div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="NIK" class="form-label form-required">NIK</label>
                                                    <input type="text" id="NIK" name="NIK" class="form-control"
                                                        pattern="[0-9]{16}" maxlength="16" required
                                                        placeholder="16 digit angka"
                                                        value="<?= htmlspecialchars($form_data['NIK']) ?>">
                                                    <small class="text-muted">16 digit NIK</small>
                                                    <div id="error-nik" class="invalid-feedback"></div>
                                                </div>

                                                <div class="col-md-8 mb-3">
                                                    <label for="NAMA_LGKP" class="form-label form-required">Nama Lengkap</label>
                                                    <input type="text" id="NAMA_LGKP" name="NAMA_LGKP" class="form-control" required
                                                        value="<?= htmlspecialchars($form_data['NAMA_LGKP']) ?>">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="NAMA_PANGGILAN" class="form-label">Nama Panggilan</label>
                                                    <input type="text" id="NAMA_PANGGILAN" name="NAMA_PANGGILAN" class="form-control"
                                                        value="<?= htmlspecialchars($form_data['NAMA_PANGGILAN']) ?>">
                                                </div>

                                                <div class="col-md-3 mb-3">
                                                    <label class="form-label form-required">Jenis Kelamin</label><br>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="JK" id="jk_l" value="L" required
                                                            <?= $form_data['JK'] == 'L' ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="jk_l">Laki-laki</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="JK" id="jk_p" value="P" required
                                                            <?= $form_data['JK'] == 'P' ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="jk_p">Perempuan</label>
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="TMPT_LHR" class="form-label form-required">Tempat Lahir</label>
                                                    <input type="text" id="TMPT_LHR" name="TMPT_LHR" class="form-control" required
                                                        value="<?= htmlspecialchars($form_data['TMPT_LHR']) ?>">
                                                </div>

                                                <div class="col-md-5 mb-3">
                                                    <label for="TGL_LHR" class="form-label form-required">Tanggal Lahir</label>
                                                    <input type="date" id="TGL_LHR" name="TGL_LHR" class="form-control"
                                                        max="<?= date('Y-m-d'); ?>" required
                                                        value="<?= htmlspecialchars($form_data['TGL_LHR']) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Status -->
                                    <div class="col-md-12">
                                        <div class="form-section">
                                            <h5><i class="ti ti-id-badge"></i> Status & Informasi</h5>
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label for="AGAMA" class="form-label form-required">Agama</label>
                                                    <select id="AGAMA" name="AGAMA" class="form-select" required>
                                                        <option value="">Pilih Agama</option>
                                                        <?php foreach ($agama_options as $agama): ?>
                                                            <option value="<?= $agama ?>" <?= $form_data['AGAMA'] == $agama ? 'selected' : '' ?>>
                                                                <?= $agama ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="STATUS_KAWIN" class="form-label form-required">Status Perkawinan</label>
                                                    <select id="STATUS_KAWIN" name="STATUS_KAWIN" class="form-select" required>
                                                        <option value="">Pilih Status</option>
                                                        <?php foreach ($status_kawin_options as $status): ?>
                                                            <option value="<?= $status ?>" <?= $form_data['STATUS_KAWIN'] == $status ? 'selected' : '' ?>>
                                                                <?= $status ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-4 mb-3 conditional-upload" id="upload_buku_nikah">
                                                    <label for="buku_nikah" class="form-label">Upload Buku Nikah</label>
                                                    <input type="file" id="buku_nikah" name="buku_nikah" class="form-control"
                                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                                                    <small class="text-muted">Format: PDF, JPG, PNG, GIF, DOC, DOCX (Max 5MB)</small>
                                                    <div class="mt-2">
                                                        <label for="nomor_dokumen[BUKU_NIKAH]" class="form-label small">Nomor Buku Nikah</label>
                                                        <input type="text" id="nomor_dokumen[BUKU_NIKAH]" name="nomor_dokumen[BUKU_NIKAH]"
                                                            class="form-control form-control-sm">
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="PENDIDIKAN" class="form-label">Pendidikan Terakhir</label>
                                                    <select id="PENDIDIKAN" name="PENDIDIKAN" class="form-select">
                                                        <option value="">Pilih Pendidikan</option>
                                                        <?php foreach ($pendidikan_options as $pendidikan): ?>
                                                            <option value="<?= $pendidikan ?>" <?= $form_data['PENDIDIKAN'] == $pendidikan ? 'selected' : '' ?>>
                                                                <?= $pendidikan ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="PEKERJAAN" class="form-label">Pekerjaan</label>
                                                    <input type="text" id="PEKERJAAN" name="PEKERJAAN" class="form-control"
                                                        value="<?= htmlspecialchars($form_data['PEKERJAAN']) ?>">
                                                </div>

                                                <div class="col-md-2 mb-3">
                                                    <label for="KEWARGANEGARAAN" class="form-label form-required">Kewarganegaraan</label>
                                                    <select id="KEWARGANEGARAAN" name="KEWARGANEGARAAN" class="form-select" required>
                                                        <?php foreach ($kewarganegaraan_options as $kwn): ?>
                                                            <option value="<?= $kwn ?>" <?= $form_data['KEWARGANEGARAAN'] == $kwn ? 'selected' : '' ?>>
                                                                <?= $kwn ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-2 mb-3">
                                                    <label for="STATUS_TINGGAL" class="form-label form-required">Status Tinggal</label>
                                                    <select id="STATUS_TINGGAL" name="STATUS_TINGGAL" class="form-select" required>
                                                        <?php foreach ($status_tinggal_options as $status): ?>
                                                            <option value="<?= $status ?>" <?= $form_data['STATUS_TINGGAL'] == $status ? 'selected' : '' ?>>
                                                                <?= $status ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-4 mb-3 conditional-upload" id="upload_akta_kematian">
                                                    <label for="akta_kematian" class="form-label">Upload Akta Kematian</label>
                                                    <input type="file" id="akta_kematian" name="akta_kematian" class="form-control"
                                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                                                    <small class="text-muted">Format: PDF, JPG, PNG, GIF, DOC, DOCX (Max 5MB)</small>
                                                    <div class="mt-2">
                                                        <label for="tanggal_dokumen[AKTA_KEMATIAN]" class="form-label small">Tanggal Kematian</label>
                                                        <input type="date" id="tanggal_dokumen[AKTA_KEMATIAN]" name="tanggal_dokumen[AKTA_KEMATIAN]"
                                                            class="form-control form-control-sm">
                                                    </div>
                                                </div>

                                                <div class="col-md-4 mb-3 conditional-upload" id="upload_akta_pindah">
                                                    <label for="akta_pindah" class="form-label">Upload Akta Pindah</label>
                                                    <input type="file" id="akta_pindah" name="akta_pindah" class="form-control"
                                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                                                    <small class="text-muted">Format: PDF, JPG, PNG, GIF, DOC, DOCX (Max 5MB)</small>
                                                    <div class="mt-2">
                                                        <label for="tanggal_dokumen[AKTA_PINDAH]" class="form-label small">Tanggal Pindah</label>
                                                        <input type="date" id="tanggal_dokumen[AKTA_PINDAH]" name="tanggal_dokumen[AKTA_PINDAH]"
                                                            class="form-control form-control-sm">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dokumen yang sudah diupload -->
                                    <?php if (!empty($dokumen_list)): ?>
                                        <div class="col-md-12">
                                            <div class="form-section">
                                                <h5><i class="ti ti-files"></i> Dokumen Terlampir</h5>
                                                <div class="row" id="dokumen-container">
                                                    <?php foreach ($dokumen_list as $dokumen): ?>
                                                        <div class="col-md-6 dokumen-item" data-id="<?= $dokumen['id'] ?>">
                                                            <div class="dokumen-card">
                                                                <div class="dokumen-header">
                                                                    <div>
                                                                        <span class="dokumen-badge"><?= $jenis_dokumen_options[$dokumen['jenis_dokumen']] ?></span>
                                                                        <h6 class="mt-2 mb-0"><?= htmlspecialchars($dokumen['original_name']) ?></h6>
                                                                    </div>
                                                                    <a href="#" class="delete-dokumen" onclick="deleteDokumen(<?= $dokumen['id'] ?>, this)">
                                                                        <i class="ti ti-trash"></i>
                                                                    </a>
                                                                </div>
                                                                <?php if ($dokumen['nomor_dokumen']): ?>
                                                                    <p class="mb-1"><small>No. Dokumen: <?= htmlspecialchars($dokumen['nomor_dokumen']) ?></small></p>
                                                                <?php endif; ?>
                                                                <?php if ($dokumen['tanggal_dokumen']): ?>
                                                                    <p class="mb-1"><small>Tanggal: <?= $dokumen['tanggal_dokumen'] ?></small></p>
                                                                <?php endif; ?>
                                                                <a href="<?= $base_url . '/' . $dokumen['path'] ?>" target="_blank" class="file-link">
                                                                    <i class="ti ti-eye"></i> Lihat Dokumen
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Dokumen Lainnya -->
                                    <div class="col-md-12">
                                        <div class="form-section">
                                            <h5><i class="ti ti-upload"></i> Dokumen Lainnya</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="dokumen_lainnya" class="form-label">Upload Dokumen Lainnya</label>
                                                    <input type="file" id="dokumen_lainnya" name="dokumen_lainnya" class="form-control"
                                                        accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx">
                                                    <small class="text-muted">Format: PDF, JPG, PNG, GIF, DOC, DOCX (Max 5MB)</small>
                                                    <div class="mt-2">
                                                        <label for="keterangan[LAINNYA]" class="form-label small">Keterangan</label>
                                                        <input type="text" id="keterangan[LAINNYA]" name="keterangan[LAINNYA]"
                                                            class="form-control form-control-sm" placeholder="Keterangan dokumen">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Keluarga -->
                                    <div class="col-md-12">
                                        <div class="form-section">
                                            <h5><i class="ti ti-users"></i> Data Keluarga</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="HBKEL" class="form-label form-required">Hubungan dalam Keluarga</label>
                                                    <select id="HBKEL" name="HBKEL" class="form-select" required>
                                                        <option value="">Pilih Hubungan</option>
                                                        <?php foreach ($hubungan_options as $hubungan): ?>
                                                            <option value="<?= $hubungan ?>" <?= $form_data['HBKEL'] == $hubungan ? 'selected' : '' ?>>
                                                                <?= $hubungan ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="GOL_DARAH" class="form-label">Golongan Darah</label>
                                                    <select id="GOL_DARAH" name="GOL_DARAH" class="form-select">
                                                        <?php foreach ($gol_darah_options as $gol): ?>
                                                            <option value="<?= $gol ?>" <?= $form_data['GOL_DARAH'] == $gol ? 'selected' : '' ?>>
                                                                <?= $gol ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="NAMA_LGKP_AYAH" class="form-label form-required">Nama Ayah</label>
                                                    <input type="text" id="NAMA_LGKP_AYAH" name="NAMA_LGKP_AYAH" class="form-control" required
                                                        value="<?= htmlspecialchars($form_data['NAMA_LGKP_AYAH']) ?>">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="NAMA_LGKP_IBU" class="form-label form-required">Nama Ibu</label>
                                                    <input type="text" id="NAMA_LGKP_IBU" name="NAMA_LGKP_IBU" class="form-control" required
                                                        value="<?= htmlspecialchars($form_data['NAMA_LGKP_IBU']) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Data Alamat -->
                                    <div class="col-md-12">
                                        <div class="form-section">
                                            <h5><i class="ti ti-map-pin"></i> Alamat</h5>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="KECAMATAN" class="form-label form-required">Kecamatan</label>
                                                    <input type="text" id="KECAMATAN" name="KECAMATAN" class="form-control" required
                                                        value="<?= htmlspecialchars($form_data['KECAMATAN']) ?>">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="KELURAHAN" class="form-label form-required">Kelurahan/Desa</label>
                                                    <input type="text" id="KELURAHAN" name="KELURAHAN" class="form-control" required
                                                        value="<?= htmlspecialchars($form_data['KELURAHAN']) ?>">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="DSN" class="form-label form-required">Dusun</label>
                                                    <select id="DSN" name="DSN" class="form-select" required>
                                                        <option value="">Pilih Dusun</option>
                                                        <?php foreach ($dusun_options as $dusun): ?>
                                                            <option value="<?= $dusun['id'] ?>" <?= $form_data['DSN'] == $dusun['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($dusun['dusun']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-2 mb-3">
                                                    <label for="rt" class="form-label">RT</label>
                                                    <input type="text" id="rt" name="rt" class="form-control" maxlength="3"
                                                        value="<?= htmlspecialchars($form_data['rt']) ?>">
                                                </div>

                                                <div class="col-md-2 mb-3">
                                                    <label for="rw" class="form-label">RW</label>
                                                    <input type="text" id="rw" name="rw" class="form-control" maxlength="3"
                                                        value="<?= htmlspecialchars($form_data['rw']) ?>">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <div class="row">
                                                        <div class="col-md-12 mb-2">
                                                            <label class="form-label">Disabilitas</label><br>
                                                            <?php foreach ($disabilitas_options as $disabilitas): ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="DISABILITAS"
                                                                        id="disabilitas_<?= strtolower($disabilitas) ?>"
                                                                        value="<?= $disabilitas ?>"
                                                                        <?= $form_data['DISABILITAS'] == $disabilitas ? 'checked' : '' ?>>
                                                                    <label class="form-check-label" for="disabilitas_<?= strtolower($disabilitas) ?>">
                                                                        <?= $disabilitas ?>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3 conditional-field" id="jenis_disabilitas_field">
                                                    <label for="JENIS_DISABILITAS" class="form-label">Jenis Disabilitas</label>
                                                    <input type="text" id="JENIS_DISABILITAS" name="JENIS_DISABILITAS" class="form-control"
                                                        placeholder="Jenis disabilitas"
                                                        value="<?= htmlspecialchars($form_data['JENIS_DISABILITAS']) ?>">
                                                </div>

                                                <div class="col-12 mb-3">
                                                    <label for="ALAMAT" class="form-label">Alamat Lengkap</label>
                                                    <textarea id="ALAMAT" name="ALAMAT" class="form-control" rows="3"><?= htmlspecialchars($form_data['ALAMAT']) ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <div>
                                        <a href="list.php" class="btn btn-secondary">
                                            <i class="ti ti-x"></i> Batal
                                        </a>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-danger" onclick="confirmReset()">
                                            <i class="ti ti-refresh"></i> Reset Form
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-check"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include_once '../includes/footer.php'; ?>
    <?php include_once '../includes/js.php'; ?>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Event delegation for delete buttons
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-dokumen')) {
                    e.preventDefault();
                    const deleteBtn = e.target.closest('.delete-dokumen');
                    const dokumenId = deleteBtn.closest('.dokumen-item').dataset.id;
                    deleteDokumen(dokumenId, deleteBtn);
                }
            });

            // Handle conditional fields for disabilitas
            const disabilitasYa = document.getElementById('disabilitas_ya');
            const disabilitasTidak = document.getElementById('disabilitas_tidak');
            const jenisDisabilitasField = document.getElementById('jenis_disabilitas_field');

            function toggleDisabilitasField() {
                if (disabilitasYa.checked) {
                    jenisDisabilitasField.classList.add('show');
                } else {
                    jenisDisabilitasField.classList.remove('show');
                }
            }

            // Handle conditional fields for status tinggal dan kawin
            const statusKawinSelect = document.getElementById('STATUS_KAWIN');
            const statusTinggalSelect = document.getElementById('STATUS_TINGGAL');
            const uploadBukuNikah = document.getElementById('upload_buku_nikah');
            const uploadAktaKematian = document.getElementById('upload_akta_kematian');
            const uploadAktaPindah = document.getElementById('upload_akta_pindah');

            function toggleUploadFields() {
                // Toggle buku nikah
                if (statusKawinSelect.value === 'KAWIN') {
                    uploadBukuNikah.classList.add('show');
                } else {
                    uploadBukuNikah.classList.remove('show');
                }

                // Toggle akta kematian
                if (statusTinggalSelect.value === 'MENINGGAL') {
                    uploadAktaKematian.classList.add('show');
                } else {
                    uploadAktaKematian.classList.remove('show');
                }

                // Toggle akta pindah
                if (statusTinggalSelect.value === 'PINDAH') {
                    uploadAktaPindah.classList.add('show');
                } else {
                    uploadAktaPindah.classList.remove('show');
                }
            }

            // Initial toggle
            toggleDisabilitasField();
            toggleUploadFields();

            // Add event listeners
            disabilitasYa.addEventListener('change', toggleDisabilitasField);
            disabilitasTidak.addEventListener('change', toggleDisabilitasField);
            statusKawinSelect.addEventListener('change', toggleUploadFields);
            statusTinggalSelect.addEventListener('change', toggleUploadFields);

            // Format input numbers for NIK and KK
            function formatNumberInput(input) {
                input.addEventListener('input', function(e) {
                    let value = this.value.replace(/\D/g, '');
                    this.value = value;
                });
            }

            // Format KK and NIK inputs
            formatNumberInput(document.getElementById('NO_KK'));
            formatNumberInput(document.getElementById('NIK'));

            // Auto-calculate age from date of birth
            const tglLahirInput = document.getElementById('TGL_LHR');
            tglLahirInput.addEventListener('change', function() {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }

                // Show age info
                if (age >= 0) {
                    const ageInfo = document.createElement('small');
                    ageInfo.className = 'form-text text-info';
                    ageInfo.id = 'age-info';
                    ageInfo.textContent = `Usia: ${age} tahun`;

                    const existingInfo = document.getElementById('age-info');
                    if (existingInfo) {
                        existingInfo.remove();
                    }

                    tglLahirInput.parentNode.appendChild(ageInfo);
                }
            });

            // Validate NIK and KK on blur
            document.getElementById('NIK').addEventListener('blur', function() {
                const nik = this.value.trim();
                const errorDiv = document.getElementById('error-nik');

                if (nik.length !== 16) {
                    this.classList.add('is-invalid');
                    errorDiv.textContent = 'NIK harus 16 digit angka';
                } else if (!/^\d+$/.test(nik)) {
                    this.classList.add('is-invalid');
                    errorDiv.textContent = 'NIK harus angka saja';
                } else {
                    this.classList.remove('is-invalid');
                    errorDiv.textContent = '';
                }
            });

            document.getElementById('NO_KK').addEventListener('blur', function() {
                const kk = this.value.trim();
                const errorDiv = document.getElementById('error-no_kk');

                if (kk.length !== 16) {
                    this.classList.add('is-invalid');
                    errorDiv.textContent = 'No. KK harus 16 digit angka';
                } else if (!/^\d+$/.test(kk)) {
                    this.classList.add('is-invalid');
                    errorDiv.textContent = 'No. KK harus angka saja';
                } else {
                    this.classList.remove('is-invalid');
                    errorDiv.textContent = '';
                }
            });

            // Validate file size
            function validateFileSize(fileInput, maxSizeMB) {
                if (fileInput.files.length > 0) {
                    const fileSize = fileInput.files[0].size;
                    const maxSize = maxSizeMB * 1024 * 1024;

                    if (fileSize > maxSize) {
                        alert(`Ukuran file terlalu besar. Maksimal ${maxSizeMB}MB.`);
                        fileInput.value = '';
                        return false;
                    }
                }
                return true;
            }

            // Add file validation
            document.getElementById('buku_nikah')?.addEventListener('change', function() {
                validateFileSize(this, 5);
            });

            document.getElementById('akta_kematian')?.addEventListener('change', function() {
                validateFileSize(this, 5);
            });

            document.getElementById('akta_pindah')?.addEventListener('change', function() {
                validateFileSize(this, 5);
            });

            document.getElementById('dokumen_lainnya')?.addEventListener('change', function() {
                validateFileSize(this, 5);
            });

            // Check if there's an error and scroll to it
            <?php if ($error): ?>
                window.scrollTo(0, 0);
            <?php endif; ?>
        });

        // Function to delete dokumen - global function
        window.deleteDokumen = function(dokumenId, element) {
            Swal.fire({
                title: 'Hapus Dokumen?',
                text: 'Dokumen akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX request
                    const formData = new FormData();
                    formData.append('dokumen_id', dokumenId);
                    formData.append('penduduk_id', <?= $id ?>);

                    fetch('delete_dokumen.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                const card = element.closest('.dokumen-item');
                                card.classList.add('fade-out');

                                setTimeout(() => {
                                    card.remove();
                                    Swal.fire(
                                        'Berhasil!',
                                        'Dokumen telah dihapus.',
                                        'success'
                                    );

                                    // Reload page if no documents left
                                    if (document.querySelectorAll('.dokumen-item').length === 0) {
                                        setTimeout(() => location.reload(), 1500);
                                    }
                                }, 500);
                            } else {
                                Swal.fire(
                                    'Gagal!',
                                    data.message || 'Gagal menghapus dokumen.',
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire(
                                'Error!',
                                'Terjadi kesalahan saat menghapus dokumen.',
                                'error'
                            );
                        });
                }
            });

            // Prevent default
            return false;
        };

        // Confirm form reset
        function confirmReset() {
            Swal.fire({
                title: 'Reset Form?',
                text: 'Semua perubahan yang belum disimpan akan hilang.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Reset!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Reload page to get original data
                    window.location.reload();
                }
            });
        }

        // Allow only numbers for KK and NIK
        function isNumberKey(evt) {
            const charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        // Form validation before submit
        document.getElementById('formEditPenduduk').addEventListener('submit', function(e) {
            let isValid = true;

            // Validate NIK length
            const nik = document.getElementById('NIK').value.trim();
            if (nik.length !== 16 || !/^\d+$/.test(nik)) {
                e.preventDefault();
                alert('NIK harus 16 digit angka');
                document.getElementById('NIK').focus();
                return false;
            }

            // Validate KK length
            const kk = document.getElementById('NO_KK').value.trim();
            if (kk.length !== 16 || !/^\d+$/.test(kk)) {
                e.preventDefault();
                alert('No. KK harus 16 digit angka');
                document.getElementById('NO_KK').focus();
                return false;
            }

            // Validate file sizes
            const bukuNikah = document.getElementById('buku_nikah');
            const aktaKematian = document.getElementById('akta_kematian');
            const aktaPindah = document.getElementById('akta_pindah');
            const dokumenLainnya = document.getElementById('dokumen_lainnya');

            if (bukuNikah && bukuNikah.files.length > 0) {
                if (bukuNikah.files[0].size > 5 * 1024 * 1024) {
                    e.preventDefault();
                    alert('Ukuran file Buku Nikah maksimal 5MB');
                    return false;
                }
            }

            if (aktaKematian && aktaKematian.files.length > 0) {
                if (aktaKematian.files[0].size > 5 * 1024 * 1024) {
                    e.preventDefault();
                    alert('Ukuran file Akta Kematian maksimal 5MB');
                    return false;
                }
            }

            if (aktaPindah && aktaPindah.files.length > 0) {
                if (aktaPindah.files[0].size > 5 * 1024 * 1024) {
                    e.preventDefault();
                    alert('Ukuran file Akta Pindah maksimal 5MB');
                    return false;
                }
            }

            if (dokumenLainnya && dokumenLainnya.files.length > 0) {
                if (dokumenLainnya.files[0].size > 5 * 1024 * 1024) {
                    e.preventDefault();
                    alert('Ukuran file Dokumen Lainnya maksimal 5MB');
                    return false;
                }
            }

            // Check required fields
            const requiredFields = document.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Mohon lengkapi semua field yang wajib diisi (ditandai dengan *)');
                return false;
            }

            return true;
        });
    </script>
</body>

</html>