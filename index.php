<?php
session_start();

// Koneksi ke database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'simkbs_fix';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Fungsi untuk query database
function query($sql)
{
    global $conn;
    $result = mysqli_query($conn, $sql);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// Fungsi untuk mengambil data statistik
function getStatistics()
{
    global $conn;

    $stats = [];

    // Total Penduduk
    $sql_total = "SELECT COUNT(*) as total FROM tabel_kependudukan";
    $result = mysqli_query($conn, $sql_total);
    $stats['total_penduduk'] = mysqli_fetch_assoc($result)['total'];

    // Jenis Kelamin
    $sql_laki = "SELECT COUNT(*) as total FROM tabel_kependudukan WHERE JK = 'L'";
    $result = mysqli_query($conn, $sql_laki);
    $stats['laki_laki'] = mysqli_fetch_assoc($result)['total'];

    $sql_perempuan = "SELECT COUNT(*) as total FROM tabel_kependudukan WHERE JK = 'P'";
    $result = mysqli_query($conn, $sql_perempuan);
    $stats['perempuan'] = mysqli_fetch_assoc($result)['total'];

    // Persentase
    if ($stats['total_penduduk'] > 0) {
        $stats['persen_laki'] = round(($stats['laki_laki'] / $stats['total_penduduk']) * 100, 1);
        $stats['persen_perempuan'] = round(($stats['perempuan'] / $stats['total_penduduk']) * 100, 1);
    } else {
        $stats['persen_laki'] = 0;
        $stats['persen_perempuan'] = 0;
    }

    return $stats;
}

// Fungsi untuk data status kawin
function getStatusKawin()
{
    $sql = "SELECT 
            SUM(CASE WHEN STATUS_KAWIN = 'KAWIN' THEN 1 ELSE 0 END) as kawin,
            SUM(CASE WHEN STATUS_KAWIN = 'BELUM KAWIN' THEN 1 ELSE 0 END) as belum_kawin,
            SUM(CASE WHEN STATUS_KAWIN IN ('CERAI HIDUP', 'CERAI MATI') THEN 1 ELSE 0 END) as janda_duda,
            COUNT(*) as total
            FROM tabel_kependudukan";
    $result = query($sql);

    if (!empty($result)) {
        $data = $result[0];
        if ($data['total'] > 0) {
            $data['persen_kawin'] = round(($data['kawin'] / $data['total']) * 100, 1);
            $data['persen_belum_kawin'] = round(($data['belum_kawin'] / $data['total']) * 100, 1);
            $data['persen_janda_duda'] = round(($data['janda_duda'] / $data['total']) * 100, 1);
        } else {
            $data['persen_kawin'] = $data['persen_belum_kawin'] = $data['persen_janda_duda'] = 0;
        }
        return $data;
    }
    return null;
}

// Fungsi untuk data pendidikan
function getPendidikan()
{
    $sql = "SELECT PENDIDIKAN, COUNT(*) as jumlah 
            FROM tabel_kependudukan 
            WHERE PENDIDIKAN IS NOT NULL 
            GROUP BY PENDIDIKAN";
    $data = query($sql);

    // Hitung total
    $sql_total = "SELECT COUNT(*) as total FROM tabel_kependudukan WHERE PENDIDIKAN IS NOT NULL";
    $result = query($sql_total);
    $total = $result[0]['total'];

    $pendidikan_data = [];
    foreach ($data as $row) {
        $pendidikan_data[$row['PENDIDIKAN']] = [
            'jumlah' => $row['jumlah'],
            'persen' => $total > 0 ? round(($row['jumlah'] / $total) * 100, 1) : 0
        ];
    }

    return $pendidikan_data;
}

// Fungsi untuk data pekerjaan
function getPekerjaan()
{
    $sql = "SELECT PEKERJAAN, COUNT(*) as jumlah 
            FROM tabel_kependudukan 
            WHERE PEKERJAAN IS NOT NULL AND PEKERJAAN != '' 
            GROUP BY PEKERJAAN 
            ORDER BY jumlah DESC 
            LIMIT 10";
    $data = query($sql);

    // Hitung total
    $sql_total = "SELECT COUNT(*) as total FROM tabel_kependudukan WHERE PEKERJAAN IS NOT NULL AND PEKERJAAN != ''";
    $result = query($sql_total);
    $total = $result[0]['total'];

    $pekerjaan_data = [];
    foreach ($data as $row) {
        $pekerjaan_data[$row['PEKERJAAN']] = [
            'jumlah' => $row['jumlah'],
            'persen' => $total > 0 ? round(($row['jumlah'] / $total) * 100, 1) : 0
        ];
    }

    return $pekerjaan_data;
}

// Fungsi untuk data kelompok umur
function getKelompokUmur()
{
    $sql = "SELECT 
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, TGL_LHR, CURDATE()) BETWEEN 0 AND 14 THEN 1 ELSE 0 END) as usia_0_14,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, TGL_LHR, CURDATE()) BETWEEN 15 AND 64 THEN 1 ELSE 0 END) as usia_15_64,
            SUM(CASE WHEN TIMESTAMPDIFF(YEAR, TGL_LHR, CURDATE()) >= 65 THEN 1 ELSE 0 END) as usia_65_plus,
            AVG(TIMESTAMPDIFF(YEAR, TGL_LHR, CURDATE())) as rata_umur,
            COUNT(*) as total
            FROM tabel_kependudukan";
    $result = query($sql);

    if (!empty($result)) {
        $data = $result[0];
        if ($data['total'] > 0) {
            $data['persen_0_14'] = round(($data['usia_0_14'] / $data['total']) * 100, 1);
            $data['persen_15_64'] = round(($data['usia_15_64'] / $data['total']) * 100, 1);
            $data['persen_65_plus'] = round(($data['usia_65_plus'] / $data['total']) * 100, 1);
            $data['rata_umur'] = round($data['rata_umur'], 1);
        } else {
            $data['persen_0_14'] = $data['persen_15_64'] = $data['persen_65_plus'] = 0;
            $data['rata_umur'] = 0;
        }
        return $data;
    }
    return null;
}

