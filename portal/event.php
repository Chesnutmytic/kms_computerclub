<?php
/**
 * portal/event.php
 * Halaman untuk user melihat event yang mereka ikuti,
 * membaca materi prasyarat, dan mengakses tambah catatan.
 */
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$myId = (int) $_SESSION['id_user'];

// Ambil semua event yang diikuti user (peserta)
$stmtEvents = $conn->prepare(
    "SELECT e.*, p.nama_lengkap AS nama_pembuat
     FROM `event` e
     JOIN event_peserta ep ON ep.id_event = e.id_event
     JOIN pengguna p ON p.id_user = e.id_pembuat
     WHERE ep.id_user = :u
     ORDER BY e.status = 'Aktif' DESC, e.tgl_mulai DESC"
);
$stmtEvents->execute([':u' => $myId]);
$events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

// Untuk setiap event, ambil materi prasyarat & progress user
$eventDetails = [];
foreach ($events as $ev) {
    $evId = $ev['id_event'];

    // Materi prasyarat
    $stmtMateri = $conn->prepare(
        "SELECT a.id_arsip, a.judul_dokumen, a.kategori
         FROM event_materi em
         JOIN arsip_materi a ON a.id_arsip = em.id_arsip
         WHERE em.id_event = :ev"
    );
    $stmtMateri->execute([':ev' => $evId]);
    $materiList = $stmtMateri->fetchAll(PDO::FETCH_ASSOC);

    // Progress baca user untuk materi event ini
    $stmtProg = $conn->prepare(
        "SELECT id_arsip FROM progress_belajar
         WHERE id_user = :u AND id_arsip IN (
             SELECT id_arsip FROM event_materi WHERE id_event = :ev
         )"
    );
    $stmtProg->execute([':u' => $myId, ':ev' => $evId]);
    $sudahBaca = $stmtProg->fetchAll(PDO::FETCH_COLUMN);

    // Cek apakah user sudah membuat catatan untuk event ini
    $stmtCatatan = $conn->prepare(
        "SELECT id_catatan, status FROM catatan_pengalaman
         WHERE id_user = :u AND id_event = :ev LIMIT 1"
    );
    $stmtCatatan->execute([':u' => $myId, ':ev' => $evId]);
    $catatanSaya = $stmtCatatan->fetch(PDO::FETCH_ASSOC);

    $eventDetails[$evId] = [
        'event'       => $ev,
        'materi'      => $materiList,
        'sudah_baca'  => $sudahBaca,
        'catatan'     => $catatanSaya,
    ];
}
?>
<?php include '../includes/header_portal.html'; ?>

