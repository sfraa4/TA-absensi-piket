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
    
    // Capture photo
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/jpeg', 0.8);
    
    try {
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
                title: 'Berhasil!',
                text: 'Absensi telah dicatat.',
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
    keepFocus();
}

// Setup Event Listeners
document.addEventListener('click', keepFocus);
window.addEventListener('blur', keepFocus);
window.addEventListener('focus', keepFocus);

// Init
canvas.width = 400; // default capture size
canvas.height = 300;
initCamera();
keepFocus();
