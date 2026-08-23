<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$activeTab = $_GET['tab'] ?? 'semua';   // 'semua' | 'saya'
$q         = trim($_GET['q'] ?? '');
$searchTag = '';

// Deteksi jika query dimulai dengan # → perlakukan sebagai tag search
if ($q !== '' && str_starts_with($q, '#')) {
    $searchTag = $q;
    $q = '';
}

// ── TAB: Semua Catatan (hanya Published) ─────────────────────────────────────
$sql = "SELECT cp.*, p.nama_lengkap,
               (SELECT COUNT(*) FROM like_catatan WHERE id_catatan = cp.id_catatan) AS jumlah_like,
               (SELECT COUNT(*) FROM like_catatan WHERE id_catatan = cp.id_catatan AND id_user = :id_user) AS sudah_like,
               (SELECT COUNT(*) FROM komentar_catatan WHERE id_catatan = cp.id_catatan) AS jumlah_komentar
        FROM catatan_pengalaman cp
        JOIN pengguna p ON p.id_user = cp.id_user
        WHERE cp.status = 'Published'";
$params = [':id_user' => $_SESSION['id_user']];

if ($q !== '') {
    $sql .= " AND (cp.judul_kegiatan LIKE :q OR cp.kategori LIKE :q OR cp.tags LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($searchTag !== '') {
    $tagClean = ltrim($searchTag, '#');
    $sql .= " AND cp.tags LIKE :tag";
    $params[':tag'] = '%#' . $tagClean . '%';
}

$sql .= ' ORDER BY jumlah_like DESC, cp.tgl_unggah DESC, cp.id_catatan DESC';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// ── TAB: Catatan Saya (semua status, hanya milik user login) ─────────────────
// Keamanan: WHERE id_user = :my_id — server-side, bukan filter JS
$stmtSaya = $conn->prepare(
    "SELECT cp.*, p.nama_lengkap
     FROM catatan_pengalaman cp
     JOIN pengguna p ON p.id_user = cp.id_user
     WHERE cp.id_user = :my_id
     ORDER BY cp.status = 'Pending' DESC, cp.tgl_unggah DESC, cp.id_catatan DESC"
);
$stmtSaya->execute([':my_id' => $_SESSION['id_user']]);
$itemsSaya = $stmtSaya->fetchAll();

$jumlahRejected = count(array_filter($itemsSaya, fn($r) => $r['status'] === 'Rejected'));

// Ambil semua tag unik dari catatan yang published
$rawTags = $conn->query(
    "SELECT tags FROM catatan_pengalaman WHERE status = 'Published' AND tags IS NOT NULL AND tags <> ''"
)->fetchAll(PDO::FETCH_COLUMN);
$allTags = [];
foreach ($rawTags as $tagStr) {
    foreach (explode(' ', $tagStr) as $t) {
        $t = trim($t);
        if ($t !== '' && str_starts_with($t, '#')) {
            $allTags[ltrim($t, '#')] = ($allTags[ltrim($t, '#')] ?? 0) + 1;
        }
    }
}
arsort($allTags);

