<?php
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    header("Location: {$base_url}auth/login.php");
    exit;
}

// Jika diperlukan role tertentu (admin/kades), sesuaikan dengan kebutuhan
if ($_SESSION['role'] !== 'kepala_desa') {
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Parameter filter
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$jk_filter = isset($_GET['jk']) ? $_GET['jk'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Ambil data dusun untuk filter
$sql_dusun = "SELECT * FROM tabel_dusun ORDER BY dusun";
$data_dusun = query($sql_dusun);

// Query data kependudukan dengan filter
$where_conditions = [];
$params = [];
$params_types = '';

if (!empty($dusun_filter)) {
    $where_conditions[] = "k.DSN = ?";
    $params[] = $dusun_filter;
    $params_types .= 'i';
}

if (!empty($jk_filter)) {
    $where_conditions[] = "k.JK = ?";
    $params[] = $jk_filter;
    $params_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(k.NO_KK LIKE ? OR k.NIK LIKE ? OR k.NAMA_LGKP LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params_types .= 'sss';
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query utama dengan JOIN ke tabel pekerjaan
$sql = "SELECT 
            k.id,
            k.NO_KK,
            k.NIK,
            k.NAMA_LGKP,
            k.NAMA_PANGGILAN,
            k.HBKEL,
            k.JK,
            k.TMPT_LHR,
            k.TGL_LHR,
            k.AGAMA,
            k.STATUS_KAWIN,
            k.PENDIDIKAN,
            k.PEKERJAAN,
            k.KECAMATAN,
            k.KELURAHAN,
            k.DSN,
            k.rt,
            k.rw,
            k.ALAMAT,
            k.GOL_DARAH,
            k.KEWARGANEGARAAN,
            k.STATUS_TINGGAL,
            k.DISABILITAS,
            d.dusun,
            p.jenis_pekerjaan,
            p.penghasilan_per_bulan,
            p.status_pekerjaan
        FROM tabel_kependudukan k
        LEFT JOIN tabel_dusun d ON k.DSN = d.id
        LEFT JOIN tabel_pekerjaan p ON k.NIK = p.NIK
        $where_sql
        ORDER BY k.NAMA_LGKP";

// PERBAIKAN: Gunakan prepared statement yang benar
global $conn;

// Inisialisasi variabel kependudukan
$kependudukan = [];

try {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Bind parameter berdasarkan tipe
            if (!empty($params_types)) {
                $stmt->bind_param($params_types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $kependudukan = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            // Jika prepare gagal, log error
            error_log("Prepare statement failed: " . $conn->error);
            $_SESSION['error'] = "Terjadi kesalahan dalam memproses filter.";
        }
    } else {
        // Jika tidak ada parameter, gunakan query biasa
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $kependudukan = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
        } else {
            error_log("Query failed: " . mysqli_error($conn));
            $_SESSION['error'] = "Terjadi kesalahan dalam mengambil data.";
        }
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem. Silakan coba lagi.";
}

// Hitung total data
$total_data = count($kependudukan);
?>

<style>
    /* Paksa SweetAlert berada di atas segalanya */
    .swal2-container {
        z-index: 99999 !important;
    }

    .badge-jk-l {
        background-color: #0d6efd;
        color: white;
    }

    .badge-jk-p {
        background-color: #dc3545;
        color: white;
    }

    .badge-hbkel {
        background-color: #6c757d;
        color: white;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }

    .filter-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .btn-excel {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
    }

    .btn-excel:hover {
        background-color: #218838;
        border-color: #1e7e34;
        color: white;
    }

    .btn-pdf {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .btn-pdf:hover {
        background-color: #c82333;
        border-color: #bd2130;
        color: white;
    }

    .age-badge {
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 10px;
    }

    .age-child {
        background-color: #17a2b8;
        color: white;
    }

    .age-teen {
        background-color: #20c997;
        color: white;
    }

    .age-adult {
        background-color: #6f42c1;
        color: white;
    }

    .age-elder {
        background-color: #fd7e14;
        color: white;
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
                    <h2>Data Kependudukan</h2>

                </div>

                <!-- Filter Section -->
                <div class="filter-container">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="dusun" class="form-label">Filter Dusun</label>
                            <select name="dusun" id="dusun" class="form-select">
                                <option value="">Semua Dusun</option>
                                <?php foreach ($data_dusun as $dusun): ?>
                                    <option value="<?= $dusun['id'] ?>" <?= $dusun_filter == $dusun['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dusun['dusun']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="jk" class="form-label">Jenis Kelamin</label>
                            <select name="jk" id="jk" class="form-select">
                                <option value="">Semua</option>
                                <option value="L" <?= $jk_filter == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= $jk_filter == 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="search" class="form-label">Cari (KK/NIK/Nama)</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Masukkan No. KK, NIK, atau Nama"
                                value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-filter"></i> Filter
                                </button>
                                <a href="list.php" class="btn btn-secondary">
                                    <i class="ti ti-refresh"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Statistik Filter -->
                    <?php if (!empty($dusun_filter) || !empty($jk_filter) || !empty($search)): ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="ti ti-info-circle"></i>
                                Menampilkan <?= $total_data ?> data dengan filter:
                                <?php
                                $filter_info = [];
                                if (!empty($dusun_filter)) {
                                    $nama_dusun = '';
                                    foreach ($data_dusun as $dusun) {
                                        if ($dusun['id'] == $dusun_filter) {
                                            $nama_dusun = $dusun['dusun'];
                                            break;
                                        }
                                    }
                                    $filter_info[] = "Dusun: $nama_dusun";
                                }
                                if (!empty($jk_filter)) {
                                    $filter_info[] = "Jenis Kelamin: " . ($jk_filter == 'L' ? 'Laki-laki' : 'Perempuan');
                                }
                                if (!empty($search)) {
                                    $filter_info[] = "Kata kunci: \"$search\"";
                                }
                                echo implode(', ', $filter_info);
                                ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card p-3">

                    <!-- Tampilkan pesan error atau success -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong> <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Sukses!</strong> <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <!-- /Tampilkan pesan error atau success -->

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="text-muted">Total Data: <strong><?= number_format($total_data, 0, ',', '.') ?></strong> penduduk</span>
                        </div>
                        <div class="btn-group">
                            <a href="export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-excel">
                                <i class="ti ti-file-spreadsheet"></i> Excel
                            </a>
                            <a href="export_pdf.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-pdf">
                                <i class="ti ti-file-text"></i> PDF
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTable">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 3%;">No</th>
                                    <th style="width: 12%;">No. KK</th>
                                    <th style="width: 12%;">NIK</th>
                                    <th style="width: 18%;">Nama Lengkap</th>
                                    <th style="width: 5%;">JK</th>
                                    <th style="width: 10%;">Hub. Keluarga</th>
                                    <th style="width: 15%;">Tempat, Tgl Lahir</th>
                                    <th style="width: 20%;">Alamat</th>
                                    <th style="width: 8%;">Pekerjaan Utama</th>
                                    <th style="width: 10%;">Penghasilan/Bulan</th>
                                    <th style="width: 7%;">Dusun</th>
                                    <th style="width: 5%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($kependudukan)): ?>
                                    <tr>
                                        <td colspan="12" class="text-center py-4">
                                            <div class="text-muted mb-2">
                                                <i class="ti ti-user-off fs-1"></i>
                                            </div>
                                            Tidak ada data kependudukan
                                            <?php if (!empty($dusun_filter) || !empty($jk_filter) || !empty($search)): ?>
                                                <br>
                                                <small class="text-muted">Coba reset filter atau ubah kriteria pencarian</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($kependudukan as $penduduk):
                                        // Hitung usia
                                        $tgl_lahir = new DateTime($penduduk['TGL_LHR']);
                                        $today = new DateTime();
                                        $usia = $today->diff($tgl_lahir)->y;

                                        // Tentukan kategori usia
                                        $usia_class = '';
                                        if ($usia < 5) $usia_class = 'age-child';
                                        elseif ($usia >= 5 && $usia < 18) $usia_class = 'age-teen';
                                        elseif ($usia >= 18 && $usia < 60) $usia_class = 'age-adult';
                                        else $usia_class = 'age-elder';

                                        // Format tanggal lahir
                                        $tgl_lahir_formatted = dateIndo($penduduk['TGL_LHR']);

                                        // Format alamat lengkap
                                        $alamat_lengkap = $penduduk['ALAMAT'] ?? 'RT ' . $penduduk['rt'] . ' / RW ' . $penduduk['rw'] . ', ' . ($penduduk['dusun'] ?? '');
                                    ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td class="text-center"><?= formatKKNIK($penduduk['NO_KK']); ?></td>
                                            <td class="text-center"><?= formatKKNIK($penduduk['NIK']); ?></td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <strong><?= htmlspecialchars($penduduk['NAMA_LGKP']); ?></strong>
                                                    <?php if ($penduduk['NAMA_PANGGILAN']): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($penduduk['NAMA_PANGGILAN']); ?></small>
                                                    <?php endif; ?>
                                                    <span class="badge <?= $usia_class ?> age-badge mt-1"><?= $usia ?> tahun</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $penduduk['JK'] == 'L' ? 'badge-jk-l' : 'badge-jk-p' ?>">
                                                    <?= $penduduk['JK'] == 'L' ? 'L' : 'P' ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-hbkel">
                                                    <?=
                                                    $penduduk['HBKEL'] == 'KEPALA KELUARGA' ? 'Kepala' : ($penduduk['HBKEL'] == 'ISTRI' ? 'Istri' : ($penduduk['HBKEL'] == 'ANAK' ? 'Anak' : 'Famili'))
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars($penduduk['TMPT_LHR']); ?></small><br>
                                                <small class="text-muted"><?= $tgl_lahir_formatted; ?></small>
                                            </td>
                                            <td>
                                                <small>
                                                    RT <?= $penduduk['rt']; ?>/RW <?= $penduduk['rw']; ?><br>
                                                    Dusun <?= htmlspecialchars($penduduk['dusun'] ?? '-'); ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($penduduk['jenis_pekerjaan']): ?>
                                                    <span class="badge bg-info"><?= htmlspecialchars($penduduk['jenis_pekerjaan']); ?></span>
                                                <?php elseif ($penduduk['PEKERJAAN']): ?>
                                                    <small><?= htmlspecialchars($penduduk['PEKERJAAN']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($penduduk['penghasilan_per_bulan'] > 0): ?>
                                                    <?= formatRupiah($penduduk['penghasilan_per_bulan']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($penduduk['dusun'] ?? '-'); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view.php?nik=<?= $penduduk['NIK']; ?>"
                                                        class="btn btn-info"
                                                        title="Detail"
                                                        data-bs-toggle="tooltip">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination atau info jika data banyak -->
                    <?php if ($total_data > 50): ?>
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="ti ti-info-circle"></i>
                                Menampilkan <?= min($total_data, 50) ?> data pertama. Gunakan filter untuk pencarian spesifik.
                            </small>
                        </div>
                    <?php endif; ?>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

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

    // Function to print table
    function printTable() {
        var printContents = document.getElementById('dataTable').outerHTML;
        var originalContents = document.body.innerHTML;

        document.body.innerHTML =
            '<html><head><title>Data Kependudukan</title>' +
            '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">' +
            '<style>@media print { .no-print { display: none; } }</style></head>' +
            '<body>' +
            '<div class="container mt-4">' +
            '<h3 class="text-center mb-3">Data Kependudukan</h3>' +
            '<small class="text-muted mb-3 d-block">' + new Date().toLocaleDateString('id-ID') + '</small>' +
            printContents +
            '</div>' +
            '</body></html>';

        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }

    // PERBAIKAN: Hapus auto-submit filter yang menyebabkan masalah
    // document.getElementById('dusun').addEventListener('change', function() {
    //     if (this.value) {
    //         this.form.submit();
    //     }
    // });
</script>

</html>