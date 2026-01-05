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

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'kades') {
    ob_end_clean();
    header("Location: {$base_url}/auth/role_tidak_cocok.php");
    exit();
}

// Include TCPDF
require_once '../../vendor/autoload.php';

// Parameter filter
$dusun_filter = isset($_GET['dusun']) ? $_GET['dusun'] : '';
$rt_filter = isset($_GET['rt']) ? $_GET['rt'] : '';
$rw_filter = isset($_GET['rw']) ? $_GET['rw'] : '';

// Query data penduduk dengan filter
$where_conditions = [];
$params = [];

if (!empty($dusun_filter)) {
    $where_conditions[] = "k.DSN = ?";
    $params[] = $dusun_filter;
}

if (!empty($rt_filter)) {
    $where_conditions[] = "k.rt = ?";
    $params[] = $rt_filter;
}

if (!empty($rw_filter)) {
    $where_conditions[] = "k.rw = ?";
    $params[] = $rw_filter;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Query data penduduk
$sql = "SELECT 
            k.*,
            d.dusun,
            YEAR(CURDATE()) - YEAR(k.TGL_LHR) - 
            (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(k.TGL_LHR, '%m%d')) as usia
        FROM tabel_kependudukan k
        LEFT JOIN tabel_dusun d ON k.DSN = d.id
        $where_sql
        ORDER BY d.dusun, k.rw, k.rt, k.JK";

// Eksekusi query
if (!empty($params)) {
    $data_penduduk = query($sql, $params);
} else {
    $data_penduduk = query($sql);
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
$pdf->SetFont('times', '', 10);

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
$pdf->Cell(0, 10, 'LAPORAN DATA KLASIFIKASI PENDUDUK', 0, 1, 'C');

// Info filter
$filter_info = [];
if (!empty($dusun_filter)) {
    $sql_dusun = "SELECT dusun FROM tabel_dusun WHERE id = ?";
    $dusun_data = query($sql_dusun, [$dusun_filter]);
    $nama_dusun = $dusun_data[0]['dusun'] ?? 'Dusun tidak diketahui';
    $filter_info[] = "Dusun: $nama_dusun";
}
if (!empty($rt_filter)) $filter_info[] = "RT: $rt_filter";
if (!empty($rw_filter)) $filter_info[] = "RW: $rw_filter";

$filter_text = !empty($filter_info) ? 'Filter: ' . implode(', ', $filter_info) : 'Semua Data';
$pdf->SetFont('times', '', 9);
$pdf->Cell(0, 5, $filter_text, 0, 1);
$pdf->Cell(0, 5, 'Tanggal Cetak: ' . date('d/m/Y H:i:s'), 0, 1);
$pdf->Cell(0, 5, 'Total Data: ' . count($data_penduduk) . ' penduduk', 0, 1);
$pdf->Ln(5);

// **TABEL 1: Klasifikasi RT/RW dan Jenis Kelamin**
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 8, '1. Klasifikasi Berdasarkan RT/RW dan Jenis Kelamin', 0, 1);
$pdf->SetFont('times', '', 9);

// Hitung data tabel 1
$klasifikasi_data = [];
foreach ($data_penduduk as $penduduk) {
    $key = $penduduk['dusun'] . '_' . $penduduk['rt'] . '_' . $penduduk['rw'] . '_' . $penduduk['JK'];

    if (!isset($klasifikasi_data[$key])) {
        $klasifikasi_data[$key] = [
            'dusun' => $penduduk['dusun'] ?? 'Tidak diketahui',
            'rt' => $penduduk['rt'] ?? '0',
            'rw' => $penduduk['rw'] ?? '0',
            'jk' => $penduduk['JK'],
            'jumlah' => 0
        ];
    }
    $klasifikasi_data[$key]['jumlah']++;
}

// Tabel header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('times', 'B', 9);

$col_widths = [8, 150, 30, 30, 30, 30];
$headers = ['No', 'Dusun', 'RT', 'RW', 'Jenis Kelamin', 'Jumlah'];

foreach ($headers as $i => $header) {
    $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
}
$pdf->Ln();

// Data rows
$pdf->SetFont('times', '', 8);
$no = 1;
$grand_total = 0;

foreach ($klasifikasi_data as $data) {
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $pdf->SetFont('times', 'B', 9);
        foreach ($headers as $i => $header) {
            $pdf->Cell($col_widths[$i], 7, $header, 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('times', '', 8);
    }

    $pdf->Cell($col_widths[0], 6, $no++, 1, 0, 'C');
    $pdf->Cell($col_widths[1], 6, substr($data['dusun'], 0, 30), 1);
    $pdf->Cell($col_widths[2], 6, $data['rt'], 1, 0, 'C');
    $pdf->Cell($col_widths[3], 6, $data['rw'], 1, 0, 'C');
    $pdf->Cell($col_widths[4], 6, $data['jk'] == 'L' ? 'Laki-laki' : 'Perempuan', 1);
    $pdf->Cell($col_widths[5], 6, number_format($data['jumlah']), 1, 0, 'C');
    $pdf->Ln();

    $grand_total += $data['jumlah'];
}

// Total
$pdf->SetFont('times', 'B', 9);
$pdf->Cell(array_sum($col_widths) - $col_widths[5], 6, 'TOTAL:', 1, 0, 'R');
$pdf->Cell($col_widths[5], 6, number_format($grand_total), 1, 0, 'C');
$pdf->Ln(10);


// Footer dengan TTD sederhana
$pdf->Ln(10);

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



// **TABEL 2: Rentang Umur**
$pdf->AddPage();
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 8, '2. Distribusi Berdasarkan Rentang Usia', 0, 1);
$pdf->SetFont('times', '', 9);

// Rentang umur
$rentang_labels = [
    '0-5',
    '6-10',
    '11-15',
    '16-20',
    '21-25',
    '26-30',
    '31-35',
    '36-40',
    '41-45',
    '46-50',
    '51-55',
    '56-60',
    '60+'
];

// Kelompokkan per dusun
$rentang_umur = [];
foreach ($data_penduduk as $penduduk) {
    $dusun = $penduduk['dusun'] ?? 'Tidak diketahui';
    $usia = (int)$penduduk['usia'];

    if (!isset($rentang_umur[$dusun])) {
        $rentang_umur[$dusun] = [
            'dusun' => $dusun,
            'total' => 0
        ];
        foreach ($rentang_labels as $label) {
            $rentang_umur[$dusun][$label] = 0;
        }
    }

    $rentang_umur[$dusun]['total']++;

    // Tentukan rentang
    if ($usia <= 5) $rentang_umur[$dusun]['0-5']++;
    elseif ($usia <= 10) $rentang_umur[$dusun]['6-10']++;
    elseif ($usia <= 15) $rentang_umur[$dusun]['11-15']++;
    elseif ($usia <= 20) $rentang_umur[$dusun]['16-20']++;
    elseif ($usia <= 25) $rentang_umur[$dusun]['21-25']++;
    elseif ($usia <= 30) $rentang_umur[$dusun]['26-30']++;
    elseif ($usia <= 35) $rentang_umur[$dusun]['31-35']++;
    elseif ($usia <= 40) $rentang_umur[$dusun]['36-40']++;
    elseif ($usia <= 45) $rentang_umur[$dusun]['41-45']++;
    elseif ($usia <= 50) $rentang_umur[$dusun]['46-50']++;
    elseif ($usia <= 55) $rentang_umur[$dusun]['51-55']++;
    elseif ($usia <= 60) $rentang_umur[$dusun]['56-60']++;
    else $rentang_umur[$dusun]['60+']++;
}

// Tabel header untuk rentang umur
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('times', 'B', 8);

$col_widths_umur = [8, 100];
foreach ($rentang_labels as $label) {
    $col_widths_umur[] = 12;
}
$col_widths_umur[] = 15;

// Header
$pdf->Cell($col_widths_umur[0], 7, 'No', 1, 0, 'C', 1);
$pdf->Cell($col_widths_umur[1], 7, 'Dusun', 1, 0, 'C', 1);
foreach ($rentang_labels as $label) {
    $pdf->Cell($col_widths_umur[2], 7, $label, 1, 0, 'C', 1);
}
$pdf->Cell($col_widths_umur[count($col_widths_umur) - 1], 7, 'Total', 1, 0, 'C', 1);
$pdf->Ln();

// Data rows
$pdf->SetFont('times', '', 7);
$no = 1;
$total_per_rentang = array_fill_keys($rentang_labels, 0);
$grand_total_umur = 0;

foreach ($rentang_umur as $data) {
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell($col_widths_umur[0], 7, 'No', 1, 0, 'C', 1);
        $pdf->Cell($col_widths_umur[1], 7, 'Dusun', 1, 0, 'C', 1);
        foreach ($rentang_labels as $label) {
            $pdf->Cell($col_widths_umur[2], 7, $label, 1, 0, 'C', 1);
        }
        $pdf->Cell($col_widths_umur[count($col_widths_umur) - 1], 7, 'Total', 1, 0, 'C', 1);
        $pdf->Ln();
        $pdf->SetFont('times', '', 7);
    }

    $pdf->Cell($col_widths_umur[0], 5, $no++, 1, 0, 'C');
    $pdf->Cell($col_widths_umur[1], 5, substr($data['dusun'], 0, 20), 1);

    foreach ($rentang_labels as $label) {
        $jumlah = $data[$label] ?? 0;
        $total_per_rentang[$label] += $jumlah;
        $pdf->Cell($col_widths_umur[2], 5, number_format($jumlah), 1, 0, 'C');
    }

    $pdf->Cell($col_widths_umur[count($col_widths_umur) - 1], 5, number_format($data['total']), 1, 0, 'C');
    $pdf->Ln();

    $grand_total_umur += $data['total'];
}

