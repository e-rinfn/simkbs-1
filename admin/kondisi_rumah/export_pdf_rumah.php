<?php
// Pastikan tidak ada output sebelum ini
ob_start(); // Start output buffering

require_once '../includes/header.php';
require_once '../../vendor/autoload.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    ob_end_clean(); // Clear buffer
    header("Location: {$base_url}auth/login.php");
    exit;
}

// Jika diperlukan role tertentu (admin/kades), sesuaikan dengan kebutuhan
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades') {
    ob_end_clean(); // Clear buffer
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Pastikan tidak ada output dari header.php
ob_end_clean(); // Clear buffer sebelum memulai PDF

// Ambil parameter filter yang sama dengan list.php
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$jenis_lantai_filter = isset($_GET['jenis_lantai']) ? $_GET['jenis_lantai'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Query data dengan filter yang sama
$where_conditions = [];

if (!empty($dusun_filter)) {
    $dusun_filter_safe = mysqli_real_escape_string($conn, $dusun_filter);
    $where_conditions[] = "k.DSN = '$dusun_filter_safe'";
}

if (!empty($jenis_lantai_filter)) {
    $jenis_lantai_filter_safe = mysqli_real_escape_string($conn, $jenis_lantai_filter);
    $where_conditions[] = "r.jenis_lantai = '$jenis_lantai_filter_safe'";
}

if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where_conditions[] = "(k.NO_KK LIKE '%$search_safe%' OR k.NIK LIKE '%$search_safe%' OR k.NAMA_LGKP LIKE '%$search_safe%')";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query utama
$sql = "SELECT 
            k.id,
            k.NO_KK,
            k.NIK,
            k.NAMA_LGKP,
            k.NAMA_PANGGILAN,
            k.JK,
            k.DSN,
            k.rt,
            k.rw,
            d.dusun,
            r.id as rumah_id,
            r.status_tempat_tinggal,
            r.luas_lantai,
            r.jenis_lantai,
            r.jenis_dinding,
            r.fasilitas_bab,
            r.sumber_penerangan,
            r.sumber_air_minum,
            r.bahan_bakar_memasak,
            r.kondisi_rumah,
            r.created_at as rumah_created_at,
            r.updated_at as rumah_updated_at
        FROM tabel_kependudukan k
        LEFT JOIN tabel_dusun d ON k.DSN = d.id
        LEFT JOIN tabel_rumah r ON k.NIK = r.NIK
        $where_sql
        ORDER BY k.NAMA_LGKP";

$data_rumah = query($sql);

// Hitung total
$total_data = count($data_rumah);

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
$pdf->Cell(0, 10, 'LAPORAN DATA KONDISI RUMAH', 0, 1, 'C');

// Informasi filter
$filter_info = [];
if (!empty($dusun_filter)) {
    // Ambil nama dusun
    $sql_dusun = "SELECT dusun FROM tabel_dusun WHERE id = '$dusun_filter'";
    $dusun_data = query($sql_dusun);
    $nama_dusun = $dusun_data[0]['dusun'] ?? $dusun_filter;
    $filter_info[] = "Dusun: $nama_dusun";
}
if (!empty($jenis_lantai_filter)) {
    $filter_info[] = "Jenis Lantai: $jenis_lantai_filter";
}
if (!empty($search)) {
    $filter_info[] = "Kata kunci: \"$search\"";
}

$filter_text = !empty($filter_info) ? 'Filter: ' . implode(', ', $filter_info) : 'Semua Data';

$pdf->SetFont('times', '', 9);
$pdf->Cell(0, 5, $filter_text, 0, 1);
$pdf->Cell(0, 5, 'Tanggal Cetak: ' . dateIndo(date('Y-m-d H:i:s')), 0, 1);
$pdf->Cell(0, 5, 'Total Data: ' . number_format($total_data) . ' rumah', 0, 1);
$pdf->Ln(3);

// Tabel header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('times', 'B', 9);

// Set column widths - SESUAIKAN JUMLAHNYA DENGAN HEADERS
// Total width = 8 + 30 + 30 + 40 + 40 + 20 + 20 + 20 + 20 + 20 + 30 = 278mm (A4 landscape width = 297mm)
$col_widths = array(8, 30, 30, 35, 20, 20, 25, 25, 25, 30, 30);

$headers = array('No', 'No. KK', 'NIK', 'Nama', 'Dusun', 'Luas Lantai', 'Jenis Lantai', 'Penerangan', 'Sumber Air', 'Bahan Bakar', 'Kondisi');

// Print headers
foreach ($headers as $i => $header) {
    $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
}
$pdf->Ln();

// Data rows
$pdf->SetFont('times', '', 8);
$no = 1;

foreach ($data_rumah as $rumah) {
    // Jika data terlalu banyak, tambah halaman baru
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        // Print header lagi di halaman baru
        $pdf->SetFont('times', 'B', 9);
        foreach ($headers as $i => $header) {
            $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('times', '', 8);
    }

    $pdf->Cell($col_widths[0], 6, $no++, 1, 0, 'C');  // No
    $pdf->Cell($col_widths[1], 6, formatKKNIK($rumah['NO_KK']), 1, 0, 'C');  // No. KK
    $pdf->Cell($col_widths[2], 6, formatKKNIK($rumah['NIK']), 1, 0, 'C');  // NIK
    $pdf->Cell($col_widths[3], 6, substr($rumah['NAMA_LGKP'], 0, 20), 1);  // Nama
    $pdf->Cell($col_widths[4], 6, substr($rumah['dusun'] ?? '-', 0, 10), 1, 0, 'C');  // Dusun
    $pdf->Cell($col_widths[5], 6, $rumah['luas_lantai'] ? number_format($rumah['luas_lantai'], 1) . ' m²' : '-', 1, 0, 'C');  // Luas Lantai
    $pdf->Cell($col_widths[6], 6, substr($rumah['jenis_lantai'] ?? '-', 0, 10), 1);  // Jenis Lantai
    $pdf->Cell($col_widths[7], 6, substr($rumah['sumber_penerangan'] ?? '-', 0, 10), 1);  // Penerangan
    $pdf->Cell($col_widths[8], 6, substr($rumah['sumber_air_minum'] ?? '-', 0, 10), 1);  // Sumber Air
    $pdf->Cell($col_widths[9], 6, substr($rumah['bahan_bakar_memasak'] ?? '-', 0, 10), 1);  // Bahan Bakar

    // Warna untuk kondisi rumah
    $kondisi = $rumah['kondisi_rumah'] ?? '-';
    $kondisi_short = substr($kondisi, 0, 12);

    // Set warna berdasarkan kondisi
    if (strpos($kondisi, 'LAYAK') !== false) {
        $pdf->SetTextColor(0, 128, 0); // Hijau
    } elseif (strpos($kondisi, 'RUSAK RINGAN') !== false) {
        $pdf->SetTextColor(255, 165, 0); // Orange
    } elseif (strpos($kondisi, 'RUSAK BERAT') !== false) {
        $pdf->SetTextColor(255, 0, 0); // Merah
    }

    $pdf->Cell($col_widths[10], 6, $kondisi_short, 1, 0, 'C');  // Kondisi (kolom ke-11, index 10)

    // Reset warna
    $pdf->SetTextColor(0, 0, 0);

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

// Output PDF ke browser
$filename = 'laporan_kondisi_rumah_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');
exit();
