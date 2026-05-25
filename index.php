<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Piket Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-pattern d-flex flex-column vh-100">

    <nav class="navbar navbar-expand-lg glass-navbar mb-4">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-gradient" href="#">beres.in</a>
            <div class="d-flex">
                <a href="admin/login.php" class="btn border-0 rounded-pill px-4">
                    <i class="bi bi-box-arrow-in-right me-2"></i>LogIn
                </a>
            </div>
        </div>
    </nav>


    <div class="container flex-grow-1 d-flex align-items-center">
        <div class="row w-100 justify-content-center align-items-center gap-4 gap-lg-5">
            
            <div class="col-lg-5">
                <div class="glass-card p-4 text-center h-100 d-flex flex-column justify-content-center align-items-center relative">
                    <h6 class="mb-3 text-light-50">pastikan wajah terlihat jelas</h6>
                    <div class="video-container position-relative">
                        <video id="video" width="100%" height="100%" autoplay playsinline class="rounded-4 shadow-lg border border-secondary border-opacity-25"></video>
                        <canvas id="canvas" class="d-none"></canvas>
                        <div class="scanner-overlay">
                            <div class="scanner-line"></div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-lg-5">
                <div class="mb-3 text-center">
                    <div class="d-inline-block px-4 py-2 rounded-pill shadow-sm border border-secondary border-opacity-25 glass-card">
                        <i class="bi bi-calendar-event text-primary me-2"></i>
                        <span id="realtime_date" class="fw-medium text-light me-3" style="font-size: 0.9rem;">Memuat...</span>
                        <i class="bi bi-clock text-primary me-2"></i>
                        <span id="realtime_clock" class="fw-bold text-light" style="font-size: 0.9rem;">Memuat...</span>
                    </div>
                </div>
                <div class="glass-card p-5 h-100 d-flex flex-column justify-content-center position-relative overflow-hidden text-center" id="data_container">
                    <div class="decor-circle circle-1"></div>
                    <div class="decor-circle circle-2"></div>
                    
                    <div class="content-wrapper position-relative z-1" id="idle_state">
                        <div class="display-1 text-primary mb-3"></div>
                        <h2 class="fw-bold text-light mb-3">tempelkan kartu anda</h2>
                        <div class="spinner-grow text-primary spinner-grow-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <div class="content-wrapper position-relative z-1 d-none" id="success_state">
                        <h3 class="fw-bold text-success mb-4"><i class="bi bi-check-circle-fill me-2"></i>Absensi Berhasil</h3>
                        <div class="text-start fs-10">
                            <p class="mb-2"><span class="text-light-50">Hari:</span> <strong id="res_hari" class="text-light"></strong></p>
                            <p class="mb-2"><span class="text-light-50">Tanggal:</span> <strong id="res_tanggal" class="text-light"></strong></p>
                            <p class="mb-2"><span class="text-light-50">Nama:</span> <strong id="res_nama" class="text-light"></strong></p>
                            <p class="mb-2"><span class="text-light-50">Kelas:</span> <strong id="res_kelas" class="text-light"></strong></p>
                            <p class="mb-0"><span class="text-light-50">Jam:</span> <strong id="res_jam" class="text-light"></strong></p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <input type="password" id="rfid_input" class="visually-hidden" autocomplete="off" autofocus>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/face-api.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
