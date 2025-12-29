<?php
// export_excel_phpspreadsheet.php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

include_once __DIR__ . '/../../config/config.php';

// Cek apakah user sudah login
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: {$base_url}auth/login.php");
    exit();
}

// Filter functionality (sama seperti di list.php)
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$dusun_filter = isset($_GET['dusun']) ? intval($_GET['dusun']) : 0;
$jk_filter = isset($_GET['jk']) ? $conn->real_escape_string($_GET['jk']) : '';

$where_conditions = [];

// Build WHERE conditions
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

// Get all data
$sql = "SELECT k.*, d.dusun as nama_dusun 
        FROM tabel_kependudukan k 
        LEFT JOIN tabel_dusun d ON k.DSN = d.id 
        $where 
        ORDER BY k.DSN, k.NAMA_LGKP";

$result = $conn->query($sql);

// Create new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set properties
$spreadsheet->getProperties()
    ->setCreator('Sistem Kependudukan Desa')
    ->setLastModifiedBy('Desa Kurniabakti')
    ->setTitle('Data Kependudukan')
    ->setSubject('Export Data Penduduk');

// Function untuk format tanggal Indonesia
function dateIndoExcel($date)
{
    if (empty($date)) return '';

    if (strpos($date, '-') !== false) {
        $pecah = explode('-', $date);
    } elseif (strpos($date, '/') !== false) {
        $pecah = explode('/', $date);
    } else {
        return $date;
    }

    if (count($pecah) != 3) return $date;

    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    return $pecah[2] . ' ' . $bulan[(int)$pecah[1]] . ' ' . $pecah[0];
}

// Function untuk hitung usia
function hitungUsiaExcel($tgl_lahir)
{
    if (empty($tgl_lahir)) return '';

    try {
        $tgl_lahir_dt = new DateTime($tgl_lahir);
        $today = new DateTime();
        $usia = $today->diff($tgl_lahir_dt)->y;
        return $usia . ' tahun';
    } catch (Exception $e) {
        return '';
    }
}

// Header utama
$sheet->mergeCells('A1:Z1');
$sheet->setCellValue('A1', 'DATA KEPENDUDUKAN');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A2:Z2');
$sheet->setCellValue('A2', 'Desa Kurniabakti, Kecamatan Cineam, Kabupaten Tasikmalaya');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->mergeCells('A3:Z3');
$sheet->setCellValue('A3', 'Telp: (0265) 123456 | Email: desakurniabakti@email.com');
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Informasi filter
$sheet->mergeCells('A5:Z5');
$filter_info = [];
if (!empty($search)) {
    $filter_info[] = "Pencarian: \"$search\"";
}

// Get dusun name
$dusun_nama = 'Semua Dusun';
if ($dusun_filter > 0) {
    $sql_dusun = "SELECT dusun FROM tabel_dusun WHERE id = $dusun_filter";
    $dusun_result = $conn->query($sql_dusun);
    if ($dusun_result && $dusun_row = $dusun_result->fetch_assoc()) {
        $dusun_nama = $dusun_row['dusun'];
    }
}

$filter_info[] = "Dusun: $dusun_nama";

if (!empty($jk_filter)) {
    $jk_text = ($jk_filter == 'L') ? 'Laki-laki' : 'Perempuan';
    $filter_info[] = "Jenis Kelamin: $jk_text";
} else {
    $filter_info[] = "Jenis Kelamin: Semua";
}

$sheet->setCellValue('A5', 'Filter: ' . implode(' | ', $filter_info));

$sheet->mergeCells('A6:Z6');
$sheet->setCellValue('A6', 'Tanggal Cetak: ' . date('d/m/Y H:i:s'));

// Get statistics
$total_records = $result->num_rows;
$total_laki = 0;
$total_perempuan = 0;

// Hitung statistik dan simpan data
$data_rows = [];
while ($row = $result->fetch_assoc()) {
    $data_rows[] = $row;
    if ($row['JK'] == 'L') {
        $total_laki++;
    } else {
        $total_perempuan++;
    }
}

$sheet->mergeCells('A7:Z7');
$sheet->setCellValue('A7', 'Total Data: ' . number_format($total_records) .
    ' | Laki-laki: ' . number_format($total_laki) .
    ' | Perempuan: ' . number_format($total_perempuan));