<main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Event Saya</h1>
      <p class="text-gray-500 mt-1">Event yang kamu ikuti dan akses catatan pengalaman.</p>
    </div>
    <div class="text-sm text-gray-400">
      <?= count($events) ?> event diikuti
    </div>
  </div>

  <?php if ($flash): ?>
    <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium
      <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' ?>">
      <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <?php if (!$events): ?>
    <!-- Empty State -->
    <div class="py-20 text-center bg-white rounded-2xl border border-gray-100 shadow-sm">
      <div class="w-16 h-16 rounded-2xl bg-violet-50 flex items-center justify-center mx-auto mb-4">
        <i data-lucide="calendar-x" class="w-8 h-8 text-violet-400"></i>
      </div>
      <h2 class="text-lg font-semibold text-gray-700">Belum ada event</h2>
      <p class="text-sm text-gray-400 mt-2 max-w-xs mx-auto">
        Kamu belum di-assign ke event apapun oleh admin. Hubungi admin untuk informasi lebih lanjut.
      </p>
    </div>
  <?php endif; ?>

  <div class="space-y-6">
    <?php foreach ($eventDetails as $evId => $detail): ?>
      <?php
        $ev         = $detail['event'];
        $materi     = $detail['materi'];
        $sudahBaca  = $detail['sudah_baca'];
        $catatan    = $detail['catatan'];
        $isSelesai  = ($ev['status'] === 'Selesai');
        $allRead    = !empty($materi) && count($sudahBaca) === count($materi);
        $noMateri   = empty($materi);
        $bisaCatatan = $isSelesai && !$catatan;

        $jenisColor = match($ev['jenis_event']) {
          'Lomba'     => 'bg-rose-50 text-rose-700 border-rose-200',
          'Workshop'  => 'bg-blue-50 text-blue-700 border-blue-200',
          'Pelatihan' => 'bg-amber-50 text-amber-700 border-amber-200',
          'Seminar'   => 'bg-teal-50 text-teal-700 border-teal-200',
          default     => 'bg-gray-100 text-gray-600 border-gray-200',
        };
      ?>
      <article class="bg-white rounded-2xl border <?= $isSelesai ? 'border-gray-100' : 'border-emerald-100' ?> shadow-sm overflow-hidden">

        <!-- Status stripe -->
        <?php if (!$isSelesai): ?>
          <div class="h-1 bg-gradient-to-r from-emerald-400 to-teal-400"></div>
        <?php endif; ?>

        <div class="p-6">
          <!-- Event Header -->
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
            <div>
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg border text-xs font-semibold <?= $jenisColor ?>">
                  <?= htmlspecialchars($ev['jenis_event']) ?>
                </span>
                <?php if ($isSelesai): ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                    <i data-lucide="check-circle" class="w-3 h-3"></i>Selesai
                    <?php if ($ev['tgl_selesai']): ?>
                      · <?= htmlspecialchars($ev['tgl_selesai']) ?>
                    <?php endif; ?>
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Berlangsung
                  </span>
                <?php endif; ?>
              </div>
              <h2 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($ev['nama_event']) ?></h2>
              <p class="text-xs text-gray-400 mt-1">
                Dibuat oleh <?= htmlspecialchars($ev['nama_pembuat']) ?> ·
                Mulai <?= htmlspecialchars($ev['tgl_mulai']) ?>
              </p>
              <?php if ($ev['deskripsi']): ?>
                <p class="text-sm text-gray-600 mt-2 leading-relaxed"><?= nl2br(htmlspecialchars($ev['deskripsi'])) ?></p>
              <?php endif; ?>
            </div>

            <!-- CTA Catatan -->
            <div class="shrink-0">
              <?php if ($catatan): ?>
                <!-- Sudah membuat catatan -->
                <div class="flex flex-col items-end gap-2">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold
                    <?= $catatan['status'] === 'Published' ? 'bg-emerald-50 text-emerald-700' : ($catatan['status'] === 'Pending' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') ?>">
                    <i data-lucide="notebook-pen" class="w-3.5 h-3.5"></i>
                    Catatan <?= htmlspecialchars($catatan['status']) ?>
                  </span>
                  <a href="detail_catatan.php?id=<?= $catatan['id_catatan'] ?>"
                     class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline transition-colors">
                    Lihat catatan →
                  </a>
                </div>
              <?php elseif ($bisaCatatan): ?>
                <!-- Event selesai, belum buat catatan -->
                <a href="tambah_catatan.php?id_event=<?= $evId ?>"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm shadow-indigo-100 whitespace-nowrap">
                  <i data-lucide="plus" class="w-4 h-4"></i>Tambah Catatan
                </a>
              <?php else: ?>
                <!-- Event belum selesai -->
                <div class="text-center">
                  <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-gray-100 text-gray-500">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>Menunggu selesai
                  </div>
                  <p class="text-[11px] text-gray-400 mt-1.5">Catatan bisa dibuat setelah event selesai</p>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Materi Prasyarat -->
          <?php if (!$noMateri): ?>
            <div class="border-t border-gray-100 pt-5">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <i data-lucide="book-open" class="w-4 h-4 text-gray-500"></i>
                  <span class="text-sm font-semibold text-gray-700">Materi Prasyarat</span>
                  <span class="text-xs text-gray-400">(<?= count($sudahBaca) ?>/<?= count($materi) ?> dibaca)</span>
                </div>
                <?php if ($allRead): ?>
                  <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>Semua terbaca
                  </span>
                <?php endif; ?>
              </div>

              <!-- Progress bar -->
              <div class="w-full bg-gray-100 rounded-full h-1.5 mb-4">
                <?php $pct = count($materi) > 0 ? round(count($sudahBaca) / count($materi) * 100) : 0; ?>
                <div class="h-1.5 rounded-full transition-all duration-500
                     <?= $allRead ? 'bg-emerald-500' : 'bg-indigo-500' ?>"
                     style="width: <?= $pct ?>%"></div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <?php foreach ($materi as $m): ?>
                  <?php $sudah = in_array($m['id_arsip'], $sudahBaca, false); ?>
                  <a href="detail_materi.php?id=<?= $m['id_arsip'] ?>"
                     class="flex items-center gap-3 p-3 rounded-xl border transition-all
                            <?= $sudah ? 'border-emerald-200 bg-emerald-50 hover:bg-emerald-100' : 'border-gray-100 bg-gray-50 hover:bg-gray-100' ?>">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                                <?= $sudah ? 'bg-emerald-100' : 'bg-white border border-gray-200' ?>">
                      <i data-lucide="<?= $sudah ? 'check' : 'file-text' ?>"
                         class="w-3.5 h-3.5 <?= $sudah ? 'text-emerald-600' : 'text-gray-400' ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium <?= $sudah ? 'text-emerald-800' : 'text-gray-700' ?> truncate">
                        <?= htmlspecialchars($m['judul_dokumen']) ?>
                      </p>
                      <p class="text-xs <?= $sudah ? 'text-emerald-600' : 'text-gray-400' ?>">
                        <?= htmlspecialchars($m['kategori'] ?? 'Umum') ?>
                        <?= $sudah ? '· Sudah dibaca' : '· Belum dibaca' ?>
                      </p>
                    </div>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 <?= $sudah ? 'text-emerald-400' : 'text-gray-400' ?> shrink-0"></i>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php else: ?>
            <div class="border-t border-gray-100 pt-4">
              <p class="text-sm text-gray-400 flex items-center gap-2">
                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                Tidak ada materi prasyarat untuk event ini.
              </p>
            </div>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</main>

<!-- Close dropdown when clicking outside -->
<script>
  document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdown-event');
    const btn = document.getElementById('btn-tambah-catatan');
    if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });
</script>

<?php include '../includes/footer_portal.html'; ?>
