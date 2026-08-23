<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin', 'Ketua'], true)) {
    header('Location: ../login.html');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: kelola_materi.php'); exit; }

$stmt = $conn->prepare(
    "SELECT am.*, p.nama_lengkap, mk.tahun_ajaran, mk.status AS status_kepengurusan
     FROM arsip_materi am
     JOIN pengguna p ON p.id_user = am.id_user
     LEFT JOIN masa_kepengurusan mk ON mk.id_kepengurusan = am.id_kepengurusan
     WHERE am.id_arsip = :id"
);
$stmt->execute([':id' => $id]);
$m = $stmt->fetch();

if (!$m) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Materi tidak ditemukan.'];
    header('Location: kelola_materi.php');
    exit;
}

$isArchived = ($m['status_kepengurusan'] ?? '') === 'Diarsipkan';

$pageTitle = 'Edit Materi';
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
      <div class="w-1 h-5 rounded-full bg-gradient-to-b from-indigo-500 to-violet-500"></div>
      <h1 class="text-xl font-bold text-gray-900">Edit Materi</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Perbarui informasi arsip materi.</p>
  </div>
  <a href="kelola_materi.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
  </a>
</div>

<?php if ($isArchived): ?>
  <div class="mb-6 max-w-2xl p-4 rounded-2xl bg-amber-50 border border-amber-200 shadow-sm flex items-start gap-3">
    <div class="w-9 h-9 rounded-xl bg-amber-100 border border-amber-200 flex items-center justify-center shrink-0">
      <i data-lucide="archive" class="w-5 h-5 text-amber-600"></i>
    </div>
    <div>
      <h3 class="text-sm font-bold text-amber-900">Materi Ini Diarsipkan</h3>
      <p class="text-xs text-amber-700 mt-1">
        Materi ini terikat pada <strong>Masa Kepengurusan <?= htmlspecialchars($m['tahun_ajaran'] ?? '-') ?></strong> yang sudah diarsipkan. Materi yang diarsipkan tidak dapat diubah kembali.
      </p>
    </div>
  </div>
<?php endif; ?>

