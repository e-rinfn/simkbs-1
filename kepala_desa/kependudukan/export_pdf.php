<?php
// Start output buffering untuk menangkap semua output
ob_start();

require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    ob_end_clean();
    header("Location: {$base_url}auth/login.php");
    exit;
}

// Jika diperlukan role tertentu (admin/kades), sesuaikan dengan kebutuhan
if ($_SESSION['role'] !== 'kepala_desa') {
    ob_end_clean();
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Include TCPDF
require_once '../../vendor/autoload.php';

// Parameter filter yang sama dengan list.php
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$jk_filter = isset($_GET['jk']) ? $_GET['jk'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query data kependudukan dengan filter yang sama
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

// Query utama
$sql = "SELECT 
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
            k.DSN,
            k.rt,
            k.rw,
            d.dusun,
            p.jenis_pekerjaan,
            p.penghasilan_per_bulan
        FROM tabel_kependudukan k
        LEFT JOIN tabel_dusun d ON k.DSN = d.id
        LEFT JOIN tabel_pekerjaan p ON k.NIK = p.NIK
        $where_sql
        ORDER BY k.NAMA_LGKP";

// Eksekusi query dengan prepared statement
global $conn;
$data_kependudukan = [];

try {
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($params_types)) {
                $stmt->bind_param($params_types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $data_kependudukan = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    } else {
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $data_kependudukan = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
        }
    }
} catch (Exception $e) {
    error_log("Database error in PDF export: " . $e->getMessage());
}

// Clear semua output buffer sebelum membuat PDF
ob_end_clean();

// Inisialisasi TCPDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Header dengan Logo
$pdf->SetFont('helvetica', 'B', 16);

// Path logo (sesuaikan dengan lokasi logo Anda)
$logoPath = __DIR__ . '/../../assets/img/LogoKBS.png'; // Ganti dengan path logo Anda

// Cek apakah logo ada
if (file_exists($logoPath)) {
    // Tambahkan logo di kiri atas
    $pdf->Image($logoPath, 10, 10, 25); // x=10, y=10, width=25

    // Pindahkan posisi untuk judul di kanan logo
    $pdf->SetXY(40, 10); // Mulai dari 40mm dari kiri (10+25+5)
    $pdf->Cell(0, 10, 'LAPORAN DATA KEPENDUDUKAN', 0, 1);

    // Informasi desa di bawah judul
    $pdf->SetXY(40, $pdf->GetY() + 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Desa Kurniabakti, Kecamatan Cineam, Kabupaten Tasikmalaya', 0, 1);

    $pdf->SetXY(40, $pdf->GetY());
    $pdf->Cell(0, 5, 'Telp: (0265) 123456 | Email: desakurniabakti@email.com', 0, 1);
} else {
    // Jika logo tidak ada, tampilkan header biasa
    $pdf->Cell(0, 10, 'LAPORAN DATA KEPENDUDUKAN', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Desa Kurniabakti, Kecamatan Cineam, Kabupaten Tasikmalaya', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Telp: (0265) 123456 | Email: desakurniabakti@email.com', 0, 1, 'C');
}

// Garis pemisah
$pdf->Line(10, $pdf->GetY(), $pdf->GetPageWidth() - 10, $pdf->GetY());
$pdf->Ln(5);


// Informasi filter
$filter_info = [];
if (!empty($dusun_filter)) {
    $sql_dusun = "SELECT dusun FROM tabel_dusun WHERE id = ?";
    $stmt = $conn->prepare($sql_dusun);
    $stmt->bind_param("i", $dusun_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    $dusun_data = $result->fetch_assoc();
    $nama_dusun = $dusun_data['dusun'] ?? 'Dusun tidak diketahui';
    $filter_info[] = "Dusun: $nama_dusun";
    $stmt->close();
}

if (!empty($jk_filter)) {
    $jk_text = ($jk_filter == 'L') ? 'Laki-laki' : 'Perempuan';
    $filter_info[] = "Jenis Kelamin: $jk_text";
}

if (!empty($search)) {
    $filter_info[] = "Kata kunci: \"$search\"";
}

$filter_text = !empty($filter_info) ? 'Filter: ' . implode(', ', $filter_info) : 'Semua Data';
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, $filter_text, 0, 1);
$pdf->Cell(0, 5, 'Tanggal Cetak: ' . dateIndo(date('Y-m-d H:i:s')), 0, 1);
$pdf->Cell(0, 5, 'Total Data: ' . number_format(count($data_kependudukan)) . ' penduduk', 0, 1);
$pdf->Ln(3);

// Tabel header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 9);

// Set column widths (landscape A4 = 297mm width, minus margins)
$col_widths = array(8, 30, 30, 40, 15, 20, 25, 25, 20, 30, 35);

$headers = array(
    'No',
    'No. KK',
    'NIK',
    'Nama Lengkap',
    'Kelamin',
    'Hub. Kel',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Pendidikan',
    'Pekerjaan',
    'Dusun'
);

// Print headers
foreach ($headers as $i => $header) {
    $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
}
$pdf->Ln();

// Data rows
$pdf->SetFont('helvetica', '', 8);
$no = 1;

foreach ($data_kependudukan as $penduduk) {
    // Jika hampir penuh, tambah halaman baru
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        // Print header lagi di halaman baru
        $pdf->SetFont('helvetica', 'B', 9);
        foreach ($headers as $i => $header) {
            $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 8);
    }

    // Hitung usia
    $usia = '';
    if (!empty($penduduk['TGL_LHR'])) {
        $tgl_lahir = new DateTime($penduduk['TGL_LHR']);
        $today = new DateTime();
        $usia = $today->diff($tgl_lahir)->y . ' th';
    }

    // Format tanggal lahir
    $tgl_lahir_formatted = !empty($penduduk['TGL_LHR']) ? dateIndo($penduduk['TGL_LHR']) : '';

    // Format pekerjaan
    $pekerjaan = $penduduk['jenis_pekerjaan'] ?? $penduduk['PEKERJAAN'] ?? '';
    $pekerjaan = smartTruncate($pekerjaan, 15);

    // Format nama
    $nama = smartTruncate($penduduk['NAMA_LGKP'] ?? '', 25);

    // Format tempat lahir
    $tempat_lahir = smartTruncate($penduduk['TMPT_LHR'] ?? '', 12);

    // Format pendidikan
    $pendidikan = smartTruncate($penduduk['PENDIDIKAN'] ?? '', 10);

    // Format dusun - khusus untuk dusun, jangan dipotong
    $dusun = $penduduk['dusun'] ?? '';

    $pdf->Cell($col_widths[0], 6, $no++, 1, 0, 'C');  // No
    $pdf->Cell($col_widths[1], 6, formatKKNIKPDF($penduduk['NO_KK'] ?? ''), 1);  // No. KK
    $pdf->Cell($col_widths[2], 6, formatKKNIKPDF($penduduk['NIK'] ?? ''), 1);  // NIK
    $pdf->Cell($col_widths[3], 6, $nama, 1);  // Nama
    $pdf->Cell($col_widths[4], 6, $penduduk['JK'] ?? '', 1, 0, 'C');  // JK
    $pdf->Cell($col_widths[5], 6, truncateHubKeluarga($penduduk['HBKEL'] ?? '', 8), 1, 0, 'C');  // Hub. Kel
    $pdf->Cell($col_widths[6], 6, $tempat_lahir, 1);  // Tempat Lahir
    $pdf->Cell($col_widths[7], 6, $tgl_lahir_formatted, 1, 0, 'C');  // Tanggal Lahir
    $pdf->Cell($col_widths[8], 6, $pendidikan, 1);  // Pendidikan
    $pdf->Cell($col_widths[9], 6, $pekerjaan, 1);  // Pekerjaan
    $pdf->Cell($col_widths[10], 6, $dusun, 1, 0, 'C');  // Dusun

    $pdf->Ln();
}

// Footer dengan TTD sederhana
$pdf->Ln(10);

// Garis pemisah
$pdf->Line(10, $pdf->GetY(), $pdf->GetPageWidth() - 10, $pdf->GetY());
$pdf->Ln(5);

// Hitung posisi untuk TTD
$pageWidth = $pdf->GetPageWidth();
$ttdX = $pageWidth - 80; // 80mm dari kiri untuk TTD

// Posisi untuk TTD
$pdf->SetX($ttdX);

// TTD di sebelah kanan
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(70, 5, 'Mengetahui,', 0, 1, 'C');
$pdf->SetX($ttdX);
$pdf->Cell(70, 15, '', 0, 1, 'C'); // Space untuk tanda tangan

$pdf->SetX($ttdX);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 5, 'KEPALA DESA KURNIABAKTI', 0, 1, 'C');

$pdf->SetX($ttdX);
$pdf->SetFont('helvetica', 'BU', 10);
$pdf->Cell(70, 5, 'NAMA KEPALA DESA', 0, 1, 'C');

$pdf->SetX($ttdX);
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(70, 5, 'NIP. 1234567890123456', 0, 1, 'C');

// Informasi laporan di bagian bawah
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, '--- Laporan ini dicetak secara otomatis dari Sistem Administrasi Desa Kurniabakti ---', 0, 1, 'C');
$pdf->Cell(0, 5, 'Halaman ' . $pdf->PageNo(), 0, 1, 'C');

// Output PDF ke browser
$filename = 'data_kependudukan_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');
exit();