// Header tabel
$headers = [
    'No',
    'NIK',
    'No. KK',
    'Nama Lengkap',
    'Nama Panggilan',
    'Jenis Kelamin',
    'Usia',
    'Tempat Lahir',
    'Tanggal Lahir',
    'Agama',
    'Status Kawin',
    'Hub. Keluarga',
    'Pendidikan',
    'Pekerjaan',
    'Alamat',
    'RT',
    'RW',
    'Dusun',
    'Kecamatan',
    'Kelurahan',
    'Gol. Darah',
    'Kewarganegaraan',
    'Status Tinggal',
    'Disabilitas',
    'Jenis Disabilitas',
    'Tanggal Input'
];

$row_num = 9;
$col_num = 1;

// Set header style
foreach ($headers as $header) {
    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_num) . $row_num;
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
    $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_num))->setAutoSize(true);
    $col_num++;
}

// Data rows
$row_num = 10;
$no = 1;

foreach ($data_rows as $row) {
    $col_num = 1;

    $sheet->setCellValue([$col_num++, $row_num], $no++);
    $sheet->setCellValue([$col_num++, $row_num], $row['NIK'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['NO_KK'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['NAMA_LGKP'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['NAMA_PANGGILAN'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['JK'] == 'L' ? 'Laki-laki' : 'Perempuan');
    $sheet->setCellValue([$col_num++, $row_num], hitungUsiaExcel($row['TGL_LHR'] ?? ''));
    $sheet->setCellValue([$col_num++, $row_num], $row['TMPT_LHR'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], !empty($row['TGL_LHR']) ? dateIndoExcel($row['TGL_LHR']) : '');
    $sheet->setCellValue([$col_num++, $row_num], $row['AGAMA'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['STATUS_KAWIN'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['HBKEL'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['PENDIDIKAN'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['PEKERJAAN'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['ALAMAT'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['rt'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['rw'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['nama_dusun'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['KECAMATAN'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['KELURAHAN'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['GOL_DARAH'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['KEWARGANEGARAAN'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['STATUS_TINGGAL'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['DISABILITAS'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], $row['JENIS_DISABILITAS'] ?? '');
    $sheet->setCellValue([$col_num++, $row_num], !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '');

    // Set border untuk row
    for ($i = 1; $i <= 26; $i++) {
        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i) . $row_num;
        $sheet->getStyle($cell)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    $row_num++;
}

// Set wrap text untuk kolom tertentu
$sheet->getStyle('O10:O' . $row_num)->getAlignment()->setWrapText(true);

// Footer dengan TTD
$ttd_row = $row_num + 3;
$sheet->mergeCells('V' . $ttd_row . ':Z' . $ttd_row);
$sheet->setCellValue('V' . $ttd_row, 'Mengetahui,');
$sheet->getStyle('V' . $ttd_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$ttd_row++;
$sheet->mergeCells('V' . $ttd_row . ':Z' . ($ttd_row + 3));
$sheet->getStyle('V' . $ttd_row . ':Z' . ($ttd_row + 3))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

$ttd_row += 4;
$sheet->mergeCells('V' . $ttd_row . ':Z' . $ttd_row);
$sheet->setCellValue('V' . $ttd_row, 'KEPALA DESA KURNIABAKTI');
$sheet->getStyle('V' . $ttd_row)->getFont()->setBold(true);
$sheet->getStyle('V' . $ttd_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$ttd_row++;
$sheet->mergeCells('V' . $ttd_row . ':Z' . $ttd_row);
$sheet->setCellValue('V' . $ttd_row, 'NAMA KEPALA DESA');
$sheet->getStyle('V' . $ttd_row)->getFont()->setBold(true)->setUnderline(true);
$sheet->getStyle('V' . $ttd_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$ttd_row++;
$sheet->mergeCells('V' . $ttd_row . ':Z' . $ttd_row);
$sheet->setCellValue('V' . $ttd_row, 'NIP. 1234567890123456');
$sheet->getStyle('V' . $ttd_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set footer informasi
$footer_row = $row_num + 15;
$sheet->mergeCells('A' . $footer_row . ':Z' . $footer_row);
$sheet->setCellValue('A' . $footer_row, '--- Laporan ini dicetak secara otomatis dari Sistem Administrasi Desa Kurniabakti ---');
$sheet->getStyle('A' . $footer_row)->getFont()->setItalic(true);
$sheet->getStyle('A' . $footer_row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Output Excel file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="data_kependudukan_' . date('Ymd_His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
