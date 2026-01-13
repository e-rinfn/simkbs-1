// Fungsi untuk label status tinggal
function labelStatusTinggal($status) {
switch (strtoupper($status)) {
case 'MENINGGAL': return 'Meninggal';
case 'PINDAH': return 'Pindah';
case 'TETAP':
case 'SEMENTARA':
case 'PENDATANG':
return 'Tinggal';
default: return $status;
}
}
<?php
// export_pdf.php
// Aktifkan error reporting
error_reporting(error_level: E_ALL);
ini_set('display_errors', 1);

// Pastikan tidak ada output sebelum ini
if (ob_get_length()) {
    ob_end_clean();
}

// Mulai output buffering
ob_start();

include_once __DIR__ . '/../../config/config.php';

// Cek apakah user sudah login
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect ke login
    header("Location: {$base_url}/auth/login.php");
    exit();
}

// Filter functionality (sama seperti di list.php)
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$dusun_filter = isset($_GET['dusun']) ? intval($_GET['dusun']) : 0;
$jk_filter = isset($_GET['jk']) ? $conn->real_escape_string($_GET['jk']) : '';

$where_conditions = [];

// Build WHERE conditions (sama seperti di list.php)
if ($search) {
    $where_conditions[] = "(NIK LIKE '%$search%' OR NO_KK LIKE '%$search%' OR NAMA_LGKP LIKE '%$search%' OR NAMA_PANGGILAN LIKE '%$search%' OR ALAMAT LIKE '%$search%')";
}

if ($dusun_filter > 0) {
    $where_conditions[] = "k.DSN = $dusun_filter";
}

if ($jk_filter && in_array($jk_filter, ['L', 'P'])) {
    $where_conditions[] = "k.JK = '$jk_filter'";
}

// Combine WHERE conditions
$where = '';
if (!empty($where_conditions)) {
    $where = "WHERE " . implode(" AND ", $where_conditions);
}

// Get all data without pagination (untuk PDF)
$sql = "SELECT k.*, d.dusun as nama_dusun 
        FROM tabel_kependudukan k 
        LEFT JOIN tabel_dusun d ON k.DSN = d.id 
        $where 
        ORDER BY k.DSN, k.NAMA_LGKP";

$result = $conn->query($sql);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Get statistics
$total_records = count($data);
$total_laki = 0;
$total_perempuan = 0;

foreach ($data as $row) {
    if ($row['JK'] == 'L') {
        $total_laki++;
    } else {
        $total_perempuan++;
    }
}

// Get dusun name for filter info
$dusun_nama = 'Semua Dusun';
if ($dusun_filter > 0) {
    $sql_dusun = "SELECT dusun FROM tabel_dusun WHERE id = $dusun_filter";
    $dusun_result = $conn->query($sql_dusun);
    if ($dusun_result && $dusun_row = $dusun_result->fetch_assoc()) {
        $dusun_nama = $dusun_row['dusun'];
    }
}

// Pastikan TCPDF tersedia
$tcpdf_path = __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    // Fallback: coba path lain
    $tcpdf_path = __DIR__ . '/../../vendor/tcpdf/tcpdf.php';

    if (!file_exists($tcpdf_path)) {
        die('TCPDF library tidak ditemukan. Silakan install dengan: composer require tecnickcom/tcpdf');
    }
}

require_once $tcpdf_path;

// Hapus semua output buffer sebelum membuat PDF
while (ob_get_level()) {
    ob_end_clean();
}

// Create new PDF document
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Sistem Kependudukan Desa');
$pdf->SetAuthor('Desa Kurniabakti');
$pdf->SetTitle('Data Penduduk');
$pdf->SetSubject('Export Data Penduduk');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Function untuk truncate status kawin
function truncateStatusKawin($text)
{
    $short = [
        'BELUM KAWIN' => 'BLM KWN',
        'KAWIN' => 'KAWIN',
        'CERAI HIDUP' => 'CR HIDUP',
        'CERAI MATI' => 'CR MATI'
    ];
    return isset($short[$text]) ? $short[$text] : substr($text, 0, 8);
}

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
$pdf->Cell(0, 10, 'LAPORAN DATA KEPENDUDUKAN', 0, 1, 'C');

// Informasi filter
$pdf->SetFont('times', '', 9);
$filter_info = [];

if (!empty($search)) {
    $filter_info[] = "Pencarian: \"" . htmlspecialchars($search) . "\"";
}

$filter_info[] = "Dusun: $dusun_nama";

