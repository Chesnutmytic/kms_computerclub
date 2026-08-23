<?php
/**
 * portal/tambah_catatan.php
 * Hanya dapat diakses oleh peserta event yang sudah selesai.
 * Judul & jenis kegiatan otomatis dari event (readonly).
 */
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

// Admin/Super Admin boleh akses langsung (tanpa id_event)
$isAdmin = in_array($_SESSION['role'] ?? '', ['Super Admin', 'Admin'], true);

$idEvent   = (int) ($_GET['id_event'] ?? 0);
$eventData = null;

if (!$isAdmin) {
    // Anggota wajib punya id_event yang valid, dan harus menjadi peserta event selesai
    if ($idEvent <= 0) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Kamu belum memiliki akses untuk menambahkan catatan pengalaman. Ikuti event terlebih dahulu.'];
        header('Location: catatan.php');
        exit;
    }

    $stmtEv = $conn->prepare(
        "SELECT e.id_event, e.nama_event, e.jenis_event
         FROM `event` e
         JOIN event_peserta ep ON ep.id_event = e.id_event
         WHERE e.id_event = :ev
           AND ep.id_user = :u
           AND e.status = 'Selesai'"
    );
    $stmtEv->execute([':ev' => $idEvent, ':u' => $_SESSION['id_user']]);
    $eventData = $stmtEv->fetch(PDO::FETCH_ASSOC);

    if (!$eventData) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Akses ditolak: kamu bukan peserta event ini atau event belum selesai.'];
        header('Location: catatan.php');
        exit;
    }

    // Cegah duplikasi catatan per event per user
    $stmtDup = $conn->prepare(
        "SELECT id_catatan FROM catatan_pengalaman WHERE id_user = :u AND id_event = :ev LIMIT 1"
    );
    $stmtDup->execute([':u' => $_SESSION['id_user'], ':ev' => $idEvent]);
    if ($stmtDup->fetch()) {
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Kamu sudah pernah menambahkan catatan untuk event ini.'];
        header('Location: catatan.php?tab=saya');
        exit;
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Catatan — KMS Computer Club</title>
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
    <a href="catatan.php" class="text-sm text-gray-400 hover:text-white transition-colors hidden sm:block">
      ← Kembali ke Catatan
    </a>
    <a href="../logout.php" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-800 text-gray-300 hover:text-white hover:bg-gray-700 transition-all flex items-center gap-1.5">
      <i data-lucide="log-out" class="w-4 h-4"></i>Logout
    </a>
  </div>
</nav>

<main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

  <!-- Header -->
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Tambah Catatan Pengalaman</h1>
    <p class="text-gray-500 mt-1 text-sm">Catatan akan menunggu persetujuan Admin sebelum dipublikasikan.</p>
  </div>

  <?php if ($flash): ?>
    <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium
      <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
      <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Banner Event (hanya untuk Anggota) -->
  <?php if ($eventData): ?>
    <div class="mb-6 flex items-center gap-4 p-4 rounded-2xl bg-violet-50 border border-violet-200">
      <div class="w-10 h-10 rounded-xl bg-violet-600 flex items-center justify-center shrink-0">
        <i data-lucide="calendar-check" class="w-5 h-5 text-white"></i>
      </div>
      <div>
        <p class="text-xs font-semibold text-violet-500 uppercase tracking-wide">Event yang telah diikuti</p>
        <p class="text-sm font-bold text-violet-900 mt-0.5"><?= htmlspecialchars($eventData['nama_event']) ?></p>
        <p class="text-xs text-violet-600"><?= htmlspecialchars($eventData['jenis_event']) ?> · Judul dan jenis telah otomatis diisi</p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Form Card -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
      <i data-lucide="notebook-pen" class="w-5 h-5 text-indigo-600"></i>
      <span class="font-semibold text-gray-900">Detail Catatan</span>
    </div>
    <form class="p-6 space-y-5" method="post" action="../admin/proses_catatan.php?action=create" enctype="multipart/form-data">

      <?php if ($idEvent > 0): ?>
        <input type="hidden" name="id_event" value="<?= $idEvent ?>">
      <?php endif; ?>

      <!-- Judul Kegiatan -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Judul Kegiatan <span class="text-red-500">*</span>
        </label>
        <?php if ($eventData): ?>
          <!-- Auto-filled dari event, readonly untuk Anggota -->
          <input value="<?= htmlspecialchars($eventData['nama_event']) ?>"
                 readonly
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 cursor-not-allowed">
          <input type="hidden" name="judul_kegiatan" value="<?= htmlspecialchars($eventData['nama_event']) ?>">
        <?php else: ?>
          <input name="judul_kegiatan" required
                 placeholder="Masukkan judul kegiatan"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <!-- Jenis Kegiatan -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Jenis Kegiatan <span class="text-red-500">*</span>
          </label>
          <?php if ($eventData): ?>
            <!-- Auto-filled, readonly untuk Anggota -->
            <input value="<?= htmlspecialchars($eventData['jenis_event']) ?>"
                   readonly
                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 bg-gray-50 cursor-not-allowed">
            <input type="hidden" name="jenis_kegiatan" value="<?= htmlspecialchars($eventData['jenis_event']) ?>">
          <?php else: ?>
            <select name="jenis_kegiatan" required
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
              <option value="">Pilih jenis kegiatan</option>
              <option>Lomba</option>
              <option>Workshop</option>
              <option>Pelatihan</option>
              <option>Seminar</option>
              <option>Lainnya</option>
            </select>
          <?php endif; ?>
        </div>
        <!-- Kategori -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
          <select name="kategori" required
                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
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
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Gambar Dokumentasi (opsional)</label>
          <input type="file" name="gambar_dokumentasi" accept="image/*"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Tags <span class="text-red-500">*</span></label>
          <input name="tags" placeholder="Contoh: #lomba #juara" required
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
                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Solusi <span class="text-red-500">*</span></label>
        <textarea name="solusi" rows="3" required
                  placeholder="Bagikan solusi yang kamu temukan"
                  class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"></textarea>
      </div>

      <div class="flex gap-3 pt-2">
        <button type="submit"
                class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm shadow-indigo-200">
          <i data-lucide="send" class="w-4 h-4 inline mr-1.5"></i>Kirim Catatan
        </button>
        <a href="catatan.php"
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