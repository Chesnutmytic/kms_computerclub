<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$id_catatan = (int)($_GET['id'] ?? 0);

if ($id_catatan <= 0) {
    header('Location: catatan.php');
    exit;
}

$isAdmin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['Super Admin', 'Admin'], true);

$stmt = $conn->prepare(
    "SELECT cp.*, p.nama_lengkap,
            (SELECT COUNT(*) FROM like_catatan WHERE id_catatan = cp.id_catatan) AS jumlah_like,
            (SELECT COUNT(*) FROM like_catatan WHERE id_catatan = cp.id_catatan AND id_user = :id_user) AS sudah_like,
            (SELECT COUNT(*) FROM komentar_catatan WHERE id_catatan = cp.id_catatan) AS jumlah_komentar
     FROM catatan_pengalaman cp
     JOIN pengguna p ON p.id_user = cp.id_user
     WHERE cp.id_catatan = :id_catatan" . ($isAdmin ? '' : " AND cp.status = 'Published'")
);
$stmt->execute([':id_catatan' => $id_catatan, ':id_user' => $_SESSION['id_user']]);
$c = $stmt->fetch();

if (!$c) {
    header('Location: catatan.php');
    exit;
}

include '../includes/header_portal.html';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Back Button -->
  <?php if ($isAdmin && $c['status'] !== 'Published'): ?>
    <a href="../admin/kelola_catatan.php" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors mb-4">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali ke Kelola Catatan
    </a>
    <!-- Admin status banner -->
    <?php
      $bannerCls = $c['status'] === 'Pending'
        ? 'bg-amber-50 border-amber-200 text-amber-800'
        : 'bg-red-50 border-red-200 text-red-800';
      $bannerIcon = $c['status'] === 'Pending' ? 'clock' : 'x-circle';
    ?>
    <div class="flex items-start gap-3 px-4 py-3 rounded-xl border <?= $bannerCls ?> mb-6 text-sm">
      <i data-lucide="<?= $bannerIcon ?>" class="w-4 h-4 mt-0.5 shrink-0"></i>
      <div>
        <span class="font-semibold">Status: <?= htmlspecialchars($c['status']) ?></span>
        <?php if ($c['status'] === 'Rejected' && !empty($c['alasan_reject'])): ?>
          <span class="block mt-0.5 opacity-80">Alasan: <?= htmlspecialchars($c['alasan_reject']) ?></span>
        <?php endif; ?>
        <span class="block mt-0.5 opacity-70 text-xs">Pratinjau admin - catatan ini belum dipublikasikan.</span>
      </div>
    </div>
  <?php else: ?>
    <a href="catatan.php" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors mb-6">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali ke Catatan
    </a>
  <?php endif; ?>

  <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
    <?php if ($c['gambar_dokumentasi']): ?>
      <div class="w-full h-64 sm:h-80 bg-gray-100 border-b border-gray-100">
        <img src="../<?= htmlspecialchars($c['gambar_dokumentasi']) ?>" alt="Dokumentasi" class="w-full h-full object-cover">
      </div>
    <?php endif; ?>

    <div class="p-6 sm:p-8">
      <div class="flex items-center gap-2 mb-4">
        <span class="px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-semibold">
          <?= htmlspecialchars($c['jenis_kegiatan']) ?>
        </span>
        <span class="px-3 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-medium">
          <?= htmlspecialchars($c['kategori'] ?: 'Umum') ?>
        </span>
      </div>
      
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3"><?= htmlspecialchars($c['judul_kegiatan']) ?></h1>
      <p class="text-sm text-gray-500 mb-6">
        Ditulis oleh <span class="font-medium text-gray-700"><?= htmlspecialchars($c['nama_lengkap']) ?></span> pada <?= htmlspecialchars($c['tgl_unggah']) ?>
      </p>

      <?php if ($c['tags']): ?>
        <div class="flex flex-wrap gap-2 mb-8">
          <?php foreach (explode(' ', $c['tags']) as $tag): ?>
            <?php if (trim($tag)): ?>
              <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md"><?= htmlspecialchars($tag) ?></span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="space-y-8">
        <div>
          <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
            <i data-lucide="book-open" class="w-4 h-4 text-indigo-500"></i>Pengalaman
          </h2>
          <div class="prose prose-sm sm:prose-base max-w-none text-gray-700">
            <?= nl2br(htmlspecialchars(strip_tags($c['pengalaman'] ?? ''))) ?>
          </div>
        </div>

        <?php if ($c['kendala']): ?>
        <div>
          <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500"></i>Kendala
          </h2>
          <div class="prose prose-sm sm:prose-base max-w-none text-gray-700">
            <?= nl2br(htmlspecialchars($c['kendala'])) ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($c['solusi']): ?>
        <div>
          <h2 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500"></i>Solusi
          </h2>
          <div class="prose prose-sm sm:prose-base max-w-none text-gray-700">
            <?= nl2br(htmlspecialchars($c['solusi'])) ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </article>

  <!-- Interaksi -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8" x-data="{ 
    liked: <?= $c['sudah_like'] ? 'true' : 'false' ?>, 
    likes: <?= $c['jumlah_like'] ?>,
    komentar: '',
    loading: false,
    async toggleLike() {
      if(this.loading) return;
      this.loading = true;
      try {
        const res = await fetch('proses_interaksi.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'like', id_catatan: <?= $c['id_catatan'] ?> })
        });
        const data = await res.json();
        if(data.success) {
          this.liked = data.liked;
          this.likes = data.likes;
        }
      } finally { this.loading = false; }
    },
    async kirimKomentar() {
      if(!this.komentar.trim() || this.loading) return;
      this.loading = true;
      try {
        const res = await fetch('proses_interaksi.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'komentar', id_catatan: <?= $c['id_catatan'] ?>, komentar: this.komentar })
        });
        const data = await res.json();
        if(data.success) {
          window.location.reload();
        }
      } finally { this.loading = false; }
    }
  }">
    <div class="flex items-center gap-4 mb-8">
      <button @click="toggleLike" :disabled="loading"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border text-sm font-semibold transition-all"
        :class="liked ? 'bg-red-50 border-red-200 text-red-600' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
        <i data-lucide="heart" class="w-4 h-4" :class="liked ? 'fill-red-500' : ''"></i>
        <span x-text="likes + ' Suka'"></span>
      </button>
    </div>
    
    <div class="space-y-6">
      <h3 class="text-lg font-bold text-gray-900">Komentar (<?= $c['jumlah_komentar'] ?>)</h3>
      
      <div class="flex gap-4">
        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold shrink-0">
          <?= mb_strtoupper(mb_substr($_SESSION['nama_lengkap'], 0, 1)) ?>
        </div>
        <div class="flex-1 flex flex-col sm:flex-row gap-3">
          <input x-model="komentar" @keydown.enter="kirimKomentar" type="text" placeholder="Tulis komentar Anda..."
            class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
          <button @click="kirimKomentar" :disabled="!komentar.trim() || loading"
            class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-500 disabled:opacity-50 transition-all shrink-0">
            Kirim
          </button>
        </div>
      </div>
      
      <!-- List Komentar -->
      <div class="space-y-4 mt-8">
        <?php
        $stmtK = $conn->prepare("SELECT k.*, p.nama_lengkap FROM komentar_catatan k JOIN pengguna p ON p.id_user = k.id_user WHERE k.id_catatan = ? ORDER BY k.tgl_komentar DESC");
        $stmtK->execute([$c['id_catatan']]);
        $komentars = $stmtK->fetchAll();
        foreach ($komentars as $kom):
        ?>
          <div class="flex gap-4">
            <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center text-sm font-bold shrink-0">
              <?= mb_strtoupper(mb_substr($kom['nama_lengkap'], 0, 1)) ?>
            </div>
            <div class="flex-1 bg-gray-50 rounded-2xl p-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($kom['nama_lengkap']) ?></span>
                <span class="text-xs text-gray-400"><?= htmlspecialchars(date('d M Y H:i', strtotime($kom['tgl_komentar']))) ?></span>
              </div>
              <p class="text-sm text-gray-700 leading-relaxed"><?= htmlspecialchars($kom['komentar']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$komentars): ?>
          <p class="text-sm text-gray-500 text-center py-4">Belum ada komentar. Jadilah yang pertama berkomentar!</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

</main>

<?php include '../includes/footer_portal.html'; ?>
