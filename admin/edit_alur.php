<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'ID alur tidak valid.'];
    header('Location: kelola_alur.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM alur_pembelajaran WHERE id_alur = :id');
$stmt->execute([':id' => $id]);
$alur = $stmt->fetch();
if (!$alur) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Alur tidak ditemukan.'];
    header('Location: kelola_alur.php');
    exit;
}

$pageTitle = 'Edit Alur';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <div class="flex items-center gap-2.5 mb-1">
      <div class="w-1 h-5 rounded-full bg-gradient-to-b from-violet-500 to-purple-500"></div>
      <h1 class="text-xl font-bold text-gray-900">Edit Alur Belajar</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Perbarui informasi alur pembelajaran.</p>
  </div>
  <a href="kelola_alur.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
  </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden max-w-2xl">
  <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
    <i data-lucide="folder-edit" class="w-5 h-5 text-violet-600"></i>
    <span class="font-semibold text-gray-900">Detail Alur</span>
  </div>
  <form class="p-6 space-y-5" method="post" action="proses_alur.php?action=edit">
    <input type="hidden" name="id_alur" value="<?= $alur['id_alur'] ?>">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Alur <span class="text-red-500">*</span></label>
      <input name="nama_alur" required value="<?= htmlspecialchars($alur['nama_alur']) ?>"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Tingkat Level</label>
      <input name="tingkat_level" value="<?= htmlspecialchars($alur['tingkat_level']) ?>"
        placeholder="Pemula / Menengah / Lanjutan"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
    </div>

    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
      <select name="status"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
        <option <?= $alur['status'] === 'Draft' ? 'selected' : '' ?>>Draft</option>
        <option <?= $alur['status'] === 'Published' ? 'selected' : '' ?>>Published</option>
      </select>
    </div>
    <?php else: ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
      <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-100 bg-gray-50">
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $alur['status'] === 'Published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
          <?= htmlspecialchars($alur['status']) ?>
        </span>
        <span class="text-xs text-gray-400">Hanya Super Admin yang dapat mengubah status.</span>
      </div>
    </div>
    <?php endif; ?>

    <div class="flex gap-3 pt-2">
      <button type="submit"
        class="px-6 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white text-sm font-semibold rounded-xl hover:from-violet-500 hover:to-purple-500 transition-all shadow-md shadow-violet-500/20">
        <i data-lucide="save" class="w-4 h-4 inline mr-1.5"></i>Simpan Perubahan
      </button>
      <a href="kelola_alur.php" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">Batal</a>
    </div>
  </form>
</div>
</div>

<?php include '../includes/footer.php'; ?>
