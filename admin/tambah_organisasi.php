<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Tambah Arsip Organisasi';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
.ql-toolbar.ql-snow { border-color: #e5e7eb; border-radius: .75rem .75rem 0 0; background: #f9fafb; }
.ql-container.ql-snow { border-color: #e5e7eb; border-radius: 0 0 .75rem .75rem; }
.ql-editor { min-height: 160px; font-family: 'Inter', sans-serif; }
</style>

<div class="fade-in">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <div class="flex items-center gap-2.5 mb-1">
      <div class="w-1 h-5 rounded-full bg-gradient-to-b from-violet-500 to-fuchsia-500"></div>
      <h1 class="text-xl font-bold text-gray-900">Tambah Arsip Organisasi</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Dokumen baru akan menunggu approval Super Admin.</p>
  </div>
  <a href="kelola_organisasi.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
  </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden max-w-2xl">
  <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
    <i data-lucide="folder-plus" class="w-5 h-5 text-violet-600"></i>
    <span class="font-semibold text-gray-900">Detail Arsip</span>
  </div>
  <form class="p-6 space-y-5" method="post" action="proses_organisasi.php?action=create" enctype="multipart/form-data">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Dokumen <span class="text-red-500">*</span></label>
      <input name="judul_dokumen" required placeholder="Masukkan judul dokumen"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori <span class="text-red-500">*</span></label>
      <select name="kategori_organisasi" required
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
        <option value="">Pilih kategori</option>
        <option>Laporan Akhir (LPJ)</option>
        <option>Galeri Kegiatan</option>
        <option>SOP</option>
        <option>Troubleshooting</option>
        <option>Panduan Kaderisasi</option>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
      <div id="editor-deskripsi"></div>
      <input type="hidden" name="deskripsi" id="deskripsi">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">File Dokumen (PDF, DOC, DOCX — maks. 10 MB)</label>
      <p class="text-xs text-gray-400 mb-2">Untuk: SOP, LPJ, Kelola Aset, Troubleshooting, Panduan Kaderisasi</p>
      <input type="file" name="file_path"
        accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.pdf,.doc,.docx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,.ppt,.pptx"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 transition-all">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">File Media / Foto (Gambar — maks. 10 MB)</label>
      <p class="text-xs text-gray-400 mb-2">Untuk: Galeri Kegiatan, thumbnail, atau foto dokumentasi</p>
      <input type="file" name="file_media" accept="image/*"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 transition-all">
    </div>

    <div class="flex gap-3 pt-2">
      <button type="submit"
        class="px-6 py-2.5 bg-violet-600 text-white text-sm font-semibold rounded-xl hover:bg-violet-500 transition-all shadow-sm">
        <i data-lucide="upload" class="w-4 h-4 inline mr-1.5"></i>Upload Arsip
      </button>
      <a href="kelola_organisasi.php" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">Batal</a>
    </div>
  </form>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
  const editor = new Quill('#editor-deskripsi', {
    theme: 'snow',
    modules: { toolbar: [['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['link'],['clean']] }
  });
  document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('deskripsi').value = editor.root.innerHTML;
  });
</script>

<?php include '../includes/footer.php'; ?>
