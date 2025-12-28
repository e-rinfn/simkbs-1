<?php

include_once __DIR__ . '/../../config/config.php';
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

// Cek parameter NIK
if (!isset($_GET['nik'])) {
    header("Location: list.php");
    exit();
}

$nik = $conn->real_escape_string($_GET['nik']);

// Ambil data penduduk berdasarkan NIK
$sql_kependudukan = "SELECT 
    k.*, 
    d.dusun as nama_dusun 
    FROM tabel_kependudukan k
    LEFT JOIN tabel_dusun d ON k.DSN = d.id
    WHERE k.NIK = '$nik'";
$kependudukan = query($sql_kependudukan);

if (empty($kependudukan)) {
    header("Location: list.php");
    exit();
}

$penduduk = $kependudukan[0];

// Hitung usia
$tgl_lahir = new DateTime($penduduk['TGL_LHR']);
$today = new DateTime();
$usia = $today->diff($tgl_lahir)->y;

// Format tanggal lahir
$tgl_lahir_formatted = date('d F Y', strtotime($penduduk['TGL_LHR']));

// Ambil data dari tabel terkait
$data = [
    'kependudukan' => $penduduk,
    'usia' => $usia
];

// Ambil data pekerjaan
$sql_pekerjaan = "SELECT * FROM tabel_pekerjaan WHERE NIK = '$nik'";
$pekerjaan_data = query($sql_pekerjaan);
$data['pekerjaan'] = !empty($pekerjaan_data) ? $pekerjaan_data[0] : null;

// Ambil data pendidikan history terakhir
$sql_pendidikan = "SELECT * FROM tabel_pendidikan_history WHERE NIK = '$nik' ORDER BY tahun_lulus DESC LIMIT 1";
$pendidikan_data = query($sql_pendidikan);
$data['pendidikan'] = !empty($pendidikan_data) ? $pendidikan_data[0] : null;

// Ambil data konsumsi
$sql_konsumsi = "SELECT * FROM tabel_konsumsi WHERE NIK = '$nik'";
$konsumsi_data = query($sql_konsumsi);
$data['konsumsi'] = !empty($konsumsi_data) ? $konsumsi_data[0] : null;

// Ambil data pakaian
$sql_pakaian = "SELECT * FROM tabel_pakaian WHERE NIK = '$nik'";
$pakaian_data = query($sql_pakaian);
$data['pakaian'] = !empty($pakaian_data) ? $pakaian_data[0] : null;

// Ambil data kesehatan
$sql_kesehatan = "SELECT * FROM tabel_kesehatan WHERE NIK = '$nik'";
$kesehatan_data = query($sql_kesehatan);
$data['kesehatan'] = !empty($kesehatan_data) ? $kesehatan_data[0] : null;

// Ambil data tabungan
$sql_tabungan = "SELECT * FROM tabel_tabungan WHERE NIK = '$nik'";
$tabungan_data = query($sql_tabungan);
$data['tabungan'] = !empty($tabungan_data) ? $tabungan_data[0] : null;

// Ambil data bantuan aktif
$sql_bantuan = "SELECT * FROM tabel_bantuan WHERE NIK = '$nik' AND status = 'AKTIF' LIMIT 1";
$bantuan_data = query($sql_bantuan);
$data['bantuan'] = !empty($bantuan_data) ? $bantuan_data[0] : null;

// Ambil data rumah
$sql_rumah = "SELECT * FROM tabel_rumah WHERE NIK = '$nik'";
$rumah_data = query($sql_rumah);
$data['rumah'] = !empty($rumah_data) ? $rumah_data[0] : null;

// Mapping untuk tampilan yang lebih user-friendly
$jk_map = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$hbkel_map = [
    'KEPALA KELUARGA' => 'Kepala Keluarga',
    'ISTRI' => 'Istri',
    'ANAK' => 'Anak',
    'FAMILI LAIN' => 'Famili Lain'
];
$status_kawin_map = [
    'BELUM KAWIN' => 'Belum Kawin',
    'KAWIN' => 'Kawin',
    'CERAI HIDUP' => 'Cerai Hidup',
    'CERAI MATI' => 'Cerai Mati'
];

