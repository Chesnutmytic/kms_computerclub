
<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$id_arsip = (int)($_GET['id'] ?? 0);

if ($id_arsip <= 0) {
    header('Location: arsip_materi.php');
    exit;
}

// Ambil detail materi (tanpa filter status 'Published' terlebih dahulu agar admin/ketua/pemilik bisa preview)
$stmt = $conn->prepare(
    "SELECT am.*, p.nama_lengkap
     FROM arsip_materi am
     JOIN pengguna p ON p.id_user = am.id_user
     WHERE am.id_arsip = :id"
);
$stmt->execute([':id' => $id_arsip]);
$materi = $stmt->fetch();

if (!$materi) {
    header('Location: arsip_materi.php');
    exit;
}

// Hak akses untuk melihat materi yang belum Published:
$userRole = $_SESSION['role'] ?? '';
$isStaff  = in_array($userRole, ['Super Admin', 'Admin', 'Ketua'], true);
$isOwner  = isset($_SESSION['id_user']) && ($materi['id_user'] == $_SESSION['id_user']);

$tglBukaMateri = !empty($materi['tgl_buka']) ? $materi['tgl_buka'] : $materi['tgl_unggah'];
$isScheduledInFuture = ($tglBukaMateri > date('Y-m-d'));

// Pengecekan status rilis jadwal dan approval:
if (($materi['status'] !== 'Published' || $isScheduledInFuture) && !$isStaff && !$isOwner) {
    header('Location: arsip_materi.php');
    exit;
}

// Ambil catatan pribadi jika ada
$stmtNotes = $conn->prepare("SELECT isi_notes FROM catatan_pribadi WHERE id_user = ? AND id_arsip = ?");
$stmtNotes->execute([$_SESSION['id_user'], $id_arsip]);
$notes = $stmtNotes->fetchColumn();

include '../includes/header_portal.html';
?>

