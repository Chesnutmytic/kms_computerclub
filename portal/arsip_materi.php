<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$userRole = $_SESSION['role'] ?? '';
$isStaff  = in_array($userRole, ['Super Admin', 'Admin', 'Ketua'], true);

$q            = trim($_GET['q'] ?? '');
$kategori      = trim($_GET['kategori'] ?? '');
$tag           = trim($_GET['tag'] ?? '');
$kepengurusanId= (int)($_GET['kepengurusan'] ?? 0);
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 12;
$where        = " WHERE am.status = 'Published'";
if (!$isStaff) {
    $where .= " AND (am.tgl_buka IS NULL OR am.tgl_buka <= CURDATE())";
}
$params       = [];

// Deteksi jika query dimulai dengan # → perlakukan sebagai tag search
$searchTag = $tag;
if ($q !== '' && str_starts_with($q, '#')) {
    $searchTag = $q;
    $q = '';
}

if ($q !== '') {
    $where .= " AND (am.judul_dokumen LIKE :q OR am.kategori LIKE :q OR am.tags LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($kategori !== '') {
    $where .= " AND am.kategori = :kategori";
    $params[':kategori'] = $kategori;
}
if ($searchTag !== '') {
    $tagClean = ltrim($searchTag, '#');
    $where .= " AND am.tags LIKE :tag";
    $params[':tag'] = '%#' . $tagClean . '%';
}
if ($kepengurusanId > 0) {
    $where .= " AND am.id_kepengurusan = :kepengurusan_id";
    $params[':kepengurusan_id'] = $kepengurusanId;
}

$schedCond = $isStaff ? "" : " AND (tgl_buka IS NULL OR tgl_buka <= CURDATE())";

$categories = $conn->query(
    "SELECT DISTINCT kategori FROM arsip_materi
     WHERE status = 'Published' $schedCond AND kategori IS NOT NULL AND kategori <> ''
     ORDER BY kategori"
)->fetchAll(PDO::FETCH_COLUMN);

