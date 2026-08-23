<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

// Filter kategori
$kategoriList = ['Semua', 'Laporan Akhir (LPJ)', 'Galeri Kegiatan', 'SOP', 'Troubleshooting', 'Panduan Kaderisasi'];
$filterKat = $_GET['kategori'] ?? 'Semua';
$search    = trim($_GET['q'] ?? '');

if ($filterKat !== 'Semua' && in_array($filterKat, $kategoriList, true)) {
    if ($search !== '') {
        $stmt = $conn->prepare(
            "SELECT * FROM arsip_organisasi
             WHERE status = 'Published'
               AND kategori_organisasi = :kat
               AND judul_dokumen LIKE :q
             ORDER BY tgl_unggah DESC"
        );
        $stmt->execute([':kat' => $filterKat, ':q' => '%' . $search . '%']);
    } else {
        $stmt = $conn->prepare(
            "SELECT * FROM arsip_organisasi
             WHERE status = 'Published' AND kategori_organisasi = :kat
             ORDER BY tgl_unggah DESC"
        );
        $stmt->execute([':kat' => $filterKat]);
    }
} else {
    if ($search !== '') {
        $stmt = $conn->prepare(
            "SELECT * FROM arsip_organisasi
             WHERE status = 'Published' AND judul_dokumen LIKE :q
             ORDER BY tgl_unggah DESC"
        );
        $stmt->execute([':q' => '%' . $search . '%']);
    } else {
        $stmt = $conn->query("SELECT * FROM arsip_organisasi WHERE status = 'Published' ORDER BY tgl_unggah DESC");
    }
}
$arsipList = $stmt->fetchAll();

// Konfigurasi ikon & warna per kategori
$katConfig = [
    'Kelola Aset'           => ['icon' => 'package',      'badge' => 'bg-blue-50 text-blue-700',     'card' => 'from-blue-400 to-blue-500'],
    'Laporan Akhir (LPJ)'   => ['icon' => 'file-bar-chart','badge' => 'bg-emerald-50 text-emerald-700','card' => 'from-emerald-400 to-emerald-500'],
    'Galeri Kegiatan'       => ['icon' => 'image',         'badge' => 'bg-pink-50 text-pink-700',     'card' => 'from-pink-400 to-pink-500'],
    'SOP'                   => ['icon' => 'clipboard-list','badge' => 'bg-amber-50 text-amber-700',   'card' => 'from-amber-400 to-amber-500'],
    'Troubleshooting'       => ['icon' => 'wrench',        'badge' => 'bg-red-50 text-red-700',       'card' => 'from-red-400 to-red-500'],
    'Panduan Kaderisasi'    => ['icon' => 'users',         'badge' => 'bg-violet-50 text-violet-700', 'card' => 'from-violet-400 to-violet-500'],
];

include '../includes/header_portal.html';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Header -->
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Arsip Organisasi</h1>
    <p class="text-gray-500 mt-1">Dokumen, SOP, LPJ, galeri, dan panduan organisasi Computer Club.</p>
  </div>

  <!-- Search & Filters -->
  <form method="get" class="mb-6 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
      <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari dokumen..."
        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 transition-all bg-white">
      <input type="hidden" name="kategori" value="<?= htmlspecialchars($filterKat) ?>">
    </div>
    <button type="submit" class="px-5 py-2.5 bg-violet-600 text-white text-sm font-semibold rounded-xl hover:bg-violet-500 transition-all">Cari</button>
  </form>

  <!-- Tabs Kategori -->
  <div class="flex flex-wrap gap-2 mb-8">
    <?php foreach ($kategoriList as $kat): ?>
      <a href="?kategori=<?= urlencode($kat) ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
         class="px-4 py-2 rounded-full text-xs font-semibold transition-all
           <?= $filterKat === $kat
               ? 'bg-violet-600 text-white shadow-md'
               : 'bg-white text-gray-600 border border-gray-200 hover:border-violet-400 hover:text-violet-700' ?>">
        <?= htmlspecialchars($kat) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Grid Arsip -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($arsipList as $a):
      $cfg = $katConfig[$a['kategori_organisasi']] ?? ['icon' => 'file', 'badge' => 'bg-gray-50 text-gray-700', 'card' => 'from-gray-400 to-gray-500'];
    ?>
      <article class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col overflow-hidden group">
        <!-- Gambar (jika ada) -->
        <?php if (!empty($a['file_media'])): ?>
          <div class="h-40 overflow-hidden">
            <img src="../<?= htmlspecialchars($a['file_media']) ?>" alt="<?= htmlspecialchars($a['judul_dokumen']) ?>"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
          </div>
        <?php else: ?>
          <div class="h-1.5 bg-gradient-to-r <?= $cfg['card'] ?>"></div>
        <?php endif; ?>

        <div class="p-5 flex flex-col flex-1">
          <div class="flex items-start justify-between mb-3">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg <?= $cfg['badge'] ?> text-xs font-semibold">
              <i data-lucide="<?= $cfg['icon'] ?>" class="w-3 h-3"></i>
              <?= htmlspecialchars($a['kategori_organisasi']) ?>
            </span>
          </div>
          <h2 class="text-sm font-bold text-gray-900 mb-2 flex-1 leading-snug line-clamp-2"><?= htmlspecialchars($a['judul_dokumen']) ?></h2>
          <?php if (!empty($a['deskripsi'])): ?>
            <p class="text-xs text-gray-400 mb-4 line-clamp-2"><?= strip_tags($a['deskripsi']) ?></p>
          <?php endif; ?>
          <p class="text-xs text-gray-400 mb-4"><?= date('d M Y', strtotime($a['tgl_unggah'])) ?></p>
          <a href="detail_organisasi.php?id=<?= $a['id_organisasi'] ?>"
             class="mt-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-900 text-white text-xs font-semibold rounded-xl hover:bg-violet-600 transition-all group-hover:bg-violet-600">
            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>Lihat Detail
          </a>
        </div>
      </article>
    <?php endforeach; ?>

    <?php if (!$arsipList): ?>
      <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-gray-100">
        <i data-lucide="folder-open" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
        <p class="text-gray-500 font-medium">Belum ada arsip organisasi yang dipublikasikan.</p>
        <?php if ($search || $filterKat !== 'Semua'): ?>
          <a href="organisasi.php" class="mt-3 inline-block text-sm text-violet-600 hover:underline">Hapus filter</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

</main>

<?php include '../includes/footer_portal.html'; ?>
