<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Upload Materi';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$kepengurusanAktif = $conn->query(
    "SELECT * FROM masa_kepengurusan WHERE status = 'Aktif' LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

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
        <div class="w-1 h-5 rounded-full bg-gradient-to-b from-indigo-500 to-violet-500"></div>
        <h1 class="text-xl font-bold text-gray-900">Upload Materi</h1>
      </div>
      <p class="text-sm text-gray-500 ml-3.5">Materi baru akan ditambahkan ke masa kepengurusan yang sedang aktif.</p>
    </div>
    <a href="kelola_materi.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
    </a>
  </div>

  <?php if (!$kepengurusanAktif): ?>
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-2xl p-5 mb-6 max-w-2xl flex items-start gap-3">
      <i data-lucide="triangle-alert" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
      <div>
        <h3 class="font-bold text-sm">Tidak ada Masa Kepengurusan Aktif</h3>
        <p class="text-xs text-amber-700 mt-1">
          Anda tidak dapat mengunggah materi baru sebelum Super Admin membuat atau mengaktifkan masa kepengurusan baru.
        </p>
        <?php if ($_SESSION['role'] === 'Super Admin'): ?>
          <a href="kelola_kepengurusan.php" class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 bg-amber-600 text-white text-xs font-semibold rounded-lg hover:bg-amber-500 transition-all">
            <i data-lucide="plus" class="w-3.5 h-3.5"></i>Buat Kepengurusan
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden max-w-2xl <?= !$kepengurusanAktif ? 'opacity-50 pointer-events-none' : '' ?>">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <i data-lucide="upload" class="w-5 h-5 text-indigo-600"></i>
        <span class="font-semibold text-gray-900">Detail Materi</span>
      </div>
      <?php if ($kepengurusanAktif): ?>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 text-teal-700 text-xs font-semibold border border-teal-200">
          <i data-lucide="calendar" class="w-3 h-3"></i>
          Kepengurusan <?= htmlspecialchars($kepengurusanAktif['tahun_ajaran']) ?>
        </span>
      <?php endif; ?>
    </div>
    <form class="p-6 space-y-5" method="post" action="proses_materi.php?action=create" enctype="multipart/form-data">
      <?php if ($kepengurusanAktif): ?>
        <input type="hidden" name="id_kepengurusan" value="<?= $kepengurusanAktif['id_kepengurusan'] ?>">
      <?php endif; ?>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Pembelajaran <span class="text-red-500">*</span></label>
        <input name="judul_dokumen" required placeholder="Masukkan judul dokumen"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
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
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Tags<span class="text-red-500">*</span></label>
          <input name="tags" placeholder="Contoh: #html #web #pemula" required
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Jadwal Pelajaran / Tanggal Buka <span class="text-red-500">*</span></label>
          <input type="date" name="tgl_buka" value="<?= date('Y-m-d') ?>" required
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5" required>Deskripsi<span class="text-red-500">*</span></label>
        <div id="editor-deskripsi"></div>
        <input type="hidden" name="deskripsi" id="deskripsi" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">File Dokumen (PDF, PPT, PPTX — maks. 10 MB) <span class="text-red-500">*</span></label>
        <input type="file" name="dokumen" required
          accept="application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,.ppt,.pptx"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Media Tambahan (Gambar/Video)</label>
          <input type="file" name="file_media" accept="image/*,video/*"
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Link Tautan (opsional)</label>
          <input type="url" name="link_tautan" placeholder="https://..."
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
        </div>
      </div>

    <div class="flex gap-3 pt-2">
      <button type="submit"
        class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm">
        <i data-lucide="upload" class="w-4 h-4 inline mr-1.5"></i>Upload Materi
      </button>
      <a href="kelola_materi.php" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">Batal</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
  const editor = new Quill('#editor-deskripsi', {
    theme: 'snow',
    modules: { toolbar: [['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['link','image'],['clean']] }
  });

  // Image Upload Handler for Quill
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
    document.getElementById('deskripsi').value = editor.root.innerHTML;
  });
</script>
</div>
<?php include '../includes/footer.php'; ?>