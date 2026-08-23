<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$userRole = $_SESSION['role'] ?? '';
$isStaff  = in_array($userRole, ['Super Admin', 'Admin', 'Ketua'], true);
$schedCond = $isStaff ? "" : " AND (am.tgl_buka IS NULL OR am.tgl_buka <= CURDATE())";
$schedCondSimple = $isStaff ? "" : " AND (tgl_buka IS NULL OR tgl_buka <= CURDATE())";

$count = static fn(string $sql): int => (int)$conn->query($sql)->fetch()['total'];
$stats = [
    'materi'  => $count("SELECT COUNT(*) total FROM arsip_materi WHERE status = 'Published' $schedCondSimple"),
    'catatan' => $count("SELECT COUNT(*) total FROM catatan_pengalaman WHERE status = 'Published'"),
    'alur'    => $count("SELECT COUNT(*) total FROM alur_pembelajaran WHERE status = 'Published'"),
    'event'   => $count("SELECT COUNT(*) total FROM `event` WHERE status = 'Aktif'")
];

$materi = $conn->query(
    "SELECT am.id_arsip, am.judul_dokumen, am.kategori, am.deskripsi, am.tgl_unggah, am.tgl_buka, p.nama_lengkap
     FROM arsip_materi am
     JOIN pengguna p ON p.id_user = am.id_user
     WHERE am.status = 'Published' $schedCond
     ORDER BY COALESCE(am.tgl_buka, am.tgl_unggah) DESC, am.id_arsip DESC
     LIMIT 5"
)->fetchAll();

$catatan = $conn->query(
    "SELECT cp.id_catatan, cp.judul_kegiatan, cp.jenis_kegiatan, cp.pengalaman, cp.tgl_unggah, p.nama_lengkap
     FROM catatan_pengalaman cp
     JOIN pengguna p ON p.id_user = cp.id_user
     WHERE cp.status = 'Published'
     ORDER BY cp.tgl_unggah DESC, cp.id_catatan DESC
     LIMIT 5"
)->fetchAll();

$pengumuman = [];
try {
    $pengumuman = $conn->query(
        "SELECT p.*, u.nama_lengkap 
         FROM pengumuman p 
         JOIN pengguna u ON p.id_user = u.id_user 
         ORDER BY p.tgl_dibuat DESC LIMIT 3"
    )->fetchAll();
} catch (PDOException $e) {}

