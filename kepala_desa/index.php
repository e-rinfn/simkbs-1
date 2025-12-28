<?php
require_once '../config/functions.php';
require_once './includes/header.php';

// Ambil data statistik penduduk
$sql_total_penduduk = "SELECT COUNT(*) as total FROM tabel_kependudukan";
$result_total = query($sql_total_penduduk);
$total_penduduk = $result_total[0]['total'] ?? 0;

$sql_pria = "SELECT COUNT(*) as total FROM tabel_kependudukan WHERE JK = 'L'";
$result_pria = query($sql_pria);
$total_pria = $result_pria[0]['total'] ?? 0;

$sql_wanita = "SELECT COUNT(*) as total FROM tabel_kependudukan WHERE JK = 'P'";
$result_wanita = query($sql_wanita);
$total_wanita = $result_wanita[0]['total'] ?? 0;

// Ambil data per dusun
$sql_dusun = "SELECT 
                d.id,
                d.dusun,
                COUNT(k.NIK) as jumlah_penduduk,
                SUM(CASE WHEN k.JK = 'L' THEN 1 ELSE 0 END) as pria,
                SUM(CASE WHEN k.JK = 'P' THEN 1 ELSE 0 END) as wanita
              FROM tabel_dusun d
              LEFT JOIN tabel_kependudukan k ON d.id = k.DSN
              GROUP BY d.id, d.dusun
              ORDER BY d.dusun";

$data_dusun = query($sql_dusun);

// Hitung persentase
$persen_pria = $total_penduduk > 0 ? round(($total_pria / $total_penduduk) * 100, 1) : 0;
$persen_wanita = $total_penduduk > 0 ? round(($total_wanita / $total_penduduk) * 100, 1) : 0;
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
    <?php include_once './includes/sidebar.php'; ?>
    <!-- Sidebar End -->

    <?php include_once './includes/navbar.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">

            <!-- [ Main Content ] start -->
            <div class="row">

                <!-- Card Pria -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted fw-semibold">Penduduk Pria</span>
                                <h3 class="fw-bold mb-0 mt-1">
                                    <?= number_format($total_pria, 0, ',', '.') ?>
                                </h3>
                                <small class="text-muted">Jiwa</small>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                                style="width:56px; height:56px;">
                                <i class="ti ti-user fs-3 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Wanita -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted fw-semibold">Penduduk Wanita</span>
                                <h3 class="fw-bold mb-0 mt-1">
                                    <?= number_format($total_wanita, 0, ',', '.') ?>
                                </h3>
                                <small class="text-muted">Jiwa</small>
                            </div>
                            <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                                style="width:56px; height:56px;">
                                <i class="ti ti-user fs-3 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Total Penduduk -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted fw-semibold">Total Penduduk</span>
                                <h3 class="fw-bold mb-0 mt-1">
                                    <?= number_format($total_penduduk, 0, ',', '.') ?>
                                </h3>
                                <small class="text-muted">Jiwa</small>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                                style="width:56px; height:56px;">
                                <i class="ti ti-users fs-3 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>


                <hr>
                <!-- Card Penduduk Per Dusun -->
                <div class="col-12">
                    <div class="row">

                        <?php if (empty($data_dusun)): ?>
                            <div class="col-12 text-center py-5">
                                <i class="ti ti-home-off fs-1 text-muted mb-2 d-block"></i>
                                <div class="text-muted">Data dusun belum tersedia</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($data_dusun as $dusun): ?>
                                <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                    <div class="card h-100 shadow-sm border-0">
                                        <div class="card-body">

                                            <!-- Header -->
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-semibold mb-0">
                                                    <?= htmlspecialchars($dusun['dusun']) ?>
                                                </h6>
                                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                                    <i class="ti ti-home me-1"></i>
                                                </span>
                                            </div>

                                            <!-- Jumlah Penduduk -->
                                            <h3 class="fw-bold mb-0">
                                                <?= number_format($dusun['jumlah_penduduk'], 0, ',', '.') ?>
                                            </h3>
                                            <small class="text-muted">Jiwa</small>

                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>


            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    <?php include_once './includes/footer.php'; ?>

</body>
<!-- [Body] end -->

</html>