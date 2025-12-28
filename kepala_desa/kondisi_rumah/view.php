<?php

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

// Cek apakah ada parameter NIK yang dikirim
if (!isset($_GET['nik']) || empty($_GET['nik'])) {
    header("Location: list.php");
    exit();
}

$nik_view = $conn->real_escape_string($_GET['nik']);

// Ambil data kondisi rumah berdasarkan NIK
$sql_rumah = "SELECT * FROM tabel_rumah WHERE NIK = '$nik_view'";
$result_rumah = query($sql_rumah);

if (empty($result_rumah)) {
    $_SESSION['error'] = "Data kondisi rumah tidak ditemukan";
    header("Location: list.php");
    exit();
}

$data_rumah = $result_rumah[0];

// Ambil data penduduk berdasarkan NIK
$sql_penduduk = "SELECT * FROM tabel_kependudukan WHERE NIK = '$nik_view'";
$result_penduduk = query($sql_penduduk);

if (empty($result_penduduk)) {
    $_SESSION['error'] = "Data penduduk tidak ditemukan";
    header("Location: list.php");
    exit();
}

$data_penduduk = $result_penduduk[0];

// Ambil data dusun
$dusun_id = $data_penduduk['DSN'];
$sql_dusun = "SELECT * FROM tabel_dusun WHERE id = '$dusun_id'";
$result_dusun = query($sql_dusun);
$dusun_nama = !empty($result_dusun) ? $result_dusun[0]['dusun'] : 'Tidak diketahui';

// Data untuk label
$jenis_lantai_labels = [
    'SEMEN' => 'Semen',
    'KERAMIK' => 'Keramik',
    'MARMER' => 'Marmer',
    'PARQUET' => 'Parquet',
    'UBIN/TEGEL' => 'Ubin/Tegel',
    'TANAH' => 'Tanah',
    'BAMBU' => 'Bambu',
    'KAYU' => 'Kayu',
    'LAINNYA' => 'Lainnya'
];

$jenis_dinding_labels = [
    'TEMBOK' => 'Tembok',
    'PAPAN' => 'Papan',
    'BAMBU' => 'Bambu',
    'KAYU' => 'Kayu',
    'ANYAMAN' => 'Anyaman',
    'LAINNYA' => 'Lainnya'
];

$fasilitas_mck_labels = [
    'JAMBAN SENDIRI' => 'Jamban Sendiri',
    'JAMBAN BERSAMA' => 'Jamban Bersama',
    'JAMBAN UMUM' => 'Jamban Umum',
    'TIDAK ADA' => 'Tidak Ada'
];

$sumber_penerangan_labels = [
    'PLN' => 'PLN',
    'GENSET' => 'Genset',
    'SOLAR CELL' => 'Solar Cell',
    'LAMPU MINYAK' => 'Lampu Minyak',
    'TIDAK ADA' => 'Tidak Ada'
];

$sumber_air_minum_labels = [
    'PDAM' => 'PDAM',
    'SUMUR BOR' => 'Sumur Bor',
    'SUMUR GALI' => 'Sumur Gali',
    'MATA AIR' => 'Mata Air',
    'AIR HUJAN' => 'Air Hujan',
    'AIR KEMASAN' => 'Air Kemasan',
    'LAINNYA' => 'Lainnya'
];

$bahan_bakar_labels = [
    'GAS' => 'Gas',
    'KAYU BAKAR' => 'Kayu Bakar',
    'MINYAK TANAH' => 'Minyak Tanah',
    'BRIKET' => 'Briket',
    'LISTRIK' => 'Listrik',
    'LAINNYA' => 'Lainnya'
];

$kondisi_rumah_labels = [
    'LAYAK HUNI' => 'Layak Huni',
    'RUSAK RINGAN' => 'Rusak Ringan',
    'RUSAK BERAT' => 'Rusak Berat'
];

$status_tempat_tinggal_labels = [
    'MILIK SENDIRI' => 'Milik Sendiri',
    'SEWA' => 'Sewa',
    'KONTRAK' => 'Kontrak',
    'BEBAS SEWA' => 'Bebas Sewa',
    'LAINNYA' => 'Lainnya'
];

// Format tanggal
function formatTanggal($date)
{
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d F Y', strtotime($date));
}

function formatDateTime($datetime)
{
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    return date('d/m/Y H:i', strtotime($datetime));
}
?>

