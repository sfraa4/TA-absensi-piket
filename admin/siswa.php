<?php
require_once 'header.php';

if (isset($_GET['delete'])) {
    $nisn = $_GET['delete'];
    $stmt = $pdo->prepare("SELECT foto_profil FROM siswa WHERE nisn = ?");
    $stmt->execute([$nisn]);
    $student = $stmt->fetch();
    if ($student && $student['foto_profil'] && file_exists("../uploads/profil/" . $student['foto_profil'])) {
        unlink("../uploads/profil/" . $student['foto_profil']);
    }

    $stmt = $pdo->prepare("DELETE FROM siswa WHERE nisn = ?");
    if($stmt->execute([$nisn])){
        $_SESSION['msg'] = "Data siswa berhasil dihapus!";
    }
    header("Location: siswa.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama'];
    $kelas = $_POST['kelas'];
    $hari_piket = $_POST['hari_piket'] ?? '';
    $rfid_uid = $_POST['rfid_uid'];
    $action = $_POST['action'];
    $old_nisn = $_POST['old_nisn'] ?? '';

    $foto_profil = null;
    $upload_dir = '../uploads/profil/';
    
    // Handle File Upload
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0) {
        $ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
        $foto_profil = $nisn . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_dir . $foto_profil);
    }

    try {
        if ($action == 'add') {
            $stmt = $pdo->prepare("INSERT INTO siswa (nisn, nama, kelas, hari_piket, rfid_uid, foto_profil) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nisn, $nama, $kelas, $hari_piket, $rfid_uid, $foto_profil]);
            $_SESSION['msg'] = "Siswa berhasil ditambahkan!";
        } elseif ($action == 'edit') {
            if ($foto_profil) {
                // Delete old photo
                $stmt = $pdo->prepare("SELECT foto_profil FROM siswa WHERE nisn = ?");
                $stmt->execute([$old_nisn]);
                $old = $stmt->fetch();
                if ($old && $old['foto_profil'] && file_exists($upload_dir . $old['foto_profil'])) {
                    unlink($upload_dir . $old['foto_profil']);
                }
                
                $stmt = $pdo->prepare("UPDATE siswa SET nisn=?, nama=?, kelas=?, hari_piket=?, rfid_uid=?, foto_profil=? WHERE nisn=?");
                $stmt->execute([$nisn, $nama, $kelas, $hari_piket, $rfid_uid, $foto_profil, $old_nisn]);
            } else {
                $stmt = $pdo->prepare("UPDATE siswa SET nisn=?, nama=?, kelas=?, hari_piket=?, rfid_uid=? WHERE nisn=?");
                $stmt->execute([$nisn, $nama, $kelas, $hari_piket, $rfid_uid, $old_nisn]);
            }
            $_SESSION['msg'] = "Siswa berhasil diupdate!";
        }
    } catch (PDOException $e) {
        $_SESSION['err'] = "Gagal menyimpan data: " . $e->getMessage();
    }
    header("Location: siswa.php");
    exit;
}

$students = $pdo->query("SELECT * FROM siswa ORDER BY nama ASC")->fetchAll();
?>

<?php if(isset($_SESSION['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['msg'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php unset($_SESSION['msg']); endif; ?>

<?php if(isset($_SESSION['err'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $_SESSION['err'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php unset($_SESSION['err']); endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold text-primary">Data Siswa</h5>
        <button class="btn btn-primary btn-sm" id="modalAdd" data-bs-toggle="modal" data-bs-target="#modalForm">
            <i class="bi bi-plus-lg"></i> Tambah Siswa
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped datatable">
                <thead class="table-light">
                        <th>NISN</th>
                        <th>Foto</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Hari Piket</th>
                        <th>RFID UID</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($students as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nisn']) ?></td>
                        <td>
                            <?php if($row['foto_profil']): ?>
                                <img src="../uploads/profil/<?= $row['foto_profil'] ?>" alt="Profil" class="rounded-circle object-fit-cover" width="40" height="40">
                            <?php else: ?>
                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px;"><i class="bi bi-person"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['kelas']) ?></td>
                        <td><?= htmlspecialchars($row['hari_piket']) ?></td>
                        <td><code><?= htmlspecialchars($row['rfid_uid']) ?></code></td>
                        <td>
                            <button class="btn btn-sm btn-warning text-white" onclick="editData('<?= $row['nisn'] ?>', '<?= addslashes($row['nama']) ?>', '<?= $row['kelas'] ?>', '<?= htmlspecialchars($row['hari_piket']) ?>', '<?= $row['rfid_uid'] ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="siswa.php?delete=<?= $row['nisn'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus data ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalForm" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Form Siswa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="old_nisn" id="formOldNisn" value="">
                    
                    <div class="mb-3">
                        <label>NISN</label>
                        <input type="text" name="nisn" id="formNisn" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" id="formNama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Kelas</label>
                        <input type="text" name="kelas" id="formKelas" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Hari Piket</label>
                        <select name="hari_piket" id="formHariPiket" class="form-select" required>
                            <option value="">Pilih Hari</option>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>RFID UID</label>
                        <div class="input-group">
                            <input type="text" name="rfid_uid" id="formRfid" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="scanRfid()">
                                <i class="bi bi-upc-scan"></i> Scan
                            </button>
                        </div>
                        <small class="text-muted">Fokuskan pada input dan tap kartu untuk mengisi UID otomatis.</small>
                    </div>
                    <div class="mb-3">
                        <label>Foto Acuan (Wajah)</label>
                        <input type="file" name="foto_profil" id="formFoto" class="form-control" accept="image/*">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah foto (saat edit).</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editData(nisn, nama, kelas, hari_piket, rfid) {
    document.getElementById('modalTitle').innerText = 'Edit Data Siswa';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formOldNisn').value = nisn;
    
    document.getElementById('formNisn').value = nisn;
    document.getElementById('formNama').value = nama;
    document.getElementById('formKelas').value = kelas;
    document.getElementById('formHariPiket').value = hari_piket;
    document.getElementById('formRfid').value = rfid;
    
    var modal = new bootstrap.Modal(document.getElementById('modalForm'));
    modal.show();
}

document.getElementById('modalAdd')?.addEventListener('click', function() {
    document.getElementById('modalTitle').innerText = 'Tambah Data Siswa';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formOldNisn').value = '';
    
    document.getElementById('formNisn').value = '';
    document.getElementById('formNama').value = '';
    document.getElementById('formKelas').value = '';
    document.getElementById('formHariPiket').value = '';
    document.getElementById('formRfid').value = '';
    
    var modal = new bootstrap.Modal(document.getElementById('modalForm'));
    modal.show();
});

function scanRfid() {
    let inputRfid = document.getElementById('formRfid');
    inputRfid.value = '';
    inputRfid.focus();
    inputRfid.placeholder = 'Silakan tap kartu...';
}
</script>

<?php require_once 'footer.php'; ?>
