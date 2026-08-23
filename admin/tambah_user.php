<?php
session_start();
require_once '../config/koneksi.php';

if (($_SESSION['role'] ?? '') !== 'Super Admin') {
    header('Location: dashboard.php');
    exit;
}

$id_user = (int)($_GET['id'] ?? 0);
$isEdit = $id_user > 0;
$user = null;

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE id_user = :id");
    $stmt->execute([':id' => $id_user]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Pengguna tidak ditemukan.'];
        header('Location: user_management.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in">
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <div class="flex items-center gap-2.5 mb-1">
      <div class="w-1 h-5 rounded-full bg-gradient-to-b from-sky-500 to-cyan-500"></div>
      <h1 class="text-xl font-bold text-gray-900"><?= $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' ?></h1>
    </div>
    <p class="text-sm text-gray-500 ml-3.5"><?= $isEdit ? 'Perbarui informasi pengguna.' : 'Tambahkan pengguna baru ke dalam sistem.' ?></p>
  </div>
  <a href="user_management.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-all">
    <i data-lucide="arrow-left" class="w-4 h-4"></i>Kembali
  </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden max-w-2xl">
  <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
    <i data-lucide="<?= $isEdit ? 'user-pen' : 'user-plus' ?>" class="w-5 h-5 text-indigo-600"></i>
    <span class="font-semibold text-gray-900">Detail Pengguna</span>
  </div>
  <form class="p-6 space-y-5" method="post" action="proses_user.php?action=<?= $isEdit ? 'edit' : 'create' ?>" enctype="multipart/form-data">
    <?php if ($isEdit): ?>
      <input type="hidden" name="id_user" value="<?= $user['id_user'] ?>">
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required placeholder="Nama lengkap"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kelas <span class="text-red-500">*</span></label>
        <input type="text" name="kelas" value="<?= htmlspecialchars($user['kelas'] ?? '') ?>" required placeholder="Contoh: XI MIPA 1"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Username <span class="text-red-500">*</span></label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required placeholder="Username unik"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5"><?= $isEdit ? 'Password Baru' : 'Password' ?> <?= $isEdit ? '<span class="text-gray-400 font-normal">(opsional)</span>' : '<span class="text-red-500">*</span>' ?></label>
        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> placeholder="<?= $isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' ?>"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Role <span class="text-red-500">*</span></label>
        <select name="role" required
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
          <?php foreach (['Anggota', 'Admin', 'Super Admin'] as $role): ?>
            <option <?= ($user['role'] ?? 'Anggota') === $role ? 'selected' : '' ?>><?= $role ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5"><?= $isEdit ? 'Kartu Pelajar Baru' : 'Kartu Pelajar' ?> <span class="text-gray-400 font-normal">(opsional)</span></label>
        <input type="file" name="kartu_pelajar" accept="image/jpeg,image/png"
          class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">Alasan Bergabung <span class="text-gray-400 font-normal">(opsional)</span></label>
      <textarea name="alasan_masuk" rows="3" placeholder="Alasan bergabung..."
        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-none"><?= htmlspecialchars($user['alasan_masuk'] ?? '') ?></textarea>
    </div>

    <div class="flex gap-3 pt-2">
      <button type="submit"
        class="px-6 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm">
        <i data-lucide="save" class="w-4 h-4 inline mr-1.5"></i><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna' ?>
      </button>
      <a href="user_management.php" class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">Batal</a>
    </div>
  </form>
  </div>
</div>
<?php include '../includes/footer.php'; ?>