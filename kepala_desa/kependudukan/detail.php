<?php

// view.php
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

// Cek apakah parameter ID ada
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID tidak valid!";
    header("Location: list.php");
    exit();
}

$id = intval($_GET['id']);

// Ambil data penduduk
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

// Ambil dokumen yang sudah diupload
$sql_dokumen = "SELECT * FROM tabel_dokumen_penduduk WHERE penduduk_id = $id ORDER BY jenis_dokumen, created_at DESC";
$dokumen_list = query($sql_dokumen);

// Kelompokkan dokumen berdasarkan jenis
$dokumen_grup = [];
foreach ($dokumen_list as $dokumen) {
    $dokumen_grup[$dokumen['jenis_dokumen']][] = $dokumen;
}

// Mapping jenis dokumen ke label
$jenis_dokumen_labels = [
    'AKTA_KEMATIAN' => 'Akta Kematian',
    'AKTA_PINDAH' => 'Akta Pindah',
    'BUKU_NIKAH' => 'Buku Nikah',
    'LAINNYA' => 'Dokumen Lainnya'
];

// Hitung usia
$tgl_lahir = new DateTime($data['TGL_LHR']);
$today = new DateTime();
$usia = $today->diff($tgl_lahir)->y;

// Function untuk format status kawin lengkap
function formatStatusKawinLengkap($status)
{
    $statuses = [
        'BELUM KAWIN' => 'Belum Kawin',
        'KAWIN' => 'Kawin',
        'CERAI HIDUP' => 'Cerai Hidup',
        'CERAI MATI' => 'Cerai Mati'
    ];
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

// Function untuk format hubungan keluarga lengkap
function formatHubKeluargaLengkap($hbkel)
{
    $hubungan = [
        'KEPALA KELUARGA' => 'Kepala Keluarga',
        'ISTRI' => 'Istri',
        'ANAK' => 'Anak',
        'FAMILI LAIN' => 'Famili Lain'
    ];
    return isset($hubungan[$hbkel]) ? $hubungan[$hbkel] : $hbkel;
}

// Function untuk format JK lengkap
function formatJKLengkap($jk)
{
    return $jk == 'L' ? 'Laki-laki' : 'Perempuan';
}

// Function untuk format status tinggal lengkap
function formatStatusTinggalLengkap($status)
{
    $statuses = [
        'TETAP' => 'Tinggal Tetap',
        'SEMENTARA' => 'Tinggal Sementara',
        'PENDATANG' => 'Pendatang',
        'MENINGGAL' => 'Meninggal',
        'PINDAH' => 'Pindah'
    ];
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

// Function untuk menentukan warna badge berdasarkan status tinggal
function getStatusTinggalBadgeColor($status)
{
    $colors = [
        'TETAP' => 'bg-primary',
        'SEMENTARA' => 'bg-info',
        'PENDATANG' => 'bg-warning',
        'MENINGGAL' => 'bg-dark',
        'PINDAH' => 'bg-secondary'
    ];
    return isset($colors[$status]) ? $colors[$status] : 'bg-secondary';
}

// Function untuk format tanggal Indonesia
function formatTanggalIndo($date_string)
{
    if (empty($date_string) || $date_string == '0000-00-00') {
        return '-';
    }

    $bulan = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $tanggal = date('j', strtotime($date_string));
    $bulan_idx = date('n', strtotime($date_string)) - 1;
    $tahun = date('Y', strtotime($date_string));

    return $tanggal . ' ' . $bulan[$bulan_idx] . ' ' . $tahun;
}

// Function untuk format tanggal dengan waktu
function formatTanggalWaktu($datetime_string)
{
    if (empty($datetime_string) || $datetime_string == '0000-00-00 00:00:00') {
        return '-';
    }

    return date('d-m-Y H:i', strtotime($datetime_string));
}

// Function untuk format ukuran file
function formatUkuranFile($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Data Penduduk</title>
    <!-- <?php include_once '../includes/css.php'; ?> -->
    <style>
        .card-detail {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header-detail {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 180px;
        }

        .info-value {
            color: #212529;
            font-weight: 500;
        }

        .badge-detail {
            font-size: 0.85em;
            padding: 5px 10px;
        }

        .section-title {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }

        .profile-header {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background-color: #0d6efd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 32px;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 15px;
        }

        .timeline-item:before {
            content: '';
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
            border: 2px solid white;
        }

        .dokumen-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fff;
            transition: transform 0.2s;
        }

        .dokumen-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

        .dokumen-keterangan {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .file-link {
            display: inline-block;
            margin-top: 5px;
            padding: 8px 15px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #0d6efd;
            font-size: 0.9em;
        }

        .file-link:hover {
            background-color: #e9ecef;
            color: #0a58ca;
        }

        .status-alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .status-alert.meninggal {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .status-alert.pindah {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }

        .file-icon {
            font-size: 1.5em;
            margin-right: 8px;
            color: #0d6efd;
        }

        .no-dokumen {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .no-dokumen i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #dee2e6;
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
                    <h2>Detail Data Kependudukan</h2>
                    <div>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="ti ti-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card card-detail">
                        <div class="card-body">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong> <?= $_SESSION['error']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>

                            <!-- Alert untuk status khusus -->
                            <?php if ($data['STATUS_TINGGAL'] == 'MENINGGAL'): ?>
                                <div class="status-alert meninggal">
                                    <h5><i class="ti ti-alert-triangle"></i> Penduduk Telah Meninggal</h5>
                                    <p class="mb-0">Data ini memiliki status "Meninggal". Informasi selengkapnya dapat dilihat pada dokumen akta kematian di bawah.</p>
                                </div>
                            <?php elseif ($data['STATUS_TINGGAL'] == 'PINDAH'): ?>
                                <div class="status-alert pindah">
                                    <h5><i class="ti ti-user-exclamation"></i> Penduduk Telah Pindah</h5>
                                    <p class="mb-0">Data ini memiliki status "Pindah". Informasi tentang perpindahan dapat dilihat pada dokumen akta pindah di bawah.</p>
                                </div>
                            <?php endif; ?>

                            <!-- Profile Header -->
                            <div class="profile-header text-center mb-4">
                                <div class="profile-icon">
                                    <i class="ti ti-user"></i>
                                </div>
                                <h3 class="mb-2"><?= htmlspecialchars($data['NAMA_LGKP']) ?></h3>
                                <?php if ($data['NAMA_PANGGILAN']): ?>
                                    <p class="text-muted mb-2">"<?= htmlspecialchars($data['NAMA_PANGGILAN']) ?>"</p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-center gap-2 flex-wrap">
                                    <span class="badge bg-primary badge-detail">
                                        <i class="ti ti-id"></i> NIK: <?= $data['NIK'] ?>
                                    </span>
                                    <span class="badge bg-info badge-detail">
                                        <i class="ti ti-id-badge"></i> KK: <?= $data['NO_KK'] ?>
                                    </span>
                                    <span class="badge bg-<?= $data['JK'] == 'L' ? 'primary' : 'danger' ?> badge-detail">
                                        <i class="ti ti-<?= $data['JK'] == 'L' ? 'gender-male' : 'gender-female' ?>"></i>
                                        <?= formatJKLengkap($data['JK']) ?>
                                    </span>
                                    <span class="badge bg-success badge-detail">
                                        <i class="ti ti-calendar"></i> <?= $usia ?> Tahun
                                    </span>
                                    <span class="badge <?= getStatusTinggalBadgeColor($data['STATUS_TINGGAL']) ?> badge-detail">
                                        <i class="ti ti-<?= $data['STATUS_TINGGAL'] == 'MENINGGAL' ? 'grave' : ($data['STATUS_TINGGAL'] == 'PINDAH' ? 'map-pin' : 'home') ?>"></i>
                                        <?= formatStatusTinggalLengkap($data['STATUS_TINGGAL']) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Data Pribadi -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="section-title mb-0">
                                                <i class="ti ti-user-check"></i> Data Pribadi
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">NIK</div>
                                                <div class="col-sm-8 info-value"><?= $data['NIK'] ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">No. Kartu Keluarga</div>
                                                <div class="col-sm-8 info-value"><?= $data['NO_KK'] ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Nama Lengkap</div>
                                                <div class="col-sm-8 info-value"><?= htmlspecialchars($data['NAMA_LGKP']) ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Nama Panggilan</div>
                                                <div class="col-sm-8 info-value"><?= !empty($data['NAMA_PANGGILAN']) ? htmlspecialchars($data['NAMA_PANGGILAN']) : '<span class="text-muted">-</span>' ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Jenis Kelamin</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge bg-<?= $data['JK'] == 'L' ? 'primary' : 'danger' ?>">
                                                        <?= formatJKLengkap($data['JK']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Tempat, Tanggal Lahir</div>
                                                <div class="col-sm-8 info-value">
                                                    <?= htmlspecialchars($data['TMPT_LHR']) ?>,
                                                    <?= formatTanggalIndo($data['TGL_LHR']) ?>
                                                    <small class="text-muted">(<?= $usia ?> tahun)</small>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Agama</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge bg-info"><?= $data['AGAMA'] ?></span>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Golongan Darah</div>
                                                <div class="col-sm-8 info-value">
                                                    <?= $data['GOL_DARAH'] == 'TIDAK TAHU' ?
                                                        '<span class="text-muted">Tidak Tahu</span>' :
                                                        '<span class="badge bg-danger">' . $data['GOL_DARAH'] . '</span>' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Data Status & Keluarga -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="section-title mb-0">
                                                <i class="ti ti-users"></i> Data Status & Keluarga
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Status Perkawinan</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge bg-primary">
                                                        <?= formatStatusKawinLengkap($data['STATUS_KAWIN']) ?>
                                                    </span>
                                                    <?php if ($data['STATUS_KAWIN'] == 'KAWIN' && isset($dokumen_grup['BUKU_NIKAH'])): ?>
                                                        <small class="text-success ms-2">
                                                            <i class="ti ti-file-check"></i> Buku nikah terlampir
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Hubungan Keluarga</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge bg-secondary">
                                                        <?= formatHubKeluargaLengkap($data['HBKEL']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Status Tinggal</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge <?= getStatusTinggalBadgeColor($data['STATUS_TINGGAL']) ?>">
                                                        <?= formatStatusTinggalLengkap($data['STATUS_TINGGAL']) ?>
                                                    </span>
                                                    <?php if ($data['STATUS_TINGGAL'] == 'MENINGGAL' && isset($dokumen_grup['AKTA_KEMATIAN'])): ?>
                                                        <small class="text-success ms-2">
                                                            <i class="ti ti-file-check"></i> Akta kematian terlampir
                                                        </small>
                                                    <?php endif; ?>
                                                    <?php if ($data['STATUS_TINGGAL'] == 'PINDAH' && isset($dokumen_grup['AKTA_PINDAH'])): ?>
                                                        <small class="text-success ms-2">
                                                            <i class="ti ti-file-check"></i> Akta pindah terlampir
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Nama Ayah</div>
                                                <div class="col-sm-8 info-value"><?= htmlspecialchars($data['NAMA_LGKP_AYAH']) ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Nama Ibu</div>
                                                <div class="col-sm-8 info-value"><?= htmlspecialchars($data['NAMA_LGKP_IBU']) ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Pendidikan Terakhir</div>
                                                <div class="col-sm-8 info-value">
                                                    <?= !empty($data['PENDIDIKAN']) ?
                                                        '<span class="badge bg-warning text-dark">' . $data['PENDIDIKAN'] . '</span>' :
                                                        '<span class="text-muted">-</span>' ?>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Pekerjaan</div>
                                                <div class="col-sm-8 info-value">
                                                    <?= !empty($data['PEKERJAAN']) ?
                                                        '<span class="badge bg-success">' . htmlspecialchars($data['PEKERJAAN']) . '</span>' :
                                                        '<span class="text-muted">-</span>' ?>
                                                </div>
                                            </div>
                                            <!-- <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Kewarganegaraan</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge bg-<?= $data['KEWARGANEGARAAN'] == 'WNI' ? 'primary' : 'warning' ?>">
                                                        <?= $data['KEWARGANEGaraan'] ?>
                                                    </span>
                                                </div>
                                            </div> -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Data Alamat & Kontak -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="section-title mb-0">
                                                <i class="ti ti-map-pin"></i> Data Alamat
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Kecamatan</div>
                                                <div class="col-sm-8 info-value"><?= htmlspecialchars($data['KECAMATAN']) ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Kelurahan/Desa</div>
                                                <div class="col-sm-8 info-value"><?= htmlspecialchars($data['KELURAHAN']) ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Dusun</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($data['nama_dusun'] ?? '-') ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">RT/RW</div>
                                                <div class="col-sm-8 info-value">
                                                    <?php if ($data['rt'] || $data['rw']): ?>
                                                        RT <?= $data['rt'] ?? '-' ?> / RW <?= $data['rw'] ?? '-' ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Alamat Lengkap</div>
                                                <div class="col-sm-8 info-value">
                                                    <?= !empty($data['ALAMAT']) ? htmlspecialchars($data['ALAMAT']) : '<span class="text-muted">-</span>' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Data Kesehatan & Disabilitas -->
                                <div class="col-md-6 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="section-title mb-0">
                                                <i class="ti ti-heart"></i> Data Kesehatan & Disabilitas
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Status Disabilitas</div>
                                                <div class="col-sm-8 info-value">
                                                    <?php if ($data['DISABILITAS'] == 'YA'): ?>
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="ti ti-alert-circle"></i> Ya
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">
                                                            <i class="ti ti-check"></i> Tidak
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($data['DISABILITAS'] == 'YA' && !empty($data['JENIS_DISABILITAS'])): ?>
                                                <div class="row mb-2">
                                                    <div class="col-sm-4 info-label">Jenis Disabilitas</div>
                                                    <div class="col-sm-8 info-value">
                                                        <span class="badge bg-warning text-dark">
                                                            <?= htmlspecialchars($data['JENIS_DISABILITAS']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Informasi Sistem -->
                                            <div class="timeline mt-4">
                                                <div class="timeline-item">
                                                    <strong>Data Input</strong><br>
                                                    <small class="text-muted">
                                                        <i class="ti ti-calendar"></i>
                                                        <?= formatTanggalWaktu($data['created_at']) ?>
                                                    </small>
                                                </div>
                                                <div class="timeline-item">
                                                    <strong>Data Update Terakhir</strong><br>
                                                    <small class="text-muted">
                                                        <i class="ti ti-refresh"></i>
                                                        <?= formatTanggalWaktu($data['updated_at']) ?>
                                                    </small>
                                                </div>
                                                <div class="timeline-item">
                                                    <strong>ID Sistem</strong><br>
                                                    <small class="text-muted">
                                                        <i class="ti ti-hash"></i> #<?= $data['id'] ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dokumen Pendukung -->
                                <div class="col-12 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="section-title mb-0">
                                                <i class="ti ti-files"></i> Dokumen Pendukung
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <!-- Buku Nikah -->
                                            <?php if ($data['STATUS_KAWIN'] == 'KAWIN'): ?>
                                                <h6 class="mb-3">
                                                    <i class="ti ti-ring"></i> Dokumen Pernikahan
                                                    <?php if (isset($dokumen_grup['BUKU_NIKAH'])): ?>
                                                        <span class="badge bg-success ms-2">
                                                            <?= count($dokumen_grup['BUKU_NIKAH']) ?> Dokumen
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark ms-2">
                                                            Belum ada dokumen
                                                        </span>
                                                    <?php endif; ?>
                                                </h6>
                                                <?php if (isset($dokumen_grup['BUKU_NIKAH'])): ?>
                                                    <div class="row">
                                                        <?php foreach ($dokumen_grup['BUKU_NIKAH'] as $dokumen): ?>
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="dokumen-card">
                                                                    <div class="dokumen-header">
                                                                        <div>
                                                                            <span class="dokumen-badge">Buku Nikah</span>
                                                                            <h6 class="mt-2 mb-0"><?= htmlspecialchars($dokumen['original_name']) ?></h6>
                                                                        </div>
                                                                        <small class="text-muted">
                                                                            <?= formatTanggalIndo($dokumen['tanggal_dokumen']) ?>
                                                                        </small>
                                                                    </div>
                                                                    <?php if ($dokumen['nomor_dokumen']): ?>
                                                                        <div class="dokumen-keterangan">
                                                                            <strong>No. Dokumen:</strong> <?= htmlspecialchars($dokumen['nomor_dokumen']) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <?php if ($dokumen['keterangan']): ?>
                                                                        <div class="dokumen-keterangan">
                                                                            <?= htmlspecialchars($dokumen['keterangan']) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                                        <a href="<?= $base_url  . '/' . $dokumen['path'] ?>" target="_blank" class="file-link">
                                                                            <i class="ti ti-eye"></i> Lihat Dokumen
                                                                        </a>
                                                                        <small class="text-muted">
                                                                            <?= pathinfo($dokumen['original_name'], PATHINFO_EXTENSION) ?>
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="no-dokumen">
                                                        <i class="ti ti-files-off"></i>
                                                        <p class="mb-0">Belum ada dokumen buku nikah yang diupload</p>
                                                    </div>
                                                <?php endif; ?>
                                                <hr>
                                            <?php endif; ?>

                                            <!-- Akta Kematian -->
                                            <?php if ($data['STATUS_TINGGAL'] == 'MENINGGAL'): ?>
                                                <h6 class="mb-3">
                                                    <i class="ti ti-grave"></i> Dokumen Kematian
                                                    <?php if (isset($dokumen_grup['AKTA_KEMATIAN'])): ?>
                                                        <span class="badge bg-success ms-2">
                                                            <?= count($dokumen_grup['AKTA_KEMATIAN']) ?> Dokumen
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark ms-2">
                                                            Belum ada dokumen
                                                        </span>
                                                    <?php endif; ?>
                                                </h6>
                                                <?php if (isset($dokumen_grup['AKTA_KEMATIAN'])): ?>
                                                    <div class="row">
                                                        <?php foreach ($dokumen_grup['AKTA_KEMATIAN'] as $dokumen): ?>
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="dokumen-card">
                                                                    <div class="dokumen-header">
                                                                        <div>
                                                                            <span class="dokumen-badge">Akta Kematian</span>
                                                                            <h6 class="mt-2 mb-0"><?= htmlspecialchars($dokumen['original_name']) ?></h6>
                                                                        </div>
                                                                        <small class="text-muted">
                                                                            <?= formatTanggalIndo($dokumen['tanggal_dokumen']) ?>
                                                                        </small>
                                                                    </div>
                                                                    <?php if ($dokumen['nomor_dokumen']): ?>
                                                                        <div class="dokumen-keterangan">
                                                                            <strong>No. Akta:</strong> <?= htmlspecialchars($dokumen['nomor_dokumen']) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                                        <a href="<?= $base_url . '/' . $dokumen['path'] ?>" target="_blank" class="file-link">
                                                                            <i class="ti ti-eye"></i> Lihat Dokumen
                                                                        </a>
                                                                        <small class="text-muted">
                                                                            <?= pathinfo($dokumen['original_name'], PATHINFO_EXTENSION) ?>
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="no-dokumen">
                                                        <i class="ti ti-files-off"></i>
                                                        <p class="mb-0">Belum ada dokumen akta kematian yang diupload</p>
                                                    </div>
                                                <?php endif; ?>
                                                <hr>
                                            <?php endif; ?>

                                            <!-- Akta Pindah -->
                                            <?php if ($data['STATUS_TINGGAL'] == 'PINDAH'): ?>
                                                <h6 class="mb-3">
                                                    <i class="ti ti-map-pin"></i> Dokumen Perpindahan
                                                    <?php if (isset($dokumen_grup['AKTA_PINDAH'])): ?>
                                                        <span class="badge bg-success ms-2">
                                                            <?= count($dokumen_grup['AKTA_PINDAH']) ?> Dokumen
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark ms-2">
                                                            Belum ada dokumen
                                                        </span>
                                                    <?php endif; ?>
                                                </h6>
                                                <?php if (isset($dokumen_grup['AKTA_PINDAH'])): ?>
                                                    <div class="row">
                                                        <?php foreach ($dokumen_grup['AKTA_PINDAH'] as $dokumen): ?>
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="dokumen-card">
                                                                    <div class="dokumen-header">
                                                                        <div>
                                                                            <span class="dokumen-badge">Akta Pindah</span>
                                                                            <h6 class="mt-2 mb-0"><?= htmlspecialchars($dokumen['original_name']) ?></h6>
                                                                        </div>
                                                                        <small class="text-muted">
                                                                            <?= formatTanggalIndo($dokumen['tanggal_dokumen']) ?>
                                                                        </small>
                                                                    </div>
                                                                    <?php if ($dokumen['nomor_dokumen']): ?>
                                                                        <div class="dokumen-keterangan">
                                                                            <strong>No. Dokumen:</strong> <?= htmlspecialchars($dokumen['nomor_dokumen']) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <?php if ($dokumen['keterangan']): ?>
                                                                        <div class="dokumen-keterangan">
                                                                            <?= htmlspecialchars($dokumen['keterangan']) ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                                        <a href="<?= $base_url  . '/' . $dokumen['path'] ?>" target="_blank" class="file-link">
                                                                            <i class="ti ti-eye"></i> Lihat Dokumen
                                                                        </a>
                                                                        <small class="text-muted">
                                                                            <?= pathinfo($dokumen['original_name'], PATHINFO_EXTENSION) ?>
                                                                        </small>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="no-dokumen">
                                                        <i class="ti ti-files-off"></i>
                                                        <p class="mb-0">Belum ada dokumen akta pindah yang diupload</p>
                                                    </div>
                                                <?php endif; ?>
                                                <hr>
                                            <?php endif; ?>

                                            <!-- Dokumen Lainnya -->
                                            <?php if (isset($dokumen_grup['LAINNYA'])): ?>
                                                <h6 class="mb-3">
                                                    <i class="ti ti-files"></i> Dokumen Lainnya
                                                    <span class="badge bg-info ms-2">
                                                        <?= count($dokumen_grup['LAINNYA']) ?> Dokumen
                                                    </span>
                                                </h6>
                                                <div class="row">
                                                    <?php foreach ($dokumen_grup['LAINNYA'] as $dokumen): ?>
                                                        <div class="col-md-6 col-lg-4">
                                                            <div class="dokumen-card">
                                                                <div class="dokumen-header">
                                                                    <div>
                                                                        <span class="dokumen-badge">Lainnya</span>
                                                                        <h6 class="mt-2 mb-0"><?= htmlspecialchars($dokumen['original_name']) ?></h6>
                                                                    </div>
                                                                    <small class="text-muted">
                                                                        <?= formatTanggalWaktu($dokumen['created_at']) ?>
                                                                    </small>
                                                                </div>
                                                                <h6 class="mb-1"><?= htmlspecialchars($dokumen['original_name']) ?></h6>
                                                                <?php if ($dokumen['keterangan']): ?>
                                                                    <div class="dokumen-keterangan">
                                                                        <?= htmlspecialchars($dokumen['keterangan']) ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                                    <a href="<?= $base_url  . '/' . $dokumen['path'] ?>" target="_blank" class="file-link">
                                                                        <i class="ti ti-eye"></i> Lihat Dokumen
                                                                    </a>
                                                                    <small class="text-muted">
                                                                        <?= pathinfo($dokumen['original_name'], PATHINFO_EXTENSION) ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Tidak ada dokumen sama sekali -->
                                            <?php if (empty($dokumen_list)): ?>
                                                <div class="no-dokumen">
                                                    <i class="ti ti-files-off"></i>
                                                    <p class="mb-0">Tidak ada dokumen yang diupload</p>
                                                    <small class="text-muted">Upload dokumen melalui menu edit data</small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include_once '../includes/footer.php'; ?>
    <!-- <?php include_once '../includes/js.php'; ?> -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Print function
            function printDetail() {
                const url = `print_detail.php?id=<?= $id ?>`;
                const printWindow = window.open(url, '_blank');
                printWindow.onload = function() {
                    printWindow.print();
                };
            }

            // Preview dokumen modal
            function previewDokumen(url, title) {
                const extension = url.split('.').pop().toLowerCase();

                if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                    // Preview gambar
                    Swal.fire({
                        title: title,
                        html: `<img src="${url}" class="img-fluid" alt="${title}">`,
                        showCloseButton: true,
                        showConfirmButton: false,
                        width: '80%'
                    });
                } else if (extension === 'pdf') {
                    // Preview PDF (membuka di tab baru karena Swal tidak support iframe dengan CORS)
                    window.open(url, '_blank');
                } else {
                    // Dokumen lain, download saja
                    window.open(url, '_blank');
                }
            }

            // Add click event to all file-link for preview, but allow download if .download-link is clicked
            document.querySelectorAll('.file-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Jika klik pada tombol download, jangan preventDefault
                    if (e.target.classList.contains('download-link')) {
                        // allow default download
                        return;
                    }
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    let title = '';
                    const h6 = this.closest('.dokumen-card')?.querySelector('h6');
                    if (h6) title = h6.textContent;
                    previewDokumen(url, title);
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl + P untuk print
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    printDetail();
                }
                // Escape untuk kembali
                if (e.key === 'Escape') {
                    window.location.href = 'list.php';
                }
                // Ctrl + E untuk edit
                if (e.ctrlKey && e.key === 'e') {
                    e.preventDefault();
                    window.location.href = 'edit.php?id=<?= $id ?>';
                }
            });

            // Copy NIK atau KK
            function copyToClipboard(text, label) {
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: `${label} berhasil disalin!`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }).catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal menyalin teks',
                        timer: 1500,
                        showConfirmButton: false
                    });
                });
            }

            // Add copy functionality to NIK and KK badges
            document.querySelector('.badge-detail:nth-child(1)').addEventListener('click', function() {
                copyToClipboard('<?= $data['NIK'] ?>', 'NIK');
            });

            document.querySelector('.badge-detail:nth-child(2)').addEventListener('click', function() {
                copyToClipboard('<?= $data['NO_KK'] ?>', 'No. KK');
            });
        });
    </script>
</body>

</html>