// Total
$pdf->SetFont('times', 'B', 8);
$pdf->Cell($col_widths_umur[0] + $col_widths_umur[1], 6, 'TOTAL:', 1, 0, 'R');
foreach ($rentang_labels as $label) {
    $pdf->Cell($col_widths_umur[2], 6, number_format($total_per_rentang[$label]), 1, 0, 'C');
}
$pdf->Cell($col_widths_umur[count($col_widths_umur) - 1], 6, number_format($grand_total_umur), 1, 0, 'C');
$pdf->Ln(10);


// Footer dengan TTD sederhana
$pdf->Ln(10);

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



// **TABEL 3: Klasifikasi Pekerjaan**
$pdf->AddPage();
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 8, '3. Klasifikasi Berdasarkan Pekerjaan', 0, 1);
$pdf->SetFont('times', '', 9);

// Daftar pekerjaan
$daftar_pekerjaan = [
    'PNS',
    'TNI/POLRI',
    'SWASTA',
    'WIRAUSAHA',
    'PETANI',
    'NELAYAN',
    'BURUH',
    'PENSIUNAN',
    'TIDAK BEKERJA',
    'LAINNYA'
];

// Kelompokkan pekerjaan per dusun
$pekerjaan_data = [];
foreach ($data_penduduk as $penduduk) {
    $dusun = $penduduk['dusun'] ?? 'Tidak diketahui';
    $pekerjaan = $penduduk['PEKERJAAN'] ?? 'TIDAK BEKERJA';

    // Normalisasi pekerjaan
    $pekerjaan_normalized = 'LAINNYA';
    foreach ($daftar_pekerjaan as $p) {
        if (stripos($pekerjaan, $p) !== false || $pekerjaan == $p) {
            $pekerjaan_normalized = $p;
            break;
        }
    }

    $key = $dusun . '_' . $pekerjaan_normalized;

    if (!isset($pekerjaan_data[$key])) {
        $pekerjaan_data[$key] = [
            'dusun' => $dusun,
            'pekerjaan' => $pekerjaan_normalized,
            'jumlah' => 0
        ];
    }

    $pekerjaan_data[$key]['jumlah']++;
}

