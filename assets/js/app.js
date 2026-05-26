const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const context = canvas.getContext('2d');
const rfidInput = document.getElementById('rfid_input');

const idleState = document.getElementById('idle_state');
const successState = document.getElementById('success_state');

const resHari = document.getElementById('res_hari');
const resTanggal = document.getElementById('res_tanggal');
const resNama = document.getElementById('res_nama');
const resKelas = document.getElementById('res_kelas');
const resJam = document.getElementById('res_jam');

let isProcessing = false;
let modelsLoaded = false;

// Load Face API Models
Promise.all([
    faceapi.nets.ssdMobilenetv1.loadFromUri('assets/models'),
    faceapi.nets.faceLandmark68Net.loadFromUri('assets/models'),
    faceapi.nets.faceRecognitionNet.loadFromUri('assets/models')
]).then(() => {
    modelsLoaded = true;
    console.log("Face API Models loaded.");
}).catch(err => {
    console.error("Error loading models:", err);
});

// Initialize Webcam
async function initCamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
    } catch (err) {
        console.error("Error accessing webcam: ", err);
        Swal.fire({
            icon: 'error',
            title: 'Kamera Tidak Ditemukan',
            text: 'Harap pastikan webcam terhubung dan diizinkan.',
            background: '#1e293b',
            color: '#f8fafc'
        });
    }
}

// Keep focus on RFID input
function keepFocus() {
    if (!isProcessing) {
        rfidInput.focus();
    }
}

// Handle RFID Input
rfidInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        
        if (isProcessing) return;
        
        const uid = rfidInput.value.trim();
        
        if (uid.length > 0) {
            processAttendance(uid);
        }
    }
});

