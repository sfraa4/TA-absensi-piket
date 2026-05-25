<?php
require_once 'header.php';

if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $stmt = $pdo->prepare("SELECT foto FROM absensi WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if ($data) {

        if (file_exists("../" . $data['foto'])) {
            unlink("../" . $data['foto']);
        }

        $delete = $pdo->prepare("DELETE FROM absensi WHERE id = ?");
        $delete->execute([$id]);
    }

    header("Location: rekap.php");
    exit;
}

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
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center py-3">
        <h5 class="mb-2 mb-md-0 fw-bold text-primary">Rekap Absensi</h5>
        <form class="d-flex align-items-center gap-2" method="GET" id="filterForm">
            <select name="piket" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Semua Piket</option>
                <option value="Senin" <?= $filter_piket == 'Senin' ? 'selected' : '' ?>>Senin</option>
                <option value="Selasa" <?= $filter_piket == 'Selasa' ? 'selected' : '' ?>>Selasa</option>
                <option value="Rabu" <?= $filter_piket == 'Rabu' ? 'selected' : '' ?>>Rabu</option>
                <option value="Kamis" <?= $filter_piket == 'Kamis' ? 'selected' : '' ?>>Kamis</option>
                <option value="Jumat" <?= $filter_piket == 'Jumat' ? 'selected' : '' ?>>Jumat</option>
            </select>
            <select name="filter" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="today" <?= $filter == 'today' ? 'selected' : '' ?>>Hari Ini (24 Jam)</option>
                <option value="week" <?= $filter == 'week' ? 'selected' : '' ?>>Minggu Ini</option>
                <?php
                for ($i = 0; $i < 6; $i++) {
                    $time = mktime(0, 0, 0, date('n') - $i, 1, date('Y'));
                    $month_val = date('Y-m', $time);
                    $month_name = date('F Y', $time);
                    
                    $bulan_inggris = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    $bulan_indo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    $month_name = str_replace($bulan_inggris, $bulan_indo, $month_name);
                    
                    if ($i == 0) {
                        $label = "Bulan Ini ($month_name)";
                        $val = 'month';
                        $selected = ($filter == 'month') ? 'selected' : '';
                    } else {
                        $label = $month_name;
                        $val = $month_val;
                        $selected = ($filter == $month_val) ? 'selected' : '';
                    }
                    echo "<option value=\"$val\" $selected>$label</option>";
                }
                ?>
                <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>Semua Waktu</option>
            </select>
            <a href="export.php?filter=<?= $filter ?>&piket=<?= $filter_piket ?>" class="btn btn-sm btn-outline-primary text-nowrap" onclick="return confirm('PENTING: Export Manual ini akan MENGHAPUS foto fisik yang di-export dari server. Lanjutkan?')">
                <i class="bi bi-file-earmark-arrow-down"></i> Export Manual
            </a>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-striped datatable align-middle" id="rekapTable">
                <thead class="table-light">
                    <tr>
                        <th>NISN</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Hari Piket</th>
                        <th>Tanggal Tapping</th>
                        <th>Jam Tapping</th>
                        <th>Bukti Foto</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($attendances as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nisn']) ?></td>
                        <td><?= htmlspecialchars($row['nama']) ?></td>
                        <td><?= htmlspecialchars($row['kelas']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['hari_piket'] ?: '-') ?></span></td>
                        <td><?= substr($row['hari'], 0, 3) ?>, <?= date('d/m/y', strtotime($row['tanggal'])) ?></td>
                        <td><span class="badge bg-success"><?= substr($row['jam'], 0, 5) ?></span></td>
                        <td>
                            <?php if ($row['foto'] == 'archived'): ?>
                                <span class="badge bg-secondary"><i class="bi bi-archive"></i> Diarsipkan ke Excel</span>
                            <?php else: ?>
                                <button class="btn btn-sm btn-info text-white" onclick="showPhoto('../<?= $row['foto'] ?>', '<?= htmlspecialchars($row['nama']) ?>')">
                                    <i class="bi bi-image"></i> Lihat Foto
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="rekap.php?delete=<?= $row['id'] ?>" 
                            class="btn btn-sm btn-danger"
                            onclick="return confirm('Yakin hapus data ini?')">
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

setInterval(function() {
    if (!document.getElementById('photoModal').classList.contains('show')) {
        let fetchUrl = new URL(window.location.href);
        fetchUrl.searchParams.set('_t', new Date().getTime());
        
        fetch(fetchUrl.toString())
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('#rekapTable');
                
                if (newTable && window.$) {
                    let dt = $('.datatable').DataTable();
                    let currentPage = dt.page();
                    
                    dt.destroy();
                    document.querySelector('#rekapTable').innerHTML = newTable.innerHTML;
                    dt = $('.datatable').DataTable();
                    dt.page(currentPage).draw('page');
                }
            })
            .catch(err => console.error('Error fetching data:', err));
    }
}, 3000);
</script>

<?php require_once 'footer.php'; ?>