// Fungsi untuk data agama
function getAgama()
{
    $sql = "SELECT AGAMA, COUNT(*) as jumlah 
            FROM tabel_kependudukan 
            GROUP BY AGAMA 
            ORDER BY jumlah DESC";
    $data = query($sql);

    // Hitung total
    $sql_total = "SELECT COUNT(*) as total FROM tabel_kependudukan";
    $result = query($sql_total);
    $total = $result[0]['total'];

    $agama_data = [];
    foreach ($data as $row) {
        $agama_data[$row['AGAMA']] = [
            'jumlah' => $row['jumlah'],
            'persen' => $total > 0 ? round(($row['jumlah'] / $total) * 100, 1) : 0
        ];
    }

    return $agama_data;
}

// Fungsi untuk data dusun
function getDusunData()
{
    $sql = "SELECT 
            d.dusun as nama_dusun,
            d.id,
            COUNT(k.id) as jumlah_penduduk,
            COUNT(DISTINCT k.NO_KK) as jumlah_kk
            FROM tabel_dusun d
            LEFT JOIN tabel_kependudukan k ON d.id = k.DSN
            GROUP BY d.id, d.dusun
            ORDER BY jumlah_penduduk DESC";
    $data = query($sql);

    // Hitung total untuk persentase
    $sql_total = "SELECT COUNT(*) as total FROM tabel_kependudukan";
    $result = query($sql_total);
    $total_penduduk = $result[0]['total'];

    foreach ($data as &$dusun) {
        $dusun['persen'] = $total_penduduk > 0 ? round(($dusun['jumlah_penduduk'] / $total_penduduk) * 100, 1) : 0;
    }

    return $data;
}

