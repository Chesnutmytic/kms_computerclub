<?php
/**
 * detail_event.php
 * Detail event: info event, daftar peserta + progress baca materi prasyarat.
 * Hanya Admin & Super Admin.
 */
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'ID event tidak valid.'];
    header('Location: kelola_event.php');
    exit;
}

// Ambil data event
$stmtEv = $conn->prepare(
    "SELECT e.*, p.nama_lengkap AS nama_pembuat
     FROM `event` e
     JOIN pengguna p ON p.id_user = e.id_pembuat
     WHERE e.id_event = :id"
);
$stmtEv->execute([':id' => $id]);
$event = $stmtEv->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Event tidak ditemukan.'];
    header('Location: kelola_event.php');
    exit;
}

// Ambil materi prasyarat event
$materiList = $conn->prepare(
    "SELECT a.id_arsip, a.judul_dokumen, a.kategori
     FROM event_materi em
     JOIN arsip_materi a ON a.id_arsip = em.id_arsip
     WHERE em.id_event = :id"
);
$materiList->execute([':id' => $id]);
$materiList = $materiList->fetchAll(PDO::FETCH_ASSOC);
$materiIds  = array_column($materiList, 'id_arsip');

// Ambil peserta beserta status baca materi (progress_belajar)
$peserta = $conn->prepare(
    "SELECT p.id_user, p.nama_lengkap, p.kelas, p.role
     FROM event_peserta ep
     JOIN pengguna p ON p.id_user = ep.id_user
     WHERE ep.id_event = :id
     ORDER BY p.nama_lengkap"
);
$peserta->execute([':id' => $id]);
$peserta = $peserta->fetchAll(PDO::FETCH_ASSOC);

// Ambil progress baca per peserta, per materi
$progressMap = [];
if (!empty($materiIds) && !empty($peserta)) {
    $pesertaIds  = array_column($peserta, 'id_user');
    $inMateri    = implode(',', array_fill(0, count($materiIds), '?'));
    $inPeserta   = implode(',', array_fill(0, count($pesertaIds), '?'));
    $stmtProg    = $conn->prepare(
        "SELECT id_user, id_arsip FROM progress_belajar
         WHERE id_arsip IN ($inMateri) AND id_user IN ($inPeserta)"
    );
    $stmtProg->execute(array_merge($materiIds, $pesertaIds));
    foreach ($stmtProg->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $progressMap[$row['id_user']][$row['id_arsip']] = true;
    }
}

