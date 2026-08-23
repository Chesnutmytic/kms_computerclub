<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Kelola Pengguna';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$users = $conn->query(
    "SELECT id_user, nama_lengkap, kelas, username, role, alasan_masuk
     FROM pengguna
     WHERE status_akun != 'Pending'
     ORDER BY nama_lengkap"
)->fetchAll();

$pending = $conn->query(
    "SELECT id_user, nama_lengkap, kelas, username, alasan_masuk, kartu_pelajar, status_akun
     FROM pengguna
     WHERE status_akun = 'Pending'
     ORDER BY id_user ASC"
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <div class="flex items-center gap-2.5 mb-1">
      <div class="w-1 h-5 rounded-full bg-gradient-to-b from-sky-500 to-cyan-500"></div>
      <h1 class="text-xl font-bold text-gray-900">Kelola Pengguna</h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5">Hanya Super Admin yang dapat mengubah role dan data pengguna.</p>
  </div>
  <?php if ($_SESSION['role'] === 'Super Admin'): ?>
  <a href="tambah_user.php"
    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-sky-600 to-cyan-600 text-white text-sm font-semibold rounded-xl hover:from-sky-500 hover:to-cyan-500 transition-all shadow-md shadow-sky-500/20 hover:-translate-y-0.5">
    <i data-lucide="user-plus" class="w-4 h-4"></i>Tambah Pengguna
  </a>
  <?php endif; ?>
</div>

<!-- Pending Section -->
<?php if ($pending): ?>
<div class="mb-6">
  <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
    <span class="w-6 h-6 rounded-lg bg-amber-50 flex items-center justify-center">
      <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-500"></i>
    </span>
    Perlu Review <span class="ml-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold"><?= count($pending) ?></span>
  </h2>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <?php foreach ($pending as $u): ?>
      <div class="bg-white rounded-2xl border-2 border-amber-200 shadow-sm p-5">
        <div class="flex items-start gap-4">
          <!-- Kartu Pelajar Thumbnail -->
          <?php if ($u['kartu_pelajar']): ?>
            <a href="../<?= htmlspecialchars($u['kartu_pelajar']) ?>" target="_blank" rel="noopener"
               class="shrink-0 group">
              <div class="w-16 h-20 rounded-xl overflow-hidden border-2 border-gray-200 group-hover:border-indigo-400 transition-all">
                <img src="../<?= htmlspecialchars($u['kartu_pelajar']) ?>" alt="Kartu Pelajar"
                     class="w-full h-full object-cover">
              </div>
              <p class="text-xs text-indigo-600 text-center mt-1 group-hover:underline">Lihat full</p>
            </a>
          <?php else: ?>
            <div class="w-16 h-20 rounded-xl bg-gray-100 flex items-center justify-center shrink-0">
              <i data-lucide="id-card" class="w-6 h-6 text-gray-400"></i>
            </div>
          <?php endif; ?>

          <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($u['nama_lengkap']) ?></p>
            <p class="text-xs text-gray-500 mt-0.5">@<?= htmlspecialchars($u['username']) ?> · <?= htmlspecialchars($u['kelas']) ?></p>
            <?php if ($u['alasan_masuk']): ?>
              <p class="text-xs text-gray-600 mt-2 line-clamp-2 italic">"<?= htmlspecialchars($u['alasan_masuk']) ?>"</p>
            <?php endif; ?>

            <div class="flex gap-2 mt-4">
              <a href="proses_pengguna.php?action=approve&id=<?= $u['id_user'] ?>"
                 onclick="return confirm('Setujui pendaftaran <?= htmlspecialchars($u['nama_lengkap']) ?>?')"
                 class="flex-1 py-2 text-center rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-all inline-flex items-center justify-center gap-1">
                <i data-lucide="check" class="w-3.5 h-3.5"></i>Approve
              </a>
              <a href="proses_pengguna.php?action=reject&id=<?= $u['id_user'] ?>"
                 onclick="return confirm('Tolak pendaftaran <?= htmlspecialchars($u['nama_lengkap']) ?>?')"
                 class="flex-1 py-2 text-center rounded-xl text-xs font-semibold bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition-all inline-flex items-center justify-center gap-1">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>Tolak
              </a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
  <div class="px-5 py-3.5 border-b border-gray-100 flex items-center gap-2">
    <span class="w-6 h-6 rounded-lg bg-sky-50 flex items-center justify-center">
      <i data-lucide="users" class="w-3.5 h-3.5 text-sky-600"></i>
    </span>
    <span class="text-sm font-bold text-gray-800">Daftar Pengguna Aktif</span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="bg-gray-50 border-b border-gray-100">
          <th class="px-5 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest w-8">No</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nama</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Kelas</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Username</th>
          <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-400 uppercase tracking-widest">Role</th>
          <?php if ($_SESSION['role'] === 'Super Admin'): ?>
          <th class="px-5 py-3 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">Aksi</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach ($users as $i => $u): ?>
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-4 text-gray-400 font-mono text-xs"><?= $i + 1 ?></td>
            <td class="px-4 py-4 font-medium text-gray-800">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                  <?= mb_strtoupper(mb_substr($u['nama_lengkap'], 0, 1)) ?>
                </div>
                <?= htmlspecialchars($u['nama_lengkap']) ?>
                <?php if ($u['id_user'] == $_SESSION['id_user']): ?>
                  <span class="px-2 py-0.5 rounded-full bg-indigo-600 text-white text-xs font-semibold">Anda</span>
                <?php endif; ?>
              </div>
            </td>
            <td class="px-4 py-4 text-gray-500"><?= htmlspecialchars($u['kelas']) ?></td>
            <td class="px-4 py-4 text-gray-500 font-mono text-xs"><?= htmlspecialchars($u['username']) ?></td>
            <td class="px-4 py-4">
              <?php
                $roleColor = match($u['role']) {
                  'Super Admin' => 'bg-violet-50 text-violet-700',
                  'Admin'       => 'bg-indigo-50 text-indigo-700',
                  default       => 'bg-gray-100 text-gray-600',
                };
              ?>
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $roleColor ?>">
                <?= htmlspecialchars($u['role']) ?>
              </span>
            </td>
            <?php if ($_SESSION['role'] === 'Super Admin'): ?>
            <td class="px-5 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <a href="tambah_user.php?id=<?= $u['id_user'] ?>"
                  class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 hover:bg-amber-100 transition-all inline-flex items-center gap-1">
                  <i data-lucide="pencil" class="w-3 h-3"></i>Edit
                </a>
                <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
                  <a href="proses_user.php?action=delete&id=<?= $u['id_user'] ?>"
                     onclick="return confirm('Hapus pengguna ini?')"
                     class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 transition-all inline-flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3 h-3"></i>Hapus
                  </a>
                <?php endif; ?>
              </div>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>