if (!empty($jk_filter)) {
    $jk_text = ($jk_filter == 'L') ? 'Laki-laki' : 'Perempuan';
    $filter_info[] = "Jenis Kelamin: $jk_text";
} else {
    $filter_info[] = "Jenis Kelamin: Semua";
}

$filter_text = 'Filter: ' . implode(' | ', $filter_info);
$pdf->Cell(0, 5, $filter_text, 0, 1);
$pdf->Cell(0, 5, 'Tanggal Cetak: ' . dateIndo('now'), 0, 1);
$pdf->Cell(0, 5, 'Total Data: ' . number_format($total_records) . ' | Laki-laki: ' . number_format($total_laki) . ' | Perempuan: ' . number_format($total_perempuan), 0, 1);
$pdf->Ln(3);

// Tabel header - SEDERHANA TANPA MULTICELL
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('times', 'B', 9);

// Set column widths yang lebih sederhana

// Tambahkan kolom Status Tinggal
$col_widths = array(8, 28, 28, 40, 10, 10, 25, 22, 23, 18, 30, 35);

$headers = array(
    'No',
    'NIK',
    'No. KK',
    'Nama',
    'JK',
    'Usia',
    'Tgl Lahir',
    'Status Kawin',
    'Status Tinggal',
    'Agama',
    'Alamat',
    'Dusun'
);

// Print headers
foreach ($headers as $i => $header) {
    $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
}

$pdf->Ln();

// Data rows - VERSI SEDERHANA
$pdf->SetFont('times', '', 8);
$no = 1;

foreach ($data as $row) {
    // Jika hampir penuh, tambah halaman baru
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

    // Hitung usia
    $usia = '';
    if (!empty($row['TGL_LHR'])) {
        try {
            $tgl_lahir = new DateTime($row['TGL_LHR']);
            $today = new DateTime();
            $usia = $today->diff($tgl_lahir)->y;
        } catch (Exception $e) {
            $usia = '';
        }
    }

    // Format tanggal lahir (singkat)
    $tgl_lahir_formatted = !empty($row['TGL_LHR']) ? dateIndo($row['TGL_LHR']) : '';

    // Format nama (lebih pendek)
    $nama = smartTruncate($row['NAMA_LGKP'] ?? '', 20);

    // Format alamat (singkat)
    $alamat = smartTruncate($row['ALAMAT'] ?? '', 18);
    if (!empty($row['rt'])) {
        $alamat .= ' RT' . $row['rt'];
    }

    // Format agama (singkat)
    $agama = smartTruncate($row['AGAMA'] ?? '', 6);

    // Kolom 1: No
    $pdf->Cell($col_widths[0], 6, $no++, 1, 0, 'C');

    // Kolom 2: NIK (format tanpa spasi agar muat)
    $nik_text = $row['NIK'] ?? '';
    $pdf->Cell($col_widths[1], 6, $nik_text, 1, 0, 'L');

    // Kolom 3: No. KK
    $kk_text = $row['NO_KK'] ?? '';
    $pdf->Cell($col_widths[2], 6, $kk_text, 1, 0, 'L');

    // Kolom 4: Nama
    $pdf->Cell($col_widths[3], 6, $nama, 1, 0, 'L');

    // Kolom 5: JK
    $pdf->Cell($col_widths[4], 6, $row['JK'] ?? '', 1, 0, 'C');

    // Kolom 6: Usia
    $pdf->Cell($col_widths[5], 6, $usia, 1, 0, 'C');

    // Kolom 7: Tgl Lahir
    $pdf->Cell($col_widths[6], 6, $tgl_lahir_formatted, 1, 0, 'C');


    // Kolom 8: Status Kawin (singkat)
    $status_kawin = truncateStatusKawin($row['STATUS_KAWIN'] ?? '');
    $pdf->Cell($col_widths[7], 6, $status_kawin, 1, 0, 'C');

    // Kolom 9: Status Tinggal (label ramah)
    $pdf->Cell($col_widths[8], 6, ($row['STATUS_TINGGAL'] ?? ''), 1, 0, 'C');

    // Kolom 10: Agama
    $pdf->Cell($col_widths[9], 6, $agama, 1, 0, 'C');

    // Kolom 11: Alamat
    $pdf->Cell($col_widths[10], 6, $alamat, 1, 0, 'L');

    // Kolom 12: Dusun
    $pdf->Cell($col_widths[11], 6, $row['nama_dusun'] ?? '-', 1, 0, 'C');

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
$filename = 'data_kependudukan_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');

exit();