<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data='{
    notes: <?= json_encode($notes ?: '', JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    saving: false,
    saved: false,
    async saveNotes() {
        if(this.saving) return;
        this.saving = true;
        this.saved = false;
        try {
            const res = await fetch("proses_notes.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ id_arsip: <?= $id_arsip ?>, isi_notes: this.notes })
            });
            const data = await res.json();
            if(data.success) {
                this.saved = true;
                setTimeout(() => this.saved = false, 3000);
            }
        } finally {
            this.saving = false;
        }
    }
}'>

  <!-- Back Button & Status Banner -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <a href="arsip_materi.php" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali ke Arsip Materi
    </a>

    <?php if ($materi['status'] !== 'Published'): ?>
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold
        <?= $materi['status'] === 'Pending' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <i data-lucide="<?= $materi['status'] === 'Pending' ? 'clock' : 'alert-circle' ?>" class="w-4 h-4"></i>
        <span>Status Dokumen: <strong><?= htmlspecialchars($materi['status']) ?></strong> (Mode Preview)</span>
      </div>
    <?php elseif ($isScheduledInFuture): ?>
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
        <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
        <span>Jadwal Buka: <strong><?= date('d/m/Y', strtotime($tglBukaMateri)) ?></strong> (Mode Preview - Belum dibuka untuk Anggota)</span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Header Materi -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 mb-8">
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <?php if ($materi['kategori']): ?>
        <span class="px-3 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold">
          <?= htmlspecialchars($materi['kategori']) ?>
        </span>
      <?php endif; ?>
      <?php if ($materi['tags']): ?>
        <?php foreach (explode(' ', $materi['tags']) as $tag): ?>
          <?php if (trim($tag)): ?>
            <span class="text-[11px] font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-md"><?= htmlspecialchars($tag) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($materi['judul_dokumen']) ?></h1>
    <p class="text-sm text-gray-500 mb-6">
      Diunggah oleh <span class="font-medium text-gray-700"><?= htmlspecialchars($materi['nama_lengkap']) ?></span> pada <?= htmlspecialchars($materi['tgl_unggah']) ?>
    </p>

    <!-- Deskripsi -->
    <?php if ($materi['deskripsi']): ?>
      <div class="prose prose-sm sm:prose-base max-w-none text-gray-700 mb-8">
        <?= $materi['deskripsi'] ?>
      </div>
    <?php endif; ?>

    <!-- Media Utama (File Path) -->
    <?php if ($materi['file_path']): ?>
      <div class="mb-8">
        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
          <i data-lucide="file" class="w-4 h-4 text-indigo-600"></i>Dokumen Utama
        </h3>
        <?php 
          $ext = strtolower(pathinfo($materi['file_path'], PATHINFO_EXTENSION));
          if ($ext === 'pdf'):
        ?>
          <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-gray-50">
            <iframe src="../<?= htmlspecialchars($materi['file_path']) ?>" class="w-full h-[600px]" title="PDF Viewer"></iframe>
          </div>
        <?php else: ?>
          <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <i data-lucide="file-text" class="w-8 h-8 text-indigo-500"></i>
              <div>
                <p class="text-sm font-semibold text-indigo-900">File Presentasi / Dokumen</p>
                <p class="text-xs text-indigo-600"><?= htmlspecialchars(basename($materi['file_path'])) ?></p>
              </div>
            </div>
            <a href="../<?= htmlspecialchars($materi['file_path']) ?>" target="_blank" rel="noopener" download
               class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm flex items-center gap-2">
              <i data-lucide="download" class="w-4 h-4"></i>Download
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Media Tambahan -->
    <?php if ($materi['file_media']): ?>
      <div class="mb-8">
        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
          <i data-lucide="image" class="w-4 h-4 text-indigo-600"></i>Media Tambahan
        </h3>
        <?php 
          $extMedia = strtolower(pathinfo($materi['file_media'], PATHINFO_EXTENSION));
          if (in_array($extMedia, ['mp4', 'webm', 'ogg'])):
        ?>
          <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-black">
            <video controls class="w-full max-h-[500px]">
              <source src="../<?= htmlspecialchars($materi['file_media']) ?>" type="video/<?= $extMedia === 'mp4' ? 'mp4' : ($extMedia === 'webm' ? 'webm' : 'ogg') ?>">
              Browser Anda tidak mendukung tag video.
            </video>
          </div>
        <?php else: ?>
          <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm">
            <img src="../<?= htmlspecialchars($materi['file_media']) ?>" alt="Media Tambahan" class="w-full h-auto max-h-[600px] object-contain bg-gray-50">
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Link Tautan -->
    <?php if ($materi['link_tautan']): ?>
      <div class="mb-8">
        <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
          <i data-lucide="link" class="w-4 h-4 text-indigo-600"></i>Tautan Eksternal
        </h3>
        <?php 
          $isYoutube = false;
          $ytId = '';
          if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $materi['link_tautan'], $matches)) {
              $isYoutube = true;
              $ytId = $matches[1];
          }
        ?>
        
        <?php if ($isYoutube): ?>
          <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm bg-black relative w-full" style="padding-top: 56.25%;">
            <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId) ?>" 
                    class="absolute top-0 left-0 w-full h-full" 
                    title="YouTube video player" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
            </iframe>
          </div>
          <div class="mt-3">
            <a href="<?= htmlspecialchars($materi['link_tautan']) ?>" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
              <i data-lucide="external-link" class="w-4 h-4"></i>Buka di YouTube
            </a>
          </div>
        <?php else: ?>
          <a href="<?= htmlspecialchars($materi['link_tautan']) ?>" target="_blank" rel="noopener"
             class="inline-flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-indigo-600 text-sm font-medium hover:bg-indigo-50 hover:border-indigo-200 transition-all">
            <i data-lucide="external-link" class="w-4 h-4"></i>Buka Tautan: <?= htmlspecialchars(mb_strimwidth($materi['link_tautan'], 0, 50, '...')) ?>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Panel Catatan Pribadi -->
  <div class="bg-amber-50 rounded-2xl border border-amber-200 shadow-sm p-6 sm:p-8">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-bold text-amber-900 flex items-center gap-2">
        <i data-lucide="pen-tool" class="w-5 h-5 text-amber-600"></i>Catatan Pribadi Saya
      </h2>
      <span x-show="saved" x-transition class="text-xs font-semibold text-emerald-600 flex items-center gap-1">
        <i data-lucide="check" class="w-3.5 h-3.5"></i>Tersimpan
      </span>
    </div>
    <p class="text-sm text-amber-700 mb-4">Tulis catatan, ringkasan, atau hal penting dari materi ini. Catatan ini hanya bisa dilihat oleh Anda.</p>
    
    <div class="relative">
      <textarea x-model="notes" @input.debounce.1000ms="saveNotes" rows="6"
        placeholder="Mulai mengetik catatan Anda di sini... (Otomatis tersimpan)"
        class="w-full px-4 py-3 rounded-xl border border-amber-300 bg-white text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all resize-y"></textarea>
      
      <div x-show="saving" class="absolute bottom-3 right-3 flex items-center gap-1.5 text-xs font-medium text-amber-500 bg-white/80 backdrop-blur-sm px-2 py-1 rounded-lg">
        <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>Menyimpan...
      </div>
    </div>
    <div class="mt-3 flex justify-end">
      <button @click="saveNotes" :disabled="saving"
        class="px-4 py-2 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 disabled:opacity-50 transition-all shadow-sm flex items-center gap-2">
        <i data-lucide="save" class="w-4 h-4"></i>Simpan Manual
      </button>
    </div>
  </div>

</main>

<?php include '../includes/footer_portal.html'; ?>
