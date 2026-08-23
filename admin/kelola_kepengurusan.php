<?php
/**
 * kelola_kepengurusan.php
 * Daftar dan manajemen masa kepengurusan.
 * Hanya Super Admin.
 */
session_start();
require_once '../config/koneksi.php';

if (($_SESSION['role'] ?? '') !== 'Super Admin') {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Kelola Kepengurusan';
$flash     = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$daftar = $conn->query(
    "SELECT mk.*,
            p.nama_lengkap AS nama_pembuat,
            (SELECT COUNT(*) FROM arsip_materi am WHERE am.id_kepengurusan = mk.id_kepengurusan) AS jumlah_materi,
            (SELECT COUNT(*) FROM arsip_materi am WHERE am.id_kepengurusan = mk.id_kepengurusan AND am.status = 'Published') AS jumlah_published
     FROM masa_kepengurusan mk
     JOIN pengguna p ON p.id_user = mk.id_pembuat
     ORDER BY mk.status = 'Aktif' DESC, mk.tgl_mulai DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$adaAktif = !empty(array_filter($daftar, fn($k) => $k['status'] === 'Aktif'));

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
      <div class="flex items-center gap-2.5 mb-1">
        <div class="w-1 h-5 rounded-full bg-gradient-to-b from-teal-500 to-emerald-600"></div>
        <h1 class="text-xl font-bold text-gray-900">Kelola Masa Kepengurusan</h1>
      </div>
      <p class="text-sm text-gray-500 ml-3.5">
        Setiap materi terikat ke satu masa kepengurusan. Hanya satu kepengurusan yang bisa Aktif.
      </p>
    </div>
    <?php if (!$adaAktif): ?>
      <button onclick="document.getElementById('modal-buat').classList.remove('hidden')"
              class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white text-sm font-semibold rounded-xl hover:from-teal-500 hover:to-emerald-500 transition-all shadow-md shadow-teal-500/20 hover:-translate-y-0.5 self-start">
        <i data-lucide="plus" class="w-4 h-4"></i>Buat Kepengurusan Baru
      </button>
    <?php else: ?>
      <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-400 text-sm font-semibold rounded-xl cursor-not-allowed self-start"
           title="Arsipkan kepengurusan aktif terlebih dahulu sebelum membuat yang baru">
        <i data-lucide="lock" class="w-4 h-4"></i>Buat Kepengurusan Baru
      </div>
    <?php endif; ?>
  </div>

  <?php if ($flash): ?>
    <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium
      <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' :
         ($flash['type'] === 'warning' ? 'bg-amber-50 text-amber-800 border border-amber-200' :
          'bg-red-50 text-red-800 border border-red-200') ?>">
      <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'warning' ? 'alert-triangle' : 'alert-circle') ?>" class="w-4 h-4 shrink-0"></i>
      <?= htmlspecialchars($flash['msg']) ?>
    </div>
  <?php endif; ?>

  <!-- Info Banner: tidak ada kepengurusan aktif -->
  <?php if (!$adaAktif): ?>
    <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl bg-amber-50 border border-amber-200 text-sm text-amber-800">
      <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0 mt-0.5"></i>
      <div>
        <span class="font-semibold">Tidak ada masa kepengurusan aktif.</span>
        Admin tidak dapat mengunggah materi baru sampai kepengurusan baru dibuat.
      </div>
    </div>
  <?php endif; ?>

  <!-- Stats Row -->
  <?php
    $total      = count($daftar);
    $aktif      = count(array_filter($daftar, fn($k) => $k['status'] === 'Aktif'));
    $diarsipkan = count(array_filter($daftar, fn($k) => $k['status'] === 'Diarsipkan'));
    $totalMateri = array_sum(array_column($daftar, 'jumlah_materi'));
  ?>
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center shrink-0">
        <i data-lucide="layers" class="w-4.5 h-4.5 text-teal-600"></i>
      </div>
      <div><p class="text-xs text-gray-500">Total</p><p class="text-xl font-bold text-gray-900"><?= $total ?></p></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
        <i data-lucide="check-circle" class="w-4.5 h-4.5 text-emerald-600"></i>
      </div>
      <div><p class="text-xs text-gray-500">Aktif</p><p class="text-xl font-bold text-gray-900"><?= $aktif ?></p></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
        <i data-lucide="archive" class="w-4.5 h-4.5 text-gray-500"></i>
      </div>
      <div><p class="text-xs text-gray-500">Diarsipkan</p><p class="text-xl font-bold text-gray-900"><?= $diarsipkan ?></p></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
        <i data-lucide="file-text" class="w-4.5 h-4.5 text-indigo-600"></i>
      </div>
      <div><p class="text-xs text-gray-500">Total Materi</p><p class="text-xl font-bold text-gray-900"><?= $totalMateri ?></p></div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
      <span class="w-6 h-6 rounded-lg bg-teal-50 flex items-center justify-center">
        <i data-lucide="calendar-range" class="w-3.5 h-3.5 text-teal-600"></i>
      </span>
      <span class="text-sm font-bold text-gray-800">Daftar Masa Kepengurusan</span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-100">
            <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest w-8">No</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tahun Ajaran</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nama Kepengurusan</th>
            <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Materi</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tgl Mulai</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tgl Arsip</th>
            <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php if (!$daftar): ?>
            <tr>
              <td colspan="8" class="px-5 py-14 text-center">
                <i data-lucide="calendar-x" class="w-10 h-10 text-gray-200 mx-auto mb-3"></i>
                <p class="text-gray-400 text-sm font-medium">Belum ada masa kepengurusan.</p>
                <p class="text-gray-400 text-xs mt-1">Buat masa kepengurusan pertama untuk mulai mengunggah materi.</p>
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($daftar as $i => $k): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-4 text-gray-400 font-mono text-xs"><?= $i + 1 ?></td>
              <td class="px-4 py-4">
                <span class="font-bold text-gray-900"><?= htmlspecialchars($k['tahun_ajaran']) ?></span>
                <span class="block text-xs text-gray-400 mt-0.5">oleh <?= htmlspecialchars($k['nama_pembuat']) ?></span>
              </td>
              <td class="px-4 py-4 text-gray-600 text-sm">
                <?= $k['nama_kepengurusan'] ? htmlspecialchars($k['nama_kepengurusan']) : '<span class="text-gray-300">—</span>' ?>
              </td>
              <td class="px-4 py-4 text-center">
                <span class="inline-flex flex-col items-center">
                  <span class="text-base font-bold text-gray-800"><?= $k['jumlah_materi'] ?></span>
                  <span class="text-[10px] text-gray-400"><?= $k['jumlah_published'] ?> published</span>
                </span>
              </td>
              <td class="px-4 py-4">
                <?php if ($k['status'] === 'Aktif'): ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Aktif
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-semibold">
                    <i data-lucide="archive" class="w-3 h-3"></i>Diarsipkan
                  </span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-4 text-gray-400 text-xs whitespace-nowrap"><?= htmlspecialchars($k['tgl_mulai']) ?></td>
              <td class="px-4 py-4 text-gray-400 text-xs whitespace-nowrap">
                <?= $k['tgl_arsip'] ? htmlspecialchars($k['tgl_arsip']) : '<span class="text-gray-200">—</span>' ?>
              </td>
              <td class="px-5 py-4 text-center">
                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                  <?php if ($k['status'] === 'Aktif'): ?>
                    <a href="proses_kepengurusan.php?action=arsipkan&id=<?= $k['id_kepengurusan'] ?>"
                       onclick="return confirm('Arsipkan kepengurusan ini?\n\nSeluruh materi akan diarsipkan dan alur belajar yang menggunakan materi kepengurusan ini akan direset (materi dilepas dari alur).\n\nPastikan Anda sudah menyiapkan kepengurusan baru setelah ini.\n\nLanjutkan?')"
                       class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 transition-all inline-flex items-center gap-1">
                      <i data-lucide="archive" class="w-3.5 h-3.5"></i>Arsipkan
                    </a>
                  <?php endif; ?>
                  <?php if ($k['jumlah_materi'] == 0): ?>
                    <a href="proses_kepengurusan.php?action=delete&id=<?= $k['id_kepengurusan'] ?>"
                       onclick="return confirm('Hapus masa kepengurusan ini permanen?')"
                       class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-all inline-flex items-center gap-1">
                      <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Hapus
                    </a>
                  <?php else: ?>
                    <span class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-gray-50 text-gray-300 cursor-not-allowed inline-flex items-center gap-1"
                          title="Tidak dapat dihapus karena masih ada materi terkait">
                      <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Hapus
                    </span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Buat Kepengurusan Baru -->
<div id="modal-buat" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
    <div class="relative px-6 pt-6 pb-4 border-b border-gray-100">
      <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-teal-500 to-emerald-500"></div>
      <div class="flex items-start justify-between">
        <div>
          <h3 class="font-bold text-gray-900 text-base">Buat Masa Kepengurusan Baru</h3>
          <p class="text-xs text-gray-500 mt-0.5">Hanya satu masa kepengurusan yang dapat aktif dalam satu waktu.</p>
        </div>
        <button onclick="document.getElementById('modal-buat').classList.add('hidden')"
                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all -mr-1 -mt-1">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>
    </div>
    <form method="post" action="proses_kepengurusan.php?action=create" class="p-6 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Tahun Ajaran <span class="text-red-500">*</span>
        </label>
        <input name="tahun_ajaran" required
               placeholder="Contoh: 2025/2026"
               pattern="\d{4}/\d{4}"
               title="Format: YYYY/YYYY, contoh: 2025/2026"
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all">
        <p class="text-xs text-gray-400 mt-1">Format: YYYY/YYYY · Contoh: 2025/2026</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Kepengurusan (opsional)</label>
        <input name="nama_kepengurusan"
               placeholder="Contoh: Kepengurusan Periode V"
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">
          Tanggal Mulai <span class="text-red-500">*</span>
        </label>
        <input type="date" name="tgl_mulai" required
               class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent transition-all">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit"
                class="flex-1 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 text-white text-sm font-semibold rounded-xl hover:from-teal-500 hover:to-emerald-500 transition-all shadow-sm inline-flex items-center justify-center gap-2">
          <i data-lucide="plus" class="w-4 h-4"></i>Buat Kepengurusan
        </button>
        <button type="button"
                onclick="document.getElementById('modal-buat').classList.add('hidden')"
                class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">
          Batal
        </button>
      </div>
    </form>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
