<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$alur = $conn->query(
    "SELECT a.*, p.nama_lengkap
     FROM alur_pembelajaran a
     JOIN pengguna p ON p.id_user = a.id_user
     WHERE a.status = 'Published'
     ORDER BY a.tgl_dibuat DESC, a.id_alur DESC"
)->fetchAll();

include '../includes/header_portal.html';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Header -->
  <div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Alur Belajar</h1>
    <p class="text-gray-500 mt-1">Pilih jalur pembelajaran yang sesuai dengan levelmu.</p>
  </div>

  <!-- Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php
    $levelColors = [
      'Pemula'   => ['bg-emerald-50 text-emerald-700', 'from-emerald-400 to-emerald-500'],
      'Menengah' => ['bg-amber-50 text-amber-700',     'from-amber-400 to-amber-500'],
      'Lanjutan' => ['bg-rose-50 text-rose-700',       'from-rose-400 to-rose-500'],
    ];
    foreach ($alur as $a):
      $level = $a['tingkat_level'] ?: 'Semua Level';
      [$badgeCls, $gradCls] = $levelColors[$level] ?? ['bg-indigo-50 text-indigo-700', 'from-indigo-400 to-indigo-500'];
    ?>
      <article class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-200 flex flex-col overflow-hidden group">
        <!-- Card top accent -->
        <div class="h-1.5 bg-gradient-to-r <?= $gradCls ?>"></div>
        <div class="p-6 flex flex-col flex-1">
          <div class="flex items-start justify-between mb-3">
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg <?= $badgeCls ?> text-xs font-semibold">
              <?= htmlspecialchars($level) ?>
            </span>
            <i data-lucide="git-branch" class="w-5 h-5 text-gray-300 group-hover:text-indigo-400 transition-colors"></i>
          </div>
          <h2 class="text-base font-bold text-gray-900 mb-2 flex-1"><?= htmlspecialchars($a['nama_alur']) ?></h2>
          <p class="text-xs text-gray-400 mb-5">
            Dibuat oleh <span class="font-medium text-gray-500"><?= htmlspecialchars($a['nama_lengkap']) ?></span>
            &nbsp;·&nbsp; <?= htmlspecialchars($a['tgl_dibuat']) ?>
          </p>
          <a href="detail_alur.php?id=<?= $a['id_alur'] ?>"
             class="mt-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-indigo-600 transition-all group-hover:bg-indigo-600">
            <i data-lucide="arrow-right" class="w-4 h-4"></i>Lihat Detail
          </a>
        </div>
      </article>
    <?php endforeach; ?>

    <?php if (!$alur): ?>
      <div class="col-span-full py-16 text-center bg-white rounded-2xl border border-gray-100">
        <i data-lucide="git-branch" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
        <p class="text-gray-500 font-medium">Belum ada alur belajar yang dipublikasikan.</p>
      </div>
    <?php endif; ?>
  </div>

</main>

<?php include '../includes/footer_portal.html'; ?>