<?php
// add.php
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

// Ambil data untuk dropdown dusun
$sql_dusun = "SELECT * FROM tabel_dusun ORDER BY dusun";
$dusun_options = query($sql_dusun);

// Data untuk dropdown
$agama_options = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU', 'LAINNYA'];
$hubungan_options = ['KEPALA KELUARGA', 'ISTRI', 'ANAK', 'FAMILI LAIN'];
$pendidikan_options = ['TIDAK/BELUM SEKOLAH', 'SD/SEDERAJAT', 'SMP/SEDERAJAT', 'SMA/SEDERAJAT', 'D1/D2/D3', 'S1', 'S2', 'S3'];
$kewarganegaraan_options = ['WNI', 'WNA'];
$status_tinggal_options = ['TETAP', 'SEMENTARA', 'PENDATANG'];
$gol_darah_options = ['A', 'B', 'AB', 'O', 'TIDAK TAHU'];
$disabilitas_options = ['YA', 'TIDAK'];
$status_kawin_options = ['BELUM KAWIN', 'KAWIN', 'CERAI HIDUP', 'CERAI MATI'];

$error = '';
$success = '';

// Inisialisasi variabel untuk menyimpan data form jika ada error
$form_data = [
    'NO_KK' => '',
    'NIK' => '',
    'NAMA_LGKP' => '',
    'NAMA_PANGGILAN' => '',
    'HBKEL' => '',
    'JK' => '',
    'TMPT_LHR' => '',
    'TGL_LHR' => '',
    'AGAMA' => '',
    'STATUS_KAWIN' => 'BELUM KAWIN',
    'PENDIDIKAN' => '',
    'PEKERJAAN' => '',
    'NAMA_LGKP_AYAH' => '',
    'NAMA_LGKP_IBU' => '',
    'KECAMATAN' => '',
    'KELURAHAN' => '',
    'DSN' => '',
    'rt' => '',
    'rw' => '',
    'ALAMAT' => '',
    'GOL_DARAH' => 'TIDAK TAHU',
    'KEWARGANEGARAAN' => 'WNI',
    'STATUS_TINGGAL' => 'TETAP',
    'DISABILITAS' => 'TIDAK',
    'JENIS_DISABILITAS' => ''
];

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

        // Cek apakah NIK sudah ada
        $check_nik = query("SELECT NIK FROM tabel_kependudukan WHERE NIK = '$NIK'");
        if (!empty($check_nik)) {
            throw new Exception("NIK $NIK sudah terdaftar!");
        }

        // Insert data kependudukan
        $sql = "INSERT INTO tabel_kependudukan (
            NO_KK, NIK, NAMA_LGKP, NAMA_PANGGILAN, HBKEL, JK, TMPT_LHR, TGL_LHR, 
            AGAMA, STATUS_KAWIN, PENDIDIKAN, PEKERJAAN, NAMA_LGKP_AYAH, NAMA_LGKP_IBU,
            KECAMATAN, KELURAHAN, DSN, rt, rw, ALAMAT, GOL_DARAH, KEWARGANEGARAAN, 
            STATUS_TINGGAL, DISABILITAS, JENIS_DISABILITAS
        ) VALUES (
            '$NO_KK', '$NIK', '$NAMA_LGKP', '$NAMA_PANGGILAN', '$HBKEL', '$JK', '$TMPT_LHR', '$TGL_LHR',
            '$AGAMA', '$STATUS_KAWIN', '$PENDIDIKAN', '$PEKERJAAN', '$NAMA_LGKP_AYAH', '$NAMA_LGKP_IBU',
            '$KECAMATAN', '$KELURAHAN', '$DSN', '$rt', '$rw', '$ALAMAT', '$GOL_DARAH', '$KEWARGANEGARAAN',
            '$STATUS_TINGGAL', '$DISABILITAS', '$JENIS_DISABILITAS'
        )";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Data penduduk berhasil ditambahkan!";
            header("Location: list.php");
            exit();
        } else {
            throw new Exception("Gagal menyimpan data: " . $conn->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Penduduk</title>
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
                    <div>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="ti ti-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>


                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> <?= htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Sukses!</strong> <?= htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="post" id="formPenduduk">
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
                                                    <input type="text"
                                                        id="NAMA_LGKP"
                                                        name="NAMA_LGKP"
                                                        class="form-control"
                                                        required
                                                        value="<?= htmlspecialchars($form_data['NAMA_LGKP']) ?>"
                                                        oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label for="NAMA_PANGGILAN" class="form-label">Nama Panggilan</label>
                                                    <input type="text"
                                                        id="NAMA_PANGGILAN"
                                                        name="NAMA_PANGGILAN"
                                                        class="form-control"
                                                        value="<?= htmlspecialchars($form_data['NAMA_PANGGILAN']) ?>"
                                                        oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
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

                                                <div class="col-md-6 mb-3">
                                                    <label for="PEKERJAAN" class="form-label">Pekerjaan</label>
                                                    <input type="text" id="PEKERJAAN" name="PEKERJAAN" class="form-control"
                                                        value="<?= htmlspecialchars($form_data['PEKERJAAN']) ?>">
                                                </div>

                                                <div class="col-md-3 mb-3">
                                                    <label for="KEWARGANEGARAAN" class="form-label form-required">Kewarganegaraan</label>
                                                    <select id="KEWARGANEGARAAN" name="KEWARGANEGARAAN" class="form-select" required>
                                                        <?php foreach ($kewarganegaraan_options as $kwn): ?>
                                                            <option value="<?= $kwn ?>" <?= $form_data['KEWARGANEGARAAN'] == $kwn ? 'selected' : '' ?>>
                                                                <?= $kwn ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-3 mb-3">
                                                    <label for="STATUS_TINGGAL" class="form-label form-required">Status Tinggal</label>
                                                    <select id="STATUS_TINGGAL" name="STATUS_TINGGAL" class="form-select" required>
                                                        <?php foreach ($status_tinggal_options as $status): ?>
                                                            <option value="<?= $status ?>" <?= $form_data['STATUS_TINGGAL'] == $status ? 'selected' : '' ?>>
                                                                <?= $status ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
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
                                                    <input type="text"
                                                        id="NAMA_LGKP_AYAH"
                                                        name="NAMA_LGKP_AYAH"
                                                        class="form-control"
                                                        required
                                                        value="<?= htmlspecialchars($form_data['NAMA_LGKP_AYAH']) ?>"
                                                        oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="NAMA_LGKP_IBU" class="form-label form-required">Nama Ibu</label>
                                                    <input type="text"
                                                        id="NAMA_LGKP_IBU"
                                                        name="NAMA_LGKP_IBU"
                                                        class="form-control"
                                                        required
                                                        value="<?= htmlspecialchars($form_data['NAMA_LGKP_IBU']) ?>"
                                                        oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')">
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
                                    <a href="list.php" class="btn btn-secondary">
                                        <i class="ti ti-x"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-check"></i> Simpan Data
                                    </button>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            // Initial toggle
            toggleDisabilitasField();

            // Add event listeners
            disabilitasYa.addEventListener('change', toggleDisabilitasField);
            disabilitasTidak.addEventListener('change', toggleDisabilitasField);

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

            // Check if there's an error and scroll to it
            <?php if ($error): ?>
                window.scrollTo(0, 0);
            <?php endif; ?>
        });

        // Allow only numbers for KK and NIK
        function isNumberKey(evt) {
            const charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        // Form validation before submit
        document.getElementById('formPenduduk').addEventListener('submit', function(e) {
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