// Daftar masa kepengurusan untuk dropdown filter
$kepengurusanList = $conn->query(
    "SELECT * FROM masa_kepengurusan ORDER BY status = 'Aktif' DESC, tgl_mulai DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Ambil semua tag unik dari arsip materi yang published
$rawTags = $conn->query(
    "SELECT tags FROM arsip_materi WHERE status = 'Published' $schedCond AND tags IS NOT NULL AND tags <> ''"
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

$counter = $conn->prepare("SELECT COUNT(*) FROM arsip_materi am $where");
$counter->execute($params);
$total = (int)$counter->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page  = min($page, $pages);

$stmt = $conn->prepare(
    "SELECT am.*, p.nama_lengkap, mk.tahun_ajaran, mk.nama_kepengurusan, mk.status AS status_kepengurusan
     FROM arsip_materi am
     JOIN pengguna p ON p.id_user = am.id_user
     LEFT JOIN masa_kepengurusan mk ON mk.id_kepengurusan = am.id_kepengurusan
     $where
     ORDER BY (mk.status = 'Aktif') DESC, mk.tgl_mulai DESC, am.tgl_unggah DESC, am.id_arsip DESC
     LIMIT $perPage OFFSET " . (($page - 1) * $perPage)
);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kelompokkan materi per Masa Kepengurusan
$grouped = [];
foreach ($items as $m) {
    $groupKey = !empty($m['tahun_ajaran'])
        ? $m['tahun_ajaran'] . ($m['nama_kepengurusan'] ? ' - ' . $m['nama_kepengurusan'] : '')
        : 'Umum / Tanpa Periode';
    
    if (!isset($grouped[$groupKey])) {
        $grouped[$groupKey] = [
            'tahun_ajaran'       => $m['tahun_ajaran'] ?? null,
            'nama_kepengurusan'  => $m['nama_kepengurusan'] ?? null,
            'status_kepengurusan'=> $m['status_kepengurusan'] ?? null,
            'materi'             => []
        ];
    }
    $grouped[$groupKey]['materi'][] = $m;
}

include '../includes/header_portal.html';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Header -->
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Arsip Materi</h1>
    <p class="text-gray-500 mt-1">Koleksi materi pembelajaran yang dikelompokkan per Masa Kepengurusan / Tahun Ajaran.</p>
  </div>

  <!-- Search & Filter -->
  <form class="flex flex-col sm:flex-row gap-3 mb-4 bg-white border border-gray-100 rounded-2xl shadow-sm p-4" method="get" id="form-search-materi">
    <div class="relative flex-1">
      <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
      <input name="q" id="input-q-materi" value="<?= htmlspecialchars($searchTag !== '' ? $searchTag : $q) ?>" placeholder="Cari judul, kategori, atau #tag…"
        class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
    </div>
    
    <!-- Filter Kepengurusan -->
    <select name="kepengurusan"
      class="px-3 py-2 rounded-xl border border-gray-200 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
      <option value="">Semua Masa Kepengurusan</option>
      <?php foreach ($kepengurusanList as $k): ?>
        <option value="<?= $k['id_kepengurusan'] ?>" <?= $kepengurusanId === (int)$k['id_kepengurusan'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($k['tahun_ajaran']) ?> <?= $k['status'] === 'Aktif' ? '(Aktif)' : '(Arsip)' ?>
        </option>
      <?php endforeach; ?>
    </select>

    <!-- Filter Kategori -->
    <select name="kategori"
      class="px-3 py-2 rounded-xl border border-gray-200 text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
      <option value="">Semua Kategori</option>
      <?php foreach ($categories as $item): ?>
        <option value="<?= htmlspecialchars($item) ?>" <?= $kategori === $item ? 'selected' : '' ?>>
          <?= htmlspecialchars($item) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <div class="flex gap-2">
      <button class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm">
        <i data-lucide="search" class="w-4 h-4 inline mr-1"></i>Cari
      </button>
      <a href="arsip_materi.php" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">
        Reset
      </a>
    </div>
  </form>

  <!-- Tag Cloud -->
  <?php if (!empty($allTags)): ?>
  <div class="mb-8 bg-white border border-gray-100 rounded-2xl shadow-sm p-4">
    <div class="flex items-center gap-2 mb-3">
      <i data-lucide="tag" class="w-4 h-4 text-indigo-500"></i>
      <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Filter by Tag</span>
    </div>
    <div class="flex flex-wrap gap-2" id="tag-cloud-materi">
      <?php foreach ($allTags as $tagName => $tagCount): ?>
        <?php $isActive = (ltrim($searchTag, '#') === $tagName); ?>
        <a href="?<?= htmlspecialchars(http_build_query(['q' => '#'.$tagName, 'kategori' => $kategori, 'kepengurusan' => $kepengurusanId])) ?>"
           class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold transition-all duration-150
                  <?= $isActive
                      ? 'bg-indigo-600 text-white shadow-sm scale-105'
                      : 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100 hover:scale-105' ?>">
          <span>#<?= htmlspecialchars($tagName) ?></span>
          <span class="<?= $isActive ? 'bg-indigo-500' : 'bg-indigo-100 text-indigo-500' ?> rounded-full px-1.5 py-0.5 text-[10px] font-bold"><?= $tagCount ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Results count -->
  <?php if ($q || $kategori || $searchTag || $kepengurusanId): ?>
    <p class="text-sm text-gray-500 mb-6">
      Menampilkan <span class="font-semibold text-gray-900"><?= $total ?></span> materi
      <?= $q ? "untuk \"<em>" . htmlspecialchars($q) . "</em>\"" : '' ?>
      <?= $searchTag ? "dengan tag <span class=\"inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold\">" . htmlspecialchars($searchTag) . "</span>" : '' ?>
      <?= $kategori ? "di kategori \"<em>" . htmlspecialchars($kategori) . "</em>\"" : '' ?>
    </p>
  <?php endif; ?>

  <!-- Grouped Sections Grid -->
  <?php if (!$items): ?>
    <div class="py-16 text-center bg-white rounded-2xl border border-gray-100">
      <i data-lucide="file-search" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
      <p class="text-gray-500 font-medium">Materi tidak ditemukan.</p>
      <p class="text-sm text-gray-400 mt-1">Coba ubah filter atau kata kunci pencarian.</p>
    </div>
  <?php else: ?>
    <div class="space-y-10">
      <?php foreach ($grouped as $groupTitle => $groupData): ?>
        <section class="space-y-4">
          <!-- Group Header -->
          <div class="flex items-center justify-between border-b border-gray-200 pb-3">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl <?= ($groupData['status_kepengurusan'] === 'Aktif') ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' ?> flex items-center justify-center font-bold">
                <i data-lucide="<?= ($groupData['status_kepengurusan'] === 'Aktif') ? 'calendar-check' : 'archive' ?>" class="w-4 h-4"></i>
              </div>
              <div>
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                  <?= htmlspecialchars($groupTitle) ?>
                  <?php if ($groupData['status_kepengurusan'] === 'Aktif'): ?>
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                      Aktif
                    </span>
                  <?php elseif ($groupData['status_kepengurusan'] === 'Diarsipkan'): ?>
                    <span class="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs font-semibold border border-gray-200">
                      Arsip
                    </span>
                  <?php endif; ?>
                </h2>
              </div>
            </div>
            <span class="text-xs text-gray-400 font-medium"><?= count($groupData['materi']) ?> materi</span>
          </div>

          <!-- Cards Grid in this Group -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($groupData['materi'] as $m): 
                $tglB = !empty($m['tgl_buka']) ? $m['tgl_buka'] : $m['tgl_unggah'];
                $isSchedFuture = ($tglB > date('Y-m-d'));
            ?>
              <article class="bg-white rounded-2xl border <?= $isSchedFuture ? 'border-amber-200 bg-amber-50/20' : 'border-gray-100' ?> shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col overflow-hidden">
                <div class="p-5 flex flex-col flex-1">
                  <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold">
                      <?= htmlspecialchars($m['kategori'] ?: 'Umum') ?>
                    </span>
                    <?php if ($isSchedFuture): ?>
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 text-[11px] font-bold border border-amber-200" title="Belum dibuka untuk Anggota">
                        <i data-lucide="clock" class="w-3 h-3 text-amber-600"></i>
                        Dijadwalkan: <?= date('d/m/Y', strtotime($tglB)) ?>
                      </span>
                    <?php elseif (!empty($m['tahun_ajaran'])): ?>
                      <span class="text-[11px] font-semibold text-gray-400">
                        <?= htmlspecialchars($m['tahun_ajaran']) ?>
                      </span>
                    <?php endif; ?>
                  </div>

                  <h3 class="text-base font-semibold text-gray-900 mb-2 flex-1 line-clamp-2">
                    <?= htmlspecialchars($m['judul_dokumen']) ?>
                  </h3>
                  <p class="text-xs text-gray-400 mb-3">
                    <span class="font-medium text-gray-500">oleh <?= htmlspecialchars($m['nama_lengkap']) ?></span>
                    &nbsp;·&nbsp; Jadwal: <?= date('d/m/Y', strtotime($tglB)) ?>
                  </p>
                  <p class="text-sm text-gray-600 line-clamp-3 mb-4">
                    <?= htmlspecialchars(mb_strimwidth(strip_tags($m['deskripsi'] ?? ''), 0, 160, '…')) ?>
                  </p>

                  <?php if (!empty($m['tags'])): ?>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                      <?php foreach (explode(' ', $m['tags']) as $t): ?>
                        <?php $t = trim($t); if (!$t || !str_starts_with($t, '#')) continue; $tName = ltrim($t, '#'); ?>
                        <a href="?<?= htmlspecialchars(http_build_query(['q' => $t, 'kategori' => $kategori, 'kepengurusan' => $kepengurusanId])) ?>"
                           class="text-[11px] font-medium px-2 py-0.5 rounded-md transition-all
                                  <?= (ltrim($searchTag, '#') === $tName) ? 'bg-indigo-600 text-white' : 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100' ?>">
                          <?= htmlspecialchars($t) ?>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($m['file_path'])): ?>
                    <a href="detail_materi.php?id=<?= $m['id_arsip'] ?>"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm self-start mt-auto">
                      <i data-lucide="eye" class="w-3.5 h-3.5"></i>Lihat Detail
                    </a>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
    <nav class="mt-10 flex justify-center gap-1.5">
      <?php for ($n = 1; $n <= $pages; $n++): ?>
        <a href="?<?= htmlspecialchars(http_build_query(['q' => $searchTag !== '' ? $searchTag : $q, 'kategori' => $kategori, 'kepengurusan' => $kepengurusanId, 'page' => $n])) ?>"
           class="w-9 h-9 flex items-center justify-center rounded-xl text-sm font-medium transition-all
                  <?= $n === $page ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
          <?= $n ?>
        </a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>

</main>

<?php include '../includes/footer_portal.html'; ?>