<?php
require_once 'header.php';

// Filter month
$filter_month = $_GET['month'] ?? date('Y-m');

$stmt = $pdo->prepare("
    SELECT a.*, s.nama, s.kelas 
    FROM absensi a 
    JOIN siswa s ON a.nisn = s.nisn 
    WHERE DATE_FORMAT(a.tanggal, '%Y-%m') = ?
    ORDER BY a.tanggal DESC, a.jam DESC
");
$stmt->execute([$filter_month]);
$attendances = $stmt->fetchAll();
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center py-3">
        <h5 class="mb-2 mb-md-0 fw-bold text-primary">Rekap Absensi</h5>
        <form class="d-flex align-items-center" method="GET">
            <label class="me-2 fw-semibold text-muted mb-0">Bulan:</label>
            <input type="month" name="month" class="form-control form-control-sm" value="<?= $filter_month ?>" onchange="this.form.submit()">
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped datatable align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NISN</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Hari</th>
                        <th>Tanggal</th>
                        <th>Jam Tapping</th>
                        <th>Bukti Foto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($attendances as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nisn']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['kelas']) ?></td>
                        <td><?= htmlspecialchars($row['hari']) ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                        <td><span class="badge bg-success"><?= $row['jam'] ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-info text-white" onclick="showPhoto('../<?= $row['foto'] ?>', '<?= htmlspecialchars($row['nama']) ?>')">
                                <i class="bi bi-image"></i> Lihat Foto
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Foto -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Bukti Foto: <span id="photoModalName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="photoModalImg" src="" class="img-fluid w-100 rounded-bottom">
            </div>
        </div>
    </div>
</div>

<script>
function showPhoto(src, nama) {
    document.getElementById('photoModalName').textContent = nama;
    document.getElementById('photoModalImg').src = src;
    var modal = new bootstrap.Modal(document.getElementById('photoModal'));
    modal.show();
}
</script>

<?php require_once 'footer.php'; ?>
