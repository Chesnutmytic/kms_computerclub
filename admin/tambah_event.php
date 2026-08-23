<?php
/**
 * tambah_event.php
 * Digunakan untuk CREATE (tanpa ?id) dan EDIT (dengan ?id=N) event.
 * Hanya Admin & Super Admin.
 */
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$flash    = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$editId   = (int) ($_GET['id'] ?? 0);
$isEdit   = $editId > 0;
$event    = null;
$pesertaChecked  = [];
$materiChecked   = [];

if ($isEdit) {
    $stmt = $conn->prepare("SELECT * FROM `event` WHERE id_event = :id");
    $stmt->execute([':id' => $editId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Event tidak ditemukan.'];
        header('Location: kelola_event.php');
        exit;
    }
    if ($event['status'] === 'Selesai') {
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Event yang sudah selesai tidak dapat diedit.'];
        header('Location: kelola_event.php');
        exit;
    }

    // Ambil peserta & materi yang sudah di-assign
    $pesertaChecked = $conn->prepare(
        "SELECT id_user FROM event_peserta WHERE id_event = :id"
    );
    $pesertaChecked->execute([':id' => $editId]);
    $pesertaChecked = $pesertaChecked->fetchAll(PDO::FETCH_COLUMN);

    $materiChecked = $conn->prepare(
        "SELECT id_arsip FROM event_materi WHERE id_event = :id"
    );
    $materiChecked->execute([':id' => $editId]);
    $materiChecked = $materiChecked->fetchAll(PDO::FETCH_COLUMN);
}

// Ambil daftar user aktif (Anggota + Admin, kecuali diri sendiri — tapi boleh termasuk)
$users = $conn->query(
    "SELECT id_user, nama_lengkap, kelas, role
     FROM pengguna
     WHERE status_akun = 'Aktif'
     ORDER BY nama_lengkap"
)->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar materi yang Published
$materiList = $conn->query(
    "SELECT id_arsip, judul_dokumen, kategori
     FROM arsip_materi
     WHERE status = 'Published'
     ORDER BY judul_dokumen"
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = $isEdit ? 'Edit Event' : 'Buat Event';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="fade-in">

  <!-- Page Header -->
  <div class="flex items-center gap-4 mb-6">
    <a href="kelola_event.php"
       class="w-9 h-9 rounded-xl border border-gray-200 bg-white flex items-center justify-center text-gray-500 hover:text-gray-800 hover:border-gray-300 transition-all shadow-sm">
      <i data-lucide="arrow-left" class="w-4 h-4"></i>
    </a>
    <div>
      <div class="flex items-center gap-2.5 mb-0.5">
        <div class="w-1 h-5 rounded-full bg-gradient-to-b from-violet-500 to-purple-600"></div>
        <h1 class="text-xl font-bold text-gray-900"><?= $isEdit ? 'Edit Event' : 'Buat Event Baru' ?></h1>
      </div>
      <p class="text-sm text-gray-500 ml-3.5">
        <?= $isEdit ? 'Perbarui data event dan kelola peserta serta materi prasyarat.' : 'Buat event dan assign peserta serta materi prasyarat.' ?>
      </p>
    </div>
  </div>

  <form method="post"
        action="proses_event.php?action=<?= $isEdit ? 'update' : 'create' ?>"
        class="space-y-6">

    <?php if ($isEdit): ?>
      <input type="hidden" name="id_event" value="<?= $editId ?>">
    <?php endif; ?>

    <!-- Card: Informasi Event -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
        <i data-lucide="calendar" class="w-4 h-4 text-violet-600"></i>
        <span class="font-semibold text-gray-900 text-sm">Informasi Event</span>
      </div>
      <div class="p-6 space-y-5">

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Nama Event <span class="text-red-500">*</span>
          </label>
          <input name="nama_event" required
                 value="<?= htmlspecialchars($event['nama_event'] ?? '') ?>"
                 placeholder="Contoh: Lomba Esport CC Cup 2026"
                 class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Jenis Event <span class="text-red-500">*</span>
            </label>
            <select name="jenis_event" required
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
              <option value="">Pilih jenis</option>
              <?php foreach (['Lomba','Workshop','Pelatihan','Seminar','Lainnya'] as $j): ?>
                <option <?= ($event['jenis_event'] ?? '') === $j ? 'selected' : '' ?>><?= $j ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Tanggal Mulai <span class="text-red-500">*</span>
            </label>
            <input type="date" name="tgl_mulai" required
                   value="<?= htmlspecialchars($event['tgl_mulai'] ?? '') ?>"
                   class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi (opsional)</label>
          <textarea name="deskripsi" rows="3"
                    placeholder="Deskripsi singkat tentang event ini..."
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all resize-none"><?= htmlspecialchars($event['deskripsi'] ?? '') ?></textarea>
        </div>

      </div>
    </div>

    <!-- Card: Assign Peserta -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i data-lucide="users" class="w-4 h-4 text-violet-600"></i>
          <span class="font-semibold text-gray-900 text-sm">Assign Peserta</span>
        </div>
        <div class="flex items-center gap-2">
          <span id="peserta-count" class="text-xs font-semibold text-violet-600 bg-violet-50 px-2 py-0.5 rounded-full">
            <?= count($pesertaChecked) ?> dipilih
          </span>
          <button type="button" onclick="toggleAll('peserta')"
                  class="text-xs font-medium text-gray-500 hover:text-violet-600 transition-colors">
            Pilih Semua
          </button>
        </div>
      </div>

      <!-- Search Peserta -->
      <div class="px-6 pt-4">
        <div class="relative">
          <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
          <input type="text" id="search-peserta"
                 placeholder="Cari nama peserta..."
                 oninput="filterList('peserta-list', this.value)"
                 class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
        </div>
      </div>

      <div id="peserta-list" class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-72 overflow-y-auto">
        <?php if (!$users): ?>
          <p class="text-sm text-gray-400 col-span-full text-center py-4">Belum ada pengguna aktif.</p>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
          <?php
            $roleColor = match($u['role']) {
              'Super Admin' => 'text-violet-600',
              'Admin'       => 'text-indigo-600',
              default       => 'text-gray-500',
            };
            $isChecked = in_array($u['id_user'], $pesertaChecked, false);
          ?>
          <label class="peserta-item flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all hover:border-violet-300 hover:bg-violet-50
                         <?= $isChecked ? 'border-violet-300 bg-violet-50' : 'border-gray-100 bg-gray-50' ?>"
                 data-name="<?= strtolower(htmlspecialchars($u['nama_lengkap'])) ?>">
            <input type="checkbox" name="peserta[]" value="<?= $u['id_user'] ?>"
                   <?= $isChecked ? 'checked' : '' ?>
                   class="rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                   onchange="updateCount('peserta-list','peserta-count'); this.closest('label').classList.toggle('border-violet-300', this.checked); this.closest('label').classList.toggle('bg-violet-50', this.checked); this.closest('label').classList.toggle('border-gray-100', !this.checked); this.closest('label').classList.toggle('bg-gray-50', !this.checked);">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($u['nama_lengkap']) ?></p>
              <p class="text-xs <?= $roleColor ?> truncate"><?= htmlspecialchars($u['kelas']) ?> · <?= htmlspecialchars($u['role']) ?></p>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Card: Materi Prasyarat -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i data-lucide="book-open" class="w-4 h-4 text-violet-600"></i>
          <span class="font-semibold text-gray-900 text-sm">Materi Prasyarat</span>
          <span class="text-xs text-gray-400">(opsional — peserta harus membaca sebelum event)</span>
        </div>
        <span id="materi-count" class="text-xs font-semibold text-violet-600 bg-violet-50 px-2 py-0.5 rounded-full">
          <?= count($materiChecked) ?> dipilih
        </span>
      </div>

      <!-- Search Materi -->
      <div class="px-6 pt-4">
        <div class="relative">
          <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
          <input type="text" id="search-materi"
                 placeholder="Cari judul materi..."
                 oninput="filterList('materi-list', this.value)"
                 class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all">
        </div>
      </div>

      <div id="materi-list" class="p-6 space-y-2 max-h-64 overflow-y-auto">
        <?php if (!$materiList): ?>
          <p class="text-sm text-gray-400 text-center py-4">Belum ada materi yang dipublish.</p>
        <?php endif; ?>
        <?php foreach ($materiList as $m): ?>
          <?php $isChecked = in_array($m['id_arsip'], $materiChecked, false); ?>
          <label class="materi-item flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all hover:border-violet-300 hover:bg-violet-50
                         <?= $isChecked ? 'border-violet-300 bg-violet-50' : 'border-gray-100 bg-gray-50' ?>"
                 data-name="<?= strtolower(htmlspecialchars($m['judul_dokumen'])) ?>">
            <input type="checkbox" name="materi[]" value="<?= $m['id_arsip'] ?>"
                   <?= $isChecked ? 'checked' : '' ?>
                   class="rounded border-gray-300 text-violet-600 focus:ring-violet-500 shrink-0"
                   onchange="updateCount('materi-list','materi-count'); this.closest('label').classList.toggle('border-violet-300', this.checked); this.closest('label').classList.toggle('bg-violet-50', this.checked); this.closest('label').classList.toggle('border-gray-100', !this.checked); this.closest('label').classList.toggle('bg-gray-50', !this.checked);">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($m['judul_dokumen']) ?></p>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($m['kategori'] ?? 'Umum') ?></p>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3 pb-4">
      <button type="submit"
              class="px-6 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white text-sm font-semibold rounded-xl hover:from-violet-500 hover:to-purple-500 transition-all shadow-sm shadow-violet-200 inline-flex items-center gap-2">
        <i data-lucide="<?= $isEdit ? 'save' : 'calendar-plus' ?>" class="w-4 h-4"></i>
        <?= $isEdit ? 'Simpan Perubahan' : 'Buat Event' ?>
      </button>
      <a href="kelola_event.php"
         class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all">
        Batal
      </a>
    </div>

  </form>
</div>

<script>
  // Filter search di list peserta / materi
  function filterList(listId, query) {
    const q = query.toLowerCase();
    document.querySelectorAll(`#${listId} [data-name]`).forEach(el => {
      el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
  }

  // Pilih/deselect semua peserta
  function toggleAll(type) {
    const boxes = document.querySelectorAll(`#${type}-list input[type="checkbox"]`);
    const allChecked = [...boxes].every(b => b.checked);
    boxes.forEach(b => {
      b.checked = !allChecked;
      const label = b.closest('label');
      label.classList.toggle('border-violet-300', !allChecked);
      label.classList.toggle('bg-violet-50', !allChecked);
      label.classList.toggle('border-gray-100', allChecked);
      label.classList.toggle('bg-gray-50', allChecked);
    });
    updateCount(`${type}-list`, `${type}-count`);
  }

  // Update badge jumlah yang dipilih
  function updateCount(listId, countId) {
    const total = document.querySelectorAll(`#${listId} input[type="checkbox"]:checked`).length;
    document.getElementById(countId).textContent = `${total} dipilih`;
  }
</script>

<?php include '../includes/footer.php'; ?>
