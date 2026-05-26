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

$stmt = $pdo->prepare("
    SELECT a.*, s.nama, s.kelas, s.hari_piket 
    FROM absensi a 
    JOIN siswa s ON a.nisn = s.nisn 
    $where_clause
    ORDER BY a.tanggal DESC, a.jam DESC
");
$stmt->execute();
$attendances = $stmt->fetchAll();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Rekap Absensi');

$headers = ['NISN', 'Nama Siswa', 'Kelas', 'Hari Piket', 'Tanggal Tapping', 'Jam Tapping', 'Bukti Foto'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

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
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(30);

$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(10);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(20); // Kolom Foto

$rowNum = 2;

$archived_ids = [];

foreach ($attendances as $row) {
    $sheet->setCellValue('A' . $rowNum, $row['nisn']);
    $sheet->setCellValue('B' . $rowNum, $row['nama']);
    $sheet->setCellValue('C' . $rowNum, $row['kelas']);
    $sheet->setCellValue('D' . $rowNum, $row['hari_piket'] ?: '-');
    $sheet->setCellValue('E' . $rowNum, substr($row['hari'], 0, 3) . ', ' . date('d/m/y', strtotime($row['tanggal'])));
    $sheet->setCellValue('F' . $rowNum, substr($row['jam'], 0, 5));

    $fotoPath = realpath(__DIR__ . '/../' . ltrim($row['foto'], '/'));
    if ($row['foto'] != 'archived' && $fotoPath && file_exists($fotoPath)) {
        $drawing = new Drawing();
        $drawing->setName('Foto');
        $drawing->setDescription('Bukti Absen');
        $drawing->setPath($fotoPath);
        
        $drawing->setCoordinates('G' . $rowNum);
        $drawing->setHeight(80); 
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(10);
        $drawing->setWorksheet($sheet);
        
        $sheet->getRowDimension($rowNum)->setRowHeight(80); 
        
        $archived_ids[] = $row['id'];
    } else {
        $sheet->setCellValue('G' . $rowNum, 'Tidak Ada / Diarsipkan');
        $sheet->getRowDimension($rowNum)->setRowHeight(25);
    }
    
    $sheet->getStyle('A'.$rowNum.':G'.$rowNum)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getStyle('A'.$rowNum.':G'.$rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A'.$rowNum.':G'.$rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    $rowNum++;
}

// (deletion logic moved below)

ob_end_clean();
$filename = 'Rekap_Absensi_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

if (!empty($archived_ids)) {
    $placeholders = str_repeat('?,', count($archived_ids) - 1) . '?';
    $stmtDel = $pdo->prepare("SELECT foto FROM absensi WHERE id IN ($placeholders)");
    $stmtDel->execute($archived_ids);
    $filesToDelete = $stmtDel->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($filesToDelete as $file) {
        if ($file != 'archived' && file_exists('../' . $file)) {
            unlink('../' . $file); 
        }
    }
    
    $stmtUpdate = $pdo->prepare("UPDATE absensi SET foto = 'archived' WHERE id IN ($placeholders)");
    $stmtUpdate->execute($archived_ids);
}

exit;
?>
