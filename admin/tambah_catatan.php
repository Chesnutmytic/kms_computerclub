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
<style>
.ql-toolbar.ql-snow { border-color: #e5e7eb; border-radius: .75rem .75rem 0 0; background: #f9fafb; }
.ql-container.ql-snow { border-color: #e5e7eb; border-radius: 0 0 .75rem .75rem; }
.ql-editor { min-height: 160px; font-family: 'Inter', sans-serif; }
</style>

<div class="fade-in">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <div class="flex items-center gap-2.5 mb-1">
      <div class="w-1 h-5 rounded-full bg-gradient-to-b from-emerald-500 to-teal-500"></div>
      <h1 class="text-xl font-bold text-gray-900">Tambah Catatan Pengalaman</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Catatan baru akan menunggu approval sebelum dipublikasikan.</p>
  </div>
  <a href="kelola_catatan.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
  </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden max-w-2xl">
  <form class="p-6 space-y-5" method="post" action="proses_catatan.php?action=create" enctype="multipart/form-data">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Kegiatan <span class="text-red-500">*</span></label>
      <input name="judul_kegiatan" required placeholder="Masukkan judul kegiatan"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Jenis Kegiatan <span class="text-red-500">*</span></label>
        <select name="jenis_kegiatan" required
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
          <option value="">Pilih jenis kegiatan</option>
          <option>Lomba</option>
          <option>Workshop</option>
          <option>Pelatihan</option>
          <option>Seminar</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori<span class="text-red-500">*</span></label>
        <select name="kategori" required
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
          <option value="">Pilih kategori</option>
          <option>Pemrograman</option>
          <option>Desain Grafis</option>
          <option>Jaringan</option>
          <option>Hardware</option>
          <option>Lainnya</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Gambar</label>
        <input type="file" name="gambar_dokumentasi" accept="image/*"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tags<span class="text-red-500">*</span></label>
        <input name="tags" placeholder="Contoh: #lomba #juara" required
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Link Tautan <span class="text-gray-400 font-normal">(opsional)</span></label>
      <input type="url" name="link_tautan" placeholder="https://github.com/username/repo"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Pengalaman<span class="text-red-500">*</span></label>
      <div id="editor-pengalaman"></div>
      <input type="hidden" name="pengalaman" id="pengalaman" required>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Kendala<span class="text-red-500">*</span></label>
      <textarea name="kendala" rows="3" placeholder="Kendala yang dihadapi…" required
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"></textarea>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Solusi<span class="text-red-500">*</span></label>
      <textarea name="solusi" rows="3" placeholder="Solusi yang ditemukan…" required
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"></textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <button type="submit"
        class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm">
        <i data-lucide="send" class="w-4 h-4 inline mr-1.5"></i>Kirim Catatan
      </button>
      <a href="kelola_catatan.php" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">Batal</a>
    </div>
  </form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
  const editor = new Quill('#editor-pengalaman', {
    theme: 'snow',
    modules: { toolbar: [['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['link','image'],['clean']] }
  });

  editor.getModule('toolbar').addHandler('image', () => {
    const input = document.createElement('input');
    input.setAttribute('type', 'file');
    input.setAttribute('accept', 'image/*');
    input.click();
    input.onchange = async () => {
      const file = input.files[0];
      if (file) {
        const formData = new FormData();
        formData.append('image', file);
        try {
          const res = await fetch('upload_image.php', { method: 'POST', body: formData });
          const data = await res.json();
          if (data.success) {
            const range = editor.getSelection();
            editor.insertEmbed(range.index, 'image', '../' + data.url);
          } else {
            alert(data.error || 'Gagal mengunggah gambar');
          }
        } catch (e) {
          alert('Terjadi kesalahan jaringan.');
        }
      }
    };
  });
  document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('pengalaman').value = editor.root.innerHTML;
  });
</script>

<?php include '../includes/footer.php'; ?>