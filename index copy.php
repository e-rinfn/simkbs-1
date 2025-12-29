<?php
session_start();
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
                        <a href="<?php echo isset($_SESSION['user_id']) ? 'admin/dashboard.php' : 'auth/login.php'; ?>" class="btn btn-login">
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
                            menjadi Data Klasifikasi kependudukan..
                        </p>
                        <div class="hero-stats">
                            <div class="stat-item">
                                <span class="stat-number">15,234</span>
                                <span class="stat-label">Jiwa</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">4,209</span>
                                <span class="stat-label">Keluarga</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">8</span>
                                <span class="stat-label">Dusun</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <!-- <div class="text-center">
                        <img src="https://via.placeholder.com/500x400/1a2980/26d0ce?text=Ilustrasi+Kependudukan"
                            alt="Ilustrasi Kependudukan"
                            class="img-fluid rounded-3 shadow-lg"
                            style="max-width: 90%;">
                    </div> -->
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
                        <span class="stat-number-card">15,234</span>
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
                        <span class="stat-number-card">7,512</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>49.3%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: 49.3%"></div>
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
                        <span class="stat-number-card">7,722</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>50.7%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="background-color: #e74c3c; width: 50.7%"></div>
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
                            <div class="progress-label">
                                <span>Kawin</span>
                                <span>68%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 68%"></div>
                            </div>

                            <div class="progress-label mt-3">
                                <span>Belum Kawin</span>
                                <span>25%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 25%"></div>
                            </div>

                            <div class="progress-label mt-3">
                                <span>Janda/Duda</span>
                                <span>7%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-secondary" style="width: 7%"></div>
                            </div>
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
                <!-- SD -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon warning">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h5 class="card-title">SD/Sederajat</h5>
                        <span class="stat-number-card">4,890</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>32.1%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 32.1%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SMP -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon warning">
                            <i class="fas fa-school"></i>
                        </div>
                        <h5 class="card-title">SMP/Sederajat</h5>
                        <span class="stat-number-card">3,567</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>23.4%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 23.4%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SMA -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon success">
                            <i class="fas fa-university"></i>
                        </div>
                        <h5 class="card-title">SMA/Sederajat</h5>
                        <span class="stat-number-card">3,245</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>21.3%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 21.3%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Perguruan Tinggi -->
                <div class="col-md-3 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon primary">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h5 class="card-title">Perguruan Tinggi</h5>
                        <span class="stat-number-card">1,872</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>12.3%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: 12.3%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tidak Sekolah -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h5 class="card-title">Tidak/Belum Sekolah</h5>
                        <span class="stat-number-card">1,660</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>10.9%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-secondary" style="width: 10.9%"></div>
                            </div>
                        </div>
                    </div>
                </div>
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
                <!-- Petani -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon success">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h5 class="card-title">Petani</h5>
                        <span class="stat-number-card">3,245</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>31.2%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 31.2%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wiraswasta -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon warning">
                            <i class="fas fa-store"></i>
                        </div>
                        <h5 class="card-title">Wiraswasta</h5>
                        <span class="stat-number-card">2,567</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>24.7%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 24.7%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PNS -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon primary">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h5 class="card-title">PNS/TNI/POLRI</h5>
                        <span class="stat-number-card">987</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>9.5%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: 9.5%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buruh -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon">
                            <i class="fas fa-hard-hat"></i>
                        </div>
                        <h5 class="card-title">Buruh</h5>
                        <span class="stat-number-card">1,234</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>11.9%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="background-color: #8e44ad; width: 11.9%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pelajar/Mahasiswa -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon primary">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h5 class="card-title">Pelajar/Mahasiswa</h5>
                        <span class="stat-number-card">1,567</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>15.1%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: 15.1%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lainnya -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon secondary">
                            <i class="fas fa-ellipsis-h"></i>
                        </div>
                        <h5 class="card-title">Lainnya</h5>
                        <span class="stat-number-card">789</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>7.6%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-secondary" style="width: 7.6%"></div>
                            </div>
                        </div>
                    </div>
                </div>
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
                <!-- 0-14 Tahun -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon primary">
                            <i class="fas fa-baby"></i>
                        </div>
                        <h5 class="card-title">0-14 Tahun</h5>
                        <span class="stat-number-card">3,456</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>22.7%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: 22.7%"></div>
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
                        <span class="stat-number-card">10,123</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>66.5%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 66.5%"></div>
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
                        <span class="stat-number-card">1,655</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>10.8%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="background-color: #8e44ad; width: 10.8%"></div>
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
                        <span class="stat-number-card">32.5 Tahun</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>Usia Median</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 65%"></div>
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
                        <span class="stat-number-card">1,245/km²</span>
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
                <!-- Islam -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon primary">
                            <i class="fas fa-mosque"></i>
                        </div>
                        <h5 class="card-title">Islam</h5>
                        <span class="stat-number-card">12,345</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>81.0%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: 81%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kristen -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon warning">
                            <i class="fas fa-church"></i>
                        </div>
                        <h5 class="card-title">Kristen</h5>
                        <span class="stat-number-card">1,567</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>10.3%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 10.3%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Katolik -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon">
                            <i class="fas fa-cross"></i>
                        </div>
                        <h5 class="card-title">Katolik</h5>
                        <span class="stat-number-card">876</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>5.8%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="background-color: #8e44ad; width: 5.8%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hindu -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon danger">
                            <i class="fas fa-om"></i>
                        </div>
                        <h5 class="card-title">Hindu</h5>
                        <span class="stat-number-card">234</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>1.5%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="background-color: #e74c3c; width: 1.5%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buddha -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon success">
                            <i class="fas fa-yin-yang"></i>
                        </div>
                        <h5 class="card-title">Buddha</h5>
                        <span class="stat-number-card">156</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>1.0%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 1%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Konghucu -->
                <div class="col-md-4 col-sm-6">
                    <div class="info-card">
                        <div class="card-icon">
                            <i class="fas fa-torii-gate"></i>
                        </div>
                        <h5 class="card-title">Konghucu</h5>
                        <span class="stat-number-card">56</span>
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>0.4%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-secondary" style="width: 0.4%"></div>
                            </div>
                        </div>
                    </div>
                </div>
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
                <!-- Dusun 1 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dusun-card">
                        <div class="dusun-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h5 class="dusun-name">Dusun Krajan</h5>
                        <div class="dusun-population">2,345</div>
                        <p>Jiwa • 425 KK</p>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-primary" style="width: 15.4%"></div>
                        </div>
                    </div>
                </div>

                <!-- Dusun 2 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dusun-card">
                        <div class="dusun-icon">
                            <i class="fas fa-tree"></i>
                        </div>
                        <h5 class="dusun-name">Dusun Sumber</h5>
                        <div class="dusun-population">2,123</div>
                        <p>Jiwa • 389 KK</p>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-success" style="width: 13.9%"></div>
                        </div>
                    </div>
                </div>

                <!-- Dusun 3 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dusun-card">
                        <div class="dusun-icon">
                            <i class="fas fa-water"></i>
                        </div>
                        <h5 class="dusun-name">Dusun Kaligondo</h5>
                        <div class="dusun-population">1,987</div>
                        <p>Jiwa • 356 KK</p>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-warning" style="width: 13.0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Dusun 4 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dusun-card">
                        <div class="dusun-icon">
                            <i class="fas fa-mountain"></i>
                        </div>
                        <h5 class="dusun-name">Dusun Gunungsari</h5>
                        <div class="dusun-population">1,845</div>
                        <p>Jiwa • 332 KK</p>
                        <div class="progress mt-3">
                            <div class="progress-bar" style="background-color: #8e44ad; width: 12.1%"></div>
                        </div>
                    </div>
                </div>

                <!-- Dusun 5 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dusun-card">
                        <div class="dusun-icon">
                            <i class="fas fa-sun"></i>
                        </div>
                        <h5 class="dusun-name">Dusun Surya</h5>
                        <div class="dusun-population">1,723</div>
                        <p>Jiwa • 312 KK</p>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-info" style="width: 11.3%"></div>
                        </div>
                    </div>
                </div>

                <!-- Dusun 6 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dusun-card">
                        <div class="dusun-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <h5 class="dusun-name">Dusun Tani</h5>
                        <div class="dusun-population">1,645</div>
                        <p>Jiwa • 298 KK</p>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-success" style="width: 10.8%"></div>
                        </div>
                    </div>
                </div>

                <!-- Dusun 7 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dusun-card">
                        <div class="dusun-icon">
                            <i class="fas fa-fish"></i>
                        </div>
                        <h5 class="dusun-name">Dusun Mina</h5>
                        <div class="dusun-population">1,512</div>
                        <p>Jiwa • 274 KK</p>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-primary" style="width: 9.9%"></div>
                        </div>
                    </div>
                </div>

                <!-- Dusun 8 -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="dusun-card">
                        <div class="dusun-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h5 class="dusun-name">Dusun Bintang</h5>
                        <div class="dusun-population">1,432</div>
                        <p>Jiwa • 260 KK</p>
                        <div class="progress mt-3">
                            <div class="progress-bar bg-warning" style="width: 9.4%"></div>
                        </div>
                    </div>
                </div>
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

        // Initialize animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                }
            });
        }, observerOptions);

        // Observe cards for animation
        document.querySelectorAll('.info-card, .dusun-card').forEach(card => {
            observer.observe(card);
        });

        // Animate counter numbers (contoh implementasi)
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
                        const target = parseInt(counter.textContent.replace(/,/g, ''));
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
    </script>
</body>

</html>