$myId = (int) $_SESSION['id_user'];
$events_saya = [];
try {
    $stmtEvents = $conn->prepare(
        "SELECT e.*, p.nama_lengkap AS nama_pembuat
         FROM `event` e
         JOIN event_peserta ep ON ep.id_event = e.id_event
         JOIN pengguna p ON p.id_user = e.id_pembuat
         WHERE ep.id_user = :u AND e.status = 'Aktif'
         ORDER BY e.tgl_mulai ASC LIMIT 3"
    );
    $stmtEvents->execute([':u' => $myId]);
    $events_saya = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Sapaan berdasarkan waktu
$hour = (int) date('H');
if ($hour >= 5 && $hour < 11) {
    $sapaan = "Selamat Pagi";
} elseif ($hour >= 11 && $hour < 15) {
    $sapaan = "Selamat Siang";
} elseif ($hour >= 15 && $hour < 18) {
    $sapaan = "Selamat Sore";
} else {
    $sapaan = "Selamat Malam";
}

include '../includes/header_portal.html';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

  <!-- Hero Section -->
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-700 via-indigo-600 to-violet-700 p-6 sm:p-10 text-white shadow-2xl shadow-indigo-500/20">
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-white/15 via-transparent to-transparent pointer-events-none"></div>
    <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-violet-500/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute left-1/3 -top-12 w-48 h-48 bg-indigo-400/20 rounded-full blur-2xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
      <div class="space-y-3 max-w-2xl">
        <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur-md px-3.5 py-1.5 rounded-full text-xs sm:text-sm font-medium text-indigo-100 border border-white/20 shadow-inner">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span><?= $sapaan ?>, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Anggota') ?></span>
          <?php if (!empty($userRole)): ?>
            <span class="px-2 py-0.5 rounded-full bg-white/20 text-[11px] font-semibold tracking-wide border border-white/30 uppercase">
              <?= htmlspecialchars($userRole) ?>
            </span>
          <?php endif; ?>
        </div>
        <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
          Pusat Pembelajaran & Pengetahuan Computer Club
        </h1>
        <p class="text-indigo-100 text-sm sm:text-base leading-relaxed opacity-90">
          Temukan materi, pengalaman, dan alur belajar dari sesama anggota Computer Club.
        </p>
      </div>

      <!-- Quick Action Buttons (Hanya Jelajahi Materi & Tanya AI) -->
      <div class="flex flex-wrap sm:flex-row gap-3 shrink-0 w-full md:w-auto">
        <a href="arsip_materi.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-white text-indigo-700 font-bold rounded-xl text-sm hover:bg-indigo-50 transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5">
          <i data-lucide="book-open" class="w-4 h-4"></i>Jelajahi Materi
        </a>
        <a href="rag_chat.php" class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-white/15 backdrop-blur-md text-white font-semibold rounded-xl text-sm hover:bg-white/25 transition-all border border-white/30 hover:-translate-y-0.5">
          <i data-lucide="sparkles" class="w-4 h-4 text-amber-300"></i>Tanya AI
        </a>
      </div>
    </div>
  </section>

  <!-- Stats Grid -->
  <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
    <!-- Stat 1: Materi -->
    <a href="arsip_materi.php" class="group bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-100 transition-all duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
          <i data-lucide="file-text" class="w-5 h-5"></i>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700">Materi</span>
      </div>
      <div class="mt-4">
        <p class="text-3xl font-extrabold text-gray-900 tracking-tight"><?= number_format($stats['materi']) ?></p>
        <p class="text-xs font-medium text-gray-500 mt-1">Materi Tersedia</p>
      </div>
    </a>

    <!-- Stat 2: Catatan -->
    <a href="catatan.php" class="group bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-emerald-100 transition-all duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform">
          <i data-lucide="notebook-text" class="w-5 h-5"></i>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700">Pengalaman</span>
      </div>
      <div class="mt-4">
        <p class="text-3xl font-extrabold text-gray-900 tracking-tight"><?= number_format($stats['catatan']) ?></p>
        <p class="text-xs font-medium text-gray-500 mt-1">Catatan Pengalaman</p>
      </div>
    </a>

    <!-- Stat 3: Event -->
    <a href="event.php" class="group bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-amber-100 transition-all duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center text-white shadow-md shadow-amber-500/20 group-hover:scale-105 transition-transform">
          <i data-lucide="calendar" class="w-5 h-5"></i>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Event</span>
      </div>
      <div class="mt-4">
        <p class="text-3xl font-extrabold text-gray-900 tracking-tight"><?= number_format($stats['event']) ?></p>
        <p class="text-xs font-medium text-gray-500 mt-1">Event Aktif Club</p>
      </div>
    </a>

    <!-- Stat 4: Alur Belajar -->
    <a href="alur_belajar.php" class="group bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md hover:border-violet-100 transition-all duration-200 flex flex-col justify-between">
      <div class="flex items-center justify-between">
        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 flex items-center justify-center text-white shadow-md shadow-violet-500/20 group-hover:scale-105 transition-transform">
          <i data-lucide="git-branch" class="w-5 h-5"></i>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-violet-50 text-violet-700">Learning Path</span>
      </div>
      <div class="mt-4">
        <p class="text-3xl font-extrabold text-gray-900 tracking-tight"><?= number_format($stats['alur']) ?></p>
        <p class="text-xs font-medium text-gray-500 mt-1">Alur Belajar</p>
      </div>
    </a>
  </section>

  <!-- Pengumuman Terbaru (Background Putih Bersih) -->
  <?php if ($pengumuman): ?>
  <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
    <div class="flex items-center justify-between mb-5">
      <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
        <i data-lucide="bell" class="w-5 h-5 text-indigo-600"></i>Pengumuman Terbaru
      </h2>
      <span class="text-xs text-gray-500 font-medium px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700">
        <?= count($pengumuman) ?> Informasi
      </span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php foreach ($pengumuman as $p): ?>
        <div class="bg-gray-50/80 rounded-xl p-5 border border-gray-100 shadow-xs flex flex-col justify-between">
          <div>
            <h3 class="font-bold text-gray-900 text-base mb-1"><?= htmlspecialchars($p['judul']) ?></h3>
            <p class="text-xs text-gray-400 mb-3">
              Oleh <span class="text-indigo-600 font-medium"><?= htmlspecialchars($p['nama_lengkap']) ?></span> · <?= htmlspecialchars(date('d M Y H:i', strtotime($p['tgl_dibuat']))) ?>
            </p>
            <div class="text-sm text-gray-700 leading-relaxed bg-white p-3.5 rounded-lg border border-gray-100">
              <?= nl2br(htmlspecialchars($p['isi_pengumuman'])) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Event Saya (Jika ada event aktif yang diikuti) -->
  <?php if ($events_saya): ?>
  <section class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
          <i data-lucide="calendar" class="w-5 h-5 text-amber-600"></i>Event Saya
        </h2>
        <p class="text-xs text-gray-500 mt-0.5">Event aktif yang Anda ikuti</p>
      </div>
      <a href="event.php" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-amber-600 hover:text-amber-700 transition-colors bg-amber-50 px-3.5 py-1.5 rounded-xl">
        Lihat Semua <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?php foreach ($events_saya as $ev): ?>
        <a href="event.php" class="block p-4 rounded-xl border border-amber-100 bg-amber-50/30 hover:bg-amber-50 hover:border-amber-200 transition-all group">
          <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-lg bg-amber-500 text-white flex flex-col items-center justify-center shrink-0 font-bold leading-none shadow-sm">
              <span class="text-xs uppercase font-medium"><?= date('M', strtotime($ev['tgl_mulai'])) ?></span>
              <span class="text-sm"><?= date('d', strtotime($ev['tgl_mulai'])) ?></span>
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="text-sm font-bold text-gray-900 truncate group-hover:text-amber-700 transition-colors">
                <?= htmlspecialchars($ev['nama_event']) ?>
              </h3>
              <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-gray-400 shrink-0"></i>
                <?= date('H:i', strtotime($ev['tgl_mulai'])) ?> WIB
              </p>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Materi Terbaru -->
  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-900">Materi Terbaru</h2>
        <p class="text-xs text-gray-500 mt-0.5">Konten pembelajaran yang baru diunggah</p>
      </div>
      <a href="arsip_materi.php" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition-colors bg-indigo-50 hover:bg-indigo-100 px-3.5 py-1.5 rounded-xl">
        Lihat Semua <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
      <?php foreach ($materi as $m): ?>
        <a href="detail_materi.php?id=<?= $m['id_arsip'] ?>" class="block h-full group">
          <article class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md hover:border-indigo-100 hover:-translate-y-0.5 transition-all duration-200 flex flex-col justify-between h-full">
            <div>
              <div class="flex items-center justify-between gap-2 mb-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold">
                  <?= htmlspecialchars($m['kategori'] ?: 'Umum') ?>
                </span>
                <?php if (!empty($m['tgl_buka'])): ?>
                  <span class="text-[11px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-100 font-medium">
                    Rilis <?= date('d M Y', strtotime($m['tgl_buka'])) ?>
                  </span>
                <?php endif; ?>
              </div>
              <h3 class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2 mb-2">
                <?= htmlspecialchars($m['judul_dokumen']) ?>
              </h3>
              <p class="text-xs text-gray-400 mb-2">
                oleh <?= htmlspecialchars($m['nama_lengkap']) ?>
              </p>
              <p class="text-xs text-gray-500 line-clamp-3 leading-relaxed">
                <?= htmlspecialchars(mb_strimwidth(strip_tags($m['deskripsi'] ?? ''), 0, 90, '…')) ?>
              </p>
            </div>
          </article>
        </a>
      <?php endforeach; ?>

      <?php if (!$materi): ?>
        <p class="col-span-full text-sm text-gray-400">Belum ada materi terpublikasi.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Catatan Pengalaman Terbaru -->
  <section class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-gray-900">Catatan Terbaru</h2>
        <p class="text-xs text-gray-500 mt-0.5">Pengalaman dan cerita dari anggota</p>
      </div>
      <a href="catatan.php" class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition-colors bg-indigo-50 hover:bg-indigo-100 px-3.5 py-1.5 rounded-xl">
        Lihat Semua <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
      <?php foreach ($catatan as $c): ?>
        <div class="flex items-start justify-between gap-4 px-6 py-4 hover:bg-gray-50/80 transition-colors">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-semibold">
                <?= htmlspecialchars($c['jenis_kegiatan']) ?>
              </span>
            </div>
            <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($c['judul_kegiatan']) ?></p>
            <p class="text-xs text-gray-400 mt-0.5">oleh <?= htmlspecialchars($c['nama_lengkap']) ?></p>
          </div>
          <time class="text-xs text-gray-400 shrink-0 mt-1"><?= htmlspecialchars($c['tgl_unggah']) ?></time>
        </div>
      <?php endforeach; ?>

      <?php if (!$catatan): ?>
        <p class="px-6 py-8 text-sm text-gray-400 text-center">Belum ada catatan terpublikasi.</p>
      <?php endif; ?>
    </div>
  </section>

</main>

<?php include '../includes/footer_portal.html'; ?>