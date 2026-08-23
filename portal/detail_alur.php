<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT a.*, p.nama_lengkap
     FROM alur_pembelajaran a
     JOIN pengguna p ON p.id_user = a.id_user
     WHERE a.id_alur = :id AND a.status = 'Published'"
);
$stmt->execute([':id' => $id]);
$alur = $stmt->fetch();

if (!$alur) {
    header('Location: alur_belajar.php');
    exit;
}

$userRole = $_SESSION['role'] ?? '';
$isStaff  = in_array($userRole, ['Super Admin', 'Admin', 'Ketua'], true);

$whereMateri = "WHERE d.id_alur = :id AND am.status = 'Published'";
if (!$isStaff) {
    $whereMateri .= " AND (am.tgl_buka IS NULL OR am.tgl_buka <= CURDATE())";
}

$stmt = $conn->prepare(
    "SELECT am.id_arsip, am.judul_dokumen, am.kategori, am.deskripsi, am.file_path, am.file_media, am.link_tautan, am.tgl_buka, am.tgl_unggah
     FROM detail_alur d
     JOIN arsip_materi am ON am.id_arsip = d.id_arsip
     $whereMateri
     ORDER BY d.id_detail ASC"
);
$stmt->execute([':id' => $id]);
$materi = $stmt->fetchAll();

// Get user progress
$stmtP = $conn->prepare("SELECT id_arsip FROM progress_belajar WHERE id_user = ?");
$stmtP->execute([$_SESSION['id_user']]);
$progress = $stmtP->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header_portal.html';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Back button -->
  <a href="alur_belajar.php" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors mb-6">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali ke Alur Belajar
  </a>

  <!-- Alur Header Card -->
  <?php
    $level = $alur['tingkat_level'] ?: 'Semua Level';
    $gradMap = [
      'Pemula'   => 'from-emerald-600 to-emerald-700',
      'Menengah' => 'from-amber-500 to-orange-600',
      'Lanjutan' => 'from-rose-600 to-rose-700',
    ];
    $grad = $gradMap[$level] ?? 'from-indigo-600 to-indigo-700';
  ?>
  <div class="relative overflow-hidden bg-gradient-to-br <?= $grad ?> rounded-2xl shadow-xl p-7 mb-8 text-white">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(ellipse_at_top_right,_white_0%,_transparent_60%)]"></div>
    <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-xs font-semibold mb-4">
      <?= htmlspecialchars($level) ?>
    </span>
    <h1 class="text-2xl font-bold mb-2"><?= htmlspecialchars($alur['nama_alur']) ?></h1>
    <p class="text-white/75 text-sm">
      Dibuat oleh <?= htmlspecialchars($alur['nama_lengkap']) ?>
      &nbsp;·&nbsp; <?= htmlspecialchars($alur['tgl_dibuat']) ?>
    </p>
    <div class="mt-5 flex items-center gap-3">
      <div class="flex items-center gap-1.5 bg-white/15 rounded-xl px-3 py-2 text-sm font-medium">
        <i data-lucide="file-text" class="w-4 h-4"></i>
        <?= count($materi) ?> Materi
      </div>
    </div>
  </div>

  <!-- Materi List -->
  <div class="mb-4">
    <h2 class="text-lg font-bold text-gray-900">Materi dalam Alur</h2>
    <p class="text-sm text-gray-500 mt-0.5">Daftar materi yang direkomendasikan dalam jalur ini.</p>
  </div>

  <div class="space-y-3">
    <?php 
      $locked = false;
      foreach ($materi as $i => $m): 
        $isCompleted = in_array($m['id_arsip'], $progress);
    ?>
      <div class="bg-white rounded-2xl border <?= $locked ? 'border-gray-200 opacity-60' : 'border-gray-100 shadow-sm hover:shadow-md transition-all' ?> p-5 relative overflow-hidden">
        
        <?php if ($locked): ?>
          <div class="absolute inset-0 bg-gray-50/50 backdrop-blur-[1px] z-10 flex items-center justify-center">
            <div class="bg-white/90 px-4 py-2 rounded-xl shadow-sm border border-gray-200 flex items-center gap-2 text-sm font-semibold text-gray-600">
              <i data-lucide="lock" class="w-4 h-4"></i>Terkunci
            </div>
          </div>
        <?php endif; ?>

        <div class="flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl <?= $isCompleted ? 'bg-emerald-50 text-emerald-600' : 'bg-indigo-50 text-indigo-600' ?> flex items-center justify-center font-bold text-sm shrink-0">
            <?php if ($isCompleted): ?>
              <i data-lucide="check" class="w-5 h-5"></i>
            <?php else: ?>
              <?= $i + 1 ?>
            <?php endif; ?>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-0.5 flex-wrap">
              <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide"><?= htmlspecialchars($m['kategori'] ?: 'Umum') ?></span>
              <?php 
                $tglBuka = !empty($m['tgl_buka']) ? $m['tgl_buka'] : $m['tgl_unggah'];
                if ($tglBuka > date('Y-m-d')):
              ?>
                <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 text-[10px] font-bold border border-amber-200">
                  <i data-lucide="clock" class="w-3 h-3 inline mr-0.5 text-amber-600"></i>Dijadwalkan: <?= date('d/m/Y', strtotime($tglBuka)) ?>
                </span>
              <?php endif; ?>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($m['judul_dokumen']) ?></h3>
            <?php if ($m['deskripsi']): ?>
              <p class="text-xs text-gray-500 mt-0.5 line-clamp-1"><?= htmlspecialchars(mb_strimwidth(strip_tags($m['deskripsi']), 0, 100, '…')) ?></p>
            <?php endif; ?>
          </div>
          
          <div class="shrink-0 flex gap-2">
            <?php if ($m['link_tautan']): ?>
              <a href="<?= $locked ? '#' : 'proses_progress.php?id=' . $m['id_arsip'] . '&url=' . urlencode($m['link_tautan']) ?>"
                 <?= $locked ? '' : 'target="_blank" rel="noopener"' ?>
                 class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-200 text-gray-700 text-xs font-semibold rounded-xl hover:bg-gray-50 transition-all shadow-sm">
                <i data-lucide="link" class="w-3.5 h-3.5"></i>Link
              </a>
            <?php endif; ?>
            
            <?php if ($m['file_path'] || $m['file_media']): ?>
              <a href="<?= $locked ? '#' : 'proses_progress.php?id=' . $m['id_arsip'] . '&url=' . urlencode('detail_materi.php?id=' . $m['id_arsip']) ?>"
                 class="inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm <?= $locked ? 'cursor-not-allowed opacity-80' : '' ?>">
                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>Buka Materi
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php 
        // Jika materi ini belum selesai, materi berikutnya akan terkunci
        if (!$isCompleted) $locked = true;
      endforeach; 
    ?>

    <?php if (!$materi): ?>
      <div class="py-12 text-center bg-white rounded-2xl border border-gray-100">
        <i data-lucide="file-x" class="w-10 h-10 text-gray-300 mx-auto mb-3"></i>
        <p class="text-gray-500 font-medium text-sm">Belum ada materi dalam alur ini.</p>
      </div>
    <?php endif; ?>
  </div>

</main>

<?php include '../includes/footer_portal.html'; ?>