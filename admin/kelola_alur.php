<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Kelola Alur';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$data = $conn->query(
    'SELECT a.*, p.nama_lengkap pembuat
     FROM alur_pembelajaran a
     JOIN pengguna p ON p.id_user = a.id_user
     ORDER BY a.id_alur DESC'
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <div class="flex items-center gap-2.5 mb-1">
      <div class="w-1 h-5 rounded-full bg-gradient-to-b from-violet-500 to-purple-500"></div>
      <h1 class="text-xl font-bold text-gray-900">Kelola Alur Belajar</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Susun roadmap dan konten pembelajaran anggota.</p>
  </div>
  <a href="tambah_alur.php"
    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white text-sm font-semibold rounded-xl hover:from-violet-500 hover:to-purple-500 transition-all shadow-md shadow-violet-500/20 hover:-translate-y-0.5">
    <i data-lucide="plus" class="w-4 h-4"></i>Tambah Alur
  </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
  <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
    <span class="w-6 h-6 rounded-lg bg-violet-50 flex items-center justify-center">
      <i data-lucide="git-branch" class="w-3.5 h-3.5 text-violet-600"></i>
    </span>
    <span class="text-sm font-bold text-gray-800">Daftar Alur Pembelajaran</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="bg-gray-50 border-b border-gray-100">
          <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest w-8">No</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nama Alur</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Level</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Status</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pembuat</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tanggal</th>
          <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aksi</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach ($data as $i => $a): ?>
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-4 text-gray-400 font-mono text-xs"><?= $i + 1 ?></td>
            <td class="px-4 py-4 font-medium text-gray-800"><?= htmlspecialchars($a['nama_alur']) ?></td>
            <td class="px-4 py-4 text-gray-500 text-xs"><?= htmlspecialchars($a['tingkat_level'] ?: '—') ?></td>
            <td class="px-4 py-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                <?= $a['status'] === 'Published' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' ?>">
                <?= htmlspecialchars($a['status']) ?>
              </span>
            </td>
            <td class="px-4 py-4 text-gray-500"><?= htmlspecialchars($a['pembuat']) ?></td>
            <td class="px-4 py-4 text-gray-400 text-xs whitespace-nowrap"><?= htmlspecialchars($a['tgl_dibuat']) ?></td>
            <td class="px-5 py-4">
              <div class="flex items-center justify-center gap-2">
                <a href="detail_alur.php?id=<?= $a['id_alur'] ?>"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-all inline-flex items-center gap-1">
                  <i data-lucide="settings" class="w-3 h-3"></i>Atur
                </a>
                <a href="edit_alur.php?id=<?= $a['id_alur'] ?>"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 transition-all inline-flex items-center gap-1">
                  <i data-lucide="pencil" class="w-3 h-3"></i>Edit
                </a>
                <a href="proses_alur.php?action=delete&id=<?= $a['id_alur'] ?>"
                   onclick="return confirm('Hapus alur ini?')"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-all inline-flex items-center gap-1">
                  <i data-lucide="trash-2" class="w-3 h-3"></i>Hapus
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$data): ?>
          <tr>
            <td colspan="7" class="px-5 py-12 text-center">
              <i data-lucide="git-branch" class="w-10 h-10 text-gray-300 mx-auto mb-2"></i>
              <p class="text-gray-400 text-sm">Belum ada alur belajar.</p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>


</div>
<?php include '../includes/footer.php'; ?>