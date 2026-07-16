<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM ALUR_PEMBELAJARAN WHERE id_alur = ?');
$stmt->execute([$id]);
$alur = $stmt->fetch();

if (!$alur) {
    header('Location: kelola_alur.php');
    exit;
}

$pageTitle = 'Detail Alur';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$selected = $conn->prepare(
    'SELECT d.id_detail, a.judul_dokumen, a.kategori 
     FROM DETAIL_ALUR d 
     JOIN ARSIP_MATERI a ON a.id_arsip = d.id_arsip 
     WHERE d.id_alur = ?'
);
$selected->execute([$id]);
$selected = $selected->fetchAll();

$materi = $conn->query(
    "SELECT id_arsip, judul_dokumen, kategori 
     FROM ARSIP_MATERI 
     WHERE status = 'Published' 
     ORDER BY judul_dokumen"
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Detail Alur: <?= htmlspecialchars($alur['nama_alur']) ?></h1>
        <p class="text-muted mb-0">Tambahkan materi published ke alur ini.</p>
    </div>
    <a class="btn btn-outline-secondary" href="kelola_alur.php">Kembali</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Tambah Materi</h2>
                <form method="post" action="proses_alur.php?action=add_detail">
                    <input type="hidden" name="id_alur" value="<?= $id ?>">
                    <select class="form-select mb-3" name="id_arsip" required>
                        <option value="">Pilih materi published</option>
                        <?php foreach ($materi as $m): ?>
                            <option value="<?= $m['id_arsip'] ?>"><?= htmlspecialchars($m['judul_dokumen']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary">Tambahkan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Materi dalam Alur</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($selected as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($m['judul_dokumen']) ?>
                        <a class="btn btn-sm btn-outline-danger" href="proses_alur.php?action=remove_detail&id_detail=<?= $m['id_detail'] ?>">Hapus</a>
                    </li>
                <?php endforeach; ?>
                <?php if (!$selected): ?>
                    <li class="list-group-item text-muted">Belum ada materi.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>