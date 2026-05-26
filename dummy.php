<?php
require 'config/database.php';

$students = [
    ['1234567890', 'Budi Santoso', '10 IPA 1', '12345678'],
    ['0987654321', 'Siti Aminah', '10 IPA 1', '87654321'],
    ['1122334455', 'Andi Wijaya', '10 IPS 2', '11223344']
];

foreach ($students as $s) {
    $stmt = $pdo->prepare("INSERT INTO students (nisn, nama, kelas, rfid_uid) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nama=VALUES(nama)");
    $stmt->execute($s);
}

$today = date('Y-m-d');
$time = date('H:i:s', strtotime('-1 hour'));
$stmt = $pdo->prepare("INSERT INTO attendances (nisn, tanggal, jam, foto) VALUES (?, ?, ?, ?)");
$stmt->execute(['1234567890', $today, $time, 'uploads/attendances/dummy.jpg']);

if (!file_exists('uploads/attendances')) {
    mkdir('uploads/attendances', 0777, true);
}
file_put_contents('uploads/attendances/dummy.jpg', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

echo "Dummy data inserted!";
