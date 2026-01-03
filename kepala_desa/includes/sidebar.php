<?php

$current_uri = $_SERVER['REQUEST_URI'];

function isActive($path)
{
    global $current_uri;
    return strpos($current_uri, $path) !== false ? 'active' : '';
}

?>

<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
    <div class="navbar-wrapper">
        <div class="p-3">
            <a href="<?= $base_url ?>/kepala_desa/index.php"
                class="b-brand text-primary d-inline-flex align-items-center gap-2 text-decoration-none">

                <!-- Logo -->
                <img
                    src="<?= $base_url ?>/assets/img/LogoKBS.png"
                    style="max-height: 50px; object-fit: contain;"
                    class="img-fluid logo-lg"
                    alt="logo" />

                <!-- Teks di samping logo -->
                <span class="fw-bold fs-5 text-dark">DESA KURNIABAKTI</span>
            </a>
        </div>
        <hr>
        <div class="flex-grow-1 ms-3 d-flex align-items-center gap-2">
            <!-- Avatar User -->
            <img
                src="<?= $base_url ?>/assets/img/user.png"
                alt="User Avatar"
                class="rounded-circle border"
                style="width: 38px; height: 38px; object-fit: cover;">

            <!-- Nama User -->
            <div>
                <h6 class="mb-0">
                    <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']) ?>
                </h6>
                <!-- <small class="text-muted"><?= htmlspecialchars($_SESSION['role'] ?? '-') ?></small> -->
            </div>
        </div>

        <hr>
        <div class="navbar-content">
            <ul class="pc-navbar <?= isActive('/kepala_desa/index.php') ?>">
                <li class="pc-item">
                    <a href="<?= $base_url ?>/kepala_desa/index.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-smart-home"></i></span>
                        <span class="pc-mtext">Dashboard</span>
                    </a>
                </li>

                <li class="pc-item <?= isActive('/kependudukan') ?>">
                    <a href="<?= $base_url ?>/kepala_desa/kependudukan/list.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-user"></i></span>
                        <span class="pc-mtext">Data Kependudukan</span>
                    </a>
                </li>

                <li class="pc-item <?= isActive('/kondisi_rumah') ?>">
                    <a href="<?= $base_url ?>/kepala_desa/kondisi_rumah/list.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-home"></i></span>
                        <span class="pc-mtext">Data Kondisi Rumah</span>
                    </a>
                </li>

                <li class="pc-item pc-caption">
                    <label>LAPORAN</label>
                    <i class="ti ti-dashboard"></i>
                </li>

                <li class="pc-item <?= isActive('/klasifikasi') ?>">
                    <a href="<?= $base_url ?>/kepala_desa/klasifikasi/list.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-users"></i></span>
                        <span class="pc-mtext">Klasifikasi Penduduk</span>
                    </a>
                </li>

                <li class="pc-item <?= isActive('/arsip_surat') ?>">
                    <a href="<?= $base_url ?>/kepala_desa/arsip_surat/list.php" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-report-money"></i></span>
                        <span class="pc-mtext">Arsip Surat</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>
<!-- [ Sidebar Menu ] end -->