<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM alur_pembelajaran WHERE id_alur = ?');
$stmt->execute([$id]);
$alur = $stmt->fetch();

if (!$alur) {
    header('Location: kelola_alur.php');
    exit;
}

$pageTitle = 'Detail Alur';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$selectedStmt = $conn->prepare(
    'SELECT d.id_detail, a.judul_dokumen, a.kategori
     FROM detail_alur d
     JOIN arsip_materi a ON a.id_arsip = d.id_arsip
     WHERE d.id_alur = ?'
);
$selectedStmt->execute([$id]);
$selected = $selectedStmt->fetchAll();

$materi = $conn->query(
    "SELECT id_arsip, judul_dokumen, kategori
     FROM arsip_materi
     WHERE status = 'Published'
     ORDER BY judul_dokumen"
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">
<div class="mb-6">
  <a href="kelola_alur.php" class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-400 hover:text-indigo-600 transition-colors mb-3 group">
    <i data-lucide="arrow-left" class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"></i>Kembali ke Kelola Alur
  </a>
  <div class="flex items-center gap-2.5">
    <div class="w-1 h-5 rounded-full bg-gradient-to-b from-violet-500 to-purple-500"></div>
    <h1 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($alur['nama_alur']) ?></h1>
  </div>
  <p class="text-sm text-gray-500 ml-3.5 mt-0.5">Kelola materi yang termasuk dalam alur belajar ini.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

  <!-- Tambah Materi Form -->
  <div class="lg:col-span-2">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6">
      <h2 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
        <span class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center"><i data-lucide="plus-circle" class="w-3.5 h-3.5 text-indigo-600"></i></span>Tambah Materi ke Alur
      </h2>
      <form method="post" action="proses_alur.php?action=add_detail" class="space-y-4">
        <input type="hidden" name="id_alur" value="<?= $id ?>">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Pilih Materi Published</label>
          <select name="id_arsip" required
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
            <option value="">— Pilih materi —</option>
            <?php foreach ($materi as $m): ?>
              <option value="<?= $m['id_arsip'] ?>"><?= htmlspecialchars($m['judul_dokumen']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit"
          class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm">
          Tambahkan ke Alur
        </button>
      </form>
    </div>
  </div>

  <!-- Materi dalam Alur -->
  <div class="lg:col-span-3">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900 flex items-center gap-2">
          <i data-lucide="list" class="w-5 h-5 text-indigo-600"></i>Materi dalam Alur
        </h2>
        <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold"><?= count($selected) ?> materi</span>
      </div>
      <ul class="divide-y divide-gray-50">
        <?php foreach ($selected as $m): ?>
          <li class="px-6 py-3.5 flex items-center justify-between gap-3">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($m['judul_dokumen']) ?></p>
              <?php if ($m['kategori']): ?>
                <p class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($m['kategori']) ?></p>
              <?php endif; ?>
            </div>
            <a href="proses_alur.php?action=remove_detail&id_detail=<?= $m['id_detail'] ?>"
               onclick="return confirm('Hapus materi dari alur ini?')"
               class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-all inline-flex items-center gap-1">
              <i data-lucide="trash-2" class="w-3 h-3"></i>Hapus
            </a>
          </li>
        <?php endforeach; ?>
        <?php if (!$selected): ?>
          <li class="px-6 py-10 text-center">
            <i data-lucide="file-question" class="w-10 h-10 text-gray-300 mx-auto mb-2"></i>
            <p class="text-gray-400 text-sm">Belum ada materi dalam alur ini.</p>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>