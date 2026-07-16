<?php
session_start();
require_once '../config/koneksi.php';

if (($_SESSION['role'] ?? '') !== 'Super Admin') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Kelola Pengguna';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$users = $conn->query(
    'SELECT id_user, nama_lengkap, kelas, username, role 
     FROM PENGGUNA 
     ORDER BY nama_lengkap'
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Kelola Pengguna</h1>
        <p class="text-muted mb-0">Hanya Super Admin dapat mengubah role.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#create">Tambah Pengguna</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                        <td><?= htmlspecialchars($u['kelas']) ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['role']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#edit<?= $u['id_user'] ?>">Edit</button>
                            <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
                                <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus pengguna ini?')" href="proses_user.php?action=delete&id=<?= $u['id_user'] ?>">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($users as $u): ?>
    <div class="modal fade" id="edit<?= $u['id_user'] ?>">
        <div class="modal-dialog">
            <form class="modal-content" method="post" action="proses_user.php?action=edit">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                    <input class="form-control mb-2" name="nama_lengkap" value="<?= htmlspecialchars($u['nama_lengkap']) ?>" required>
                    <input class="form-control mb-2" name="kelas" value="<?= htmlspecialchars($u['kelas']) ?>" required>
                    <input class="form-control mb-2" name="username" value="<?= htmlspecialchars($u['username']) ?>" required>
                    <input class="form-control mb-2" name="password" type="password" placeholder="Password baru (opsional)">
                    <select class="form-select" name="role">
                        <?php foreach (['Anggota', 'Admin', 'Super Admin'] as $role): ?>
                            <option <?= $u['role'] === $role ? 'selected' : '' ?>><?= $role ?></option>
                        <?php endforeach; ?>
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
        <form class="modal-content" method="post" action="proses_user.php?action=create">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input class="form-control mb-2" name="nama_lengkap" placeholder="Nama lengkap" required>
                <input class="form-control mb-2" name="kelas" placeholder="Kelas" required>
                <input class="form-control mb-2" name="username" placeholder="Username" required>
                <input class="form-control mb-2" type="password" name="password" placeholder="Password" required>
                <select class="form-select" name="role">
                    <option>Anggota</option>
                    <option>Admin</option>
                    <option>Super Admin</option>
                </select>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>