<style>
    .swal2-container {
        z-index: 99999 !important;
    }

    .info-section {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        border-left: 4px solid #0d6efd;
    }

    .info-section h5 {
        color: #0d6efd;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }

    .info-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
    }

    .info-value {
        padding: 8px 12px;
        background-color: white;
        border-radius: 4px;
        border: 1px solid #dee2e6;
        margin-bottom: 15px;
        min-height: 42px;
        display: flex;
        align-items: center;
    }

    .info-value.empty {
        color: #6c757d;
        font-style: italic;
    }

    .badge-status {
        font-size: 0.85em;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .badge-layak {
        background-color: #20c997;
        color: white;
    }

    .badge-rusak-ringan {
        background-color: #fd7e14;
        color: white;
    }

    .badge-rusak-berat {
        background-color: #dc3545;
        color: white;
    }

    .badge-milik {
        background-color: #198754;
        color: white;
    }

    .badge-sewa {
        background-color: #0dcaf0;
        color: #000;
    }

    .badge-fasilitas {
        background-color: #6f42c1;
        color: white;
    }

    .data-penduduk-info {
        background-color: #e8f4fd;
        border-left: 4px solid #0d6efd;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .data-info-box {
        background-color: #f0f7ff;
        border: 1px solid #cfe2ff;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .fasilitas-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .fasilitas-icon.bathroom {
        background-color: #17a2b8;
        color: white;
    }

    .fasilitas-icon.electricity {
        background-color: #ffc107;
        color: #212529;
    }

    .fasilitas-icon.water {
        background-color: #0d6efd;
        color: white;
    }

    .fasilitas-icon.fuel {
        background-color: #dc3545;
        color: white;
    }

    .fasilitas-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        padding: 10px;
        background-color: white;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }

    .fasilitas-text {
        flex: 1;
    }

    .fasilitas-title {
        font-weight: 600;
        color: #495057;
    }

    .fasilitas-desc {
        font-size: 0.9em;
        color: #6c757d;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
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
                    <h2>Detail Data Kondisi Rumah</h2>
                    <div class="action-buttons">
                        <a href="list.php" class="btn btn-secondary">
                            <i class="ti ti-arrow-back"></i> Kembali
                        </a>
                        <button type="button" class="btn btn-info" onclick="window.print()">
                            <i class="ti ti-printer"></i> Cetak
                        </button>
                    </div>
                </div>

                <!-- Info Data Pemilik Rumah -->
                <div class="data-info-box">
                    <h5><i class="ti ti-user"></i> Data Pemilik Rumah</h5>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <strong>NIK:</strong> <?= formatKKNIK($data_penduduk['NIK']); ?>
                        </div>
                        <div class="col-md-3 mb-2">
                            <strong>No. KK:</strong> <?= formatKKNIK($data_penduduk['NO_KK']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Nama Lengkap:</strong> <?= htmlspecialchars($data_penduduk['NAMA_LGKP']); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Jenis Kelamin:</strong>
                            <?= $data_penduduk['JK'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Tempat/Tgl Lahir:</strong>
                            <?= htmlspecialchars($data_penduduk['TMPT_LHR']); ?> /
                            <?= date('d-m-Y', strtotime($data_penduduk['TGL_LHR'])); ?>
                        </div>
                        <div class="col-md-4 mb-2">
                            <strong>Alamat:</strong>
                            <?= $dusun_nama ? 'Dusun ' . htmlspecialchars($dusun_nama) : '' ?>
                            <?= $data_penduduk['rt'] ? ', RT ' . $data_penduduk['rt'] : '' ?>
                            <?= $data_penduduk['rw'] ? '/RW ' . $data_penduduk['rw'] : '' ?>
                        </div>
                    </div>
                </div>

                <!-- Informasi Umum Rumah -->
                <div class="info-section">
                    <h5><i class="ti ti-home"></i> Informasi Umum Rumah</h5>
                    <div class="info-grid">
                        <div>
                            <div class="info-label">Status Tempat Tinggal</div>
                            <div class="info-value">
                                <?php
                                $status = $data_rumah['status_tempat_tinggal'] ?? 'MILIK SENDIRI';
                                $status_label = $status_tempat_tinggal_labels[$status] ?? $status;
                                $badge_class = $status == 'MILIK SENDIRI' ? 'badge-milik' : 'badge-sewa';
                                ?>
                                <span class="badge <?= $badge_class ?> badge-status"><?= $status_label ?></span>
                            </div>
                        </div>

                        <div>
                            <div class="info-label">Luas Lantai</div>
                            <div class="info-value">
                                <?= $data_rumah['luas_lantai'] ? number_format($data_rumah['luas_lantai'], 1) . ' m²' : '<span class="empty">-</span>' ?>
                            </div>
                        </div>

                        <div>
                            <div class="info-label">Kondisi Rumah</div>
                            <div class="info-value">
                                <?php
                                $kondisi = $data_rumah['kondisi_rumah'] ?? 'LAYAK HUNI';
                                $kondisi_label = $kondisi_rumah_labels[$kondisi] ?? $kondisi;
                                $badge_class = '';
                                if ($kondisi == 'LAYAK HUNI') $badge_class = 'badge-layak';
                                elseif ($kondisi == 'RUSAK RINGAN') $badge_class = 'badge-rusak-ringan';
                                elseif ($kondisi == 'RUSAK BERAT') $badge_class = 'badge-rusak-berat';
                                ?>
                                <span class="badge <?= $badge_class ?> badge-status"><?= $kondisi_label ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Konstruksi Rumah -->
                <div class="info-section">
                    <h5><i class="ti ti-tools"></i> Konstruksi Rumah</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="info-label">Jenis Lantai</div>
                            <div class="info-value">
                                <?= !empty($data_rumah['jenis_lantai']) ? $jenis_lantai_labels[$data_rumah['jenis_lantai']] ?? $data_rumah['jenis_lantai'] : '<span class="empty">-</span>' ?>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="info-label">Jenis Dinding</div>
                            <div class="info-value">
                                <?= !empty($data_rumah['jenis_dinding']) ? $jenis_dinding_labels[$data_rumah['jenis_dinding']] ?? $data_rumah['jenis_dinding'] : '<span class="empty">-</span>' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Fasilitas Rumah -->
                    <div class="info-section col-md-6">
                        <h5><i class="ti ti-settings"></i> Fasilitas Rumah</h5>

                        <div class="fasilitas-item">
                            <div class="fasilitas-icon bathroom">
                                <i class="ti ti-bath"></i>
                            </div>
                            <div class="fasilitas-text">
                                <div class="fasilitas-title">Fasilitas MCK</div>
                                <div class="fasilitas-desc">
                                    <?= !empty($data_rumah['fasilitas_bab']) ? $fasilitas_mck_labels[$data_rumah['fasilitas_bab']] ?? $data_rumah['fasilitas_bab'] : '<span class="empty">-</span>' ?>
                                </div>
                            </div>
                        </div>

                        <div class="fasilitas-item">
                            <div class="fasilitas-icon electricity">
                                <i class="ti ti-bolt"></i>
                            </div>
                            <div class="fasilitas-text">
                                <div class="fasilitas-title">Sumber Penerangan</div>
                                <div class="fasilitas-desc">
                                    <?= !empty($data_rumah['sumber_penerangan']) ? $sumber_penerangan_labels[$data_rumah['sumber_penerangan']] ?? $data_rumah['sumber_penerangan'] : '<span class="empty">-</span>' ?>
                                </div>
                            </div>
                        </div>

                        <div class="fasilitas-item">
                            <div class="fasilitas-icon water">
                                <i class="ti ti-droplet"></i>
                            </div>
                            <div class="fasilitas-text">
                                <div class="fasilitas-title">Sumber Air Minum</div>
                                <div class="fasilitas-desc">
                                    <?= !empty($data_rumah['sumber_air_minum']) ? $sumber_air_minum_labels[$data_rumah['sumber_air_minum']] ?? $data_rumah['sumber_air_minum'] : '<span class="empty">-</span>' ?>
                                </div>
                            </div>
                        </div>

                        <div class="fasilitas-item">
                            <div class="fasilitas-icon fuel">
                                <i class="ti ti-flame"></i>
                            </div>
                            <div class="fasilitas-text">
                                <div class="fasilitas-title">Bahan Bakar Memasak</div>
                                <div class="fasilitas-desc">
                                    <?= !empty($data_rumah['bahan_bakar_memasak']) ? $bahan_bakar_labels[$data_rumah['bahan_bakar_memasak']] ?? $data_rumah['bahan_bakar_memasak'] : '<span class="empty">-</span>' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Timestamp -->
                    <div class="info-section col-md-6">
                        <h5><i class="ti ti-clock"></i> Informasi Sistem</h5>
                        <div class="info-grid">
                            <div>
                                <div class="info-label">Data Dibuat</div>
                                <div class="info-value">
                                    <?= dateIndo($data_rumah['created_at'] ?? '') ?>
                                </div>
                            </div>

                            <div>
                                <div class="info-label">Terakhir Diperbarui</div>
                                <div class="info-value">
                                    <?= dateIndo($data_rumah['updated_at'] ?? '') ?>
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

</body>
<!-- [Body] end -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Fungsi untuk konfirmasi hapus
    function confirmDelete(nik, nama) {
        Swal.fire({
            title: 'Hapus Data Rumah?',
            html: `Apakah Anda yakin ingin menghapus data kondisi rumah milik <strong>${nama}</strong>?<br><br>
                   <small class="text-danger">Perhatian: Tindakan ini tidak dapat dibatalkan!</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect ke delete_rumah.php
                window.location.href = `delete_rumah.php?nik=${encodeURIComponent(nik)}`;
            }
        });
    }

    // SweetAlert untuk pesan session
    <?php if (isset($_SESSION['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Sukses!',
            text: '<?= addslashes($_SESSION['success']) ?>',
            confirmButtonColor: '#3085d6',
            timer: 3000,
            timerProgressBar: true
        });
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '<?= addslashes($_SESSION['error']) ?>',
            confirmButtonColor: '#d33'
        });
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    // Fungsi untuk format tanggal Indonesia
    function formatTanggalIndo(dateStr) {
        if (!dateStr) return '-';

        const date = new Date(dateStr);
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('id-ID', options);
    }

    // Update tanggal format jika diperlukan
    document.addEventListener('DOMContentLoaded', function() {
        // Anda bisa menambahkan kode untuk memformat tanggal client-side di sini
    });
</script>

</html>