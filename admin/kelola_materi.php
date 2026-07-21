<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Kelola Materi';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$materi = $conn->query(
    "SELECT am.*, p.nama_lengkap pengunggah 
     FROM ARSIP_MATERI am 
     JOIN PENGGUNA p ON p.id_user = am.id_user 
     ORDER BY am.status = 'Pending' DESC, am.id_arsip DESC"
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Kelola Arsip Materi</h1>
        <p class="text-muted mb-0">Super Admin dan Admin dapat mengunggah materi.</p>
    </div>
    <div>
        <a class="btn btn-primary" href="tambah_materi.php">
            <i class="bi bi-upload me-1"></i>Tambah Materi
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">No</th>
                    <th>Judul</th>
                    <th>Pengunggah</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th class="text-center pe-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materi as $i => $m): ?>
                    <tr>
                        <td class="ps-4"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($m['judul_dokumen']) ?></td>
                        <td><?= htmlspecialchars($m['pengunggah']) ?></td>
                        <td><?= htmlspecialchars($m['kategori']) ?></td>
                        <td>
                            <span class="badge text-bg-<?= $m['status'] === 'Published' ? 'success' : ($m['status'] === 'Pending' ? 'warning' : 'danger') ?>">
                                <?= htmlspecialchars($m['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($m['tgl_unggah']) ?></td>
                        <td class="text-center pe-4">
                            <?php if ($m['status'] === 'Pending' && $_SESSION['role'] === 'Super Admin'): ?>
                                <a class="btn btn-sm btn-outline-success" onclick="return confirm('Approve materi ini?')" href="proses_materi.php?action=approve&id=<?= $m['id_arsip'] ?>">Approve</a>
                                <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject materi ini?')" href="proses_materi.php?action=reject&id=<?= $m['id_arsip'] ?>">Reject</a>
                            <?php elseif ($m['status'] === 'Pending'): ?>
                                <span class="text-muted small">Menunggu Super Admin</span>
                            <?php else: ?>
                                <a class="btn btn-sm btn-outline-warning" href="edit_materi.php?id=<?= $m['id_arsip'] ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
                                <a class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus materi ini?')" href="proses_materi.php?action=delete&id=<?= $m['id_arsip'] ?>">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>


                <?php endforeach; ?>

                <?php if (!$materi): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">Belum ada arsip materi.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
