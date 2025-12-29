<?php
// Mulai output buffering
ob_start();

include_once __DIR__ . '/../../config/config.php';
require_once '../includes/header.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    ob_end_clean();
    header("Location: {$base_url}auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades' && $_SESSION['role'] !== 'sekretaris') {
    ob_end_clean();
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Include TCPDF
require_once '../../vendor/autoload.php';

// Parameter filter
$bulan_filter = isset($_GET['bulan_keluar']) ? $_GET['bulan_keluar'] : '';
$tahun_filter = isset($_GET['tahun_keluar']) ? $_GET['tahun_keluar'] : date('Y');
$sifat_filter = isset($_GET['sifat_keluar']) ? $_GET['sifat_keluar'] : '';
$status_filter = isset($_GET['status_keluar']) ? $_GET['status_keluar'] : '';
$search = isset($_GET['search_keluar']) ? $_GET['search_keluar'] : '';

// Query data dengan filter
$where_conditions = [];
$params = [];
$params_types = '';

if (!empty($bulan_filter) && !empty($tahun_filter)) {
    $where_conditions[] = "DATE_FORMAT(tanggal_surat, '%Y-%m') = ?";
    $params[] = $tahun_filter . '-' . $bulan_filter;
    $params_types .= 's';
} elseif (!empty($tahun_filter)) {
    $where_conditions[] = "YEAR(tanggal_surat) = ?";
    $params[] = $tahun_filter;
    $params_types .= 's';
}

if (!empty($sifat_filter)) {
    $where_conditions[] = "sifat_surat = ?";
    $params[] = $sifat_filter;
    $params_types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $params_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(nomor_surat LIKE ? OR tujuan LIKE ? OR perihal LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params_types .= 'sss';
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT * FROM tabel_surat_keluar 
        $where_sql 
        ORDER BY tanggal_surat DESC";

// Eksekusi query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($params_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $data_surat = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} else {
    $result = mysqli_query($conn, $sql);
    $data_surat = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Clear output buffer
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
    $pdf->Cell(0, 10, 'LAPORAN DATA ARSIP SURAT KELUAR', 0, 1);

    // Informasi desa di bawah judul
    $pdf->SetXY(40, $pdf->GetY() + 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Desa Kurniabakti, Kecamatan Cineam, Kabupaten Tasikmalaya', 0, 1);

    $pdf->SetXY(40, $pdf->GetY());
    $pdf->Cell(0, 5, 'Telp: (0265) 123456 | Email: desakurniabakti@email.com', 0, 1);
} else {
    // Jika logo tidak ada, tampilkan header biasa
    $pdf->Cell(0, 10, 'LAPORAN DATA ARSIP SURAT KELUAR', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Desa Kurniabakti, Kecamatan Cineam, Kabupaten Tasikmalaya', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Telp: (0265) 123456 | Email: desakurniabakti@email.com', 0, 1, 'C');
}

// Garis pemisah
$y = $pdf->GetY() + 7; // atur angka sesuai kebutuhan
$pdf->Line(10, $y, $pdf->GetPageWidth() - 10, $y);
$pdf->Ln(8);

// Info filter
$filter_info = [];
if (!empty($bulan_filter) && !empty($tahun_filter)) {
    $bulan_labels = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];
    $filter_info[] = $bulan_labels[$bulan_filter] . ' ' . $tahun_filter;
} elseif (!empty($tahun_filter)) {
    $filter_info[] = 'Tahun ' . $tahun_filter;
}
if (!empty($sifat_filter)) $filter_info[] = 'Sifat: ' . $sifat_filter;
if (!empty($status_filter)) $filter_info[] = 'Status: ' . $status_filter;
if (!empty($search)) $filter_info[] = 'Kata kunci: "' . $search . '"';

$filter_text = !empty($filter_info) ? 'Filter: ' . implode(', ', $filter_info) : 'Semua Data';
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, $filter_text, 0, 1);
$pdf->Cell(0, 5, 'Tanggal Cetak: ' . dateIndo(date('Y-m-d H:i:s')), 0, 1);
$pdf->Cell(0, 5, 'Total Data: ' . count($data_surat) . ' surat', 0, 1);
$pdf->Ln(3);

// Tabel header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 9);

$col_widths = [8, 50, 45, 45, 70, 30, 30];
$headers = ['No', 'Nomor Surat', 'Tgl Surat', 'Tujuan', 'Perihal', 'Sifat', 'Status'];

foreach ($headers as $i => $header) {
    $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
}
$pdf->Ln();

// Data rows
$pdf->SetFont('helvetica', '', 8);
$no = 1;

foreach ($data_surat as $surat) {
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 9);
        foreach ($headers as $i => $header) {
            $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 8);
    }

    $tanggal_surat = !empty($surat['tanggal_surat']) ? dateIndo($surat['tanggal_surat']) : '-';

    $pdf->Cell($col_widths[0], 6, $no++, 1, 0, 'C');
    $pdf->Cell($col_widths[1], 6, substr($surat['nomor_surat'], 0, 25), 1);
    $pdf->Cell($col_widths[2], 6, $tanggal_surat, 1, 0, 'C');
    $pdf->Cell($col_widths[3], 6, substr($surat['tujuan'], 0, 30), 1);
    $pdf->Cell($col_widths[4], 6, substr($surat['perihal'], 0, 45), 1);
    $pdf->Cell($col_widths[5], 6, substr($surat['sifat_surat'], 0, 10), 1, 0, 'C');
    $pdf->Cell($col_widths[6], 6, substr($surat['status'], 0, 10), 1, 0, 'C');
    $pdf->Ln();
}

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

// Output PDF
$filename = 'arsip_surat_keluar_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');
exit;
