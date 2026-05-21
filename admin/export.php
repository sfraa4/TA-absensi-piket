<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require '../vendor/autoload.php';
require_once '../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// 1. Ambil Parameter Filter
$filter = $_GET['filter'] ?? 'today';
$filter_piket = $_GET['piket'] ?? '';

$conditions = [];

if ($filter == 'today') {
    $conditions[] = "DATE(a.tanggal) = CURDATE()";
} elseif ($filter == 'week') {
    $conditions[] = "YEARWEEK(a.tanggal, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter == 'month') {
    $conditions[] = "MONTH(a.tanggal) = MONTH(CURDATE()) AND YEAR(a.tanggal) = YEAR(CURDATE())";
} elseif (preg_match('/^\d{4}-\d{2}$/', $filter)) {
    $conditions[] = "DATE_FORMAT(a.tanggal, '%Y-%m') = " . $pdo->quote($filter);
}

if ($filter_piket != '') {
    $conditions[] = "s.hari_piket = " . $pdo->quote($filter_piket);
}

$where_clause = "";
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

// 2. Tarik Data dari Database
$stmt = $pdo->prepare("
    SELECT a.*, s.nama, s.kelas, s.hari_piket 
    FROM absensi a 
    JOIN siswa s ON a.nisn = s.nisn 
    $where_clause
    ORDER BY a.tanggal DESC, a.jam DESC
");
$stmt->execute();
$attendances = $stmt->fetchAll();

// 3. Inisialisasi Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Absensi');

// Set Header Table
$headers = ['No', 'NISN', 'Nama Siswa', 'Kelas', 'Hari Piket', 'Tanggal', 'Jam', 'Status', 'Bukti Foto'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

// Styling Header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['argb' => 'FF0D6EFD']
    ]
];
$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(30);

// Lebar Kolom
$sheet->getColumnDimension('A')->setWidth(5);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(10);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(20);
$sheet->getColumnDimension('G')->setWidth(15);
$sheet->getColumnDimension('H')->setWidth(15);
$sheet->getColumnDimension('I')->setWidth(20); // Kolom Foto

$rowNum = 2;
$no = 1;

$archived_ids = [];

foreach ($attendances as $row) {
    $sheet->setCellValue('A' . $rowNum, $no);
    $sheet->setCellValue('B' . $rowNum, $row['nisn']);
    $sheet->setCellValue('C' . $rowNum, $row['nama']);
    $sheet->setCellValue('D' . $rowNum, $row['kelas']);
    $sheet->setCellValue('E' . $rowNum, $row['hari_piket'] ?: '-');
    $sheet->setCellValue('F' . $rowNum, $row['hari'] . ', ' . date('d/m/Y', strtotime($row['tanggal'])));
    $sheet->setCellValue('G' . $rowNum, $row['jam']);
    
    // Default status
    $sheet->setCellValue('H' . $rowNum, 'Hadir');

    // Sisipkan Foto
    $fotoPath = '../' . $row['foto'];
    if ($row['foto'] != 'archived' && file_exists($fotoPath)) {
        $drawing = new Drawing();
        $drawing->setName('Foto');
        $drawing->setDescription('Bukti Absen');
        $drawing->setPath($fotoPath); // Path lokal
        
        // Atur posisi dan ukuran
        $drawing->setCoordinates('I' . $rowNum);
        $drawing->setHeight(80); // Tinggi gambar dalam pixels
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(10);
        $drawing->setWorksheet($sheet);
        
        $sheet->getRowDimension($rowNum)->setRowHeight(80); // Samakan tinggi baris
        
        // Tandai untuk dihapus dan diupdate db
        $archived_ids[] = $row['id'];
    } else {
        $sheet->setCellValue('I' . $rowNum, 'Tidak Ada / Diarsipkan');
        $sheet->getRowDimension($rowNum)->setRowHeight(25);
    }
    
    // Vertical align center untuk semua cell di baris ini
    $sheet->getStyle('A'.$rowNum.':I'.$rowNum)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('A'.$rowNum.':I'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A'.$rowNum.':I'.$rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $rowNum++;
    $no++;
}

// 4. Proses Hapus File Foto & Update DB
if (!empty($archived_ids)) {
    // A. Hapus file fisik (kita perlu ambil ulang lokasinya)
    $placeholders = str_repeat('?,', count($archived_ids) - 1) . '?';
    $stmtDel = $pdo->prepare("SELECT foto FROM absensi WHERE id IN ($placeholders)");
    $stmtDel->execute($archived_ids);
    $filesToDelete = $stmtDel->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($filesToDelete as $file) {
        if ($file != 'archived' && file_exists('../' . $file)) {
            unlink('../' . $file); // Hapus foto!
        }
    }
    
    // B. Update status di DB menjadi 'archived'
    $stmtUpdate = $pdo->prepare("UPDATE absensi SET foto = 'archived' WHERE id IN ($placeholders)");
    $stmtUpdate->execute($archived_ids);
}

// 5. Output Excel ke Browser (Download)
ob_end_clean(); // Bersihkan output buffer sebelumnya agar file tidak corrupt
$filename = 'Rekap_Absensi_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
