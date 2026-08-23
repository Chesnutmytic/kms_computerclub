<?php
session_start();
require_once '../config/koneksi.php';

// Pastikan tabel ada
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS pengumuman (
            id_pengumuman INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            judul VARCHAR(255) NOT NULL,
            isi_pengumuman TEXT NOT NULL,
            tgl_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_user) REFERENCES pengguna(id_user) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../login.html');
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Ambil daftar pengumuman
$stmt = $conn->query(
    "SELECT p.*, u.nama_lengkap 
     FROM pengumuman p 
     JOIN pengguna u ON p.id_user = u.id_user 
     ORDER BY p.tgl_dibuat DESC"
);
$pengumuman = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in space-y-6">
<div class="mb-6">
  <div class="flex items-center gap-2.5 mb-1">
    <div class="w-1 h-5 rounded-full bg-gradient-to-b from-indigo-500 to-violet-500"></div>
    <h1 class="text-xl font-bold text-gray-900">Kelola Pengumuman</h1>
  </div>
  <p class="text-sm text-gray-500 ml-3.5">Buat dan kelola pengumuman untuk anggota.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  
  <!-- Form Tambah -->
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6 sticky top-[72px]">
      <h2 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-sm">
        <span class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center">
          <i data-lucide="plus-circle" class="w-3.5 h-3.5 text-indigo-600"></i>
        </span>
        Buat Pengumuman
      </h2>
      <form action="proses_pengumuman.php?action=add" method="POST" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Judul Pengumuman</label>
          <input type="text" name="judul" required placeholder="Contoh: Jadwal Kumpul Rutin"
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Isi Pengumuman</label>
          <textarea name="isi_pengumuman" required rows="5" placeholder="Tulis detail pengumuman di sini..."
            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all resize-y"></textarea>
        </div>
        <button type="submit" class="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-500 transition-all shadow-sm flex items-center justify-center gap-2">
          <i data-lucide="send" class="w-4 h-4"></i>Sebarkan Pengumuman
        </button>
      </form>
    </div>
  </div>

  <!-- Daftar Pengumuman -->
  <div class="lg:col-span-2 space-y-4">
    <?php foreach ($pengumuman as $p): ?>
      <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5 relative group hover:-translate-y-0.5 transition-all">
        <div class="absolute top-6 right-6 opacity-0 group-hover:opacity-100 transition-opacity">
          <a href="proses_pengumuman.php?action=delete&id=<?= $p['id_pengumuman'] ?>" 
             onclick="return confirm('Yakin ingin menghapus pengumuman ini?')"
             class="p-2 rounded-lg text-red-500 hover:bg-red-50 transition-colors inline-block" title="Hapus">
            <i data-lucide="trash-2" class="w-4 h-4"></i>
          </a>
        </div>
        <div class="pr-10">
          <h3 class="text-base font-bold text-gray-900 mb-1"><?= htmlspecialchars($p['judul']) ?></h3>
          <p class="text-xs text-gray-400 mb-4">
            Oleh <?= htmlspecialchars($p['nama_lengkap']) ?> · <?= htmlspecialchars(date('d M Y H:i', strtotime($p['tgl_dibuat']))) ?>
          </p>
          <div class="prose prose-sm max-w-none text-gray-700">
            <?= nl2br(htmlspecialchars($p['isi_pengumuman'])) ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (!$pengumuman): ?>
      <div class="py-12 text-center bg-white rounded-2xl border border-gray-100 shadow-sm">
        <i data-lucide="bell-off" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
        <p class="text-gray-500 font-medium">Belum ada pengumuman.</p>
        <p class="text-sm text-gray-400 mt-1">Pengumuman yang dibuat akan muncul di sini.</p>
      </div>
    <?php endif; ?>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>