<?php if ($m['status'] === 'Rejected'): ?>
  <div class="mb-6 max-w-2xl p-4 rounded-2xl bg-red-50 border border-red-200 shadow-sm">
    <div class="flex items-start gap-3">
      <div class="w-9 h-9 rounded-xl bg-red-100 border border-red-200 flex items-center justify-center flex-shrink-0">
        <i data-lucide="shield-alert" class="w-5 h-5 text-red-600"></i>
      </div>
      <div class="flex-1 min-w-0">
        <h3 class="text-sm font-bold text-red-800">Materi Ini Sebelumnya Ditolak</h3>
        <?php if (!empty($m['alasan_reject'])): ?>
          <p class="text-xs text-red-700 mt-0.5 font-semibold">Alasan Penolakan:</p>
          <div class="mt-1 text-sm text-red-700 bg-white/60 p-2.5 rounded-xl border border-red-100">
            <?= nl2br(htmlspecialchars($m['alasan_reject'])) ?>
          </div>
        <?php endif; ?>
        <p class="text-xs text-red-600 mt-2 flex items-center gap-1">
          <i data-lucide="info" class="w-3.5 h-3.5"></i>
          Saat Anda menyimpan perubahan, status materi akan diubah kembali menjadi <strong>Pending</strong> untuk ditinjau ulang.
        </p>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden max-w-2xl <?= $isArchived ? 'opacity-60 pointer-events-none' : '' ?>">
  <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
    <span class="font-semibold text-gray-900 text-sm">Form Edit Materi</span>
    <?php if (!empty($m['tahun_ajaran'])): ?>
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border <?= $isArchived ? 'bg-gray-100 text-gray-600 border-gray-200' : 'bg-teal-50 text-teal-700 border-teal-200' ?>">
        <i data-lucide="<?= $isArchived ? 'archive' : 'calendar' ?>" class="w-3 h-3"></i>
        Kepengurusan <?= htmlspecialchars($m['tahun_ajaran']) ?>
      </span>
    <?php endif; ?>
  </div>
  <form class="p-6 space-y-5" method="post"
        action="proses_materi.php?action=edit&id=<?= $m['id_arsip'] ?>"
        enctype="multipart/form-data">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Dokumen <span class="text-red-500">*</span></label>
      <input name="judul_dokumen" required
        value="<?= htmlspecialchars($m['judul_dokumen']) ?>"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
        <select name="kategori" required
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
          <?php foreach (['Pemrograman', 'Desain Grafis', 'Jaringan', 'Hardware', 'Lainnya'] as $kat): ?>
            <option <?= ($m['kategori'] ?? '') === $kat ? 'selected' : '' ?>><?= $kat ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tags</label>
        <input name="tags" value="<?= htmlspecialchars($m['tags'] ?? '') ?>" placeholder="Contoh: #html #web #pemula"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Jadwal Pelajaran / Tanggal Buka <span class="text-red-500">*</span></label>
        <input type="date" name="tgl_buka" value="<?= htmlspecialchars($m['tgl_buka'] ?? $m['tgl_unggah'] ?? date('Y-m-d')) ?>" required
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi</label>
      <div id="editor-deskripsi"><?= $m['deskripsi'] ?></div>
      <input type="hidden" name="deskripsi" id="deskripsi">
    </div>

    <?php if (!empty($m['file_path'])): ?>
    <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center gap-3">
      <i data-lucide="file" class="w-5 h-5 text-indigo-600 shrink-0"></i>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-indigo-900">File saat ini</p>
        <p class="text-xs text-indigo-600 truncate"><?= htmlspecialchars(basename($m['file_path'])) ?></p>
      </div>
      <a href="../<?= htmlspecialchars(ltrim($m['file_path'], '/')) ?>" target="_blank" rel="noopener"
         class="px-3 py-1.5 rounded-lg bg-white border border-indigo-200 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition-all shrink-0">
        Lihat File
      </a>
    </div>
    <?php endif; ?>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">
        <?= !empty($m['file_path']) ? 'Ganti File Dokumen' : 'Upload File Dokumen' ?> (PDF/PPT/PPTX — maks. 10 MB)
      </label>
      <input type="file" name="dokumen"
        accept="application/pdf,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,.ppt,.pptx"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          <?= !empty($m['file_media']) ? 'Ganti Media Tambahan' : 'Upload Media Tambahan' ?> (Gambar/Video)
        </label>
        <?php if (!empty($m['file_media'])): ?>
          <p class="text-xs text-indigo-600 mb-2 truncate">Saat ini: <?= htmlspecialchars(basename($m['file_media'])) ?></p>
        <?php endif; ?>
        <input type="file" name="file_media" accept="image/*,video/*"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Link Tautan (opsional)</label>
        <input type="url" name="link_tautan" value="<?= htmlspecialchars($m['link_tautan'] ?? '') ?>" placeholder="https://youtube.com/..."
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>
    </div>

    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
      <select name="status"
        class="w-48 px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
        <option value="Published" <?= $m['status'] === 'Published' ? 'selected' : '' ?>>Published</option>
        <option value="Rejected"  <?= $m['status'] === 'Rejected'  ? 'selected' : '' ?>>Rejected</option>
        <option value="Pending"   <?= ($m['status'] === 'Pending' || $m['status'] === 'Rejected') ? 'selected' : '' ?>>Pending</option>
      </select>
    </div>
    <?php endif; ?>

    <div class="flex gap-3 pt-2">
      <button type="submit"
        class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm">
        <i data-lucide="save" class="w-4 h-4 inline mr-1.5"></i>Simpan Perubahan
      </button>
      <a href="kelola_materi.php" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">Batal</a>
    </div>
  </form>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
  const editor = new Quill('#editor-deskripsi', {
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
    document.getElementById('deskripsi').value = editor.root.innerHTML;
  });
</script>

<?php include '../includes/footer.php'; ?>
