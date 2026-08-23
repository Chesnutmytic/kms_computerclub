<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin', 'Anggota'], true)) {
    header('Location: ../login.html');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: catatan.php?tab=saya');
    exit;
}

// Ambil data catatan
$stmt = $conn->prepare("SELECT * FROM catatan_pengalaman WHERE id_catatan = :id");
$stmt->execute([':id' => $id]);
$catatan = $stmt->fetch();

if (!$catatan) {
    header('Location: catatan.php?tab=saya');
    exit;
}

// Pastikan hanya milik user sendiri atau role Admin/Super Admin
if (!in_array($_SESSION['role'], ['Super Admin', 'Admin'], true) && $catatan['id_user'] != $_SESSION['id_user']) {
    header('Location: catatan.php?tab=saya');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Catatan — KMS Computer Club</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    .ql-toolbar.ql-snow { border-color: #e5e7eb; border-radius: 0.75rem 0.75rem 0 0; background: #f9fafb; }
    .ql-container.ql-snow { border-color: #e5e7eb; border-radius: 0 0 0.75rem 0.75rem; }
    .ql-editor { min-height: 160px; font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen antialiased" style="font-family:'Inter',sans-serif;">

<!-- Topbar -->
<nav class="bg-gray-900 border-b border-gray-800 h-14 flex items-center px-4 sm:px-6 sticky top-0 z-40">
  <a href="index.php" class="flex items-center gap-2 group">
    <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center">
      <i data-lucide="cpu" class="w-4 h-4 text-white"></i>
    </div>
    <span class="text-white font-bold text-sm">KMS Computer Club</span>
  </a>
  <div class="ml-auto flex items-center gap-3">
    <a href="catatan.php?tab=saya" class="text-sm text-gray-400 hover:text-white transition-colors hidden sm:block">
      ← Kembali ke Catatan Saya
    </a>
    <a href="../logout.php" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-800 text-gray-300 hover:text-white hover:bg-gray-700 transition-all flex items-center gap-1.5">
      <i data-lucide="log-out" class="w-4 h-4"></i>Logout
    </a>
  </div>
</nav>

<main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

  <!-- Header -->
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Edit Catatan Pengalaman</h1>
    <p class="text-gray-500 mt-1 text-sm">Setelah diperbarui, catatan akan berstatus <strong>Pending</strong> untuk ditinjau ulang oleh Admin.</p>
  </div>

  <?php if ($catatan['status'] === 'Rejected' && !empty($catatan['alasan_reject'])): ?>
    <!-- Alasan Penolakan Alert -->
    <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 shadow-sm">
      <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-red-100 border border-red-200 flex items-center justify-center flex-shrink-0">
          <i data-lucide="shield-alert" class="w-5 h-5 text-red-600"></i>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="text-sm font-bold text-red-800">Catatan Ini Sebelumnya Ditolak</h3>
          <p class="text-xs text-red-700 mt-0.5 font-semibold">Alasan Penolakan Admin:</p>
          <div class="mt-1 text-sm text-red-700 bg-white/60 p-2.5 rounded-xl border border-red-100">
            <?= nl2br(htmlspecialchars($catatan['alasan_reject'])) ?>
          </div>
          <p class="text-xs text-red-600 mt-2 flex items-center gap-1">
            <i data-lucide="info" class="w-3.5 h-3.5"></i>
            Silakan perbaiki bagian yang disebutkan di atas sebelum mengirim ulang.
          </p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($flash): ?>
    <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium
      <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
      <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Form Card -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
      <i data-lucide="edit-3" class="w-5 h-5 text-indigo-600"></i>
      <span class="font-semibold text-gray-900">Perbarui Detail Catatan</span>
    </div>
    <form class="p-6 space-y-5" method="post" action="../admin/proses_catatan.php?action=update" enctype="multipart/form-data">
      <input type="hidden" name="id_catatan" value="<?= $catatan['id_catatan'] ?>">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Judul Kegiatan <span class="text-red-500">*</span></label>
        <input name="judul_kegiatan" required value="<?= htmlspecialchars($catatan['judul_kegiatan']) ?>"
          placeholder="Masukkan judul kegiatan"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Jenis Kegiatan <span class="text-red-500">*</span></label>
          <select name="jenis_kegiatan" required
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
            <option value="">Pilih jenis kegiatan</option>
            <?php foreach (['Lomba', 'Workshop', 'Pelatihan', 'Seminar'] as $j): ?>
              <option value="<?= $j ?>" <?= $catatan['jenis_kegiatan'] === $j ? 'selected' : '' ?>><?= $j ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
          <select name="kategori" required
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
            <option value="">Pilih kategori</option>
            <?php foreach (['Pemrograman', 'Desain Grafis', 'Jaringan', 'Hardware', 'Lainnya'] as $kat): ?>
              <option value="<?= $kat ?>" <?= $catatan['kategori'] === $kat ? 'selected' : '' ?>><?= $kat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Gambar Dokumentasi (opsional)</label>
          <?php if (!empty($catatan['gambar_dokumentasi'])): ?>
            <div class="mb-2 flex items-center gap-2 text-xs text-gray-500">
              <span class="font-medium">Gambar saat ini:</span>
              <a href="../<?= htmlspecialchars($catatan['gambar_dokumentasi']) ?>" target="_blank" class="text-indigo-600 underline">Lihat gambar</a>
            </div>
          <?php endif; ?>
          <input type="file" name="gambar_dokumentasi" accept="image/*"
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
          <p class="text-[11px] text-gray-400 mt-1">Biarkan kosong jika tidak ingin mengubah gambar.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Tags <span class="text-red-500">*</span></label>
          <input name="tags" value="<?= htmlspecialchars($catatan['tags']) ?>" placeholder="Contoh: #lomba #juara" required
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Pengalaman <span class="text-red-500">*</span></label>
        <div id="editor-pengalaman"></div>
        <input type="hidden" name="pengalaman" id="pengalaman">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kendala <span class="text-red-500">*</span></label>
        <textarea name="kendala" rows="3" required
          placeholder="Ceritakan kendala yang dihadapi"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"><?= htmlspecialchars($catatan['kendala']) ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Solusi <span class="text-red-500">*</span></label>
        <textarea name="solusi" rows="3" required
          placeholder="Bagikan solusi yang kamu temukan"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"><?= htmlspecialchars($catatan['solusi']) ?></textarea>
      </div>

      <div class="flex gap-3 pt-2">
        <button type="submit"
          class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm shadow-indigo-200 inline-flex items-center gap-1.5">
          <i data-lucide="send" class="w-4 h-4"></i>Simpan & Kirim Ulang
        </button>
        <a href="catatan.php?tab=saya"
          class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">
          Batal
        </a>
      </div>
    </form>
  </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
  lucide.createIcons();
  const editor = new Quill('#editor-pengalaman', {
    theme: 'snow',
    modules: {
      toolbar: [
        ['bold', 'italic', 'underline'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'image'], ['clean']
      ]
    }
  });

  // Load existing pengalaman content into Quill
  const initialContent = <?= json_encode($catatan['pengalaman'] ?? '') ?>;
  if (initialContent) {
    editor.clipboard.dangerouslyPasteHTML(initialContent);
  }

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
          const res = await fetch('../admin/upload_image.php', { method: 'POST', body: formData });
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
</body>
</html>
