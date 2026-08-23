<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Tambah Alur';
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
      <h1 class="text-xl font-bold text-gray-900">Tambah Alur Belajar</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Buat roadmap pembelajaran baru untuk anggota.</p>
  </div>
  <a href="kelola_alur.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
  </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden max-w-2xl">
  <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
    <i data-lucide="git-branch" class="w-5 h-5 text-violet-600"></i>
    <span class="font-semibold text-gray-900">Detail Alur Belajar</span>
  </div>
  <form class="p-6 space-y-5" method="post" action="proses_alur.php?action=create">

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Alur <span class="text-red-500">*</span></label>
      <input name="nama_alur" required placeholder="Nama alur pembelajaran"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Tingkat Level</label>
      <input name="tingkat_level" placeholder="Pemula / Menengah / Lanjutan"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
    </div>

    <?php if ($_SESSION['role'] === 'Super Admin'): ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
      <select name="status"
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
        <option>Draft</option>
        <option>Published</option>
      </select>
    </div>
    <?php endif; ?>

    <div class="flex gap-3 pt-2">
      <button type="submit"
        class="px-6 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white text-sm font-semibold rounded-xl hover:from-violet-500 hover:to-purple-500 transition-all shadow-md shadow-violet-500/20">
        <i data-lucide="plus" class="w-4 h-4 inline mr-1.5"></i>Tambah Alur
      </button>
      <a href="kelola_alur.php" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">Batal</a>
    </div>
  </form>
</div>
</div>

<?php include '../includes/footer.php'; ?>
