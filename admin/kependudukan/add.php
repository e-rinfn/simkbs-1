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

// Ambil data untuk dropdown
$sql_dusun = "SELECT * FROM tabel_dusun ORDER BY dusun";
$dusun_options = query($sql_dusun);

// Data untuk dropdown
$agama_options = ['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDDHA', 'KONGHUCU', 'LAINNYA'];
$hubungan_options = ['KEPALA KELUARGA', 'ISTRI', 'ANAK', 'FAMILI LAIN'];
$pendidikan_options = ['TIDAK/BELUM SEKOLAH', 'SD/SEDERAJAT', 'SMP/SEDERAJAT', 'SMA/SEDERAJAT', 'D1/D2/D3', 'S1', 'S2', 'S3'];
$pekerjaan_options = ['TIDAK BEKERJA', 'PNS', 'TNI/POLRI', 'SWASTA', 'WIRAUSAHA', 'PETANI', 'NELAYAN', 'BURUH', 'PENSIUNAN', 'LAINNYA'];
$bahan_makanan_options = ['BERAS', 'TERIGU', 'JAGUNG', 'UMBI-UMBIAN', 'LAINNYA'];
$jenis_tabungan_options = ['BANK', 'KOPERASI', 'TABUNGAN HARIAN', 'TIDAK ADA'];
$jenis_bantuan_options = ['BLT', 'PKH', 'BANSOS', 'BANTUAN PENDIDIKAN', 'BANTUAN KESEHATAN', 'BANTUAN UMKM', 'LAINNYA'];
$lauk_pauk_options = ['SERING', 'KADANG-KADANG', 'JARANG']; // Tambahkan untuk frekuensi lauk pauk

$error = '';
$success = '';

// Inisialisasi variabel untuk menyimpan data form jika ada error
$form_data = [
    'no_kk' => '',
    'nik' => '',
    'nama_lgkp' => '',
    'nama_panggilan' => '',
    'hbkel' => '',
    'jk' => '',
    'tmpt_lhr' => '',
    'tgl_lhr' => '',
    'agama' => '',
    'status_kawin' => 'BELUM KAWIN',
    'pendidikan' => '',
    'pekerjaan' => '',
    'nama_ayah' => '',
    'nama_ibu' => '',
    'kecamatan' => '',
    'kelurahan' => '',
    'dsn' => '',
    'rt' => '',
    'rw' => '',
    'alamat' => '',
    'gol_darah' => 'TIDAK TAHU',
    'disabilitas' => 'TIDAK',
    'jenis_disabilitas' => '',
    'jenis_pekerjaan' => '',
    'bidang_pekerjaan' => '',
    'status_pekerjaan' => 'TIDAK BEKERJA',
    'penghasilan' => '0',
    'jumlah_tanggungan' => '0',
    'pendidikan_terakhir' => '',
    'bahan_makanan' => '',
    'frekuensi_per_minggu' => '3',
    'makan_per_hari' => '3',
    'pakaian_per_tahun' => '0',
    'biaya_pengobatan' => 'TERJANGKAU',
    'kepemilikan_tabungan' => 'TIDAK',
    'jenis_tabungan' => '',
    'harga_tabungan' => '0',
    'penerima_bantuan' => 'TIDAK',
    'jenis_bantuan' => ''
];

