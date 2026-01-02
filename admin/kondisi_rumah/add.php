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

// Data untuk dropdown
$jenis_lantai_options = ['KERAMIK', 'SEMEN', 'TANAH', 'KAYU', 'LAINNYA'];
$jenis_dinding_options = ['TEMBOK', 'KAYU', 'BAMBU', 'LAINNYA'];
$fasilitas_mck_options = ['JAMBAN SENDIRI', 'JAMBAN BERSAMA', 'TIDAK ADA'];
$sumber_penerangan_options = ['PLN', 'GENSET', 'LAMPU MINYAK', 'TIDAK ADA'];
$sumber_air_minum_options = ['PDAM', 'SUMUR BOR', 'MATA AIR', 'AIR KEMASAN', 'LAINNYA'];
$bahan_bakar_options = ['GAS', 'KAYU BAKAR', 'LISTRIK', 'MINYAK TANAH', 'LAINNYA'];
$kondisi_rumah_options = ['LAYAK HUNI', 'RUSAK RINGAN', 'RUSAK BERAT'];

$error = '';
$success = '';

// Inisialisasi variabel untuk menyimpan data form
$form_data = [
    'no_kk' => '',
    'nik' => '',
    'nama_lgkp' => '',
    'luas_lantai' => '36',
    'jenis_lantai' => 'SEMEN',
    'jenis_dinding' => 'TEMBOK',
    'fasilitas_mck' => 'JAMBAN SENDIRI',
    'sumber_penerangan' => 'PLN',
    'sumber_air_minum' => 'SUMUR BOR',
    'bahan_bakar' => 'GAS',
    'kondisi_rumah' => 'LAYAK HUNI'
];

// Data penduduk untuk autocomplete
$penduduk_options = [];

// Ambil data penduduk yang belum memiliki data rumah
$sql_penduduk = "SELECT k.NIK, k.NAMA_LGKP, k.NO_KK as NO_KK 
                 FROM tabel_kependudukan k 
                 LEFT JOIN tabel_rumah r ON k.NIK = r.NIK 
                 WHERE r.NIK IS NULL 
                 ORDER BY k.NAMA_LGKP";


$result_penduduk = query($sql_penduduk);

foreach ($result_penduduk as $row) {
    $penduduk_options[] = [
        'nik' => $row['NIK'],
        'nama' => $row['NAMA_LGKP'],
        'no_kk' => $row['NO_KK']
    ];
}