// Tabel header untuk pekerjaan
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('times', 'B', 9);

$col_widths_kerja = [8, 150, 100, 20];
$headers_kerja = ['No', 'Dusun', 'Pekerjaan', 'Jumlah'];

foreach ($headers_kerja as $i => $header) {
    $pdf->Cell($col_widths_kerja[$i], 7, $header, 1, 0, 'C', 1);
}
$pdf->Ln();

// Data rows pekerjaan
$pdf->SetFont('times', '', 8);
$no = 1;
$grand_total_pekerjaan = 0;
$current_dusun = '';
$subtotal_dusun = 0;

foreach ($pekerjaan_data as $data) {
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $pdf->SetFont('times', 'B', 9);
        foreach ($headers_kerja as $i => $header) {
            $pdf->Cell($col_widths_kerja[$i], 7, $header, 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('times', '', 8);
    }

    // Jika pindah dusun, tampilkan subtotal
    if ($current_dusun != $data['dusun'] && $current_dusun != '') {
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell($col_widths_kerja[0] + $col_widths_kerja[1] + $col_widths_kerja[2], 6, 'Subtotal Dusun ' . $current_dusun . ':', 1, 0, 'R');
        $pdf->Cell($col_widths_kerja[3], 6, number_format($subtotal_dusun), 1, 0, 'C');
        $pdf->Ln();
        $pdf->SetFont('times', '', 8);
        $subtotal_dusun = 0;
    }

    $current_dusun = $data['dusun'];
    $subtotal_dusun += $data['jumlah'];
    $grand_total_pekerjaan += $data['jumlah'];

    $pdf->Cell($col_widths_kerja[0], 6, $no++, 1, 0, 'C');
    $pdf->Cell($col_widths_kerja[1], 6, substr($data['dusun'], 0, 30), 1);
    $pdf->Cell($col_widths_kerja[2], 6, substr($data['pekerjaan'], 0, 30), 1);
    $pdf->Cell($col_widths_kerja[3], 6, number_format($data['jumlah']), 1, 0, 'C');
    $pdf->Ln();
}

// Subtotal dusun terakhir
if ($current_dusun != '') {
    $pdf->SetFont('times', 'B', 8);
    $pdf->Cell($col_widths_kerja[0] + $col_widths_kerja[1] + $col_widths_kerja[2], 6, 'Subtotal Dusun ' . $current_dusun . ':', 1, 0, 'R');
    $pdf->Cell($col_widths_kerja[3], 6, number_format($subtotal_dusun), 1, 0, 'C');
    $pdf->Ln();
}

// Total semua pekerjaan
$pdf->SetFont('times', 'B', 9);
$pdf->Cell(array_sum($col_widths_kerja) - $col_widths_kerja[3], 6, 'TOTAL SELURUH PEKERJAAN:', 1, 0, 'R');
$pdf->Cell($col_widths_kerja[3], 6, number_format($grand_total_pekerjaan), 1, 0, 'C');

// Footer dengan TTD sederhana
$pdf->Ln(10);

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


// **TABEL 4: Ringkasan Pekerjaan (Total per Kategori)**
$pdf->AddPage();
$pdf->SetFont('times', 'B', 12);
$pdf->Cell(0, 8, '4. Ringkasan Pekerjaan per Kategori', 0, 1);
$pdf->SetFont('times', '', 9);

// Hitung total per kategori pekerjaan
$total_per_kategori = array_fill_keys($daftar_pekerjaan, 0);
foreach ($pekerjaan_data as $data) {
    $total_per_kategori[$data['pekerjaan']] += $data['jumlah'];
}

// Tabel ringkasan dengan border yang lebih baik
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('times', 'B', 9);

$col_widths_ringkasan = [10, 215, 25, 25];
$headers_ringkasan = ['No', 'Kategori Pekerjaan', 'Jumlah', 'Persentase'];

// Header tabel dengan border lengkap
foreach ($headers_ringkasan as $i => $header) {
    $pdf->Cell($col_widths_ringkasan[$i], 8, $header, 1, 0, 'C', 1);
}
$pdf->Ln();

// Data ringkasan
$pdf->SetFont('times', '', 9);
$no = 1;
$total_ringkasan = 0;

// Urutkan data
arsort($total_per_kategori);

foreach ($total_per_kategori as $kategori => $jumlah) {
    // Cek page break
    if ($pdf->GetY() > 180) {
        $pdf->AddPage();
        $pdf->SetFont('times', 'B', 9);
        // Header untuk halaman baru
        foreach ($headers_ringkasan as $i => $header) {
            $pdf->Cell($col_widths_ringkasan[$i], 8, $header, 1, 0, 'C', 1);
        }
        $pdf->Ln();
        $pdf->SetFont('times', '', 9);
    }

    $total_ringkasan += $jumlah;
    $persentase = $grand_total_pekerjaan > 0 ? ($jumlah / $grand_total_pekerjaan) * 100 : 0;

    // Baris data dengan border
    $pdf->Cell($col_widths_ringkasan[0], 7, $no++, 1, 0, 'C');
    $pdf->Cell($col_widths_ringkasan[1], 7, $kategori, 1);
    $pdf->Cell($col_widths_ringkasan[2], 7, number_format($jumlah), 1, 0, 'C');
    $pdf->Cell($col_widths_ringkasan[3], 7, number_format($persentase, 1) . '%', 1, 0, 'C');
    $pdf->Ln();
}

// Baris total dengan style berbeda
$pdf->SetFont('times', 'B', 9);
$pdf->SetFillColor(220, 230, 240); // Warna biru muda untuk total

// Hitung lebar untuk kolom pertama dan kedua yang digabung
$combined_width = $col_widths_ringkasan[0] + $col_widths_ringkasan[1];

$pdf->Cell($combined_width, 8, 'TOTAL:', 1, 0, 'R', 1);
$pdf->Cell($col_widths_ringkasan[2], 8, number_format($total_ringkasan), 1, 0, 'C', 1);
$pdf->Cell($col_widths_ringkasan[3], 8, '100%', 1, 0, 'C', 1);
$pdf->Ln();

// Footer dengan TTD sederhana
$pdf->Ln(10);


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


// Output PDF
$filename = 'klasifikasi_penduduk_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'I');
exit;
