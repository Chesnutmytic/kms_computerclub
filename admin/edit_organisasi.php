<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'ID arsip tidak valid.'];
    header('Location: kelola_organisasi.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM arsip_organisasi WHERE id_organisasi = :id');
$stmt->execute([':id' => $id]);
$arsip = $stmt->fetch();
if (!$arsip) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Arsip tidak ditemukan.'];
    header('Location: kelola_organisasi.php');
    exit;
}

$pageTitle = 'Edit Arsip Organisasi';
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
      <h1 class="text-xl font-bold text-gray-900">Edit Arsip Organisasi</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Perbarui informasi arsip organisasi.</p>
  </div>
  <a href="kelola_organisasi.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
  </a>
</div>

<?php if ($arsip['status'] === 'Rejected'): ?>
<div class="mb-6 max-w-2xl p-4 rounded-2xl bg-red-50 border border-red-200 shadow-sm">
  <div class="flex items-start gap-3">
    <div class="w-9 h-9 rounded-xl bg-red-100 border border-red-200 flex items-center justify-center flex-shrink-0">
      <i data-lucide="shield-alert" class="w-5 h-5 text-red-600"></i>
    </div>
    <div class="flex-1 min-w-0">
      <h3 class="text-sm font-bold text-red-800">Arsip Ini Sebelumnya Ditolak</h3>
      <?php if (!empty($arsip['alasan_reject'])): ?>
        <p class="text-xs text-red-700 mt-0.5 font-semibold">Alasan Penolakan:</p>
        <div class="mt-1 text-sm text-red-700 bg-white/60 p-2.5 rounded-xl border border-red-100">
          <?= nl2br(htmlspecialchars($arsip['alasan_reject'])) ?>
        </div>
      <?php endif; ?>
      <p class="text-xs text-red-600 mt-2 flex items-center gap-1">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        Saat Anda menyimpan perubahan, status arsip akan diubah kembali menjadi <strong>Pending</strong> untuk ditinjau ulang oleh Super Admin.
      </p>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden max-w-2xl">
  <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
    <i data-lucide="folder-edit" class="w-5 h-5 text-violet-600"></i>
    <span class="font-semibold text-gray-900">Detail Arsip</span>
  </div>
  <form class="p-6 space-y-5" method="post" action="proses_organisasi.php?action=edit&id=<?= $id ?>" enctype="multipart/form-data">
    <input type="hidden" name="id_organisasi" value="<?= $id ?>">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Dokumen <span class="text-red-500">*</span></label>
      <input name="judul_dokumen" required value="<?= htmlspecialchars($arsip['judul_dokumen']) ?>"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori <span class="text-red-500">*</span></label>
      <select name="kategori_organisasi" required
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
        <option value="">Pilih kategori</option>
        <?php
        $kategoriOptions = ['Kelola Aset', 'Laporan Akhir (LPJ)', 'Galeri Kegiatan', 'SOP', 'Troubleshooting', 'Panduan Kaderisasi'];
        foreach ($kategoriOptions as $opt): ?>
          <option <?= $arsip['kategori_organisasi'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
      <div id="editor-deskripsi"></div>
      <input type="hidden" name="deskripsi" id="deskripsi">
    </div>

    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
      <select name="status"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all">
        <?php foreach (['Pending', 'Published', 'Rejected'] as $s): ?>
          <option <?= $arsip['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <!-- File Dokumen -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">File Dokumen (kosongkan jika tidak diganti)</label>
      <?php if (!empty($arsip['file_path'])): ?>
        <p class="text-xs text-gray-400 mb-2">
          File saat ini: <a href="../<?= htmlspecialchars($arsip['file_path']) ?>" target="_blank" class="text-violet-600 hover:underline font-medium">Lihat file</a>
        </p>
      <?php endif; ?>
      <input type="file" name="file_path"
        accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.pdf,.doc,.docx,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,.ppt,.pptx"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 transition-all">
    </div>

    <!-- File Media -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">File Media / Foto (kosongkan jika tidak diganti)</label>
      <?php if (!empty($arsip['file_media'])): ?>
        <div class="mb-2">
          <img src="../<?= htmlspecialchars($arsip['file_media']) ?>" alt="Media saat ini" class="h-24 rounded-xl object-cover border border-gray-200">
        </div>
      <?php endif; ?>
      <input type="file" name="file_media" accept="image/*"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 transition-all">
    </div>

    <div class="flex gap-3 pt-2">
      <button type="submit"
        class="px-6 py-2.5 bg-violet-600 text-white text-sm font-semibold rounded-xl hover:bg-violet-500 transition-all shadow-sm">
        <i data-lucide="save" class="w-4 h-4 inline mr-1.5"></i>Simpan Perubahan
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
  editor.root.innerHTML = <?= json_encode($arsip['deskripsi'] ?? '') ?>;
  document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('deskripsi').value = editor.root.innerHTML;
  });
</script>

<?php include '../includes/footer.php'; ?>