// Format alamat lengkap
$alamat_lengkap = "";
if ($penduduk['ALAMAT']) {
    $alamat_lengkap .= $penduduk['ALAMAT'];
}
if ($penduduk['rt']) {
    $alamat_lengkap .= ($alamat_lengkap ? ", " : "") . "RT " . $penduduk['rt'];
}
if ($penduduk['rw']) {
    $alamat_lengkap .= ($alamat_lengkap ? "/" : "RW ") . "RW " . $penduduk['rw'];
}
if ($penduduk['nama_dusun']) {
    $alamat_lengkap .= ($alamat_lengkap ? ", " : "") . "Dusun " . $penduduk['nama_dusun'];
}
if ($penduduk['KELURAHAN']) {
    $alamat_lengkap .= ($alamat_lengkap ? ", " : "") . $penduduk['KELURAHAN'];
}
if ($penduduk['KECAMATAN']) {
    $alamat_lengkap .= ($alamat_lengkap ? ", " : "") . $penduduk['KECAMATAN'];
}
?>

<style>
    .swal2-container {
        z-index: 99999 !important;
    }

    .detail-section {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #0d6efd;
    }

    .detail-section h5 {
        color: #0d6efd;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }

    .detail-item {
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eee;
    }

    .detail-label {
        font-weight: 600;
        color: #495057;
        min-width: 200px;
        display: inline-block;
    }

    .detail-value {
        color: #212529;
    }

    .badge-status {
        font-size: 0.85em;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .badge-jk-l {
        background-color: #0d6efd;
        color: white;
    }

    .badge-jk-p {
        background-color: #dc3545;
        color: white;
    }

    .badge-disabilitas {
        background-color: #fd7e14;
        color: white;
    }

    .badge-bantuan {
        background-color: #20c997;
        color: white;
    }

    .badge-tabungan {
        background-color: #6f42c1;
        color: white;
    }

    .profile-header {
        border-radius: 8px;
        padding: 25px;
        color: white;
        margin-bottom: 20px;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid white;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        color: #6c757d;
        margin-right: 20px;
    }

    .profile-info h3 {
        margin-bottom: 5px;
    }

    .profile-info p {
        margin-bottom: 10px;
        opacity: 0.9;
    }

    .info-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .info-card h6 {
        color: #0d6efd;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }

    .print-only {
        display: none;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .print-only {
            display: block !important;
        }

        .detail-section {
            break-inside: avoid;
        }

        body {
            font-size: 12px;
        }
    }

    /* PERBAIKAN: Style untuk tabel */
    .info-table {
        width: 100%;
        margin-bottom: 20px;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .info-table th {
        background-color: #0d6efd;
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #0a58ca;
    }

    .info-table th i {
        margin-right: 8px;
    }

    .info-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: top;
    }

    .info-table tr:last-child td {
        border-bottom: none;
    }

    .info-table tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }

    .info-table .label-cell {
        width: 35%;
        font-weight: 600;
        color: #495057;
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
    }

    .info-table .value-cell {
        width: 65%;
        color: #212529;
    }

    /* Grid untuk tata letak tabel */
    .table-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .table-grid {
            grid-template-columns: 1fr;
        }

        .info-table {
            font-size: 0.9rem;
        }

        .info-table th,
        .info-table td {
            padding: 10px;
        }
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
                    <h2>Detail Data Penduduk</h2>
                    <div class="no-print">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="ti ti-arrow-back"></i> Kembali ke List
                        </a>
                        <button onclick="window.print()" class="btn btn-info">
                            <i class="ti ti-printer"></i> Cetak
                        </button>
                    </div>
                </div>

                <!-- Profile Header -->
                <div class="profile-header bg-primary">
                    <div class="d-flex align-items-center">
                        <div class="profile-avatar">
                            <i class="ti ti-user"></i>
                        </div>
                        <div class="profile-info flex-grow-1">
                            <h3 class="text-white "><?= htmlspecialchars($penduduk['NAMA_LGKP']) ?></h3>
                            <p class="mb-1">
                                <i class="ti ti-id"></i> NIK: <?= formatKKNIK($penduduk['NIK']) ?> |
                                <i class="ti ti-home"></i> KK: <?= formatKKNIK($penduduk['NO_KK']) ?>
                            </p>
                            <p class="mb-1">
                                <span class="badge <?= $penduduk['JK'] == 'L' ? 'badge-jk-l' : 'badge-jk-p' ?> badge-status">
                                    <i class="ti ti-gender-<?= $penduduk['JK'] == 'L' ? 'male' : 'female' ?>"></i>
                                    <?= $jk_map[$penduduk['JK']] ?>
                                </span>
                                <span class="badge bg-secondary badge-status">
                                    <i class="ti ti-calendar"></i> <?= $usia ?> Tahun
                                </span>
                                <span class="badge bg-info badge-status">
                                    <i class="ti ti-map-pin"></i> <?= $penduduk['nama_dusun'] ?>
                                </span>
                                <?php if ($penduduk['DISABILITAS'] == 'YA'): ?>
                                    <span class="badge badge-disabilitas badge-status">
                                        <i class="ti ti-wheelchair"></i> Disabilitas
                                    </span>
                                <?php endif; ?>
                                <?php if ($data['bantuan']): ?>
                                    <span class="badge badge-bantuan badge-status">
                                        <i class="ti ti-gift"></i> Penerima Bantuan
                                    </span>
                                <?php endif; ?>
                                <?php if ($data['tabungan'] && $data['tabungan']['kepemilikan_tabungan'] == 'YA'): ?>
                                    <span class="badge badge-tabungan badge-status">
                                        <i class="ti ti-pig-money"></i> Memiliki Tabungan
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PERBAIKAN: Ganti info-grid dengan tabel -->
                <div class="table-grid">

                    <!-- Identitas Diri -->
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th colspan="2"><i class="ti ti-id me-2"></i>Identitas Diri</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label-cell">Nama Lengkap</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['NAMA_LGKP']) ?></td>
                            </tr>
                            <?php if ($penduduk['NAMA_PANGGILAN']): ?>
                                <tr>
                                    <td class="label-cell">Nama Panggilan</td>
                                    <td class="value-cell"><?= htmlspecialchars($penduduk['NAMA_PANGGILAN']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="label-cell">NIK</td>
                                <td class="value-cell"><?= formatKKNIK($penduduk['NIK']) ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">No. Kartu Keluarga</td>
                                <td class="value-cell"><?= formatKKNIK($penduduk['NO_KK']) ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Jenis Kelamin</td>
                                <td class="value-cell"><?= $jk_map[$penduduk['JK']] ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Tempat, Tanggal Lahir</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['TMPT_LHR']) ?>, <?= $tgl_lahir_formatted ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Usia</td>
                                <td class="value-cell"><?= $usia ?> Tahun</td>
                            </tr>
                            <tr>
                                <td class="label-cell">Agama</td>
                                <td class="value-cell"><?= $penduduk['AGAMA'] ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Golongan Darah</td>
                                <td class="value-cell"><?= $penduduk['GOL_DARAH'] ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Disabilitas</td>
                                <td class="value-cell">
                                    <?= $penduduk['DISABILITAS'] == 'YA' ? 'Ya' : 'Tidak' ?>
                                    <?php if ($penduduk['DISABILITAS'] == 'YA' && $penduduk['JENIS_DISABILITAS']): ?>
                                        (<?= htmlspecialchars($penduduk['JENIS_DISABILITAS']) ?>)
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Data Keluarga -->
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th colspan="2"><i class="ti ti-users me-2"></i>Data Keluarga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label-cell">Hubungan Keluarga</td>
                                <td class="value-cell"><?= $hbkel_map[$penduduk['HBKEL']] ?? $penduduk['HBKEL'] ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Status Perkawinan</td>
                                <td class="value-cell"><?= $status_kawin_map[$penduduk['STATUS_KAWIN']] ?? $penduduk['STATUS_KAWIN'] ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Nama Ayah</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['NAMA_LGKP_AYAH']) ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Nama Ibu</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['NAMA_LGKP_IBU']) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Alamat Tinggal -->
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th colspan="2"><i class="ti ti-map-pin me-2"></i>Alamat Tinggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label-cell">Alamat</td>
                                <td class="value-cell"><?= $alamat_lengkap ?: '-' ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Dusun</td>
                                <td class="value-cell"><?= $penduduk['nama_dusun'] ?: '-' ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">RT/RW</td>
                                <td class="value-cell"><?= $penduduk['rt'] ? 'RT ' . $penduduk['rt'] : '' ?><?= $penduduk['rw'] ? '/RW ' . $penduduk['rw'] : '' ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Kelurahan/Desa</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['KELURAHAN']) ?: '-' ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Kecamatan</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['KECAMATAN']) ?: '-' ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pendidikan & Pekerjaan -->
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th colspan="2"><i class="ti ti-school me-2"></i>Pendidikan & Pekerjaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label-cell">Pendidikan Terakhir</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['PENDIDIKAN']) ?: '-' ?></td>
                            </tr>
                            <?php if ($data['pendidikan'] && $data['pendidikan']['nama_sekolah']): ?>
                                <tr>
                                    <td class="label-cell">Nama Sekolah</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['pendidikan']['nama_sekolah']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="label-cell">Pekerjaan</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['PEKERJAAN']) ?: '-' ?></td>
                            </tr>
                            <?php if ($data['pekerjaan']): ?>
                                <tr>
                                    <td class="label-cell">Pekerjaan Utama</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['pekerjaan']['jenis_pekerjaan']) ?: '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Bidang Pekerjaan</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['pekerjaan']['bidang_pekerjaan']) ?: '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Status Pekerjaan</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['pekerjaan']['status_pekerjaan']) ?: '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Penghasilan/Bulan</td>
                                    <td class="value-cell"><?= $data['pekerjaan']['penghasilan_per_bulan'] > 0 ? formatRupiah($data['pekerjaan']['penghasilan_per_bulan']) : '-' ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Jumlah Tanggungan</td>
                                    <td class="value-cell"><?= $data['pekerjaan']['jumlah_tanggungan'] ?: '0' ?> orang</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Konsumsi & Kesehatan -->
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th colspan="2"><i class="ti ti-tools-kitchen me-2"></i>Konsumsi & Kesehatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($data['konsumsi']): ?>
                                <tr>
                                    <td class="label-cell">Frekuensi Makan/Hari</td>
                                    <td class="value-cell"><?= $data['konsumsi']['makan_per_hari'] ?> kali</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Konsumsi Lauk Pauk</td>
                                    <td class="value-cell"><?= $data['konsumsi']['lauk_pauk'] ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Konsumsi Sayur/Buah</td>
                                    <td class="value-cell"><?= $data['konsumsi']['sayur_buah'] ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($data['pakaian']): ?>
                                <tr>
                                    <td class="label-cell">Pakaian Baru/Tahun</td>
                                    <td class="value-cell"><?= $data['pakaian']['pakaian_baru_per_tahun'] ?> pcs</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Alas Kaki</td>
                                    <td class="value-cell"><?= $data['pakaian']['alas_kaki'] == 'LAYAK' ? 'Layak' : 'Tidak Layak' ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($data['kesehatan']): ?>
                                <tr>
                                    <td class="label-cell">Akses Kesehatan</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['kesehatan']['akses_kesehatan']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Biaya Pengobatan</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['kesehatan']['biaya_pengobatan']) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Kondisi Ekonomi -->
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th colspan="2"><i class="ti ti-cash me-2"></i>Kondisi Ekonomi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($data['tabungan']): ?>
                                <tr>
                                    <td class="label-cell">Kepemilikan Tabungan</td>
                                    <td class="value-cell"><?= $data['tabungan']['kepemilikan_tabungan'] == 'YA' ? 'Ya' : 'Tidak' ?></td>
                                </tr>
                                <?php if ($data['tabungan']['kepemilikan_tabungan'] == 'YA'): ?>
                                    <tr>
                                        <td class="label-cell">Jenis Tabungan</td>
                                        <td class="value-cell"><?= htmlspecialchars($data['tabungan']['jenis_tabungan']) ?: '-' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="label-cell">Perkiraan Saldo</td>
                                        <td class="value-cell"><?= $data['tabungan']['perkiraan_saldo'] > 0 ? formatRupiah($data['tabungan']['perkiraan_saldo']) : '-' ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($data['bantuan']): ?>
                                <tr>
                                    <td class="label-cell">Penerima Bantuan</td>
                                    <td class="value-cell">Ya</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Jenis Bantuan</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['bantuan']['jenis_bantuan']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Nama Bantuan</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['bantuan']['nama_bantuan']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Tahun Bantuan</td>
                                    <td class="value-cell"><?= $data['bantuan']['tahun_bantuan'] ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td class="label-cell">Penerima Bantuan</td>
                                    <td class="value-cell">Tidak</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Informasi Tambahan -->
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th colspan="2"><i class="ti ti-info-circle me-2"></i>Informasi Tambahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="label-cell">Kewarganegaraan</td>
                                <td class="value-cell"><?= $penduduk['KEWARGANEGARAAN'] == 'WNI' ? 'WNI' : 'WNA' ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Status Tinggal</td>
                                <td class="value-cell"><?= htmlspecialchars($penduduk['STATUS_TINGGAL']) ?: '-' ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Data Dibuat</td>
                                <td class="value-cell"><?= date('d/m/Y H:i', strtotime($penduduk['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td class="label-cell">Terakhir Diperbarui</td>
                                <td class="value-cell"><?= date('d/m/Y H:i', strtotime($penduduk['updated_at'])) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Kondisi Rumah -->
                    <?php if ($data['rumah']): ?>
                        <table class="info-table">
                            <thead>
                                <tr>
                                    <th colspan="2"><i class="ti ti-home me-2"></i>Kondisi Rumah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="label-cell">Status Tempat Tinggal</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['rumah']['status_tempat_tinggal']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Luas Lantai</td>
                                    <td class="value-cell"><?= $data['rumah']['luas_lantai'] ?> m²</td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Jenis Lantai</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['rumah']['jenis_lantai']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Jenis Dinding</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['rumah']['jenis_dinding']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Fasilitas BAB</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['rumah']['fasilitas_bab']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Sumber Penerangan</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['rumah']['sumber_penerangan']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Sumber Air Minum</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['rumah']['sumber_air_minum']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Bahan Bakar Memasak</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['rumah']['bahan_bakar_memasak']) ?></td>
                                </tr>
                                <tr>
                                    <td class="label-cell">Kondisi Rumah</td>
                                    <td class="value-cell"><?= htmlspecialchars($data['rumah']['kondisi_rumah']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>

                </div> <!-- End table-grid -->

            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include_once '../includes/footer.php'; ?>

</body>
<!-- [Body] end -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // SweetAlert for delete confirmation
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const nik = $(this).data('id');
            const nama = $(this).data('nama');

            Swal.fire({
                title: 'Konfirmasi Penghapusan',
                html: `Apakah Anda yakin ingin menghapus data penduduk?<br><br>
                      <strong>NIK:</strong> ${nik}<br>
                      <strong>Nama:</strong> ${nama}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`check_delete.php?nik=${nik}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error}`
                            );
                            return null;
                        });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    if (result.value.can_delete) {
                        // Proceed with deletion
                        window.location.href = url;
                    } else {
                        Swal.fire({
                            title: 'Tidak Bisa Dihapus',
                            html: result.value.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                } else if (result.isConfirmed && !result.value) {
                    Swal.fire({
                        title: 'Error',
                        text: 'Gagal memeriksa ketergantungan data',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    });
</script>

</html>