// Ambil semua data
$stats = getStatistics();
$status_kawin = getStatusKawin();
$pendidikan = getPendidikan();
$pekerjaan = getPekerjaan();
$kelompok_umur = getKelompokUmur();
$agama = getAgama();
$dusun_data = getDusunData();

// Total KK (Keluarga)
$sql_kk = "SELECT COUNT(DISTINCT NO_KK) as total_kk FROM tabel_kependudukan";
$result_kk = query($sql_kk);
$total_kk = $result_kk[0]['total_kk'] ?? 0;

// Total Dusun
$sql_dusun_total = "SELECT COUNT(*) as total_dusun FROM tabel_dusun";
$result_dusun = query($sql_dusun_total);
$total_dusun = $result_dusun[0]['total_dusun'] ?? 0;

// Data pekerjaan untuk mapping icon
$pekerjaan_icons = [
    'PETANI' => 'fas fa-seedling',
    'WIRASWASTA' => 'fas fa-store',
    'PNS' => 'fas fa-user-tie',
    'TNI' => 'fas fa-user-tie',
    'POLRI' => 'fas fa-user-tie',
    'BURUH' => 'fas fa-hard-hat',
    'PELAJAR/MAHASISWA' => 'fas fa-user-graduate',
    'GURU' => 'fas fa-chalkboard-teacher',
    'DOKTER' => 'fas fa-user-md',
    'PERAWAT' => 'fas fa-user-nurse',
    'PEGAWAI SWASTA' => 'fas fa-briefcase',
    'IBU RUMAH TANGGA' => 'fas fa-home',
    'SOPIR' => 'fas fa-car',
    'PENGUSAHA' => 'fas fa-chart-line',
    'NELAYAN' => 'fas fa-fish'
];

// Data agama untuk mapping icon
$agama_icons = [
    'ISLAM' => 'fas fa-mosque',
    'KRISTEN' => 'fas fa-church',
    'KATOLIK' => 'fas fa-cross',
    'HINDU' => 'fas fa-om',
    'BUDDHA' => 'fas fa-yin-yang',
    'KONGHUCU' => 'fas fa-torii-gate'
];

