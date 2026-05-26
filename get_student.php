<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rfid'])) {
    $rfid = $_POST['rfid'];

    $stmt = $pdo->prepare("SELECT nama, foto_profil FROM siswa WHERE rfid_uid = ?");
    $stmt->execute([$rfid]);
    $student = $stmt->fetch();

    if ($student) {
        if (empty($student['foto_profil'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Siswa belum memiliki foto profil acuan!'
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'nama' => $student['nama'],
            'foto_profil' => $student['foto_profil']
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
