<?php
// Pastikan session_start() dipanggil di paling atas
session_start();

require_once '../config/database.php';
require_once '../config/functions.php';
include_once '../config/config.php';

// Inisialisasi variabel error
$error = '';

// Debug: Tampilkan semua error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek jika form login disubmit
if (isset($_POST['login'])) {
  $username = $conn->real_escape_string($_POST['username']);
  $password = $_POST['password'];

  // Debug: Lihat input yang diterima
  error_log("Login attempt - Username: $username, Password: $password");

  $sql = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Debug: Lihat data user dan hash password dari database
    error_log("User found: " . print_r($user, true));
    error_log("Stored hash: " . $user['password']);
    error_log("Input password: " . $password);

    // Verifikasi password
    if (password_verify($password, $user['password'])) {
      // Set session
      $_SESSION['user_id'] = $user['id_user'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['nama'] = $user['nama_lengkap'];

      // Debug: Session values
      error_log("Session set: " . print_r($_SESSION, true));

      // Redirect berdasarkan role
      if ($user['role'] == 'admin') {
        header("Location: ../admin/index.php");
      } else {
        header("Location: ../kepala_desa/index.php");
      }
      exit();
    } else {
      $error = "Password yang Anda masukkan salah!";
      error_log("Password verification failed");
    }
  } else {
    $error = "Username tidak ditemukan!";
    error_log("User not found");
  }

  // Jika ada error, simpan di session untuk ditampilkan setelah redirect
  if (!empty($error)) {
    $_SESSION['login_error'] = $error;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<!-- [Head] start -->

<head>
  <title>SIKDES</title>
  <!-- [Meta] -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="description" content="Mantis is made using Bootstrap 5 design framework. Download the free admin template & use it for your project.">
  <meta name="keywords" content="Mantis, Dashboard UI Kit, Bootstrap 5, Admin Template, Admin Dashboard, CRM, CMS, Bootstrap Admin Template">
  <meta name="author" content="CodedThemes">

  <!-- [Favicon] icon -->
  <link rel="icon" type="image/x-icon" href="<?= $base_url ?>/assets/img/LogoKBS.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" id="main-font-link">
  <!-- [Tabler Icons] https://tablericons.com -->
  <link rel="stylesheet" href="../assets/fonts/tabler-icons.min.css">
  <!-- [Feather Icons] https://feathericons.com -->
  <link rel="stylesheet" href="../assets/fonts/feather.css">
  <!-- [Font Awesome Icons] https://fontawesome.com/icons -->
  <link rel="stylesheet" href="../assets/fonts/fontawesome.css">
  <!-- [Material Icons] https://fonts.google.com/icons -->
  <link rel="stylesheet" href="../assets/fonts/material.css">
  <!-- [Template CSS Files] -->
  <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link">
  <link rel="stylesheet" href="../assets/css/style-preset.css">

  <style>
    .auth-wrapper {
      display: flex;
      min-height: 100vh;
    }

    .auth-image {
      flex: 1;
      background-color: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }

    .auth-image-content {
      text-align: center;
      max-width: 500px;
    }

    .auth-image img {
      max-width: 100%;
      height: auto;
      margin-bottom: 2rem;
    }

    .auth-image h2 {
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: #333;
    }

    .auth-image p {
      color: #666;
      font-size: 1.1rem;
      line-height: 1.6;
    }

    .auth-form {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      background-color: #fff;
    }

    .auth-form .card {
      width: 100%;
      max-width: 400px;
      border: none;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    @media (max-width: 768px) {
      .auth-wrapper {
        flex-direction: column;
      }

      .auth-image {
        padding: 1rem;
      }

      .auth-image img {
        max-width: 200px;
      }

      .auth-form {
        padding: 1rem;
      }
    }
  </style>

</head>
<!-- [Head] end -->
<!-- [Body] Start -->

<body>

  <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow-lg border-0" style="max-width: 900px; width: 100%;">
      <div class="row g-0">

        <!-- KIRI : INFORMASI -->
        <div class="col-md-6 d-none d-md-flex text-white p-4 align-items-center">
          <div class="text-center w-100">
            <img src="Image.png" width="220" class="mb-3" alt="Logo">
          </div>
        </div>

        <!-- KANAN : FORM LOGIN -->
        <div class="col-md-6 p-4 p-md-5">
          <h4 class="text-center mb-3">Sistem Informasi Kependudukan Desa (SIKDES)</h4>

          <!-- Alert Error -->
          <?php if (isset($_SESSION['login_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= htmlspecialchars($_SESSION['login_error']) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php unset($_SESSION['login_error']);
          endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Username</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-user"></i></span>
                <input type="text" class="form-control" name="username" placeholder="Masukkan username" required autofocus>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="********" required>
                <span class="input-group-text" id="togglePassword" style="cursor:pointer">
                  <i class="ti ti-eye-off"></i>
                </span>
              </div>
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100 mt-3">
              <i class="ti ti-login me-2"></i>Masuk
            </button>

            <p class="text-center text-muted mt-4 small">
              <i class="ti ti-info-circle me-1"></i>
              Pastikan data login Anda benar
            </p>
          </form>
        </div>

      </div>
    </div>
  </div>


  <script>
    // Toggle password visibility
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');

    if (togglePassword && password) {
      const icon = togglePassword.querySelector('i');

      togglePassword.addEventListener('click', () => {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        icon.classList.toggle('ti-eye');
        icon.classList.toggle('ti-eye-off');
      });
    }

    // Fokus ke input username saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
      const usernameField = document.querySelector('input[name="username"]');
      if (usernameField) {
        usernameField.focus();
      }
    });
  </script>

  <!-- Required Js -->
  <script src="../assets/js/plugins/popper.min.js"></script>
  <script src="../assets/js/plugins/simplebar.min.js"></script>
  <script src="../assets/js/plugins/bootstrap.min.js"></script>
  <script src="../assets/js/fonts/custom-font.js"></script>
  <script src="../assets/js/pcoded.js"></script>
  <script src="../assets/js/plugins/feather.min.js"></script>

  <script>
    layout_change('light');
    change_box_container('false');
    layout_rtl_change('false');
    preset_change("preset-1");
    font_change("Public-Sans");
  </script>

</body>
<!-- [Body] end -->

</html>