// Ambil catatan pengalaman yang sudah dibuat dari event ini
$catatanList = $conn->prepare(
    "SELECT cp.id_catatan, cp.judul_kegiatan, cp.status, p.nama_lengkap
     FROM catatan_pengalaman cp
     JOIN pengguna p ON p.id_user = cp.id_user
     WHERE cp.id_event = :id
     ORDER BY cp.tgl_unggah DESC"
);
$catatanList->execute([':id' => $id]);
$catatanList = $catatanList->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Detail Event';
$flash     = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">

  <!-- Back + Header -->
  <div class="flex items-start gap-4 mb-2">
    <a href="kelola_event.php"
       class="w-9 h-9 rounded-xl border border-gray-200 bg-white flex items-center justify-center text-gray-500 hover:text-gray-800 hover:border-gray-300 transition-all shadow-sm shrink-0 mt-0.5">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>
    </a>
    <div class="flex-1 min-w-0">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <div class="flex items-center gap-2.5 mb-0.5">
            <div class="w-1 h-5 rounded-full bg-gradient-to-b from-violet-500 to-purple-600"></div>
            <h1 class="text-xl font-bold text-gray-900 truncate"><?= htmlspecialchars($event['nama_event']) ?></h1>
          </div>
          <p class="text-sm text-gray-500 ml-3.5">
            <?= htmlspecialchars($event['jenis_event']) ?> ·
            Dibuat oleh <?= htmlspecialchars($event['nama_pembuat']) ?> ·
            <?= htmlspecialchars($event['tgl_mulai']) ?>
          </p>
        </div>
        <div class="flex items-center gap-2 ml-3.5 sm:ml-0">
          <?php if ($event['status'] === 'Aktif'): ?>
            <a href="tambah_event.php?id=<?= $id ?>"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-all">
              <i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit
            </a>
            <a href="proses_event.php?action=selesai&id=<?= $id ?>"
               onclick="return confirm('Tandai event ini sebagai selesai? Peserta akan bisa menambahkan catatan pengalaman.')"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-violet-600 text-white hover:bg-violet-500 shadow-sm shadow-violet-200 transition-all">
              <i data-lucide="check-check" class="w-3.5 h-3.5"></i>Tandai Selesai
            </a>
          <?php else: ?>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 text-gray-600">
              <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>Sudah Selesai
            </span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Event Info Card -->
  <?php if ($event['deskripsi']): ?>
  <div class="bg-violet-50 border border-violet-100 rounded-2xl p-5">
    <p class="text-sm text-violet-800 leading-relaxed"><?= nl2br(htmlspecialchars($event['deskripsi'])) ?></p>
  </div>
  <?php endif; ?>

  <!-- Stats Row -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
      <p class="text-xs text-gray-500 mb-1">Peserta</p>
      <p class="text-2xl font-bold text-gray-900"><?= count($peserta) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
      <p class="text-xs text-gray-500 mb-1">Materi Prasyarat</p>
      <p class="text-2xl font-bold text-gray-900"><?= count($materiList) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
      <p class="text-xs text-gray-500 mb-1">Catatan Dibuat</p>
      <p class="text-2xl font-bold text-gray-900"><?= count($catatanList) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
      <p class="text-xs text-gray-500 mb-1">Status</p>
      <?php if ($event['status'] === 'Aktif'): ?>
        <span class="inline-flex items-center gap-1 text-sm font-bold text-emerald-600">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>Aktif
        </span>
      <?php else: ?>
        <span class="text-sm font-bold text-gray-500">Selesai</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Progress Peserta -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
      <span class="w-6 h-6 rounded-lg bg-violet-50 flex items-center justify-center">
        <i data-lucide="users" class="w-3.5 h-3.5 text-violet-600"></i>
      </span>
      <span class="text-sm font-bold text-gray-800">Progress Peserta</span>
      <span class="ml-auto text-xs text-gray-400">
        <?php if (!empty($materiList)): ?>
          Kolom ✓ = sudah baca materi tersebut
        <?php else: ?>
          Tidak ada materi prasyarat
        <?php endif; ?>
      </span>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-100">
            <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest w-8">No</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Peserta</th>
            <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Kelas</th>
            <?php foreach ($materiList as $m): ?>
              <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest max-w-[120px]">
                <span class="block truncate" title="<?= htmlspecialchars($m['judul_dokumen']) ?>">
                  <?= htmlspecialchars(mb_strimwidth($m['judul_dokumen'], 0, 20, '…')) ?>
                </span>
              </th>
            <?php endforeach; ?>
            <th class="px-4 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Lengkap</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php if (!$peserta): ?>
            <tr>
              <td colspan="<?= 4 + count($materiList) ?>" class="px-5 py-10 text-center text-gray-400 text-sm">
                Belum ada peserta di-assign ke event ini.
              </td>
            </tr>
          <?php endif; ?>
          <?php foreach ($peserta as $i => $p): ?>
            <?php
              $totalMateri  = count($materiList);
              $sudahBaca    = 0;
              foreach ($materiList as $m) {
                  if (!empty($progressMap[$p['id_user']][$m['id_arsip']])) {
                      $sudahBaca++;
                  }
              }
              $lengkap = $totalMateri > 0 && $sudahBaca === $totalMateri;
            ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-4 text-gray-400 font-mono text-xs"><?= $i + 1 ?></td>
              <td class="px-4 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center text-xs font-bold shrink-0">
                    <?= mb_strtoupper(mb_substr($p['nama_lengkap'], 0, 1)) ?>
                  </div>
                  <div>
                    <p class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($p['nama_lengkap']) ?></p>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($p['role']) ?></p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-4 text-gray-500 text-sm"><?= htmlspecialchars($p['kelas']) ?></td>
              <?php foreach ($materiList as $m): ?>
                <td class="px-4 py-4 text-center">
                  <?php if (!empty($progressMap[$p['id_user']][$m['id_arsip']])): ?>
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 text-emerald-600">
                      <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-gray-400">
                      <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                    </span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td class="px-4 py-4 text-center">
                <?php if ($totalMateri === 0): ?>
                  <span class="text-xs text-gray-400">—</span>
                <?php elseif ($lengkap): ?>
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                    <i data-lucide="check-circle-2" class="w-3 h-3"></i>Lengkap
                  </span>
                <?php else: ?>
                  <span class="text-xs text-gray-500 font-medium"><?= $sudahBaca ?>/<?= $totalMateri ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Catatan Pengalaman dari Event ini -->
  <?php if (!empty($catatanList)): ?>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
      <span class="w-6 h-6 rounded-lg bg-emerald-50 flex items-center justify-center">
        <i data-lucide="notebook-text" class="w-3.5 h-3.5 text-emerald-600"></i>
      </span>
      <span class="text-sm font-bold text-gray-800">Catatan Pengalaman dari Event Ini</span>
    </div>
    <div class="divide-y divide-gray-50">
      <?php foreach ($catatanList as $c): ?>
        <div class="px-5 py-4 flex items-center justify-between gap-4">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($c['judul_kegiatan']) ?></p>
            <p class="text-xs text-gray-400 mt-0.5">oleh <?= htmlspecialchars($c['nama_lengkap']) ?></p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
              <?= $c['status'] === 'Published' ? 'bg-emerald-50 text-emerald-700' : ($c['status'] === 'Pending' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') ?>">
              <?= htmlspecialchars($c['status']) ?>
            </span>
            <a href="../portal/detail_catatan.php?id=<?= $c['id_catatan'] ?>" target="_blank"
               class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 transition-all inline-flex items-center gap-1">
              <i data-lucide="eye" class="w-3 h-3"></i>View
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
