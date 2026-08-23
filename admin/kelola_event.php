<?php
/**
 * kelola_event.php
 * Halaman daftar event untuk Admin & Super Admin.
 */
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Kelola Event';
$flash     = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Ambil semua event beserta info pembuat & jumlah peserta
$events = $conn->query(
    "SELECT e.*,
            p.nama_lengkap AS nama_pembuat,
            (SELECT COUNT(*) FROM event_peserta ep WHERE ep.id_event = e.id_event) AS jumlah_peserta,
            (SELECT COUNT(*) FROM event_materi em WHERE em.id_event = e.id_event) AS jumlah_materi
     FROM `event` e
     JOIN pengguna p ON p.id_user = e.id_pembuat
     ORDER BY e.status = 'Aktif' DESC, e.tgl_dibuat DESC"
)->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">

  <!-- Page Header -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
      <div class="flex items-center gap-2.5 mb-1">
        <div class="w-1 h-5 rounded-full bg-gradient-to-b from-violet-500 to-purple-600"></div>
        <h1 class="text-xl font-bold text-gray-900">Kelola Event</h1>
      </div>
      <p class="text-sm text-gray-500 ml-3.5">Event menentukan siapa yang bisa menambahkan catatan pengalaman.</p>
    </div>
    <a href="tambah_event.php"
       class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white text-sm font-semibold rounded-xl hover:from-violet-500 hover:to-purple-500 transition-all shadow-md shadow-violet-500/20 hover:-translate-y-0.5">
      <i data-lucide="calendar-plus" class="w-4 h-4"></i>Buat Event
    </a>
  </div>

  <!-- Stats Row -->
  <?php
    $total   = count($events);
    $aktif   = count(array_filter($events, fn($e) => $e['status'] === 'Aktif'));
    $selesai = count(array_filter($events, fn($e) => $e['status'] === 'Selesai'));
  ?>
  <div class="grid grid-cols-3 gap-4 mb-2">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
        <i data-lucide="calendar" class="w-5 h-5 text-violet-600"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500">Total Event</p>
        <p class="text-xl font-bold text-gray-900"><?= $total ?></p>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
        <i data-lucide="play-circle" class="w-5 h-5 text-emerald-600"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500">Aktif</p>
        <p class="text-xl font-bold text-gray-900"><?= $aktif ?></p>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
        <i data-lucide="check-circle" class="w-5 h-5 text-gray-500"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500">Selesai</p>
        <p class="text-xl font-bold text-gray-900"><?= $selesai ?></p>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
      <span class="w-6 h-6 rounded-lg bg-violet-50 flex items-center justify-center">
        <i data-lucide="calendar-days" class="w-3.5 h-3.5 text-violet-600"></i>
      </span>
      <span class="text-sm font-bold text-gray-800">Daftar Event</span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-100">
            <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest w-8">No</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nama Event</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Jenis</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Peserta</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Materi</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tgl Mulai</th>
            <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($events as $i => $e): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-4 text-gray-400 font-mono text-xs"><?= $i + 1 ?></td>
              <td class="px-4 py-4 font-medium text-gray-800 max-w-[200px]">
                <span class="line-clamp-1"><?= htmlspecialchars($e['nama_event']) ?></span>
                <span class="text-xs text-gray-400 mt-0.5 block">oleh <?= htmlspecialchars($e['nama_pembuat']) ?></span>
              </td>
              <td class="px-4 py-4">
                <?php
                  $jenisColor = match($e['jenis_event']) {
                    'Lomba'    => 'bg-rose-50 text-rose-700',
                    'Workshop' => 'bg-blue-50 text-blue-700',
                    'Pelatihan'=> 'bg-amber-50 text-amber-700',
                    'Seminar'  => 'bg-teal-50 text-teal-700',
                    default    => 'bg-gray-100 text-gray-600',
                  };
                ?>
                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold <?= $jenisColor ?>">
                  <?= htmlspecialchars($e['jenis_event']) ?>
                </span>
              </td>
              <td class="px-4 py-4 text-center">
                <span class="inline-flex items-center gap-1 text-sm font-semibold text-gray-700">
                  <i data-lucide="users" class="w-3.5 h-3.5 text-gray-400"></i>
                  <?= $e['jumlah_peserta'] ?>
                </span>
              </td>
              <td class="px-4 py-4 text-center">
                <span class="inline-flex items-center gap-1 text-sm font-semibold text-gray-700">
                  <i data-lucide="book-open" class="w-3.5 h-3.5 text-gray-400"></i>
                  <?= $e['jumlah_materi'] ?>
                </span>
              </td>
              <td class="px-4 py-4">
                <?php if ($e['status'] === 'Aktif'): ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Aktif
                  </span>
                <?php else: ?>
                  <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">Selesai</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-4 text-gray-400 text-xs whitespace-nowrap"><?= htmlspecialchars($e['tgl_mulai']) ?></td>
              <td class="px-5 py-4 text-center">
                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                  <!-- Detail -->
                  <a href="detail_event.php?id=<?= $e['id_event'] ?>"
                     class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 transition-all inline-flex items-center gap-1"
                     title="Lihat Detail">
                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>Detail
                  </a>

                  <?php if ($e['status'] === 'Aktif'): ?>
                    <!-- Edit -->
                    <a href="tambah_event.php?id=<?= $e['id_event'] ?>"
                       class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 transition-all inline-flex items-center gap-1">
                      <i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit
                    </a>
                    <!-- Tandai Selesai -->
                    <a href="proses_event.php?action=selesai&id=<?= $e['id_event'] ?>"
                       onclick="return confirm('Tandai event ini sebagai selesai? Peserta akan bisa menambahkan catatan pengalaman.')"
                       class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-violet-50 text-violet-700 hover:bg-violet-100 transition-all inline-flex items-center gap-1">
                      <i data-lucide="check-check" class="w-3.5 h-3.5"></i>Selesai
                    </a>
                  <?php endif; ?>

                  <!-- Hapus -->
                  <a href="proses_event.php?action=delete&id=<?= $e['id_event'] ?>"
                     onclick="return confirm('Hapus event ini? Semua data peserta dan materi prasyarat akan terhapus.')"
                     class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-all inline-flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Hapus
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$events): ?>
            <tr>
              <td colspan="8" class="px-5 py-14 text-center">
                <i data-lucide="calendar-x" class="w-10 h-10 text-gray-300 mx-auto mb-3"></i>
                <p class="text-gray-400 text-sm font-medium">Belum ada event.</p>
                <p class="text-gray-400 text-xs mt-1">Buat event untuk mengelola siapa yang bisa menambahkan catatan pengalaman.</p>
                <a href="tambah_event.php" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-violet-600 text-white text-xs font-semibold rounded-xl hover:bg-violet-500 transition-all">
                  <i data-lucide="plus" class="w-3.5 h-3.5"></i>Buat Event Pertama
                </a>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<?php include '../includes/footer.php'; ?>
