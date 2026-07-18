<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Tambah Catatan';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Tambah Catatan Pengalaman</h1>
        <p class="text-muted mb-0">Catatan baru akan menunggu approval.</p>
    </div>
    <a class="btn btn-outline-secondary" href="kelola_catatan.php">Kembali</a>
</div>

<div class="card border-0 shadow-sm">
    <form class="card-body p-4" method="post" action="proses_catatan.php?action=create">
        <div class="mb-3">
            <label class="form-label">Judul kegiatan</label>
            <input class="form-control" name="judul_kegiatan" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Jenis kegiatan</label>
            <select class="form-select" name="jenis_kegiatan" required>
                <option value="">Pilih jenis kegiatan</option>
                <option>Lomba</option>
                <option>Workshop</option>
                <option>Pelatihan</option>
                <option>Seminar</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Kategori</label>
            <input class="form-control" name="kategori">
        </div>

        <div class="mb-3">
            <label class="form-label">Pengalaman</label>
            <div id="editor-pengalaman" style="height:180px"></div>
            <input type="hidden" name="pengalaman" id="pengalaman">
        </div>

        <div class="mb-3">
            <label class="form-label">Kendala</label>
            <textarea class="form-control" name="kendala" rows="3"></textarea>
        </div>

        <div class="mb-4">
            <label class="form-label">Solusi</label>
            <textarea class="form-control" name="solusi" rows="3"></textarea>
        </div>

        <button class="btn btn-primary">Kirim Catatan</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
    const editor = new Quill('#editor-pengalaman', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    document.querySelector('form').addEventListener('submit', function() {
        document.getElementById('pengalaman').value = editor.root.innerHTML;
    });
</script>

<?php include '../includes/footer.php'; ?>