<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Dashboard';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$count = static fn(string $sql): int => (int)$conn->query($sql)->fetch()['total'];
$summary = [
    'anggota' => $count('SELECT COUNT(*) AS total FROM pengguna'),
    'materi'  => $count("SELECT COUNT(*) AS total FROM arsip_materi WHERE status='Published'"),
    'catatan' => $count("SELECT COUNT(*) AS total FROM catatan_pengalaman WHERE status='Published'"),
    'alur'    => $count("SELECT COUNT(*) AS total FROM alur_pembelajaran WHERE status='Published'")
];

$materi = $conn->query(
    'SELECT am.judul_dokumen, am.status, am.kategori, am.tgl_unggah, p.nama_lengkap
     FROM arsip_materi am
     JOIN pengguna p ON p.id_user = am.id_user
     ORDER BY am.id_arsip DESC
     LIMIT 5'
)->fetchAll();

$catatan = $conn->query(
    'SELECT cp.judul_kegiatan, cp.jenis_kegiatan, cp.status, cp.tgl_unggah, p.nama_lengkap
     FROM catatan_pengalaman cp
     JOIN pengguna p ON p.id_user = cp.id_user
     ORDER BY cp.id_catatan DESC
     LIMIT 5'
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

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">

  <!-- ===== Page Header ===== -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <div class="flex items-center gap-2.5 mb-1">
        <div class="w-1 h-5 rounded-full bg-gradient-to-b from-indigo-500 to-violet-500"></div>
        <h1 class="text-xl font-bold text-gray-900">Dashboard Pengurus</h1>
      </div>
      <p class="text-sm text-gray-500 ml-3.5">Ringkasan aktivitas KMS Computer Club — <?= date('d F Y') ?></p>
    </div>
    <a href="tambah_materi.php"
       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-indigo-600 to-violet-600 text-white hover:from-indigo-500 hover:to-violet-500 transition-all shadow-md shadow-indigo-500/20 hover:-translate-y-0.5">
      <i data-lucide="plus" class="w-4 h-4"></i>
      <span>Tambah Materi</span>
    </a>
  </div>

  <!-- ===== Stat Cards ===== -->
  <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
    <?php
    $cards = [
      ['anggota', 'Total Anggota',     'users',         'bg-indigo-500', 'bg-indigo-50',  'text-indigo-600', 'border-indigo-100'],
      ['materi',  'Materi Published',  'file-text',     'bg-emerald-500','bg-emerald-50', 'text-emerald-600','border-emerald-100'],
      ['catatan', 'Catatan Published', 'notebook-text', 'bg-sky-500',    'bg-sky-50',     'text-sky-600',    'border-sky-100'],
      ['alur',    'Alur Published',    'git-branch',    'bg-violet-500', 'bg-violet-50',  'text-violet-600', 'border-violet-100'],
    ];
    foreach ($cards as [$key, $label, $icon, $iconBg, $iconBgLight, $iconText, $border]): ?>
    <div class="stat-card bg-white rounded-2xl border <?= $border ?> shadow-card p-5">
      <div class="flex items-center justify-between mb-3">
        <div class="<?= $iconBgLight ?> <?= $iconText ?> w-10 h-10 rounded-xl flex items-center justify-center">
          <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
        </div>
        <span class="text-2xl font-extrabold text-gray-900 tabular-nums"><?= number_format($summary[$key]) ?></span>
      </div>
      <p class="text-sm font-medium text-gray-500"><?= $label ?></p>
      <div class="mt-2 h-1 rounded-full bg-gray-100 overflow-hidden">
        <div class="h-full rounded-full <?= $iconBg ?> opacity-60" style="width: <?= min(100, $summary[$key] * 10) ?>%"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ===== Quick Actions ===== -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <?php
    $actions = [
      ['tambah_materi.php',      'file-plus',  'Tambah Materi',   'indigo'],
      ['tambah_catatan.php',     'pen-line',   'Tambah Catatan',  'emerald'],
      ['user_management.php',    'user-plus',  'Kelola Pengguna', 'sky'],
      ['kelola_pengumuman.php',  'megaphone',  'Pengumuman',      'violet'],
    ];
    foreach ($actions as [$href, $icon, $label, $c]): ?>
    <a href="<?= $href ?>"
       class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-white border border-gray-100 shadow-card
              hover:border-<?= $c ?>-200 hover:bg-<?= $c ?>-50 text-gray-600 hover:text-<?= $c ?>-700
              transition-all group text-sm font-medium hover:-translate-y-0.5">
      <div class="w-7 h-7 rounded-lg bg-gray-100 group-hover:bg-<?= $c ?>-100 flex items-center justify-center transition-all shrink-0">
        <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i>
      </div>
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- ===== Pengumuman ===== -->
  <?php if ($pengumuman): ?>
  <section>
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-sm font-bold text-gray-700 flex items-center gap-2">
        <i data-lucide="megaphone" class="w-4 h-4 text-indigo-500"></i>
        Pengumuman Terbaru
      </h2>
      <a href="kelola_pengumuman.php"
         class="text-xs text-indigo-600 hover:text-indigo-500 font-semibold transition-colors flex items-center gap-1">
        Kelola <i data-lucide="arrow-right" class="w-3 h-3"></i>
      </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <?php
      $pillColors = ['bg-indigo-100 text-indigo-700','bg-violet-100 text-violet-700','bg-sky-100 text-sky-700'];
      $borderColors = ['border-indigo-100','border-violet-100','border-sky-100'];
      foreach ($pengumuman as $idx => $p):
      ?>
      <div class="bg-white rounded-2xl border <?= $borderColors[$idx % 3] ?> shadow-card p-5 hover:-translate-y-0.5 transition-all">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold mb-3 <?= $pillColors[$idx % 3] ?>">
          <i data-lucide="megaphone" class="w-3 h-3"></i> Pengumuman
        </span>
        <h3 class="font-bold text-gray-900 text-sm mb-1 line-clamp-1"><?= htmlspecialchars($p['judul']) ?></h3>
        <p class="text-xs text-gray-400 mb-3">
          <span class="text-gray-500 font-medium"><?= htmlspecialchars($p['nama_lengkap']) ?></span>
          &middot; <?= date('d M Y', strtotime($p['tgl_dibuat'])) ?>
        </p>
        <p class="text-sm text-gray-600 line-clamp-2"><?= nl2br(htmlspecialchars($p['isi_pengumuman'])) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ===== Data Tables ===== -->
  <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">

    <!-- Materi Terbaru -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
          <span class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center">
            <i data-lucide="file-text" class="w-3.5 h-3.5 text-indigo-600"></i>
          </span>
          5 Materi Terbaru
        </h2>
        <a href="kelola_materi.php" class="text-xs text-indigo-600 hover:text-indigo-500 font-semibold transition-colors flex items-center gap-1">
          Lihat semua <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
              <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Judul</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pengunggah</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tgl</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($materi as $row):
              $sCls = $row['status'] === 'Published'
                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                : ($row['status'] === 'Pending'
                   ? 'bg-amber-50 text-amber-700 border border-amber-200'
                   : 'bg-red-50 text-red-700 border border-red-200');
            ?>
            <tr class="hover:bg-gray-50 transition-colors group">
              <td class="px-5 py-3.5">
                <p class="font-semibold text-gray-800 text-xs max-w-[140px] truncate"><?= htmlspecialchars($row['judul_dokumen']) ?></p>
                <?php if ($row['kategori']): ?>
                  <span class="text-[10px] text-gray-400"><?= htmlspecialchars($row['kategori']) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5 text-xs text-gray-500"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
              <td class="px-4 py-3.5">
                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold <?= $sCls ?>"><?= htmlspecialchars($row['status']) ?></span>
              </td>
              <td class="px-4 py-3.5 text-[10px] text-gray-400"><?= date('d/m/y', strtotime($row['tgl_unggah'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$materi): ?>
            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400 text-sm">Belum ada materi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Catatan Terbaru -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
          <span class="w-6 h-6 rounded-lg bg-emerald-50 flex items-center justify-center">
            <i data-lucide="notebook-text" class="w-3.5 h-3.5 text-emerald-600"></i>
          </span>
          5 Catatan Terbaru
        </h2>
        <a href="kelola_catatan.php" class="text-xs text-indigo-600 hover:text-indigo-500 font-semibold transition-colors flex items-center gap-1">
          Lihat semua <i data-lucide="arrow-right" class="w-3 h-3"></i>
        </a>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
              <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Judul</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pengunggah</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
              <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tgl</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($catatan as $row):
              $sCls = $row['status'] === 'Published'
                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                : ($row['status'] === 'Pending'
                   ? 'bg-amber-50 text-amber-700 border border-amber-200'
                   : 'bg-red-50 text-red-700 border border-red-200');
            ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5">
                <p class="font-semibold text-gray-800 text-xs max-w-[140px] truncate"><?= htmlspecialchars($row['judul_kegiatan']) ?></p>
                <?php if ($row['jenis_kegiatan']): ?>
                  <span class="text-[10px] text-gray-400"><?= htmlspecialchars($row['jenis_kegiatan']) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5 text-xs text-gray-500"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
              <td class="px-4 py-3.5">
                <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold <?= $sCls ?>"><?= htmlspecialchars($row['status']) ?></span>
              </td>
              <td class="px-4 py-3.5 text-[10px] text-gray-400"><?= date('d/m/y', strtotime($row['tgl_unggah'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$catatan): ?>
            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400 text-sm">Belum ada catatan.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div><!-- end fade-in -->

<?php include '../includes/footer.php'; ?>