// Jika form disubmit dengan error, simpan data POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan semua data POST ke form_data
    foreach ($form_data as $key => $value) {
        if (isset($_POST[$key])) {
            $form_data[$key] = $_POST[$key];
        }
    }

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Data kependudukan
        $no_kk = $conn->real_escape_string($_POST['no_kk']);
        $nik = $conn->real_escape_string($_POST['nik']);
        $nama_lgkp = $conn->real_escape_string($_POST['nama_lgkp']);
        $nama_panggilan = $conn->real_escape_string($_POST['nama_panggilan'] ?? '');
        $hbkel = $conn->real_escape_string($_POST['hbkel']);
        $jk = $conn->real_escape_string($_POST['jk']);
        $tmpt_lhr = $conn->real_escape_string($_POST['tmpt_lhr']);
        $tgl_lhr = $conn->real_escape_string($_POST['tgl_lhr']);
        $agama = $conn->real_escape_string($_POST['agama']);
        $status_kawin = $conn->real_escape_string($_POST['status_kawin'] ?? 'BELUM KAWIN');
        $pendidikan = $conn->real_escape_string($_POST['pendidikan'] ?? '');
        $pekerjaan = $conn->real_escape_string($_POST['pekerjaan'] ?? '');
        $nama_ayah = $conn->real_escape_string($_POST['nama_ayah']);
        $nama_ibu = $conn->real_escape_string($_POST['nama_ibu']);
        $kecamatan = $conn->real_escape_string($_POST['kecamatan'] ?? '');
        $kelurahan = $conn->real_escape_string($_POST['kelurahan'] ?? '');
        $dsn = $conn->real_escape_string($_POST['dsn']);
        $rt = $conn->real_escape_string($_POST['rt'] ?? '');
        $rw = $conn->real_escape_string($_POST['rw'] ?? '');
        $alamat = $conn->real_escape_string($_POST['alamat'] ?? '');
        $gol_darah = $conn->real_escape_string($_POST['gol_darah'] ?? 'TIDAK TAHU');
        $disabilitas = $conn->real_escape_string($_POST['disabilitas'] ?? 'TIDAK');
        $jenis_disabilitas = $conn->real_escape_string($_POST['jenis_disabilitas'] ?? '');

        // Validasi NIK dan No KK
        if (strlen($nik) !== 16 || !is_numeric($nik)) {
            throw new Exception("NIK harus 16 digit angka!");
        }

        if (strlen($no_kk) !== 16 || !is_numeric($no_kk)) {
            throw new Exception("No. KK harus 16 digit angka!");
        }

        // Cek apakah NIK sudah ada
        $check_nik = query("SELECT NIK FROM tabel_kependudukan WHERE NIK = '$nik'");
        if (!empty($check_nik)) {
            throw new Exception("NIK $nik sudah terdaftar!");
        }

        // Insert data kependudukan
        $sql_kependudukan = "INSERT INTO tabel_kependudukan (
            NO_KK, NIK, NAMA_LGKP, NAMA_PANGGILAN, HBKEL, JK, TMPT_LHR, TGL_LHR, 
            AGAMA, STATUS_KAWIN, PENDIDIKAN, PEKERJAAN, NAMA_LGKP_AYAH, NAMA_LGKP_IBU,
            KECAMATAN, KELURAHAN, DSN, rt, rw, ALAMAT, GOL_DARAH, DISABILITAS, JENIS_DISABILITAS
        ) VALUES (
            '$no_kk', '$nik', '$nama_lgkp', '$nama_panggilan', '$hbkel', '$jk', '$tmpt_lhr', '$tgl_lhr',
            '$agama', '$status_kawin', '$pendidikan', '$pekerjaan', '$nama_ayah', '$nama_ibu',
            '$kecamatan', '$kelurahan', '$dsn', '$rt', '$rw', '$alamat', '$gol_darah', '$disabilitas', '$jenis_disabilitas'
        )";

        if (!$conn->query($sql_kependudukan)) {
            throw new Exception("Gagal menyimpan data kependudukan: " . $conn->error);
        }

        // Data pekerjaan
        $jenis_pekerjaan = $conn->real_escape_string($_POST['jenis_pekerjaan'] ?? '');
        $bidang_pekerjaan = $conn->real_escape_string($_POST['bidang_pekerjaan'] ?? '');
        $status_pekerjaan = $conn->real_escape_string($_POST['status_pekerjaan'] ?? 'TIDAK BEKERJA');
        $penghasilan = $conn->real_escape_string($_POST['penghasilan'] ?? 0);
        $jumlah_tanggungan = $conn->real_escape_string($_POST['jumlah_tanggungan'] ?? 0);

        $sql_pekerjaan = "INSERT INTO tabel_pekerjaan (
            NIK, jenis_pekerjaan, bidang_pekerjaan, status_pekerjaan, penghasilan_per_bulan, jumlah_tanggungan
        ) VALUES (
            '$nik', '$jenis_pekerjaan', '$bidang_pekerjaan', '$status_pekerjaan', '$penghasilan', '$jumlah_tanggungan'
        )";

        if (!$conn->query($sql_pekerjaan)) {
            throw new Exception("Gagal menyimpan data pekerjaan: " . $conn->error);
        }

        // Data pendidikan
        $pendidikan_terakhir = $conn->real_escape_string($_POST['pendidikan_terakhir'] ?? '');

        $sql_pendidikan = "INSERT INTO tabel_pendidikan_history (
            NIK, jenjang, nama_sekolah, tahun_lulus
        ) VALUES (
            '$nik', '$pendidikan', '$pendidikan_terakhir', YEAR('$tgl_lhr')
        )";

        if (!$conn->query($sql_pendidikan)) {
            throw new Exception("Gagal menyimpan data pendidikan: " . $conn->error);
        }

        // Data konsumsi - Menyimpan bahan makanan di field yang benar
        $bahan_makanan = $conn->real_escape_string($_POST['bahan_makanan'] ?? '');
        $frekuensi_per_minggu = $conn->real_escape_string($_POST['frekuensi_per_minggu'] ?? '3');
        $makan_per_hari = $conn->real_escape_string($_POST['makan_per_hari'] ?? '3');

        // Map nilai untuk enum fields
        $lauk_pauk_value = 'KADANG-KADANG'; // default
        if ($frekuensi_per_minggu == '1' || $frekuensi_per_minggu == '2') {
            $lauk_pauk_value = 'JARANG';
        } elseif ($frekuensi_per_minggu == '3' || $frekuensi_per_minggu == '4') {
            $lauk_pauk_value = 'KADANG-KADANG';
        } elseif ($frekuensi_per_minggu == '5' || $frekuensi_per_minggu == 'LEBIH') {
            $lauk_pauk_value = 'SERING';
        }

        // Untuk sayur_buah, kita beri nilai default karena bahan makanan pokok 
        // tidak berhubungan langsung dengan konsumsi sayur/buah
        $sayur_buah_value = 'KADANG-KADANG'; // default

        // Simpan bahan makanan di field lain atau buat tabel terpisah
        // Untuk sementara, kita simpan sebagai keterangan atau buat field baru

        // Alternatif 1: Ubah tabel untuk menambahkan kolom bahan_makanan_pokok
        // Alternatif 2: Simpan di sayur_buah dengan mapping khusus

        // Mapping bahan makanan ke nilai sayur_buah yang valid
        $bahan_makanan_map = [
            'BERAS' => 'SERING',      // Asumsi: makan nasi sering berarti sayur sering
            'TERIGU' => 'SERING',     // Asumsi: makan terigu sering
            'JAGUNG' => 'KADANG-KADANG',
            'UMBI-UMBIAN' => 'JARANG',
            'LAINNYA' => 'KADANG-KADANG'
        ];

        $sayur_buah_value = $bahan_makanan_map[$bahan_makanan] ?? 'KADANG-KADANG';

        $sql_konsumsi = "INSERT INTO tabel_konsumsi (
                            NIK, makan_per_hari, lauk_pauk, sayur_buah, susu_produk_olahan, air_minum
                        ) VALUES (
                            '$nik', '$makan_per_hari', '$lauk_pauk_value', '$sayur_buah_value', 'TIDAK', 'AIR MATANG'
                        )";

        if (!$conn->query($sql_konsumsi)) {
            throw new Exception("Gagal menyimpan data konsumsi: " . $conn->error);
        }

        // Data pakaian
        $pakaian_per_tahun = $conn->real_escape_string($_POST['pakaian_per_tahun'] ?? 0);

        $sql_pakaian = "INSERT INTO tabel_pakaian (
            NIK, pakaian_baru_per_tahun, alas_kaki, pakaian_kerja, pakaian_seragam
        ) VALUES (
            '$nik', '$pakaian_per_tahun', 'LAYAK', 'YA', 'YA'
        )";

        if (!$conn->query($sql_pakaian)) {
            throw new Exception("Gagal menyimpan data pakaian: " . $conn->error);
        }

        // Data kesehatan
        $biaya_pengobatan = $conn->real_escape_string($_POST['biaya_pengobatan'] ?? 'TERJANGKAU');

        $sql_kesehatan = "INSERT INTO tabel_kesehatan (
            NIK, akses_kesehatan, biaya_pengobatan, frekuensi_berobat, kondisi_kronis
        ) VALUES (
            '$nik', 'BPJS', '$biaya_pengobatan', 'JARANG', 'TIDAK'
        )";

        if (!$conn->query($sql_kesehatan)) {
            throw new Exception("Gagal menyimpan data kesehatan: " . $conn->error);
        }

        // Data tabungan
        $kepemilikan_tabungan = $conn->real_escape_string($_POST['kepemilikan_tabungan'] ?? 'TIDAK');
        $jenis_tabungan = $conn->real_escape_string($_POST['jenis_tabungan'] ?? '');
        $harga_tabungan = $conn->real_escape_string($_POST['harga_tabungan'] ?? 0);

        $sql_tabungan = "INSERT INTO tabel_tabungan (
            NIK, kepemilikan_tabungan, jenis_tabungan, perkiraan_saldo
        ) VALUES (
            '$nik', '$kepemilikan_tabungan', '$jenis_tabungan', '$harga_tabungan'
        )";

        if (!$conn->query($sql_tabungan)) {
            throw new Exception("Gagal menyimpan data tabungan: " . $conn->error);
        }

        // Data bantuan
        $penerima_bantuan = $conn->real_escape_string($_POST['penerima_bantuan'] ?? 'TIDAK');
        $jenis_bantuan = $conn->real_escape_string($_POST['jenis_bantuan'] ?? '');

        if ($penerima_bantuan == 'YA' && !empty($jenis_bantuan)) {
            $sql_bantuan = "INSERT INTO tabel_bantuan (
                NIK, jenis_bantuan, nama_bantuan, tahun_bantuan, status
            ) VALUES (
                '$nik', '$jenis_bantuan', '$jenis_bantuan', YEAR(CURDATE()), 'AKTIF'
            )";

            if (!$conn->query($sql_bantuan)) {
                throw new Exception("Gagal menyimpan data bantuan: " . $conn->error);
            }
        }

        // Data rumah (default)
        $sql_rumah = "INSERT INTO tabel_rumah (
            NIK, status_tempat_tinggal, luas_lantai, jenis_lantai, jenis_dinding, 
            fasilitas_bab, sumber_penerangan, sumber_air_minum, bahan_bakar_memasak, kondisi_rumah
        ) VALUES (
            '$nik', 'MILIK SENDIRI', 36.00, 'SEMEN', 'TEMBOK',
            'JAMBAN SENDIRI', 'PLN', 'SUMUR BOR', 'GAS', 'LAYAK HUNI'
        )";

        if (!$conn->query($sql_rumah)) {
            throw new Exception("Gagal menyimpan data rumah: " . $conn->error);
        }

        // Commit transaction
        $conn->commit();

        $_SESSION['success'] = "Data penduduk berhasil ditambahkan!";
        header("Location: list.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
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

    .nav-tabs .nav-link.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
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
                    <h2>Tambah Data Penduduk</h2>
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

                <div class="card p-4 shadow-sm">
                    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="identitas-tab" data-bs-toggle="tab" data-bs-target="#identitas" type="button" role="tab">
                                <i class="ti ti-user"></i> Identitas Diri
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="keluarga-tab" data-bs-toggle="tab" data-bs-target="#keluarga" type="button" role="tab">
                                <i class="ti ti-home"></i> Data Keluarga
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pendidikan-tab" data-bs-toggle="tab" data-bs-target="#pendidikan" type="button" role="tab">
                                <i class="ti ti-school"></i> Pendidikan & Pekerjaan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="konsumsi-tab" data-bs-toggle="tab" data-bs-target="#konsumsi" type="button" role="tab">
                                <i class="ti ti-tools-kitchen"></i> Konsumsi & Kesehatan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ekonomi-tab" data-bs-toggle="tab" data-bs-target="#ekonomi" type="button" role="tab">
                                <i class="ti ti-cash"></i> Kondisi Ekonomi
                            </button>
                        </li>
                    </ul>

                    <form method="post" id="formPenduduk">
                        <div class="tab-content" id="myTabContent">
                            <!-- TAB 1: IDENTITAS DIRI -->
                            <div class="tab-pane fade show active" id="identitas" role="tabpanel">
                                <div class="form-section">
                                    <h5><i class="ti ti-id"></i> Data Identitas</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="no_kk" class="form-label form-required">No. Kartu Keluarga</label>
                                            <input type="text" id="no_kk" name="no_kk" class="form-control"
                                                pattern="[0-9]{16}" maxlength="16" required
                                                placeholder="16 digit angka" onkeypress="return isNumberKey(event)"
                                                value="<?= htmlspecialchars($form_data['no_kk']) ?>">
                                            <small class="text-muted">16 digit angka tanpa spasi</small>
                                            <div id="error-no_kk" class="invalid-feedback"></div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="nik" class="form-label form-required">NIK</label>
                                            <input type="text" id="nik" name="nik" class="form-control"
                                                pattern="[0-9]{16}" maxlength="16" required
                                                placeholder="16 digit angka" onkeypress="return isNumberKey(event)"
                                                value="<?= htmlspecialchars($form_data['nik']) ?>">
                                            <small class="text-muted">16 digit NIK</small>
                                            <div id="error-nik" class="invalid-feedback"></div>
                                        </div>

                                        <div class="col-md-8 mb-3">
                                            <label for="nama_lgkp" class="form-label form-required">Nama Lengkap</label>
                                            <input type="text" id="nama_lgkp" name="nama_lgkp" class="form-control" required
                                                value="<?= htmlspecialchars($form_data['nama_lgkp']) ?>">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="nama_panggilan" class="form-label">Nama Panggilan</label>
                                            <input type="text" id="nama_panggilan" name="nama_panggilan" class="form-control"
                                                value="<?= htmlspecialchars($form_data['nama_panggilan']) ?>">
                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label class="form-label form-required">Jenis Kelamin</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jk" id="jk_l" value="L" required
                                                    <?= $form_data['jk'] == 'L' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="jk_l">Laki-laki</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="jk" id="jk_p" value="P" required
                                                    <?= $form_data['jk'] == 'P' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="jk_p">Perempuan</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="tmpt_lhr" class="form-label form-required">Tempat Lahir</label>
                                            <input type="text" id="tmpt_lhr" name="tmpt_lhr" class="form-control" required
                                                value="<?= htmlspecialchars($form_data['tmpt_lhr']) ?>">
                                        </div>

                                        <div class="col-md-5 mb-3">
                                            <label for="tgl_lhr" class="form-label form-required">Tanggal Lahir</label>
                                            <input type="date" id="tgl_lhr" name="tgl_lhr" class="form-control" max="<?= date('Y-m-d'); ?>" required
                                                value="<?= htmlspecialchars($form_data['tgl_lhr']) ?>">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="agama" class="form-label form-required">Agama</label>
                                            <select id="agama" name="agama" class="form-select" required>
                                                <option value="">Pilih Agama</option>
                                                <?php foreach ($agama_options as $agama): ?>
                                                    <option value="<?= $agama ?>" <?= $form_data['agama'] == $agama ? 'selected' : '' ?>>
                                                        <?= $agama ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Disabilitas</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="disabilitas" id="disabilitas_tidak" value="TIDAK"
                                                    <?= $form_data['disabilitas'] == 'TIDAK' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="disabilitas_tidak">Tidak</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="disabilitas" id="disabilitas_ya" value="YA"
                                                    <?= $form_data['disabilitas'] == 'YA' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="disabilitas_ya">Ya</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3 conditional-field" id="jenis_disabilitas_field">
                                            <label for="jenis_disabilitas" class="form-label">Jenis Disabilitas</label>
                                            <input type="text" id="jenis_disabilitas" name="jenis_disabilitas" class="form-control"
                                                placeholder="Jenis disabilitas"
                                                value="<?= htmlspecialchars($form_data['jenis_disabilitas']) ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5><i class="ti ti-map-pin"></i> Alamat Tinggal</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="kecamatan" class="form-label">Kecamatan</label>
                                            <input type="text" id="kecamatan" name="kecamatan" class="form-control"
                                                value="<?= htmlspecialchars($form_data['kecamatan']) ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="kelurahan" class="form-label">Kelurahan/Desa</label>
                                            <input type="text" id="kelurahan" name="kelurahan" class="form-control"
                                                value="<?= htmlspecialchars($form_data['kelurahan']) ?>">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="dsn" class="form-label form-required">Dusun</label>
                                            <select id="dsn" name="dsn" class="form-select" required>
                                                <option value="">Pilih Dusun</option>
                                                <?php foreach ($dusun_options as $dusun): ?>
                                                    <option value="<?= $dusun['id'] ?>" <?= $form_data['dsn'] == $dusun['id'] ? 'selected' : '' ?>>
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
                                            <label for="gol_darah" class="form-label">Golongan Darah</label>
                                            <select id="gol_darah" name="gol_darah" class="form-select">
                                                <option value="TIDAK TAHU" <?= $form_data['gol_darah'] == 'TIDAK TAHU' ? 'selected' : '' ?>>Tidak Tahu</option>
                                                <option value="A" <?= $form_data['gol_darah'] == 'A' ? 'selected' : '' ?>>A</option>
                                                <option value="B" <?= $form_data['gol_darah'] == 'B' ? 'selected' : '' ?>>B</option>
                                                <option value="AB" <?= $form_data['gol_darah'] == 'AB' ? 'selected' : '' ?>>AB</option>
                                                <option value="O" <?= $form_data['gol_darah'] == 'O' ? 'selected' : '' ?>>O</option>
                                            </select>
                                        </div>

                                        <div class="col-12 mb-3">
                                            <label for="alamat" class="form-label">Alamat Lengkap</label>
                                            <textarea id="alamat" name="alamat" class="form-control" rows="2"><?= htmlspecialchars($form_data['alamat']) ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 2: DATA KELUARGA -->
                            <div class="tab-pane fade" id="keluarga" role="tabpanel">
                                <div class="form-section">
                                    <h5><i class="ti ti-users"></i> Data Keluarga</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="hbkel" class="form-label form-required">Hubungan dalam Keluarga</label>
                                            <select id="hbkel" name="hbkel" class="form-select" required>
                                                <option value="">Pilih Hubungan</option>
                                                <?php foreach ($hubungan_options as $hubungan): ?>
                                                    <option value="<?= $hubungan ?>" <?= $form_data['hbkel'] == $hubungan ? 'selected' : '' ?>>
                                                        <?= $hubungan ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="status_kawin" class="form-label">Status Perkawinan</label>
                                            <select id="status_kawin" name="status_kawin" class="form-select">
                                                <option value="BELUM KAWIN" <?= $form_data['status_kawin'] == 'BELUM KAWIN' ? 'selected' : '' ?>>Belum Kawin</option>
                                                <option value="KAWIN" <?= $form_data['status_kawin'] == 'KAWIN' ? 'selected' : '' ?>>Kawin</option>
                                                <option value="CERAI HIDUP" <?= $form_data['status_kawin'] == 'CERAI HIDUP' ? 'selected' : '' ?>>Cerai Hidup</option>
                                                <option value="CERAI MATI" <?= $form_data['status_kawin'] == 'CERAI MATI' ? 'selected' : '' ?>>Cerai Mati</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="nama_ayah" class="form-label form-required">Nama Ayah</label>
                                            <input type="text" id="nama_ayah" name="nama_ayah" class="form-control" required
                                                value="<?= htmlspecialchars($form_data['nama_ayah']) ?>">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="nama_ibu" class="form-label form-required">Nama Ibu</label>
                                            <input type="text" id="nama_ibu" name="nama_ibu" class="form-control" required
                                                value="<?= htmlspecialchars($form_data['nama_ibu']) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: PENDIDIKAN & PEKERJAAN -->
                            <div class="tab-pane fade" id="pendidikan" role="tabpanel">
                                <div class="form-section">
                                    <h5><i class="ti ti-school"></i> Pendidikan</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="pendidikan" class="form-label">Pendidikan Terakhir</label>
                                            <select id="pendidikan" name="pendidikan" class="form-select">
                                                <option value="">Pilih Pendidikan</option>
                                                <?php foreach ($pendidikan_options as $pendidikan): ?>
                                                    <option value="<?= $pendidikan ?>" <?= $form_data['pendidikan'] == $pendidikan ? 'selected' : '' ?>>
                                                        <?= $pendidikan ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="pendidikan_terakhir" class="form-label">Nama Sekolah/Perguruan Tinggi</label>
                                            <input type="text" id="pendidikan_terakhir" name="pendidikan_terakhir" class="form-control"
                                                value="<?= htmlspecialchars($form_data['pendidikan_terakhir']) ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5><i class="ti ti-briefcase"></i> Pekerjaan</h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="pekerjaan" class="form-label">Jenis Pekerjaan</label>
                                            <select id="pekerjaan" name="pekerjaan" class="form-select">
                                                <option value="">Pilih Pekerjaan</option>
                                                <?php foreach ($pekerjaan_options as $pekerjaan): ?>
                                                    <option value="<?= $pekerjaan ?>" <?= $form_data['pekerjaan'] == $pekerjaan ? 'selected' : '' ?>>
                                                        <?= $pekerjaan ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="jenis_pekerjaan" class="form-label">Pekerjaan Utama</label>
                                            <input type="text" id="jenis_pekerjaan" name="jenis_pekerjaan" class="form-control"
                                                placeholder="Misal: Guru, Dokter, Wirausaha"
                                                value="<?= htmlspecialchars($form_data['jenis_pekerjaan']) ?>">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="bidang_pekerjaan" class="form-label">Bidang Pekerjaan</label>
                                            <input type="text" id="bidang_pekerjaan" name="bidang_pekerjaan" class="form-control"
                                                placeholder="Misal: Pendidikan, Kesehatan"
                                                value="<?= htmlspecialchars($form_data['bidang_pekerjaan']) ?>">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="status_pekerjaan" class="form-label">Status Pekerjaan</label>
                                            <select id="status_pekerjaan" name="status_pekerjaan" class="form-select">
                                                <option value="TIDAK BEKERJA" <?= $form_data['status_pekerjaan'] == 'TIDAK BEKERJA' ? 'selected' : '' ?>>Tidak Bekerja</option>
                                                <option value="PEKERJA TETAP" <?= $form_data['status_pekerjaan'] == 'PEKERJA TETAP' ? 'selected' : '' ?>>Pekerja Tetap</option>
                                                <option value="PEKERJA KONTRAK" <?= $form_data['status_pekerjaan'] == 'PEKERJA KONTRAK' ? 'selected' : '' ?>>Pekerja Kontrak</option>
                                                <option value="PEKERJA HARIAN" <?= $form_data['status_pekerjaan'] == 'PEKERJA HARIAN' ? 'selected' : '' ?>>Pekerja Harian</option>
                                                <option value="WIRAUSAHA" <?= $form_data['status_pekerjaan'] == 'WIRAUSAHA' ? 'selected' : '' ?>>Wirausaha</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="penghasilan" class="form-label">Penghasilan Per Bulan</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" id="penghasilan" name="penghasilan" class="form-control" min="0"
                                                    value="<?= htmlspecialchars($form_data['penghasilan']) ?>">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label for="jumlah_tanggungan" class="form-label">Jumlah Tanggungan</label>
                                            <input type="number" id="jumlah_tanggungan" name="jumlah_tanggungan" class="form-control" min="0"
                                                value="<?= htmlspecialchars($form_data['jumlah_tanggungan']) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 4: KONSUMSI & KESEHATAN -->
                            <div class="tab-pane fade" id="konsumsi" role="tabpanel">
                                <div class="form-section">
                                    <h5><i class="ti ti-tools-kitchen"></i> Konsumsi</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="bahan_makanan" class="form-label">Bahan Makanan Pokok</label>
                                            <select id="bahan_makanan" name="bahan_makanan" class="form-select">
                                                <option value="">Pilih Bahan Makanan</option>
                                                <?php foreach ($bahan_makanan_options as $bahan): ?>
                                                    <option value="<?= $bahan ?>" <?= $form_data['bahan_makanan'] == $bahan ? 'selected' : '' ?>>
                                                        <?= $bahan ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="frekuensi_per_minggu" class="form-label">Frekuensi Makan Daging/Telur per Minggu</label>
                                            <select id="frekuensi_per_minggu" name="frekuensi_per_minggu" class="form-select">
                                                <option value="1" <?= $form_data['frekuensi_per_minggu'] == '1' ? 'selected' : '' ?>>1 kali</option>
                                                <option value="2" <?= $form_data['frekuensi_per_minggu'] == '2' ? 'selected' : '' ?>>2 kali</option>
                                                <option value="3" <?= $form_data['frekuensi_per_minggu'] == '3' ? 'selected' : '' ?>>3 kali</option>
                                                <option value="4" <?= $form_data['frekuensi_per_minggu'] == '4' ? 'selected' : '' ?>>4 kali</option>
                                                <option value="5" <?= $form_data['frekuensi_per_minggu'] == '5' ? 'selected' : '' ?>>5 kali atau lebih</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="makan_per_hari" class="form-label">Frekuensi Makan per Hari</label>
                                            <select id="makan_per_hari" name="makan_per_hari" class="form-select">
                                                <option value="1" <?= $form_data['makan_per_hari'] == '1' ? 'selected' : '' ?>>1 kali</option>
                                                <option value="2" <?= $form_data['makan_per_hari'] == '2' ? 'selected' : '' ?>>2 kali</option>
                                                <option value="3" <?= $form_data['makan_per_hari'] == '3' ? 'selected' : '' ?>>3 kali</option>
                                                <option value="LEBIH" <?= $form_data['makan_per_hari'] == 'LEBIH' ? 'selected' : '' ?>>Lebih dari 3 kali</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="pakaian_per_tahun" class="form-label">Pakaian Baru per Tahun</label>
                                            <div class="input-group">
                                                <input type="number" id="pakaian_per_tahun" name="pakaian_per_tahun" class="form-control" min="0"
                                                    value="<?= htmlspecialchars($form_data['pakaian_per_tahun']) ?>">
                                                <span class="input-group-text">pcs</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5><i class="ti ti-heart"></i> Kesehatan</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="biaya_pengobatan" class="form-label">Kemampuan Biaya Pengobatan</label>
                                            <select id="biaya_pengobatan" name="biaya_pengobatan" class="form-select">
                                                <option value="TERJANGKAU" <?= $form_data['biaya_pengobatan'] == 'TERJANGKAU' ? 'selected' : '' ?>>Terjangkau</option>
                                                <option value="SULIT" <?= $form_data['biaya_pengobatan'] == 'SULIT' ? 'selected' : '' ?>>Sulit</option>
                                                <option value="SANGAT SULIT" <?= $form_data['biaya_pengobatan'] == 'SANGAT SULIT' ? 'selected' : '' ?>>Sangat Sulit</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 5: KONDISI EKONOMI -->
                            <div class="tab-pane fade" id="ekonomi" role="tabpanel">
                                <div class="form-section">
                                    <h5><i class="ti ti-pig-money"></i> Tabungan</h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Kepemilikan Tabungan</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="kepemilikan_tabungan" id="tabungan_tidak" value="TIDAK"
                                                    <?= $form_data['kepemilikan_tabungan'] == 'TIDAK' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="tabungan_tidak">Tidak</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="kepemilikan_tabungan" id="tabungan_ya" value="YA"
                                                    <?= $form_data['kepemilikan_tabungan'] == 'YA' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="tabungan_ya">Ya</label>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3 conditional-field" id="jenis_tabungan_field">
                                            <label for="jenis_tabungan" class="form-label">Jenis Tabungan</label>
                                            <select id="jenis_tabungan" name="jenis_tabungan" class="form-select">
                                                <option value="">Pilih Jenis Tabungan</option>
                                                <?php foreach ($jenis_tabungan_options as $jenis): ?>
                                                    <option value="<?= $jenis ?>" <?= $form_data['jenis_tabungan'] == $jenis ? 'selected' : '' ?>>
                                                        <?= $jenis ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3 conditional-field" id="harga_tabungan_field">
                                            <label for="harga_tabungan" class="form-label">Perkiraan Saldo</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" id="harga_tabungan" name="harga_tabungan" class="form-control" min="0"
                                                    value="<?= htmlspecialchars($form_data['harga_tabungan']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h5><i class="ti ti-gift"></i> Bantuan</h5>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Penerima Bantuan</label><br>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="penerima_bantuan" id="bantuan_tidak" value="TIDAK"
                                                    <?= $form_data['penerima_bantuan'] == 'TIDAK' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="bantuan_tidak">Tidak</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="penerima_bantuan" id="bantuan_ya" value="YA"
                                                    <?= $form_data['penerima_bantuan'] == 'YA' ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="bantuan_ya">Ya</label>
                                            </div>
                                        </div>

                                        <div class="col-md-8 mb-3 conditional-field" id="jenis_bantuan_field">
                                            <label for="jenis_bantuan" class="form-label">Jenis Bantuan</label>
                                            <select id="jenis_bantuan" name="jenis_bantuan" class="form-select">
                                                <option value="">Pilih Jenis Bantuan</option>
                                                <?php foreach ($jenis_bantuan_options as $bantuan): ?>
                                                    <option value="<?= $bantuan ?>" <?= $form_data['jenis_bantuan'] == $bantuan ? 'selected' : '' ?>>
                                                        <?= $bantuan ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <div>
                                <button type="button" class="btn btn-secondary" onclick="previousTab()">
                                    <i class="ti ti-chevron-left"></i> Sebelumnya
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextTab()">
                                    Selanjutnya <i class="ti ti-chevron-right"></i>
                                </button>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success">
                                    <i class="ti ti-check"></i> Simpan Data
                                </button>
                                <a href="list.php" class="btn btn-danger">
                                    <i class="ti ti-x"></i> Batal
                                </a>
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
        // Handle conditional fields
        const disabilitasYa = document.getElementById('disabilitas_ya');
        const disabilitasTidak = document.getElementById('disabilitas_tidak');
        const jenisDisabilitasField = document.getElementById('jenis_disabilitas_field');

        const tabunganYa = document.getElementById('tabungan_ya');
        const tabunganTidak = document.getElementById('tabungan_tidak');
        const jenisTabunganField = document.getElementById('jenis_tabungan_field');
        const hargaTabunganField = document.getElementById('harga_tabungan_field');

        const bantuanYa = document.getElementById('bantuan_ya');
        const bantuanTidak = document.getElementById('bantuan_tidak');
        const jenisBantuanField = document.getElementById('jenis_bantuan_field');

        function toggleDisabilitasField() {
            if (disabilitasYa.checked) {
                jenisDisabilitasField.classList.add('show');
            } else {
                jenisDisabilitasField.classList.remove('show');
            }
        }

        function toggleTabunganFields() {
            if (tabunganYa.checked) {
                jenisTabunganField.classList.add('show');
                hargaTabunganField.classList.add('show');
            } else {
                jenisTabunganField.classList.remove('show');
                hargaTabunganField.classList.remove('show');
            }
        }

        function toggleBantuanField() {
            if (bantuanYa.checked) {
                jenisBantuanField.classList.add('show');
            } else {
                jenisBantuanField.classList.remove('show');
            }
        }

        // Initial toggle
        toggleDisabilitasField();
        toggleTabunganFields();
        toggleBantuanField();

        // Add event listeners
        disabilitasYa.addEventListener('change', toggleDisabilitasField);
        disabilitasTidak.addEventListener('change', toggleDisabilitasField);

        tabunganYa.addEventListener('change', toggleTabunganFields);
        tabunganTidak.addEventListener('change', toggleTabunganFields);

        bantuanYa.addEventListener('change', toggleBantuanField);
        bantuanTidak.addEventListener('change', toggleBantuanField);

        // Format input numbers
        function formatNumberInput(input) {
            input.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                this.value = value;
            });
        }

        // Format KK and NIK inputs
        formatNumberInput(document.getElementById('no_kk'));
        formatNumberInput(document.getElementById('nik'));

        // Auto-calculate age from date of birth
        const tglLahirInput = document.getElementById('tgl_lhr');
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
        document.getElementById('nik').addEventListener('blur', function() {
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

        document.getElementById('no_kk').addEventListener('blur', function() {
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

    // Tab navigation
    function nextTab() {
        const currentTab = document.querySelector('#myTab .nav-link.active');
        const nextTab = currentTab.parentElement.nextElementSibling;

        if (nextTab) {
            nextTab.querySelector('.nav-link').click();
        }
    }

    function previousTab() {
        const currentTab = document.querySelector('#myTab .nav-link.active');
        const prevTab = currentTab.parentElement.previousElementSibling;

        if (prevTab) {
            prevTab.querySelector('.nav-link').click();
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
    document.getElementById('formPenduduk').addEventListener('submit', function(e) {
        let isValid = true;

        // Validate NIK length
        const nik = document.getElementById('nik').value.trim();
        if (nik.length !== 16 || !/^\d+$/.test(nik)) {
            e.preventDefault();
            alert('NIK harus 16 digit angka');
            document.getElementById('nik').focus();
            return false;
        }

        // Validate KK length
        const kk = document.getElementById('no_kk').value.trim();
        if (kk.length !== 16 || !/^\d+$/.test(kk)) {
            e.preventDefault();
            alert('No. KK harus 16 digit angka');
            document.getElementById('no_kk').focus();
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

</html>