<?php
require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$uid = $_POST['uid'] ?? '';
$image_data = $_POST['image'] ?? '';

if (empty($uid) || empty($image_data)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM siswa WHERE rfid_uid = ?");
$stmt->execute([$uid]);
$siswa = $stmt->fetch();

if (!$siswa) {
    echo json_encode(['status' => 'error', 'message' => 'Kartu tidak terdaftar!']);
    exit;
}

$image_parts = explode(";base64,", $image_data);
if (count($image_parts) != 2) {
    echo json_encode(['status' => 'error', 'message' => 'Format foto tidak valid.']);
    exit;
}
$image_type_aux = explode("image/", $image_parts[0]);
$image_type = $image_type_aux[1];
$image_base64 = base64_decode($image_parts[1]);

$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_name = $siswa['nisn'] . '_' . time() . '.' . $image_type;
$file_path = $upload_dir . $file_name;

if (file_put_contents($file_path, $image_base64)) {
    $hari_array = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $hari_inggris = date('l');
    $hari = $hari_array[$hari_inggris];
    $tanggal = date('Y-m-d');
    $jam = date('H:i:s');
    
    $stmt = $pdo->prepare("INSERT INTO absensi (nisn, hari, tanggal, jam, foto) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$siswa['nisn'], $hari, $tanggal, $jam, $file_path])) {
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'hari' => $hari,
                'tanggal' => date('d-m-Y', strtotime($tanggal)),
                'nama' => $siswa['nama'],
                'kelas' => $siswa['kelas'],
                'jam' => $jam
            ]
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Absensi gagal.']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan foto.']);
    exit;
}
?>
