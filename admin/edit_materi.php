<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: kelola_materi.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT am.*, p.nama_lengkap 
     FROM ARSIP_MATERI am 
     JOIN PENGGUNA p ON p.id_user = am.id_user 
     WHERE am.id_arsip = :id"
);
$stmt->execute([':id' => $id]);
$m = $stmt->fetch();

if (!$m) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Materi tidak ditemukan.'];
    header('Location: kelola_materi.php');
    exit;
}

$pageTitle = 'Edit Materi';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Edit Materi</h1>
        <p class="text-muted mb-0">Perbarui informasi arsip materi yang sudah ada.</p>
    </div>
    <a class="btn btn-outline-secondary" href="kelola_materi.php">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <form class="card-body p-4" method="post"
          action="proses_materi.php?action=edit&id=<?= $m['id_arsip'] ?>"
          enctype="multipart/form-data">

        <div class="mb-3">
            <label class="form-label fw-semibold">Judul Dokumen <span class="text-danger">*</span></label>
            <input class="form-control" name="judul_dokumen"
                   value="<?= htmlspecialchars($m['judul_dokumen']) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Kategori</label>
            <input class="form-control" name="kategori"
                   value="<?= htmlspecialchars($m['kategori'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Deskripsi</label>
            <div id="editor-deskripsi" style="height:180px"><?= $m['deskripsi'] ?></div>
            <input type="hidden" name="deskripsi" id="deskripsi">
        </div>

        <!-- File saat ini -->
        <?php if (!empty($m['file_path'])): ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">File Saat Ini</label>
                <div class="d-flex align-items-center gap-3">
                    <a href="../<?= htmlspecialchars(ltrim($m['file_path'], '/')) ?>"
                       target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i>Lihat File
                    </a>
                    <small class="text-muted"><?= htmlspecialchars(basename($m['file_path'])) ?></small>
                </div>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label fw-semibold">
                <?= !empty($m['file_path']) ? 'Ganti File (opsional)' : 'Upload File (opsional)' ?>
            </label>
            <input class="form-control" name="dokumen" type="file"
                   accept="application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,.ppt,.pptx">
            <div class="form-text">Format: PDF, PPT, PPTX — Maks. 10 MB</div>
        </div>

        <?php if ($_SESSION['role'] === 'Super Admin'): ?>
            <div class="mb-4">
                <label class="form-label fw-semibold">Status</label>
                <select class="form-select" name="status" style="max-width:220px">
                    <option value="Published" <?= $m['status'] === 'Published' ? 'selected' : '' ?>>Published</option>
                    <option value="Rejected"  <?= $m['status'] === 'Rejected'  ? 'selected' : '' ?>>Rejected</option>
                    <option value="Pending"   <?= $m['status'] === 'Pending'   ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2-circle me-1"></i>Simpan Perubahan
            </button>
            <a class="btn btn-outline-secondary" href="kelola_materi.php">Batal</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
    const editor = new Quill('#editor-deskripsi', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    document.querySelector('form').addEventListener('submit', function () {
        document.getElementById('deskripsi').value = editor.root.innerHTML;
    });
</script>

<?php include '../includes/footer.php'; ?>
