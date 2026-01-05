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

if ($_SESSION['role'] !== 'kepala_desa') {
    ob_end_clean();
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Include TCPDF
require_once '../../vendor/autoload.php';

// Parameter filter
$bulan_filter = isset($_GET['bulan_masuk']) ? $_GET['bulan_masuk'] : '';
$tahun_filter = isset($_GET['tahun_masuk']) ? $_GET['tahun_masuk'] : date('Y');
$sifat_filter = isset($_GET['sifat_masuk']) ? $_GET['sifat_masuk'] : '';
$status_filter = isset($_GET['status_masuk']) ? $_GET['status_masuk'] : '';
$search = isset($_GET['search_masuk']) ? $_GET['search_masuk'] : '';

// Query data dengan filter (sama seperti di atas)
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
    $where_conditions[] = "(nomor_surat LIKE ? OR pengirim LIKE ? OR perihal LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params_types .= 'sss';
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT * FROM tabel_surat_masuk 
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

// Header dengan Logo
$pdf->SetFont('times', 'B', 16);

// Path logo
$logoPath = __DIR__ . '/../../assets/img/LogoKBS.png';

// Posisi awal
$marginLeft = 10;
$logoWidth  = 25;
$textStartX = $marginLeft + $logoWidth + 2; // jarak setelah logo, sedikit digeser kiri
$pageWidth  = $pdf->getPageWidth();
$textWidth  = $pageWidth - $textStartX - $marginLeft;

if (file_exists($logoPath)) {

    // Logo kiri
    $pdf->Image($logoPath, $marginLeft, 10, $logoWidth);

    // Header teks manual (tanpa align 'C')
    $y = 10;

    $pdf->SetFont('times', 'B', 12);
    $text = 'PEMERINTAH DAERAH KABUPATEN TASIKMALAYA';
    $w = $pdf->GetStringWidth($text);
    $x = max($textStartX, ($pageWidth - $w) / 2);
    $pdf->SetXY($x, $y);
    $pdf->Cell($w, 6, $text, 0, 1, 'L');

    $pdf->SetFont('times', 'B', 11);
    $text = 'KECAMATAN CIAWI';
    $w = $pdf->GetStringWidth($text);
    $x = max($textStartX, ($pageWidth - $w) / 2);
    $pdf->SetXY($x, $y + 6);
    $pdf->Cell($w, 5, $text, 0, 1, 'L');

    $pdf->SetFont('times', 'B', 14);
    $text = 'DESA KURNIABAKTI';
    $w = $pdf->GetStringWidth($text);
    $x = max($textStartX, ($pageWidth - $w) / 2);
    $pdf->SetXY($x, $y + 11);
    $pdf->Cell($w, 6, $text, 0, 1, 'L');

    $pdf->SetFont('times', '', 9);
    $text = 'Jl. Kapten Suradimadja Dalam No. 110 Kode Pos 46156 Ciawi TASIKMALAYA';
    $w = $pdf->GetStringWidth($text);
    $x = max($textStartX, ($pageWidth - $w) / 2);
    $pdf->SetXY($x, $y + 20);
    $pdf->Cell($w, 5, $text, 0, 1, 'L');
} else {

    // Jika logo tidak ada → full center halaman (manual X)
    $y = 10;

    $pdf->SetFont('times', 'B', 12);
    $text = 'PEMERINTAH DAERAH KABUPATEN TASIKMALAYA';
    $w = $pdf->GetStringWidth($text);
    $x = ($pageWidth - $w) / 2;
    $pdf->SetXY($x, $y);
    $pdf->Cell($w, 6, $text, 0, 1, 'L');

    $pdf->SetFont('times', 'B', 11);
    $text = 'KECAMATAN CIAWI';
    $w = $pdf->GetStringWidth($text);
    $x = ($pageWidth - $w) / 2;
    $pdf->SetXY($x, $y + 6);
    $pdf->Cell($w, 5, $text, 0, 1, 'L');

    $pdf->SetFont('times', 'B', 14);
    $text = 'DESA KURNIABAKTI';
    $w = $pdf->GetStringWidth($text);
    $x = ($pageWidth - $w) / 2;
    $pdf->SetXY($x, $y + 11);
    $pdf->Cell($w, 6, $text, 0, 1, 'L');

    $pdf->SetFont('times', '', 9);
    $text = 'Jl. Kapten Suradimadja Dalam No. 110 Kode Pos 46156 Ciawi TASIKMALAYA';
    $w = $pdf->GetStringWidth($text);
    $x = ($pageWidth - $w) / 2;
    $pdf->SetXY($x, $y + 17);
    $pdf->Cell($w, 5, $text, 0, 1, 'L');
}

// Garis pemisah (dua garis tipis)
$y = $pdf->GetY() + 2; // atur angka sesuai kebutuhan
$pdf->Line(10, $y, $pdf->GetPageWidth() - 10, $y);
$pdf->Line(10, $y + 1.2, $pdf->GetPageWidth() - 10, $y + 1.2);
$pdf->Ln(8);

// Judul Laporan
$pdf->SetFont('times', 'B', 14);
$pdf->Cell(0, 10, 'LAPORAN DATA SURAT MASUK', 0, 1, 'C');

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
$pdf->SetFont('times', '', 9);
$pdf->Cell(0, 5, $filter_text, 0, 1);
$pdf->Cell(0, 5, 'Tanggal Cetak: ' . dateIndo(date('Y-m-d H:i:s')), 0, 1);
$pdf->Cell(0, 5, 'Total Data: ' . count($data_surat) . ' surat', 0, 1);
$pdf->Ln(3);

// Tabel header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('times', 'B', 9);

$col_widths = [8, 50, 35, 35, 50, 60, 20, 20];
$headers = ['No', 'Nomor Surat', 'Tgl Surat', 'Tgl Terima', 'Pengirim', 'Perihal', 'Sifat', 'Status'];

foreach ($headers as $i => $header) {
    $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
}
$pdf->Ln();

// Data rows
$pdf->SetFont('times', '', 8);
$no = 1;

foreach ($data_surat as $surat) {
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $pdf->SetFont('times', 'B', 9);
        foreach ($headers as $i => $header) {
            $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('times', '', 8);
    }

    $tanggal_surat = !empty($surat['tanggal_surat']) ? dateIndo($surat['tanggal_surat']) : '-';
    $tanggal_diterima = !empty($surat['tanggal_diterima']) ? dateIndo($surat['tanggal_diterima']) : '-';

    $pdf->Cell($col_widths[0], 6, $no++, 1, 0, 'C');
    $pdf->Cell($col_widths[1], 6, substr($surat['nomor_surat'], 0, 25), 1);
    $pdf->Cell($col_widths[2], 6, $tanggal_surat, 1, 0, 'C');
    $pdf->Cell($col_widths[3], 6, $tanggal_diterima, 1, 0, 'C');
    $pdf->Cell($col_widths[4], 6, substr($surat['pengirim'], 0, 25), 1);
    $pdf->Cell($col_widths[5], 6, substr($surat['perihal'], 0, 40), 1);
    $pdf->Cell($col_widths[6], 6, substr($surat['sifat_surat'], 0, 10), 1, 0, 'C');
    $pdf->Cell($col_widths[7], 6, substr($surat['status'], 0, 10), 1, 0, 'C');
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
$pdf->SetFont('times', '', 9);
$pdf->Cell(70, 5, 'Mengetahui,', 0, 1, 'C');
$pdf->SetX($ttdX);
$pdf->Cell(70, 15, '', 0, 1, 'C'); // Space untuk tanda tangan

$pdf->SetX($ttdX);
$pdf->SetFont('times', 'B', 10);
$pdf->Cell(70, 5, 'KEPALA DESA KURNIABAKTI', 0, 1, 'C');

$pdf->SetX($ttdX);
$pdf->SetFont('times', 'BU', 10);
$pdf->Cell(70, 5, 'NAMA KEPALA DESA', 0, 1, 'C');

// $pdf->SetX($ttdX);
// $pdf->SetFont('times', '', 9);
// $pdf->Cell(70, 5, 'NIP. 1234567890123456', 0, 1, 'C');

// Informasi laporan di bagian bawah
$pdf->Ln(10);
$pdf->SetFont('times', 'I', 8);
$pdf->Cell(0, 5, '--- Laporan ini dicetak secara otomatis dari Sistem Administrasi Desa Kurniabakti ---', 0, 1, 'C');
$pdf->Cell(0, 5, 'Halaman ' . $pdf->PageNo(), 0, 1, 'C');

// Output PDF
$filename = 'arsip_surat_masuk_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');
exit;