// Process Attendance via AJAX (Fetch API)
async function processAttendance(uid) {
    isProcessing = true;
    
    // Tampilkan loading cepat
    document.querySelector('#idle_state h2').textContent = 'Memproses...';
    document.querySelector('#idle_state .spinner-grow').classList.remove('d-none');
        
    try {
        // Check if camera is covered (brightness & variance check)
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageDataCheck = context.getImageData(0, 0, canvas.width, canvas.height);
        const dataCheck = imageDataCheck.data;
        
        let sum = 0;
        let sumSq = 0;
        let count = 0;
        
        // Sample pixels to calculate average brightness and variance
        for (let i = 0; i < dataCheck.length; i += 16) {
            let r = dataCheck[i];
            let g = dataCheck[i+1];
            let b = dataCheck[i+2];
            let brightness = (r + g + b) / 3;
            sum += brightness;
            sumSq += brightness * brightness;
            count++;
        }
        
        const avgBrightness = sum / count;
        const variance = (sumSq / count) - (avgBrightness * avgBrightness);
        console.log("Average brightness:", avgBrightness, "Variance:", variance);
        
        // Camera is considered covered if it's very dark OR extremely uniform
        if (avgBrightness < 30 || variance < 100) { 
            Swal.fire({
                icon: 'error',
                title: 'Kamera Tertutup!',
                text: 'Harap jangan menutup kamera saat melakukan absensi.',
                timer: 3000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });
            resetUI();
            return;
        }

        if (!modelsLoaded) {
            Swal.fire({
                icon: 'warning',
                title: 'Harap Tunggu',
                text: 'Sistem sedang memuat modul AI...',
                timer: 2000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });
            resetUI();
            return;
        }

        // Fetch Student Data to get reference photo
        const stdFormData = new FormData();
        stdFormData.append('rfid', uid);
        const stdRes = await fetch('get_student.php', { method: 'POST', body: stdFormData });
        const stdData = await stdRes.json();
        
        if (stdData.status !== 'success') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: stdData.message || 'Kartu tidak terdaftar!',
                timer: 3000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });
            resetUI();
            return;
        }

        document.querySelector('#idle_state h2').textContent = 'Mencocokkan Wajah...';

        // Load reference image
        const refImg = new Image();
        refImg.crossOrigin = 'anonymous';
        refImg.src = 'uploads/profil/' + stdData.foto_profil;
        
        await new Promise((resolve, reject) => {
            refImg.onload = resolve;
            refImg.onerror = () => reject(new Error("Gagal memuat foto acuan"));
        });

        // Detect face in reference image
        const refDetection = await faceapi.detectSingleFace(refImg, new faceapi.SsdMobilenetv1Options()).withFaceLandmarks().withFaceDescriptor();
        if (!refDetection) {
            Swal.fire({
                icon: 'error',
                title: 'Foto Acuan Buruk',
                text: 'Wajah pada foto profil acuan tidak terdeteksi oleh AI. Harap admin mengganti foto tersebut.',
                timer: 4000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });
            resetUI();
            return;
        }

        // Detect faces in webcam
        const allWebcamDetections = await faceapi.detectAllFaces(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.15 })).withFaceLandmarks().withFaceDescriptors();
        console.log("Jumlah wajah terdeteksi:", allWebcamDetections.length);
        
        if (allWebcamDetections.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Wajah Tidak Terdeteksi',
                text: 'Pastikan wajah Anda menghadap lurus ke kamera dan tidak terhalang.',
                timer: 3000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });
            resetUI();
            return;
        }

        if (allWebcamDetections.length > 1) {
            Swal.fire({
                icon: 'error',
                title: 'Terlalu Banyak Wajah',
                text: 'Harap hanya ada 1 orang di depan kamera saat absen!',
                timer: 4000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });
            resetUI();
            return;
        }

        const webcamDetection = allWebcamDetections[0];

        // Compare face descriptors
        const distance = faceapi.euclideanDistance(refDetection.descriptor, webcamDetection.descriptor);
        console.log("Face Match Distance:", distance);
        
        if (typeof distance !== 'number' || isNaN(distance) || distance > 0.42) {
            Swal.fire({
                icon: 'error',
                title: 'Wajah Tidak Cocok',
                text: 'Jarak kemiripan: ' + (isNaN(distance) ? 'Error/NaN' : distance.toFixed(2)) + ' (Batas: 0.42)',
                timer: 4000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });
            resetUI();
            return;
        }

        document.querySelector('#idle_state h2').textContent = 'Menyimpan Absensi...';

        // Capture photo for saving
        context.save();
        context.scale(-1, 1);
        context.drawImage(video, -canvas.width, 0, canvas.width, canvas.height);
        const imageData = canvas.toDataURL('image/jpeg', 0.8);
        context.restore(); // Restore context to default state
        
        const formData = new FormData();
        formData.append('uid', uid);
        formData.append('image', imageData);

        const response = await fetch('process.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            resHari.textContent = result.data.hari;
            resTanggal.textContent = result.data.tanggal;
            resNama.textContent = result.data.nama;
            resKelas.textContent = result.data.kelas;
            resJam.textContent = result.data.jam;

            idleState.classList.add('d-none');
            successState.classList.remove('d-none');

            Swal.fire({
                icon: 'success',
                title: 'Absensi Berhasil!',
                text: 'Terima kasih, ' + result.data.nama,
                timer: 2000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });

            setTimeout(() => {
                resetUI();
            }, 3000);

        } else {
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: result.message || 'Kartu tidak terdaftar!',
                timer: 3000,
                showConfirmButton: false,
                background: '#1e293b',
                color: '#f8fafc'
            });
            resetUI();
        }

    } catch (error) {
        console.error("Error submitting attendance:", error);
        Swal.fire({
            icon: 'error',
            title: 'Kesalahan Sistem',
            text: 'Gagal terhubung dengan server.',
            timer: 3000,
            showConfirmButton: false,
            background: '#1e293b',
            color: '#f8fafc'
        });
        resetUI();
    }
}

function resetUI() {
    rfidInput.value = '';
    isProcessing = false;
    successState.classList.add('d-none');
    idleState.classList.remove('d-none');
    document.querySelector('#idle_state h2').textContent = 'tempelkan kartu anda';
    document.querySelector('#idle_state .spinner-grow').classList.remove('d-none');
    keepFocus();
}

document.addEventListener('click', keepFocus);
window.addEventListener('blur', keepFocus);
window.addEventListener('focus', keepFocus);

canvas.width = 400;
canvas.height = 300;
initCamera();
keepFocus();

function updateRealTimeClock() {
    const now = new Date();
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    const dayName = days[now.getDay()];
    const day = now.getDate();
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    
    document.getElementById('realtime_date').textContent = `${dayName}, ${day} ${month} ${year}`;
    document.getElementById('realtime_clock').textContent = `${hours}:${minutes}:${seconds} WIB`;
}

updateRealTimeClock();
setInterval(updateRealTimeClock, 1000);
