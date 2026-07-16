<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Kelola Alur';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$data = $conn->query(
    'SELECT a.*, p.nama_lengkap pembuat 
     FROM ALUR_PEMBELAJARAN a 
     JOIN PENGGUNA p ON p.id_user = a.id_user 
     ORDER BY a.id_alur DESC'
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Kelola Alur Belajar</h1>
        <p class="text-muted mb-0">Susun roadmap dan materi pembelajaran.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create">Tambah Alur</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama Alur</th>
                    <th>Level</th>
                    <th>Status</th>
                    <th>Pembuat</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $i => $a): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($a['nama_alur']) ?></td>
                        <td><?= htmlspecialchars($a['tingkat_level']) ?></td>
                        <td><?= htmlspecialchars($a['status']) ?></td>
                        <td><?= htmlspecialchars($a['pembuat']) ?></td>
                        <td><?= htmlspecialchars($a['tgl_dibuat']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="detail_alur.php?id=<?= $a['id_alur'] ?>">Atur Detail</a>
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#edit<?= $a['id_alur'] ?>">Edit</button>
                            <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus alur ini?')" href="proses_alur.php?action=delete&id=<?= $a['id_alur'] ?>">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$data): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">Belum ada alur.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($data as $a): ?>
    <div class="modal fade" id="edit<?= $a['id_alur'] ?>">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="proses_alur.php?action=edit">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Alur</h5>
                    <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_alur" value="<?= $a['id_alur'] ?>">
                    <input class="form-control mb-2" name="nama_alur" value="<?= htmlspecialchars($a['nama_alur']) ?>" required>
                    <input class="form-control mb-2" name="tingkat_level" value="<?= htmlspecialchars($a['tingkat_level']) ?>">
                    <select class="form-select" name="status">
                        <option <?= $a['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
                        <option <?= $a['status'] === 'Published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="create">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="proses_alur.php?action=create">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Alur</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input class="form-control mb-2" name="nama_alur" placeholder="Nama alur" required>
                <input class="form-control mb-2" name="tingkat_level" placeholder="Tingkat level">
                <select class="form-select" name="status">
                    <option>Draft</option>
                    <option>Published</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>