// Data pendidikan untuk mapping icon
$pendidikan_icons = [
    'TIDAK/BELUM SEKOLAH' => 'fas fa-book',
    'SD/SEDERAJAT' => 'fas fa-graduation-cap',
    'SMP/SEDERAJAT' => 'fas fa-school',
    'SMA/SEDERAJAT' => 'fas fa-university',
    'D1/D2/D3' => 'fas fa-user-graduate',
    'S1' => 'fas fa-user-graduate',
    'S2' => 'fas fa-user-graduate',
    'S3' => 'fas fa-user-graduate'
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIKDES</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link rel="icon" type="image/x-icon" href="./assets/img/LogoKBS.png" />

    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-color: #f8f9fa;
            --dark-color: #2c3e50;
            --gray-color: #95a5a6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            line-height: 1.6;
            scroll-behavior: smooth;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        /* Navbar Styles */
        .navbar {
            background-color: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }

        .nav-link {
            color: var(--primary-color) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--secondary-color) !important;
        }

        .btn-login {
            background-color: var(--secondary-color);
            color: white;
            border-radius: 25px;
            padding: 8px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #1a2980, #26d0ce);
            color: white;
            padding: 180px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: center bottom;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
            max-width: 600px;
        }

        .hero-stats {
            display: flex;
            gap: 30px;
            margin-top: 50px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            display: block;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* Section Styles */
        .section {
            padding: 100px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
            color: var(--primary-color);
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .section-title p {
            color: var(--gray-color);
            max-width: 700px;
            margin: 0 auto;
        }

        /* Card Styles */
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            height: 100%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }

        .info-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }

        .card-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .card-icon.primary {
            background: rgba(52, 152, 219, 0.1);
            color: var(--secondary-color);
        }

        .card-icon.success {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
        }

        .card-icon.warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }

        .card-icon.danger {
            background: rgba(231, 76, 60, 0.1);
            color: var(--accent-color);
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .card-text {
            color: var(--gray-color);
            margin-bottom: 20px;
        }

        .stat-number-card {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
            margin-bottom: 5px;
        }

        /* Chart/Progress Styles */
        .progress-container {
            margin: 20px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
        }

        .progress-bar {
            border-radius: 5px;
        }

        /* Dusun Section */
        .dusun-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .dusun-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .dusun-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            background: linear-gradient(135deg, var(--secondary-color), #1a2980);
            color: white;
        }

        .dusun-name {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .dusun-population {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        /* Contact Section */
        .contact-section {
            background: #f8f9fa;
        }

        .contact-info {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            height: 100%;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(52, 152, 219, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .contact-details h4 {
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .contact-details p {
            color: var(--gray-color);
        }

        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            height: 100%;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            min-height: 400px;
            border: none;
        }

        /* Footer */
        .footer {
            background: var(--primary-color);
            color: white;
            padding: 60px 0 30px;
        }

        .footer h5 {
            font-size: 1.2rem;
            margin-bottom: 25px;
            color: white;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: white;
        }

        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.8rem;
            }

            .section {
                padding: 80px 0;
            }

            .section-title h2 {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }

            .hero-stats {
                flex-wrap: wrap;
                justify-content: center;
            }

            .section-title h2 {
                font-size: 1.8rem;
            }

            .navbar-collapse {
                background: white;
                padding: 20px;
                border-radius: 10px;
                margin-top: 15px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }
        }

        @media (max-width: 576px) {
            .hero-section {
                padding: 150px 0 80px;
            }

            .hero-title {
                font-size: 1.8rem;
            }

            .section {
                padding: 60px 0;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Logo & Brand -->
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/img/logo-campur.png"
                    alt="Logo SIKDES"
                    class="me-2">
                <span>SIKDES</span>
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#hero">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#demografi">Demografi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pendidikan">Pendidikan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#pekerjaan">Pekerjaan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#umur">Kelompok Umur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#agama">Agama</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#dusun">Dusun</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#kontak">Kontak</a>
                    </li>
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a href="<?php
                                    if (isset($_SESSION['user_id'])) {
                                        echo ($_SESSION['role'] === 'kepala_desa') ? 'kepala_desa/index.php' : 'admin/index.php';
                                    } else {
                                        echo 'auth/login.php';
                                    }
                                    ?>" class="btn btn-login">
                            <?php echo isset($_SESSION['user_id']) ? 'Dashboard' : 'Login'; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="hero" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="hero-title">Sistem Informasi Kependudukan Desa</h1>
                        <p class="hero-subtitle">
                            Sistem Informasi Kependudukan Desa atau bisa disingkat SIKDES merupakan
                            suatu sistem yang dapat mengolah data kependudukan yang berada di Desa Kurniabakti
                            menjadi Data Klasifikasi kependudukan.
                        </p>
                        <div class="hero-stats">
                            <div class="stat-item">
                                <span class="stat-number" id="totalPenduduk"><?php echo number_format($stats['total_penduduk']); ?></span>
                                <span class="stat-label">Jiwa</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="totalKK"><?php echo number_format($total_kk); ?></span>
                                <span class="stat-label">Keluarga</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" id="totalDusun"><?php echo number_format($total_dusun); ?></span>
                                <span class="stat-label">Dusun</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <!-- Placeholder untuk ilustrasi -->
                </div>
            </div>
        </div>
    </section>

    <!-- Demografi Section -->
    <section id="demografi" class="section">
        <div class="container">
            <div class="section-title">
                <h2>Infografis Kependudukan Demografi</h2>
                <p>Data statistik kependudukan berdasarkan jenis kelamin dan status perkawinan</p>
            </div>

            <div class="row g-4">
                <!-- Card 1: Total Penduduk -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5 class="card-title">Total Penduduk</h5>
                        <span class="stat-number-card"><?php echo number_format($stats['total_penduduk']); ?></span>
                        <p class="card-text">Jiwa terdaftar dalam sistem</p>
                    </div>
                </div>

                <!-- Card 2: Laki-laki -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon primary">
                            <i class="fas fa-male"></i>
                        </div>
                        <h5 class="card-title">Laki-laki</h5>
                        <span class="stat-number-card"><?php echo number_format($stats['laki_laki']); ?></span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span><?php echo $stats['persen_laki']; ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: <?php echo $stats['persen_laki']; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Perempuan -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon danger">
                            <i class="fas fa-female"></i>
                        </div>
                        <h5 class="card-title">Perempuan</h5>
                        <span class="stat-number-card"><?php echo number_format($stats['perempuan']); ?></span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span><?php echo $stats['persen_perempuan']; ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="background-color: #e74c3c; width: <?php echo $stats['persen_perempuan']; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Status Perkawinan -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon success">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h5 class="card-title">Status Perkawinan</h5>
                        <div class="progress-container">
                            <?php if ($status_kawin): ?>
                                <div class="progress-label">
                                    <span>Kawin</span>
                                    <span><?php echo $status_kawin['persen_kawin']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?php echo $status_kawin['persen_kawin']; ?>%"></div>
                                </div>

                                <div class="progress-label mt-3">
                                    <span>Belum Kawin</span>
                                    <span><?php echo $status_kawin['persen_belum_kawin']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: <?php echo $status_kawin['persen_belum_kawin']; ?>%"></div>
                                </div>

                                <div class="progress-label mt-3">
                                    <span>Janda/Duda</span>
                                    <span><?php echo $status_kawin['persen_janda_duda']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-secondary" style="width: <?php echo $status_kawin['persen_janda_duda']; ?>%"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pendidikan Section -->
    <section id="pendidikan" class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="section-title">
                <h2>Infografis Tingkat Pendidikan</h2>
                <p>Distribusi penduduk berdasarkan jenjang pendidikan terakhir</p>
            </div>

            <div class="row g-4">
                <?php
                $pendidikan_labels = [
                    'TIDAK/BELUM SEKOLAH' => 'Tidak/Belum Sekolah',
                    'SD/SEDERAJAT' => 'SD/Sederajat',
                    'SMP/SEDERAJAT' => 'SMP/Sederajat',
                    'SMA/SEDERAJAT' => 'SMA/Sederajat',
                    'D1/D2/D3' => 'D1/D2/D3',
                    'S1' => 'S1',
                    'S2' => 'S2',
                    'S3' => 'S3'
                ];

                foreach ($pendidikan_labels as $key => $label):
                    $data = $pendidikan[$key] ?? ['jumlah' => 0, 'persen' => 0];
                    $icon = $pendidikan_icons[$key] ?? 'fas fa-graduation-cap';
                ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-card">
                            <div class="card-icon <?php echo $key == 'S1' || $key == 'S2' || $key == 'S3' ? 'primary' : ($key == 'TIDAK/BELUM SEKOLAH' ? '' : 'warning'); ?>">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <h5 class="card-title"><?php echo $label; ?></h5>
                            <span class="stat-number-card"><?php echo number_format($data['jumlah']); ?></span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span><?php echo $data['persen']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar <?php echo $key == 'S1' || $key == 'S2' || $key == 'S3' ? 'bg-primary' : ($key == 'TIDAK/BELUM SEKOLAH' ? 'bg-secondary' : 'bg-warning'); ?>"
                                        style="width: <?php echo $data['persen']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Pekerjaan Section -->
    <section id="pekerjaan" class="section">
        <div class="container">
            <div class="section-title">
                <h2>Infografis Pekerjaan</h2>
                <p>Distribusi pekerjaan utama penduduk usia produktif</p>
            </div>

            <div class="row g-4">
                <?php
                // Tampilkan 6 pekerjaan teratas
                $counter = 0;
                $color_classes = ['success', 'warning', 'primary', '', 'primary', 'secondary'];

                foreach ($pekerjaan as $key => $data):
                    if ($counter >= 6) break;
                    $icon = $pekerjaan_icons[strtoupper($key)] ?? 'fas fa-briefcase';
                    $color_class = $color_classes[$counter % count($color_classes)];
                ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card">
                            <div class="card-icon <?php echo $color_class; ?>">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <h5 class="card-title"><?php echo $key; ?></h5>
                            <span class="stat-number-card"><?php echo number_format($data['jumlah']); ?></span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span><?php echo $data['persen']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar <?php echo $color_class ? 'bg-' . $color_class : 'bg-info'; ?>"
                                        style="width: <?php echo $data['persen']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    $counter++;
                endforeach;

                // Jika kurang dari 6, tampilkan placeholder
                while ($counter < 6):
                ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card">
                            <div class="card-icon secondary">
                                <i class="fas fa-ellipsis-h"></i>
                            </div>
                            <h5 class="card-title">Lainnya</h5>
                            <span class="stat-number-card">0</span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>0%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-secondary" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    $counter++;
                endwhile;
                ?>
            </div>
        </div>
    </section>

    <!-- Kelompok Umur Section -->
    <section id="umur" class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="section-title">
                <h2>Infografis Kelompok Umur</h2>
                <p>Distribusi penduduk berdasarkan kelompok usia</p>
            </div>

            <div class="row g-4">
                <?php if ($kelompok_umur): ?>
                    <!-- 0-14 Tahun -->
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card">
                            <div class="card-icon primary">
                                <i class="fas fa-baby"></i>
                            </div>
                            <h5 class="card-title">0-14 Tahun</h5>
                            <span class="stat-number-card"><?php echo number_format($kelompok_umur['usia_0_14']); ?></span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span><?php echo $kelompok_umur['persen_0_14']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" style="width: <?php echo $kelompok_umur['persen_0_14']; ?>%"></div>
                                </div>
                            </div>
                            <p class="card-text">Anak-anak dan remaja awal</p>
                        </div>
                    </div>

                    <!-- 15-64 Tahun -->
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card">
                            <div class="card-icon success">
                                <i class="fas fa-user"></i>
                            </div>
                            <h5 class="card-title">15-64 Tahun</h5>
                            <span class="stat-number-card"><?php echo number_format($kelompok_umur['usia_15_64']); ?></span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span><?php echo $kelompok_umur['persen_15_64']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: <?php echo $kelompok_umur['persen_15_64']; ?>%"></div>
                                </div>
                            </div>
                            <p class="card-text">Usia produktif</p>
                        </div>
                    </div>

                    <!-- 65+ Tahun -->
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card">
                            <div class="card-icon">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <h5 class="card-title">65+ Tahun</h5>
                            <span class="stat-number-card"><?php echo number_format($kelompok_umur['usia_65_plus']); ?></span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span><?php echo $kelompok_umur['persen_65_plus']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="background-color: #8e44ad; width: <?php echo $kelompok_umur['persen_65_plus']; ?>%"></div>
                                </div>
                            </div>
                            <p class="card-text">Lansia</p>
                        </div>
                    </div>

                    <!-- Rata-rata Umur -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="card-icon warning">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h5 class="card-title">Rata-rata Umur</h5>
                            <span class="stat-number-card"><?php echo $kelompok_umur['rata_umur']; ?> Tahun</span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>Usia Median</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: <?php echo min(100, $kelompok_umur['rata_umur']); ?>%"></div>
                                </div>
                            </div>
                            <p class="card-text">Mayoritas penduduk berada dalam usia produktif</p>
                        </div>
                    </div>

                    <!-- Kepadatan Penduduk -->
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="card-icon danger">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <h5 class="card-title">Kepadatan Penduduk</h5>
                            <span class="stat-number-card"><?php echo round($stats['total_penduduk'] / 12.5, 0); ?>/km²</span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span>Kepadatan sedang</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="background-color: #e74c3c; width: 75%"></div>
                                </div>
                            </div>
                            <p class="card-text">Distribusi merata di seluruh wilayah desa</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Agama Section -->
    <section id="agama" class="section">
        <div class="container">
            <div class="section-title">
                <h2>Infografis Agama</h2>
                <p>Distribusi penduduk berdasarkan agama yang dianut</p>
            </div>

            <div class="row g-4">
                <?php
                $agama_labels = [
                    'ISLAM' => 'Islam',
                    'KRISTEN' => 'Kristen',
                    'KATOLIK' => 'Katolik',
                    'HINDU' => 'Hindu',
                    'BUDDHA' => 'Buddha',
                    'KONGHUCU' => 'Konghucu'
                ];

                $counter = 0;
                $color_classes = ['primary', 'warning', '', 'danger', 'success', 'secondary'];

                foreach ($agama_labels as $key => $label):
                    $data = $agama[$key] ?? ['jumlah' => 0, 'persen' => 0];
                    $icon = $agama_icons[$key] ?? 'fas fa-church';
                    $color_class = $color_classes[$counter % count($color_classes)];
                ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="info-card">
                            <div class="card-icon <?php echo $color_class; ?>">
                                <i class="<?php echo $icon; ?>"></i>
                            </div>
                            <h5 class="card-title"><?php echo $label; ?></h5>
                            <span class="stat-number-card"><?php echo number_format($data['jumlah']); ?></span>
                            <div class="progress-container">
                                <div class="progress-label">
                                    <span><?php echo $data['persen']; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar <?php echo $color_class ? 'bg-' . $color_class : 'bg-info'; ?>"
                                        style="width: <?php echo $data['persen']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    $counter++;
                endforeach;
                ?>
            </div>
        </div>
    </section>

    <!-- Dusun Section -->
    <section id="dusun" class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="section-title">
                <h2>Infografis Dusun</h2>
                <p>Distribusi penduduk berdasarkan dusun di wilayah desa</p>
            </div>

            <div class="row g-4">
                <?php if (!empty($dusun_data)): ?>
                    <?php
                    $dusun_icons = [
                        'fas fa-home',
                        'fas fa-tree',
                        'fas fa-water',
                        'fas fa-mountain',
                        'fas fa-sun',
                        'fas fa-seedling',
                        'fas fa-fish',
                        'fas fa-star',
                        'fas fa-building',
                        'fas fa-warehouse',
                        'fas fa-store',
                        'fas fa-school'
                    ];

                    $dusun_colors = ['bg-primary', 'bg-success', 'bg-warning', '', 'bg-info', 'bg-success', 'bg-primary', 'bg-warning'];

                    foreach ($dusun_data as $index => $dusun):
                        $icon = $dusun_icons[$index % count($dusun_icons)] ?? 'fas fa-home';
                        $color = $dusun_colors[$index % count($dusun_colors)] ?? 'bg-primary';
                    ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="dusun-card">
                                <div class="dusun-icon">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <h5 class="dusun-name"><?php echo htmlspecialchars($dusun['nama_dusun']); ?></h5>
                                <div class="dusun-population"><?php echo number_format($dusun['jumlah_penduduk']); ?></div>
                                <p>Jiwa • <?php echo number_format($dusun['jumlah_kk']); ?> KK</p>
                                <div class="progress mt-3">
                                    <div class="progress-bar <?php echo $color; ?>" style="width: <?php echo $dusun['persen']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">Data dusun belum tersedia</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="kontak" class="section contact-section">
        <div class="container">
            <div class="section-title">
                <h2>Hubungi Kami</h2>
                <p>Silakan hubungi kami untuk informasi lebih lanjut</p>
            </div>

            <div class="row g-5">
                <!-- Contact Information -->
                <div class="col-lg-5">
                    <div class="contact-info">
                        <h3 class="mb-4">Kantor Desa</h3>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Alamat</h4>
                                <p>
                                    Jl. Kapten Suradimadja Dalam <br>
                                    No. 110 Kurniabakti<br>
                                    Kecamatan Ciawi, Kabupaten Tasikmalaya
                                </p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Telepon</h4>
                                <p>(021) 1234-5678<br>
                                    0812-3456-7890</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Email</h4>
                                <p>desakurniabakti@gmail.com</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Jam Operasional</h4>
                                <p>Senin - Jumat: 08:00 - 16:00<br>
                                    Sabtu: 08:00 - 12:00<br>
                                    Minggu & Hari Libur: Tutup</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5 class="mb-3">Sosial Media</h5>
                            <div class="d-flex gap-3">
                                <a href="#" class="btn btn-primary btn-sm">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="btn btn-info btn-sm">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="btn btn-danger btn-sm">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="btn btn-success btn-sm">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map -->
                <div class="col-lg-7">
                    <div class="map-container">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d40948.12031820083!2d108.142776!3d-7.157106!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f4eaa8785249d%3A0x17bdb61a5a113357!2sKantor%20Kepala%20Desa%20Kurniabakti!5e1!3m2!1sid!2sid!4v1766666754364!5m2!1sid!2sid"
                            allowfullscreen=""
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>SIKDES</h5>
                    <p>Sistem Informasi Kependudukan Desa yang terintegrasi untuk pengelolaan data penduduk yang akurat dan efisien.</p>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Menu</h5>
                    <ul class="footer-links">
                        <li><a href="#hero">Beranda</a></li>
                        <li><a href="#demografi">Demografi</a></li>
                        <li><a href="#pendidikan">Pendidikan</a></li>
                        <li><a href="#pekerjaan">Pekerjaan</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Menu</h5>
                    <ul class="footer-links">
                        <li><a href="#umur">Kelompok Umur</a></li>
                        <li><a href="#agama">Agama</a></li>
                        <li><a href="#dusun">Dusun</a></li>
                        <li><a href="#kontak">Kontak</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Kontak</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> Jl. Kapten Suradimadja Dalam No. 110 Kurniabakti Kec. Ciawi Kab. Tasikmalaya</p>
                    <p><i class="fas fa-phone me-2"></i> (021) 1234-5678</p>
                    <p><i class="fas fa-envelope me-2"></i> desakurniabakti@gmail.com</p>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> SIKDES - Sistem Informasi Kependudukan Desa.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });

                    // Update active nav link
                    document.querySelectorAll('.nav-link').forEach(link => {
                        link.classList.remove('active');
                    });
                    this.classList.add('active');
                }
            });
        });

        // Update active nav link on scroll
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-link');

            let current = '';

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;

                if (scrollY >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.add('active');
                }
            });
        });

        // Animate counter numbers
        function animateCounter(element, target, duration = 2000) {
            const start = 0;
            const increment = target / (duration / 16);
            let current = start;

            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = Math.floor(target).toLocaleString();
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current).toLocaleString();
                }
            }, 16);
        }

        // Initialize counters when in view
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counters = entry.target.querySelectorAll('.stat-number-card, .stat-number, .dusun-population');
                    counters.forEach(counter => {
                        const text = counter.textContent.replace(/,/g, '');
                        const target = parseInt(text);
                        if (!isNaN(target)) {
                            animateCounter(counter, target);
                        }
                    });
                }
            });
        }, {
            threshold: 0.5
        });

        // Observe sections with counters
        document.querySelectorAll('section').forEach(section => {
            counterObserver.observe(section);
        });

        // Animate hero stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            const heroStats = document.querySelectorAll('.hero-stats .stat-number');
            heroStats.forEach(stat => {
                const text = stat.textContent.replace(/,/g, '');
                const target = parseInt(text);
                if (!isNaN(target)) {
                    animateCounter(stat, target, 1500);
                }
            });
        });
    </script>
</body>

</html>
<?php
// Tutup koneksi database
mysqli_close($conn);
?>