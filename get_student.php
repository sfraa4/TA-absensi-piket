<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rfid'])) {
    $rfid = $_POST['rfid'];

    $stmt = $pdo->prepare("SELECT nama FROM siswa WHERE rfid_uid = ?");
    $stmt->execute([$rfid]);
    $student = $stmt->fetch();

    if ($student) {
        echo json_encode([
            'status' => 'success',
            'nama' => $student['nama']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Kartu RFID tidak terdaftar!'
        ]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
