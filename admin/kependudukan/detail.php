<?php
// view.php
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
        'PENDATANG' => 'Pendatang'
    ];
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Data Penduduk</title>
    <?php include_once '../includes/css.php'; ?>
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
    </style>
</head>

<body>
    <?php include_once '../includes/navbar.php'; ?>
    <?php include_once '../includes/sidebar.php'; ?>

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

                            <!-- Profile Header -->
                            <div class="profile-header text-center mb-4">
                                <div class="profile-icon">
                                    <i class="ti ti-user"></i>
                                </div>
                                <h3 class="mb-2"><?= htmlspecialchars($data['NAMA_LGKP']) ?></h3>
                                <?php if ($data['NAMA_PANGGILAN']): ?>
                                    <p class="text-muted mb-2">"<?= htmlspecialchars($data['NAMA_PANGGILAN']) ?>"</p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-center gap-2">
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
                                                    <?= date('d F Y', strtotime($data['TGL_LHR'])) ?>
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
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Kewarganegaraan</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge bg-<?= $data['KEWARGANEGARAAN'] == 'WNI' ? 'primary' : 'warning' ?>">
                                                        <?= $data['KEWARGANEGARAAN'] ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-sm-4 info-label">Status Tinggal</div>
                                                <div class="col-sm-8 info-value">
                                                    <span class="badge bg-info">
                                                        <?= formatStatusTinggalLengkap($data['STATUS_TINGGAL']) ?>
                                                    </span>
                                                </div>
                                            </div>
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
                                                        <?= date('d F Y H:i', strtotime($data['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <div class="timeline-item">
                                                    <strong>Data Update Terakhir</strong><br>
                                                    <small class="text-muted">
                                                        <i class="ti ti-refresh"></i>
                                                        <?= date('d F Y H:i', strtotime($data['updated_at'])) ?>
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
                            </div>

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
            // Delete confirmation
            const deleteBtn = document.querySelector('.delete-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    Swal.fire({
                        title: 'Apakah Anda yakin?',
                        text: `Anda akan menghapus data ${name}. Tindakan ini tidak dapat dibatalkan!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            return new Promise((resolve) => {
                                window.location.href = `list.php?delete=${id}`;
                            });
                        }
                    });
                });
            }

            // Print function
            function printDetail() {
                const url = `print_detail.php?id=<?= $id ?>`;
                const printWindow = window.open(url, '_blank');
                printWindow.onload = function() {
                    printWindow.print();
                };
            }

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
            });
        });
    </script>
</body>

</html>