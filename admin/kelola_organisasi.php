<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Kelola Organisasi';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$data = $conn->query(
    "SELECT ao.*, p.nama_lengkap pengunggah
     FROM arsip_organisasi ao
     JOIN pengguna p ON p.id_user = ao.id_user
     ORDER BY ao.status = 'Pending' DESC, ao.id_organisasi DESC"
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <div class="flex items-center gap-2.5 mb-1">
      <div class="w-1 h-5 rounded-full bg-gradient-to-b from-violet-500 to-fuchsia-500"></div>
      <h1 class="text-xl font-bold text-gray-900">Kelola Arsip Organisasi</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Kelola dokumen &amp; arsip organisasi Computer Club.</p>
  </div>
  <a href="tambah_organisasi.php"
     class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white text-sm font-semibold rounded-xl hover:from-violet-500 hover:to-fuchsia-500 transition-all shadow-md shadow-violet-500/20 hover:-translate-y-0.5">
    <i data-lucide="folder-plus" class="w-4 h-4"></i>Tambah Arsip
  </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
  <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
    <span class="w-6 h-6 rounded-lg bg-violet-50 flex items-center justify-center">
      <i data-lucide="folder-archive" class="w-3.5 h-3.5 text-violet-600"></i>
    </span>
    <span class="text-sm font-bold text-gray-800">Daftar Arsip Organisasi</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="bg-gray-50 border-b border-gray-100">
          <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest w-8">No</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Judul</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pengunggah</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Kategori</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tanggal</th>
          <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach ($data as $i => $d): ?>
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-4 text-gray-400 font-mono text-xs"><?= $i + 1 ?></td>
            <td class="px-4 py-4 font-medium text-gray-800 max-w-[250px]">
              <span class="line-clamp-2"><?= htmlspecialchars($d['judul_dokumen']) ?></span>
              <?php if ($d['status'] === 'Rejected' && !empty($d['alasan_reject'])): ?>
                <div class="mt-2 text-xs text-red-600 bg-red-50 p-2 rounded-lg border border-red-100">
                  <span class="font-semibold block mb-0.5">Alasan Ditolak:</span>
                  <?= nl2br(htmlspecialchars($d['alasan_reject'])) ?>
                </div>
              <?php endif; ?>
            </td>
            <td class="px-4 py-4 text-gray-500"><?= htmlspecialchars($d['pengunggah']) ?></td>
            <td class="px-4 py-4">
              <?php if ($d['kategori_organisasi']): ?>
                <span class="px-2.5 py-1 rounded-lg bg-violet-50 text-violet-700 text-xs font-semibold">
                  <?= htmlspecialchars($d['kategori_organisasi']) ?>
                </span>
              <?php else: ?>
                <span class="text-gray-400 text-xs">—</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                <?= $d['status'] === 'Published' ? 'bg-emerald-50 text-emerald-700' : ($d['status'] === 'Pending' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') ?>">
                <?= htmlspecialchars($d['status']) ?>
              </span>
            </td>
            <td class="px-4 py-4 text-gray-400 text-xs whitespace-nowrap"><?= htmlspecialchars($d['tgl_unggah']) ?></td>
            <td class="px-5 py-4 text-center">
              <div class="flex items-center justify-center gap-1.5 flex-wrap">

                <!-- Tombol View — selalu tampil untuk Super Admin dan Admin -->
                <a href="../portal/detail_organisasi.php?id=<?= $d['id_organisasi'] ?>"
                   target="_blank"
                   class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 hover:bg-sky-100 transition-all inline-flex items-center gap-1"
                   title="Lihat Detail Arsip">
                  <i data-lucide="eye" class="w-3.5 h-3.5"></i>View
                </a>

                <?php if ($d['status'] === 'Pending' && $_SESSION['role'] === 'Super Admin'): ?>
                  <!-- Approve & Reject hanya Super Admin -->
                  <a href="proses_organisasi.php?action=approve&id=<?= $d['id_organisasi'] ?>"
                     onclick="return confirm('Approve arsip organisasi ini?')"
                     class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-all inline-flex items-center gap-1">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>Approve
                  </a>
                  <button onclick="document.getElementById('modalReject<?= $d['id_organisasi'] ?>').classList.remove('hidden')"
                     class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-all inline-flex items-center gap-1">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>Reject
                  </button>

                  <!-- Reject Modal -->
                  <div id="modalReject<?= $d['id_organisasi'] ?>" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 text-left">
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-[fadeInUp_0.25s_ease]">
                      <div class="relative px-6 pt-6 pb-5 border-b border-gray-100">
                        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-500 via-rose-500 to-pink-500"></div>
                        <div class="flex items-start gap-4">
                          <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-alert" class="w-5 h-5 text-red-500"></i>
                          </div>
                          <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-gray-900 text-base">Tolak Arsip Organisasi</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Berikan alasan agar pengunggah bisa memperbaiki</p>
                          </div>
                          <button onclick="document.getElementById('modalReject<?= $d['id_organisasi'] ?>').classList.add('hidden')"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all -mr-1 -mt-1">
                            <i data-lucide="x" class="w-4 h-4"></i>
                          </button>
                        </div>
                      </div>
                      <form method="post" action="proses_organisasi.php?action=reject" class="p-6">
                        <input type="hidden" name="id_organisasi" value="<?= $d['id_organisasi'] ?>">
                        <div class="mb-5">
                          <label class="block text-sm font-semibold text-gray-700 mb-2">Alasan Penolakan <span class="text-red-500">*</span></label>
                          <textarea name="alasan_reject" required rows="4" placeholder="Contoh: Dokumen tidak sesuai format, silakan upload ulang dengan format yang benar..."
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:border-red-400 transition-all resize-none bg-gray-50/50 placeholder:text-gray-400"></textarea>
                          <p class="text-[11px] text-gray-400 mt-1.5 flex items-center gap-1">
                            <i data-lucide="info" class="w-3 h-3"></i>Alasan akan ditampilkan kepada pengunggah
                          </p>
                        </div>
                        <div class="flex gap-3">
                          <button type="button" onclick="document.getElementById('modalReject<?= $d['id_organisasi'] ?>').classList.add('hidden')"
                            class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-200 transition-all">
                            Batal
                          </button>
                          <button type="submit"
                            class="flex-1 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 text-white text-sm font-semibold rounded-xl hover:from-red-500 hover:to-rose-500 transition-all shadow-lg shadow-red-500/25 inline-flex items-center justify-center gap-1.5">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>Tolak Arsip
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>

                <?php elseif ($d['status'] === 'Pending'): ?>
                  <!-- Admin melihat Pending: hanya teks -->
                  <span class="text-xs text-gray-400 italic">Menunggu Super Admin</span>

                <?php else: ?>
                  <!-- Published / Rejected: Edit & Hapus -->
                  <a href="edit_organisasi.php?id=<?= $d['id_organisasi'] ?>"
                     class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 transition-all inline-flex items-center gap-1">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>Edit
                  </a>
                  <?php if ($_SESSION['role'] === 'Super Admin'): ?>
                  <a href="proses_organisasi.php?action=delete&id=<?= $d['id_organisasi'] ?>"
                     onclick="return confirm('Hapus arsip ini?')"
                     class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-all inline-flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Hapus
                  </a>
                  <?php endif; ?>
                <?php endif; ?>

              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$data): ?>
          <tr>
            <td colspan="7" class="px-5 py-12 text-center">
              <i data-lucide="folder-open" class="w-10 h-10 text-gray-300 mx-auto mb-2"></i>
              <p class="text-gray-400 text-sm">Belum ada arsip organisasi.</p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