// Jika ada parameter NIK di URL, load data penduduk
if (isset($_GET['nik']) && !empty($_GET['nik'])) {
    $nik_param = $conn->real_escape_string($_GET['nik']);
    $sql_penduduk_detail = "SELECT * FROM tabel_kependudukan WHERE NIK = '$nik_param'";
    $penduduk_detail = query($sql_penduduk_detail);

    if (!empty($penduduk_detail)) {
        $penduduk = $penduduk_detail[0];
        $form_data['nik'] = $penduduk['NIK'];
        $form_data['no_kk'] = $penduduk['NO_KK'];
        $form_data['nama_lgkp'] = $penduduk['NAMA_LGKP'];
    }
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        $no_kk = $conn->real_escape_string($_POST['no_kk']);
        $nik = $conn->real_escape_string($_POST['nik']);
        $nama_lgkp = $conn->real_escape_string($_POST['nama_lgkp']);
        $luas_lantai = $conn->real_escape_string($_POST['luas_lantai']);
        $jenis_lantai = $conn->real_escape_string($_POST['jenis_lantai']);
        $jenis_dinding = $conn->real_escape_string($_POST['jenis_dinding']);
        $fasilitas_mck = $conn->real_escape_string($_POST['fasilitas_mck']);
        $sumber_penerangan = $conn->real_escape_string($_POST['sumber_penerangan']);
        $sumber_air_minum = $conn->real_escape_string($_POST['sumber_air_minum']);
        $bahan_bakar = $conn->real_escape_string($_POST['bahan_bakar']);
        $kondisi_rumah = $conn->real_escape_string($_POST['kondisi_rumah']);

        // Validasi data wajib
        if (empty($no_kk)) {
            throw new Exception("No. KK wajib diisi!");
        }

        if (empty($nik)) {
            throw new Exception("NIK wajib diisi!");
        }

        if (empty($nama_lgkp)) {
            throw new Exception("Nama wajib diisi!");
        }

        // Validasi format NIK
        if (strlen($nik) !== 16 || !is_numeric($nik)) {
            throw new Exception("NIK harus 16 digit angka!");
        }

        // Validasi format No KK
        if (strlen($no_kk) !== 16 || !is_numeric($no_kk)) {
            throw new Exception("No. KK harus 16 digit angka!");
        }

        // Cek apakah data rumah sudah ada untuk NIK ini
        $check_existing = query("SELECT * FROM tabel_rumah WHERE NIK = '$nik'");
        if (!empty($check_existing)) {
            throw new Exception("Data kondisi rumah untuk NIK $nik sudah ada!");
        }

        // Cek apakah penduduk dengan NIK tersebut ada di tabel kependudukan
        $check_penduduk = query("SELECT * FROM tabel_kependudukan WHERE NIK = '$nik'");
        if (empty($check_penduduk)) {
            throw new Exception("Penduduk dengan NIK $nik tidak ditemukan di data kependudukan!");
        }

        // Verifikasi bahwa data yang diinput sesuai dengan data kependudukan
        $penduduk_data = $check_penduduk[0];
        if ($penduduk_data['NO_KK'] !== $no_kk) {
            throw new Exception("No. KK tidak sesuai dengan data kependudukan!");
        }

        if ($penduduk_data['NAMA_LGKP'] !== $nama_lgkp) {
            throw new Exception("Nama tidak sesuai dengan data kependudukan!");
        }

        // Insert data kondisi rumah
        $sql_rumah = "INSERT INTO tabel_rumah (
            NIK, NO_KK, nama_pemilik, luas_lantai, jenis_lantai, jenis_dinding, 
            fasilitas_bab, sumber_penerangan, sumber_air_minum, bahan_bakar_memasak, 
            status_tempat_tinggal, kondisi_rumah
        ) VALUES (
            '$nik', '$no_kk', '$nama_lgkp', '$luas_lantai', '$jenis_lantai', '$jenis_dinding',
            '$fasilitas_mck', '$sumber_penerangan', '$sumber_air_minum', '$bahan_bakar',
            'MILIK SENDIRI', '$kondisi_rumah'
        )";

        if (!$conn->query($sql_rumah)) {
            throw new Exception("Gagal menyimpan data kondisi rumah: " . $conn->error);
        }

        $_SESSION['success'] = "Data kondisi rumah berhasil ditambahkan!";
        header("Location: list.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();

        // Simpan data yang diinput untuk ditampilkan kembali
        $form_data = [
            'no_kk' => $_POST['no_kk'] ?? '',
            'nik' => $_POST['nik'] ?? '',
            'nama_lgkp' => $_POST['nama_lgkp'] ?? '',
            'luas_lantai' => $_POST['luas_lantai'] ?? '36',
            'jenis_lantai' => $_POST['jenis_lantai'] ?? 'SEMEN',
            'jenis_dinding' => $_POST['jenis_dinding'] ?? 'TEMBOK',
            'fasilitas_mck' => $_POST['fasilitas_mck'] ?? 'JAMBAN SENDIRI',
            'sumber_penerangan' => $_POST['sumber_penerangan'] ?? 'PLN',
            'sumber_air_minum' => $_POST['sumber_air_minum'] ?? 'SUMUR BOR',
            'bahan_bakar' => $_POST['bahan_bakar'] ?? 'GAS',
            'kondisi_rumah' => $_POST['kondisi_rumah'] ?? 'LAYAK HUNI'
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

    .data-penduduk-info {
        background-color: #e8f4fd;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
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
                    <h2>Tambah Data Kondisi Rumah</h2>
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

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Sukses!</strong> <?= htmlspecialchars($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="card p-4 shadow-sm">
                    <!-- Pencarian Penduduk -->
                    <div class="form-section mb-4">
                        <h5><i class="ti ti-search"></i> Cari Data Penduduk</h5>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="cari_penduduk" class="form-label">Pilih Penduduk (yang belum memiliki data rumah)</label>
                                <select id="cari_penduduk" class="form-select" onchange="loadPendudukData(this.value)">
                                    <option value="">-- Pilih Nama Penduduk --</option>
                                    <?php foreach ($penduduk_options as $penduduk): ?>
                                        <option value="<?= htmlspecialchars($penduduk['nik']) ?>"
                                            data-nik="<?= htmlspecialchars($penduduk['nik']) ?>"
                                            data-no_kk="<?= htmlspecialchars($penduduk['no_kk']) ?>"
                                            data-nama="<?= htmlspecialchars($penduduk['nama']) ?>">
                                            <?= htmlspecialchars($penduduk['nama']) ?> - NIK: <?= htmlspecialchars($penduduk['nik']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Hanya menampilkan penduduk yang belum memiliki data kondisi rumah</small>
                            </div>
                        </div>
                    </div>

                    <!-- Info Data Penduduk yang Dipilih -->
                    <div id="pendudukInfo" class="data-penduduk-info" style="display: none;">
                        <h6><i class="ti ti-user-check"></i> Data Penduduk yang Dipilih</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>NIK:</strong> <span id="infoNik"></span>
                            </div>
                            <div class="col-md-4">
                                <strong>No. KK:</strong> <span id="infoNoKK"></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Nama:</strong> <span id="infoNama"></span>
                            </div>
                        </div>
                    </div>

                    <form method="post" id="formRumah">
                        <!-- Data Identitas (readonly setelah dipilih) -->
                        <div class="form-section">
                            <h5><i class="ti ti-user"></i> Data Identitas</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="no_kk" class="form-label form-required">No. Kartu Keluarga</label>
                                    <input type="text" id="no_kk" name="no_kk" class="form-control"
                                        pattern="[0-9]{16}" maxlength="16" required readonly
                                        placeholder="Pilih data penduduk terlebih dahulu"
                                        value="<?= htmlspecialchars($form_data['no_kk']) ?>">
                                    <small class="text-muted">Otomatis terisi dari data kependudukan</small>
                                    <div id="error-no_kk" class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="nik" class="form-label form-required">NIK</label>
                                    <input type="text" id="nik" name="nik" class="form-control"
                                        pattern="[0-9]{16}" maxlength="16" required readonly
                                        placeholder="Pilih data penduduk terlebih dahulu"
                                        value="<?= htmlspecialchars($form_data['nik']) ?>">
                                    <small class="text-muted">Otomatis terisi dari data kependudukan</small>
                                    <div id="error-nik" class="invalid-feedback"></div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="nama_lgkp" class="form-label form-required">Nama Lengkap Pemilik</label>
                                    <input type="text" id="nama_lgkp" name="nama_lgkp" class="form-control" required readonly
                                        placeholder="Pilih data penduduk terlebih dahulu"
                                        value="<?= htmlspecialchars($form_data['nama_lgkp']) ?>">
                                    <small class="text-muted">Otomatis terisi dari data kependudukan</small>
                                </div>
                            </div>
                        </div>

                        <!-- Data Fisik Rumah -->
                        <div class="form-section">
                            <h5><i class="ti ti-home"></i> Data Fisik Rumah</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="luas_lantai" class="form-label">Luas Lantai (m²)</label>
                                    <div class="input-group">
                                        <input type="number" id="luas_lantai" name="luas_lantai" class="form-control"
                                            min="0" step="0.01" placeholder="0.00" required
                                            value="<?= htmlspecialchars($form_data['luas_lantai']) ?>">
                                        <span class="input-group-text">m²</span>
                                    </div>
                                    <small class="text-muted">Luas total lantai rumah dalam meter persegi</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="jenis_lantai" class="form-label">Jenis Lantai</label>
                                    <select id="jenis_lantai" name="jenis_lantai" class="form-select" required>
                                        <option value="">Pilih Jenis Lantai</option>
                                        <?php foreach ($jenis_lantai_options as $jenis): ?>
                                            <option value="<?= $jenis ?>" <?= $form_data['jenis_lantai'] == $jenis ? 'selected' : '' ?>>
                                                <?= $jenis ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="jenis_dinding" class="form-label">Jenis Dinding</label>
                                    <select id="jenis_dinding" name="jenis_dinding" class="form-select" required>
                                        <option value="">Pilih Jenis Dinding</option>
                                        <?php foreach ($jenis_dinding_options as $jenis): ?>
                                            <option value="<?= $jenis ?>" <?= $form_data['jenis_dinding'] == $jenis ? 'selected' : '' ?>>
                                                <?= $jenis ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Fasilitas Rumah -->
                        <div class="form-section">
                            <h5><i class="ti ti-tools"></i> Fasilitas Rumah</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="fasilitas_mck" class="form-label">Fasilitas MCK</label>
                                    <select id="fasilitas_mck" name="fasilitas_mck" class="form-select" required>
                                        <option value="">Pilih Fasilitas MCK</option>
                                        <?php foreach ($fasilitas_mck_options as $fasilitas): ?>
                                            <option value="<?= $fasilitas ?>" <?= $form_data['fasilitas_mck'] == $fasilitas ? 'selected' : '' ?>>
                                                <?= $fasilitas ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="sumber_penerangan" class="form-label">Sumber Penerangan</label>
                                    <select id="sumber_penerangan" name="sumber_penerangan" class="form-select" required>
                                        <option value="">Pilih Sumber Penerangan</option>
                                        <?php foreach ($sumber_penerangan_options as $sumber): ?>
                                            <option value="<?= $sumber ?>" <?= $form_data['sumber_penerangan'] == $sumber ? 'selected' : '' ?>>
                                                <?= $sumber ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="sumber_air_minum" class="form-label">Sumber Air Minum</label>
                                    <select id="sumber_air_minum" name="sumber_air_minum" class="form-select" required>
                                        <option value="">Pilih Sumber Air Minum</option>
                                        <?php foreach ($sumber_air_minum_options as $sumber): ?>
                                            <option value="<?= $sumber ?>" <?= $form_data['sumber_air_minum'] == $sumber ? 'selected' : '' ?>>
                                                <?= $sumber ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="bahan_bakar" class="form-label">Bahan Bakar Memasak</label>
                                    <select id="bahan_bakar" name="bahan_bakar" class="form-select" required>
                                        <option value="">Pilih Bahan Bakar</option>
                                        <?php foreach ($bahan_bakar_options as $bahan): ?>
                                            <option value="<?= $bahan ?>" <?= $form_data['bahan_bakar'] == $bahan ? 'selected' : '' ?>>
                                                <?= $bahan ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="kondisi_rumah" class="form-label">Kondisi Rumah</label>
                                    <select id="kondisi_rumah" name="kondisi_rumah" class="form-select" required>
                                        <option value="">Pilih Kondisi Rumah</option>
                                        <?php foreach ($kondisi_rumah_options as $kondisi): ?>
                                            <option value="<?= $kondisi ?>" <?= $form_data['kondisi_rumah'] == $kondisi ? 'selected' : '' ?>>
                                                <?= $kondisi ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                <button type="submit" class="btn btn-success" id="btnSubmit" disabled>
                                    <i class="ti ti-check"></i> Simpan Data
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cek apakah sudah ada data NIK yang dipilih
        const nikValue = document.getElementById('nik').value;
        if (nikValue) {
            document.getElementById('btnSubmit').disabled = false;
            document.getElementById('pendudukInfo').style.display = 'block';
            document.getElementById('infoNik').textContent = nikValue;
            document.getElementById('infoNoKK').textContent = document.getElementById('no_kk').value;
            document.getElementById('infoNama').textContent = document.getElementById('nama_lgkp').value;
        }
    });

    // Fungsi untuk memuat data penduduk
    function loadPendudukData(nik) {
        if (!nik) {
            // Reset form jika tidak ada NIK yang dipilih
            document.getElementById('no_kk').value = '';
            document.getElementById('nik').value = '';
            document.getElementById('nama_lgkp').value = '';
            document.getElementById('btnSubmit').disabled = true;
            document.getElementById('pendudukInfo').style.display = 'none';
            return;
        }

        // Ambil data dari option yang dipilih
        const selectedOption = document.querySelector(`#cari_penduduk option[value="${nik}"]`);
        if (selectedOption) {
            const noKK = selectedOption.getAttribute('data-no_kk');
            const nama = selectedOption.getAttribute('data-nama');

            // Isi form dengan data penduduk
            document.getElementById('no_kk').value = noKK;
            document.getElementById('nik').value = nik;
            document.getElementById('nama_lgkp').value = nama;

            // Tampilkan info penduduk
            document.getElementById('pendudukInfo').style.display = 'block';
            document.getElementById('infoNik').textContent = nik;
            document.getElementById('infoNoKK').textContent = noKK;
            document.getElementById('infoNama').textContent = nama;

            // Aktifkan tombol submit
            document.getElementById('btnSubmit').disabled = false;

            // Validasi data
            validateNIK(nik);
            validateKK(noKK);
        }
    }

    // Validasi NIK
    function validateNIK(nik) {
        const errorDiv = document.getElementById('error-nik');
        const inputField = document.getElementById('nik');

        if (nik.length !== 16 || !/^\d+$/.test(nik)) {
            inputField.classList.add('is-invalid');
            errorDiv.textContent = 'NIK harus 16 digit angka';
            return false;
        } else {
            inputField.classList.remove('is-invalid');
            errorDiv.textContent = '';
            return true;
        }
    }

    // Validasi KK
    function validateKK(kk) {
        const errorDiv = document.getElementById('error-no_kk');
        const inputField = document.getElementById('no_kk');

        if (kk.length !== 16 || !/^\d+$/.test(kk)) {
            inputField.classList.add('is-invalid');
            errorDiv.textContent = 'No. KK harus 16 digit angka';
            return false;
        } else {
            inputField.classList.remove('is-invalid');
            errorDiv.textContent = '';
            return true;
        }
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
    document.getElementById('formRumah').addEventListener('submit', function(e) {
        // Validate NIK
        const nik = document.getElementById('nik').value.trim();
        if (!validateNIK(nik)) {
            e.preventDefault();
            alert('NIK harus 16 digit angka');
            document.getElementById('nik').focus();
            return false;
        }

        // Validate KK
        const kk = document.getElementById('no_kk').value.trim();
        if (!validateKK(kk)) {
            e.preventDefault();
            alert('No. KK harus 16 digit angka');
            document.getElementById('no_kk').focus();
            return false;
        }

        // Check if penduduk data is selected
        if (!nik || !kk) {
            e.preventDefault();
            alert('Silakan pilih data penduduk terlebih dahulu');
            return false;
        }

        // Check required fields
        const requiredFields = document.querySelectorAll('[required]');
        let isValid = true;

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
            alert('Mohon lengkapi semua field yang wajib diisi');
            return false;
        }

        // Confirm before submit
        if (!confirm('Apakah Anda yakin ingin menyimpan data kondisi rumah ini?')) {
            e.preventDefault();
            return false;
        }

        return true;
    });
</script>

</html>