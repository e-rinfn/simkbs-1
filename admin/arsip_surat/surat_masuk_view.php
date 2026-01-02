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

// Cek apakah ada parameter ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID surat tidak valid!';
    header("Location: list.php?tab=masuk");
    exit();
}

$id = intval($_GET['id']);

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

// Badge untuk status
$status_badges = [
    'BARU' => '<span class="badge bg-info text-dark">BARU</span>',
    'DIPROSES' => '<span class="badge bg-warning text-dark">DIPROSES</span>',
    'SELESAI' => '<span class="badge bg-success">SELESAI</span>',
    'ARSIP' => '<span class="badge bg-secondary">ARSIP</span>'
];

// Badge untuk sifat surat
$sifat_badges = [
    'BIASA' => '<span class="badge bg-primary">BIASA</span>',
    'PENTING' => '<span class="badge bg-warning text-dark">PENTING</span>',
    'RAHASIA' => '<span class="badge bg-danger">RAHASIA</span>',
    'SANGAT RAHASIA' => '<span class="badge bg-dark">SANGAT RAHASIA</span>'
];

?>

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
            <div class="row">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Detail Surat Masuk</h2>
                    <div>
                        <a href="surat_masuk_edit.php?id=<?= $surat['id'] ?>" class="btn btn-warning me-2">
                            <i class="ti ti-edit"></i> Edit
                        </a>
                        <a href="list.php?tab=masuk" class="btn btn-secondary">
                            <i class="ti ti-arrow-back"></i> Kembali
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Sukses!</strong> <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <div class="col-12">
                    <div class="card">
                        <!-- Header Card -->
                        <div class="card-header border-bottom py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">No. Surat: <?= htmlspecialchars($surat['nomor_surat']) ?></h5>
                                <div>
                                    <?= $status_badges[$surat['status']] ?? '<span class="badge bg-secondary">UNKNOWN</span>' ?>
                                    <?= $sifat_badges[$surat['sifat_surat']] ?? '<span class="badge bg-secondary">UNKNOWN</span>' ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Informasi Surat</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td style="width: 40%;">
                                                <strong>Nomor Surat</strong>
                                            </td>
                                            <td>: <?= htmlspecialchars($surat['nomor_surat']) ?></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Tanggal Surat</strong>
                                            </td>
                                            <td>: <?= date('d/m/Y', strtotime($surat['tanggal_surat'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Tanggal Diterima</strong>
                                            </td>
                                            <td>: <?= date('d/m/Y', strtotime($surat['tanggal_diterima'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Sifat Surat</strong>
                                            </td>
                                            <td>: <?= htmlspecialchars($surat['sifat_surat']) ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Detail Tambahan</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td style="width: 40%;">
                                                <strong>Pengirim</strong>
                                            </td>
                                            <td>: <?= htmlspecialchars($surat['pengirim']) ?></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Status</strong>
                                            </td>
                                            <td>: <?= htmlspecialchars($surat['status']) ?></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Dibuat Pada</strong>
                                            </td>
                                            <td>: <?= date('d/m/Y H:i', strtotime($surat['created_at'] ?? 'now')) ?></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Diupdate Pada</strong>
                                            </td>
                                            <td>: <?= date('d/m/Y H:i', strtotime($surat['updated_at'] ?? 'now')) ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <hr>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-muted mb-3">Perihal</h6>
                                    <p class="border-start border-primary ps-3">
                                        <?= nl2br(htmlspecialchars($surat['perihal'])) ?>
                                    </p>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="text-muted mb-3">Keterangan</h6>
                                    <p class="border-start border-info ps-3">
                                        <?= nl2br(htmlspecialchars($surat['keterangan'] ?? '-')) ?>
                                    </p>
                                </div>
                            </div>

                            <?php if (!empty($surat['file_surat'])): ?>
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-3">Lampiran Surat</h6>
                                        <div class="card border-light">
                                            <div class="card-body">
                                                <p class="mb-2">
                                                    <i class="ti ti-file-text text-primary"></i>
                                                    <strong><?= htmlspecialchars($surat['file_surat']) ?></strong>
                                                </p>
                                                <?php
                                                $file_path = $base_upload_path_surat . $surat['file_surat'];
                                                $file_url = $base_url_path_surat . $surat['file_surat'];
                                                $file_exists = file_exists($file_path);
                                                ?>
                                                <div class="btn-group" role="group">
                                                    <a href="<?= $base_url ?>/uploads/surat/<?= htmlspecialchars($surat['file_surat']) ?>"
                                                        class="btn btn-sm btn-primary" target="_blank" download>
                                                        <i class="ti ti-download me-1"></i>Download
                                                    </a>
                                                    <a href="<?= $base_url ?>/uploads/surat/<?= htmlspecialchars($surat['file_surat']) ?>"
                                                        class="btn btn-sm btn-info" target="_blank">
                                                        <i class="ti ti-eye me-1"></i>Lihat
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Footer Card -->
                        <div class="card-footer bg-light d-flex justify-content-between">
                            <div>
                                <small class="text-muted">
                                    <i class="ti ti-clock me-1"></i>
                                    Dibuat: <?= date('d/m/Y H:i', strtotime($surat['created_at'] ?? 'now')) ?>
                                </small>
                            </div>
                            <div>
                                <a href="surat_masuk_edit.php?id=<?= $surat['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="ti ti-edit me-1"></i>Edit Surat
                                </a>
                                <a href="list.php?tab=masuk" class="btn btn-sm btn-secondary">
                                    <i class="ti ti-arrow-back me-1"></i>Kembali ke List
                                </a>
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

</html>