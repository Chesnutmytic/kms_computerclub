<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: organisasi.php');
    exit;
}

$stmt = $conn->prepare(
    "SELECT ao.*, p.nama_lengkap
     FROM arsip_organisasi ao
     JOIN pengguna p ON p.id_user = ao.id_user
     WHERE ao.id_organisasi = :id"
);
$stmt->execute([':id' => $id]);
$arsip = $stmt->fetch();

if (!$arsip) {
    header('Location: organisasi.php');
    exit;
}

// Hak akses: staff boleh preview semua status; anggota biasa hanya Published
$userRole = $_SESSION['role'] ?? '';
$isStaff  = in_array($userRole, ['Super Admin', 'Admin', 'Ketua'], true);

if ($arsip['status'] !== 'Published' && !$isStaff) {
    header('Location: organisasi.php');
    exit;
}

// Kategori yang memiliki dokumen untuk di-download
$hasDownload = in_array($arsip['kategori_organisasi'], ['Kelola Aset', 'Laporan Akhir (LPJ)', 'SOP', 'Troubleshooting', 'Panduan Kaderisasi'], true);

$katConfig = [
    'Kelola Aset'           => ['icon' => 'package',       'badge' => 'bg-blue-50 text-blue-700'],
    'Laporan Akhir (LPJ)'   => ['icon' => 'file-bar-chart','badge' => 'bg-emerald-50 text-emerald-700'],
    'Galeri Kegiatan'       => ['icon' => 'image',          'badge' => 'bg-pink-50 text-pink-700'],
    'SOP'                   => ['icon' => 'clipboard-list', 'badge' => 'bg-amber-50 text-amber-700'],
    'Troubleshooting'       => ['icon' => 'wrench',         'badge' => 'bg-red-50 text-red-700'],
    'Panduan Kaderisasi'    => ['icon' => 'users',          'badge' => 'bg-violet-50 text-violet-700'],
];
$cfg = $katConfig[$arsip['kategori_organisasi']] ?? ['icon' => 'file', 'badge' => 'bg-gray-50 text-gray-700'];

include '../includes/header_portal.html';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

  <!-- Breadcrumb + status banner -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <nav class="flex items-center gap-2 text-xs text-gray-400">
      <a href="organisasi.php" class="hover:text-violet-600 transition-colors">Arsip Organisasi</a>
      <i data-lucide="chevron-right" class="w-3 h-3"></i>
      <span class="text-gray-600 font-medium line-clamp-1"><?= htmlspecialchars($arsip['judul_dokumen']) ?></span>
    </nav>
    <?php if ($arsip['status'] !== 'Published'): ?>
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold
        <?= $arsip['status'] === 'Pending' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <i data-lucide="<?= $arsip['status'] === 'Pending' ? 'clock' : 'alert-circle' ?>" class="w-4 h-4"></i>
        Status: <strong><?= htmlspecialchars($arsip['status']) ?></strong> (Mode Preview)
      </div>
    <?php endif; ?>
  </div>



  <!-- Article Card -->
  <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Gambar (jika Galeri atau ada media) -->
    <?php if (!empty($arsip['file_media'])): ?>
      <div class="w-full max-h-80 overflow-hidden">
        <img src="../<?= htmlspecialchars($arsip['file_media']) ?>" alt="<?= htmlspecialchars($arsip['judul_dokumen']) ?>"
          class="w-full object-cover">
      </div>
    <?php endif; ?>

    <div class="p-6 sm:p-8">

      <!-- Kategori badge -->
      <div class="flex items-center gap-3 mb-4">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full <?= $cfg['badge'] ?> text-xs font-semibold">
          <i data-lucide="<?= $cfg['icon'] ?>" class="w-3 h-3"></i>
          <?= htmlspecialchars($arsip['kategori_organisasi']) ?>
        </span>
        <span class="text-xs text-gray-400"><?= date('d F Y', strtotime($arsip['tgl_unggah'])) ?></span>
      </div>

      <!-- Judul -->
      <h1 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($arsip['judul_dokumen']) ?></h1>
      <p class="text-xs text-gray-400 mb-6">Diunggah oleh <span class="font-medium text-gray-600"><?= htmlspecialchars($arsip['nama_lengkap']) ?></span></p>

      <!-- Deskripsi -->
      <?php if (!empty($arsip['deskripsi'])): ?>
        <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed mb-8">
          <?= $arsip['deskripsi'] ?>
        </div>
      <?php endif; ?>

      <!-- Galeri Kegiatan: tampilkan gambar besar -->
      <?php if ($arsip['kategori_organisasi'] === 'Galeri Kegiatan' && !empty($arsip['file_media'])): ?>
        <div class="mt-4 mb-8">
          <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <i data-lucide="image" class="w-4 h-4 text-pink-500"></i>Foto Kegiatan
          </h2>
          <img src="../<?= htmlspecialchars($arsip['file_media']) ?>" alt="Galeri <?= htmlspecialchars($arsip['judul_dokumen']) ?>"
            class="rounded-2xl border border-gray-100 shadow-sm max-w-full">
        </div>
      <?php endif; ?>

      <!-- Download Button (jika ada file dokumen dan bukan Galeri) -->
      <?php if ($hasDownload && !empty($arsip['file_path'])): ?>
        <div class="mt-6 p-4 bg-violet-50 border border-violet-100 rounded-2xl flex items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-100 flex items-center justify-center shrink-0">
              <i data-lucide="file-down" class="w-5 h-5 text-violet-600"></i>
            </div>
            <div>
              <p class="text-sm font-semibold text-gray-800">Dokumen Tersedia</p>
              <p class="text-xs text-gray-500">Klik tombol untuk mengunduh file <?= strtoupper(pathinfo($arsip['file_path'], PATHINFO_EXTENSION)) ?></p>
            </div>
          </div>
          <a href="../<?= htmlspecialchars($arsip['file_path']) ?>" download
             class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600 text-white text-sm font-semibold rounded-xl hover:bg-violet-500 transition-all shadow-sm whitespace-nowrap">
            <i data-lucide="download" class="w-4 h-4"></i>Download
          </a>
        </div>
      <?php endif; ?>

      <!-- Kembali -->
      <div class="mt-8 pt-6 border-t border-gray-100">
        <a href="organisasi.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-violet-600 transition-colors font-medium">
          <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali ke Arsip Organisasi
        </a>
      </div>

    </div>
  </article>

</main>

<?php include '../includes/footer_portal.html'; ?>