// ── Event selesai yang diikuti user & belum dibuatkan catatan ────────────────
$isAdmin = in_array($_SESSION['role'] ?? '', ['Super Admin', 'Admin'], true);
$eventAkses = [];
if (!$isAdmin) {
    $stmtEventAkses = $conn->prepare(
        "SELECT e.id_event, e.nama_event, e.jenis_event
         FROM `event` e
         JOIN event_peserta ep ON ep.id_event = e.id_event
         WHERE ep.id_user = :u
           AND e.status = 'Selesai'
           AND NOT EXISTS (
               SELECT 1 FROM catatan_pengalaman cp
               WHERE cp.id_event = e.id_event
                 AND cp.id_user = :u2
           )
         ORDER BY e.tgl_selesai DESC"
    );
    $stmtEventAkses->execute([':u' => $_SESSION['id_user'], ':u2' => $_SESSION['id_user']]);
    $eventAkses = $stmtEventAkses->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header_portal.html';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Catatan Pengalaman</h1>
      <p class="text-gray-500 mt-1">Pengalaman dan pembelajaran dari anggota.</p>
    </div>
    <?php if ($isAdmin): ?>
      <!-- Admin: tombol langsung -->
      <a href="tambah_catatan.php"
         class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm self-start">
        <i data-lucide="plus" class="w-4 h-4"></i>Tambah Catatan
      </a>
    <?php elseif (!empty($eventAkses)): ?>
      <!-- Anggota: dropdown pilih event -->
      <div class="relative self-start" x-data="{open:false}">
        <button id="btn-tambah-catatan"
                onclick="document.getElementById('dropdown-event').classList.toggle('hidden')"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm">
          <i data-lucide="plus" class="w-4 h-4"></i>Tambah Catatan
          <i data-lucide="chevron-down" class="w-3.5 h-3.5 ml-0.5"></i>
        </button>
        <div id="dropdown-event"
             class="hidden absolute right-0 mt-2 w-72 bg-white rounded-2xl border border-gray-100 shadow-xl z-30 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pilih Event</p>
            <p class="text-xs text-gray-400 mt-0.5">Event yang kamu ikuti dan sudah selesai</p>
          </div>
          <div class="max-h-64 overflow-y-auto">
            <?php foreach ($eventAkses as $ev): ?>
              <a href="tambah_catatan.php?id_event=<?= $ev['id_event'] ?>"
                 class="flex items-center gap-3 px-4 py-3 hover:bg-indigo-50 transition-colors group">
                <div class="w-8 h-8 rounded-lg bg-violet-100 flex items-center justify-center shrink-0">
                  <i data-lucide="calendar-check" class="w-4 h-4 text-violet-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate group-hover:text-indigo-600 transition-colors">
                    <?= htmlspecialchars($ev['nama_event']) ?>
                  </p>
                  <p class="text-xs text-gray-400"><?= htmlspecialchars($ev['jenis_event']) ?></p>
                </div>
                <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gray-400 group-hover:text-indigo-500 transition-colors"></i>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Tidak punya akses -->
      <div class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 text-gray-400 text-sm font-semibold rounded-xl cursor-not-allowed self-start"
           title="Kamu belum mengikuti event apapun atau semua catatan event sudah dibuat">
        <i data-lucide="lock" class="w-4 h-4"></i>Tambah Catatan
      </div>
    <?php endif; ?>
  </div>

  <?php if ($flash): ?>
    <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium
      <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' ?>">
      <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-xl w-fit mb-6">
    <a href="?tab=semua<?= $q ? '&q=' . urlencode($q) : '' ?><?= $searchTag ? '&q=' . urlencode($searchTag) : '' ?>"
       class="px-4 py-2 rounded-lg text-sm font-semibold transition-all <?= $activeTab !== 'saya' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
      <i data-lucide="globe-2" class="w-3.5 h-3.5 inline mr-1.5"></i>Semua Catatan
    </a>
    <a href="?tab=saya"
       class="relative px-4 py-2 rounded-lg text-sm font-semibold transition-all <?= $activeTab === 'saya' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' ?>">
      <i data-lucide="user" class="w-3.5 h-3.5 inline mr-1.5"></i>Catatan Saya
      <?php if ($jumlahRejected > 0): ?>
        <span class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold leading-none"><?= $jumlahRejected ?></span>
      <?php endif; ?>
    </a>
  </div>

<?php if ($activeTab === 'saya'): ?>
  <!-- ═══════════════════════════════════════════════════════ TAB: CATATAN SAYA -->

  <?php if (!$itemsSaya): ?>
    <div class="py-16 text-center bg-white rounded-2xl border border-gray-100">
      <i data-lucide="notebook-text" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
      <p class="text-gray-500 font-medium">Belum ada catatan.</p>
      <?php if (!$isAdmin && empty($eventAkses)): ?>
        <p class="text-sm text-gray-400 mt-2 max-w-xs mx-auto">
          Kamu belum mengikuti event apapun yang sudah selesai.<br>Ikuti event untuk bisa menambahkan catatan pengalaman.
        </p>
        <a href="event.php" class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 bg-violet-600 text-white text-sm font-semibold rounded-xl hover:bg-violet-500 transition-all">
          <i data-lucide="calendar-days" class="w-4 h-4"></i>Lihat Event Saya
        </a>
      <?php elseif (!$isAdmin && !empty($eventAkses)): ?>
        <p class="text-sm text-gray-400 mt-2">Kamu punya akses untuk menambahkan catatan!</p>
        <a href="tambah_catatan.php?id_event=<?= $eventAkses[0]['id_event'] ?>" class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all">
          <i data-lucide="plus" class="w-4 h-4"></i>Tambah Catatan Pertama
        </a>
      <?php else: ?>
        <a href="tambah_catatan.php" class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all">
          <i data-lucide="plus" class="w-4 h-4"></i>Tambah Catatan Pertama
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($itemsSaya as $c): ?>
        <?php
          $isRejected  = $c['status'] === 'Rejected';
          $isPending   = $c['status'] === 'Pending';
          $isPublished = $c['status'] === 'Published';
        ?>
        <article class="bg-white rounded-2xl border <?= $isRejected ? 'border-red-200' : ($isPending ? 'border-amber-200' : 'border-gray-100') ?> shadow-sm overflow-hidden transition-all duration-200">

          <!-- Status stripe -->
          <?php if ($isRejected): ?>
            <div class="h-1 bg-gradient-to-r from-red-500 to-rose-500"></div>
          <?php elseif ($isPending): ?>
            <div class="h-1 bg-gradient-to-r from-amber-400 to-orange-400"></div>
          <?php endif; ?>

          <div class="p-5">
            <!-- Badges row -->
            <div class="flex flex-wrap items-center gap-2 mb-3">
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-semibold">
                <?= htmlspecialchars($c['jenis_kegiatan']) ?>
              </span>
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-medium">
                <?= htmlspecialchars($c['kategori'] ?: 'Umum') ?>
              </span>

              <!-- Status badge -->
              <?php if ($isRejected): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-50 text-red-700 text-xs font-bold border border-red-200">
                  <i data-lucide="x-circle" class="w-3 h-3"></i>Ditolak
                </span>
              <?php elseif ($isPending): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">
                  <i data-lucide="clock" class="w-3 h-3"></i>Menunggu Review
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                  <i data-lucide="check-circle-2" class="w-3 h-3"></i>Dipublikasikan
                </span>
              <?php endif; ?>
            </div>

            <h2 class="text-base font-semibold text-gray-900 mb-1"><?= htmlspecialchars($c['judul_kegiatan']) ?></h2>
            <p class="text-xs text-gray-400 mb-3">
              Diunggah pada <span class="font-medium text-gray-500"><?= htmlspecialchars($c['tgl_unggah']) ?></span>
            </p>

            <!-- Alasan Reject — hanya tampil jika status Rejected dan alasan ada -->
            <?php if ($isRejected && !empty($c['alasan_reject'])): ?>
              <div class="mt-3 p-4 rounded-xl bg-red-50 border border-red-200">
                <div class="flex items-start gap-2.5">
                  <div class="mt-0.5 w-8 h-8 rounded-lg bg-red-100 border border-red-200 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="shield-alert" class="w-4 h-4 text-red-500"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-red-700 mb-1">Alasan Penolakan</p>
                    <p class="text-sm text-red-700 leading-relaxed"><?= nl2br(htmlspecialchars($c['alasan_reject'])) ?></p>
                  </div>
                </div>
                <div class="mt-3 pt-3 border-t border-red-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                  <p class="text-xs text-red-500 flex items-center gap-1">
                    <i data-lucide="info" class="w-3.5 h-3.5"></i>
                    Silakan perbaiki catatan ini dan kirim ulang untuk ditinjau kembali.
                  </p>
                  <a href="edit_catatan.php?id=<?= $c['id_catatan'] ?>"
                     class="inline-flex items-center justify-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition-all shadow-sm shrink-0">
                    <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>Edit Catatan
                  </a>
                </div>
              </div>
            <?php endif; ?>

            <!-- Footer action -->
            <div class="mt-4 flex flex-wrap items-center gap-2">
              <?php if ($isPublished): ?>
                <a href="detail_catatan.php?id=<?= $c['id_catatan'] ?>"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-50 transition-all">
                  <i data-lucide="book-open" class="w-3.5 h-3.5"></i>Lihat Detail
                </a>
              <?php endif; ?>

              <?php if ($isRejected): ?>
                <a href="edit_catatan.php?id=<?= $c['id_catatan'] ?>"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-500 transition-all shadow-sm">
                  <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>Edit & Kirim Ulang
                </a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

<?php else: ?>
  <!-- ══════════════════════════════════════════════════════ TAB: SEMUA CATATAN -->

  <!-- Search -->
  <form class="flex gap-3 mb-4 bg-white border border-gray-100 rounded-2xl shadow-sm p-4" method="get" id="form-search-catatan">
    <input type="hidden" name="tab" value="semua">
    <div class="relative flex-1">
      <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
      <input name="q" id="input-q-catatan" value="<?= htmlspecialchars($searchTag !== '' ? $searchTag : $q) ?>" placeholder="Cari judul, kategori, atau #tag…"
        class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
    </div>
    <button class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm">
      Cari
    </button>
    <?php if ($q || $searchTag): ?>
      <a href="catatan.php?tab=semua" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">
        Reset
      </a>
    <?php endif; ?>
  </form>

  <!-- Tag Cloud -->
  <?php if (!empty($allTags)): ?>
  <div class="mb-8 bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
    <div class="flex items-center gap-2 mb-3">
      <i data-lucide="tag" class="w-4 h-4 text-emerald-500"></i>
      <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Filter by Tag</span>
    </div>
    <div class="flex flex-wrap gap-2" id="tag-cloud-catatan">
      <?php foreach ($allTags as $tagName => $tagCount): ?>
        <?php $isActive = (ltrim($searchTag, '#') === $tagName); ?>
        <a href="?tab=semua&q=<?= urlencode('#'.$tagName) ?>"
           class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition-all duration-150
                  <?= $isActive
                      ? 'bg-emerald-600 text-white shadow-sm scale-105'
                      : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:scale-105' ?>">
          <span>#<?= htmlspecialchars($tagName) ?></span>
          <span class="<?= $isActive ? 'bg-emerald-500' : 'bg-emerald-100 text-emerald-500' ?> rounded-full px-1.5 py-0.5 text-[10px] font-bold"><?= $tagCount ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($searchTag): ?>
    <p class="text-sm text-gray-500 mb-5">
      Menampilkan <span class="font-semibold text-gray-900"><?= count($items) ?></span> catatan
      dengan tag <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold"><?= htmlspecialchars($searchTag) ?></span>
    </p>
  <?php elseif ($q): ?>
    <p class="text-sm text-gray-500 mb-5">
      Menampilkan <span class="font-semibold text-gray-900"><?= count($items) ?></span> catatan
      untuk "<em><?= htmlspecialchars($q) ?></em>"
    </p>
  <?php endif; ?>

  <!-- List -->
  <div class="space-y-3">
    <?php foreach ($items as $c): ?>
      <article class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
        <div class="p-5 flex flex-col sm:flex-row sm:items-start gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-2">
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-semibold">
                <?= htmlspecialchars($c['jenis_kegiatan']) ?>
              </span>
              <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-medium">
                <?= htmlspecialchars($c['kategori'] ?: 'Umum') ?>
              </span>
            </div>
            <h2 class="text-base font-semibold text-gray-900 mb-1"><?= htmlspecialchars($c['judul_kegiatan']) ?></h2>
            <p class="text-xs text-gray-400 mb-3">
              oleh <span class="font-medium text-gray-500"><?= htmlspecialchars($c['nama_lengkap']) ?></span>
            </p>
            <p class="text-sm text-gray-600 line-clamp-2 mb-4">
              <?= htmlspecialchars(mb_strimwidth(strip_tags($c['pengalaman'] ?? ''), 0, 120, '…')) ?>
            </p>
            <?php if ($c['tags']): ?>
              <div class="flex flex-wrap gap-1.5 mb-4">
                <?php foreach (explode(' ', $c['tags']) as $tag): ?>
                  <?php $tag = trim($tag); if (!$tag || !str_starts_with($tag, '#')) continue; $tName = ltrim($tag, '#'); ?>
                  <a href="?tab=semua&q=<?= urlencode($tag) ?>"
                     class="text-[11px] font-medium px-2 py-0.5 rounded-md transition-all
                            <?= (ltrim($searchTag, '#') === $tName) ? 'bg-emerald-600 text-white' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' ?>">
                    <?= htmlspecialchars($tag) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="flex items-center gap-4 mt-4">
              <a href="detail_catatan.php?id=<?= $c['id_catatan'] ?>"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-indigo-200 text-indigo-700 text-xs font-semibold hover:bg-indigo-50 transition-all">
                <i data-lucide="book-open" class="w-3.5 h-3.5"></i>Baca Selengkapnya
              </a>
              
              <div class="flex items-center gap-3 text-gray-500 text-xs font-medium">
                <span class="flex items-center gap-1"><i data-lucide="heart" class="w-3.5 h-3.5 <?= $c['sudah_like'] ? 'fill-red-500 text-red-500' : '' ?>"></i> <?= $c['jumlah_like'] ?></span>
                <span class="flex items-center gap-1"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i> <?= $c['jumlah_komentar'] ?></span>
              </div>
            </div>
          </div>
          <?php if ($c['gambar_dokumentasi']): ?>
            <div class="w-full sm:w-48 h-32 shrink-0 rounded-xl overflow-hidden border border-gray-100">
              <img src="../<?= htmlspecialchars($c['gambar_dokumentasi']) ?>" alt="Dokumentasi" class="w-full h-full object-cover">
            </div>
          <?php endif; ?>
          <time class="text-xs text-gray-400 shrink-0 sm:text-right hidden sm:block"><?= htmlspecialchars($c['tgl_unggah']) ?></time>
        </div>
      </article>
    <?php endforeach; ?>

    <?php if (!$items): ?>
      <div class="py-16 text-center bg-white rounded-2xl border border-gray-100">
        <i data-lucide="search-x" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
        <p class="text-gray-500 font-medium">Catatan tidak ditemukan.</p>
        <p class="text-sm text-gray-400 mt-1">Coba ubah kata kunci pencarian.</p>
      </div>
    <?php endif; ?>
  </div>

<?php endif; ?>

</main>

<script>
  // Tutup dropdown event saat klik di luar
  document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdown-event');
    const btn = document.getElementById('btn-tambah-catatan');
    if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });
</script>

<?php include '../includes/footer_portal.html'; ?>