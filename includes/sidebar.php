<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<aside class="hidden lg:flex flex-col w-64 sidebar-bg fixed top-[60px] bottom-0 left-0 z-30 overflow-y-auto">

  <nav class="flex-1 px-3 py-3">

    <!-- ── Main Menu ── -->
    <a href="dashboard.php"
      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group relative mb-0.5
        <?= $currentPage === 'dashboard.php' ? 'nav-active text-white' : 'text-slate-400 nav-item' ?>">
      <?php if ($currentPage === 'dashboard.php'): ?>
        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-indigo-300 rounded-r-full"></span>
      <?php endif; ?>
      <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
        <?= $currentPage === 'dashboard.php' ? 'bg-white/20' : 'bg-slate-800/70 group-hover:bg-indigo-500/10' ?> transition-all">
        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
      </div>
      <span>Dashboard</span>
      <?php if ($currentPage === 'dashboard.php'): ?><span class="ml-auto w-1.5 h-1.5 rounded-full bg-white/60"></span><?php endif; ?>
    </a>

    <!-- ── Organisasi ── -->
    <p class="px-3 pt-3 pb-1.5 text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em]">Organisasi</p>

    <?php
    $navItems = [
      ['kelola_materi.php',      'file-text',      'Kelola Materi',      ['kelola_materi.php','tambah_materi.php','edit_materi.php']],
      ['kelola_catatan.php',     'notebook-text',  'Kelola Catatan',     ['kelola_catatan.php','tambah_catatan.php']],
      ['kelola_event.php',       'calendar-days',  'Kelola Event',       ['kelola_event.php','tambah_event.php','detail_event.php']],
      ['kelola_alur.php',        'git-branch',     'Kelola Alur',        ['kelola_alur.php','detail_alur.php']],
      ['kelola_organisasi.php',  'folder-archive', 'Kelola Organisasi',  ['kelola_organisasi.php','tambah_organisasi.php','edit_organisasi.php']],
    ];
    foreach ($navItems as [$href, $icon, $label, $activePages]):
      $isActive = in_array($currentPage, $activePages, true);
    ?>
    <a href="<?= $href ?>"
      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group relative mb-0.5
        <?= $isActive ? 'nav-active text-white' : 'text-slate-400 nav-item' ?>">
      <?php if ($isActive): ?>
        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-indigo-300 rounded-r-full"></span>
      <?php endif; ?>
      <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
        <?= $isActive ? 'bg-white/20' : 'bg-slate-800/70 group-hover:bg-indigo-500/10' ?> transition-all">
        <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i>
      </div>
      <span><?= $label ?></span>
    </a>
    <?php endforeach; ?>

    <!-- ── Manajemen ── -->
    <p class="px-3 pt-3 pb-1.5 text-[9px] font-bold text-slate-600 uppercase tracking-[0.2em]">Manajemen</p>

    <?php
    $mgmt = [
      ['kelola_kepengurusan.php','calendar-range', 'Masa Kepengurusan', ['kelola_kepengurusan.php']],
      ['user_management.php',    'users',          'Kelola Pengguna',   ['user_management.php','tambah_user.php']],
      ['kelola_pengumuman.php',  'bell',           'Kelola Pengumuman', ['kelola_pengumuman.php']],
    ];
    foreach ($mgmt as [$href, $icon, $label, $activePages]):
      if (in_array($href, ['kelola_pengumuman.php', 'kelola_kepengurusan.php'], true) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin')) continue;
      $isActive = in_array($currentPage, $activePages, true);
    ?>
  
    <a href="<?= $href ?>"
      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group relative mb-0.5
              <?= $isActive ? 'nav-active text-white' : 'text-slate-400 nav-item' ?>">
      <?php if ($isActive): ?>
        <span class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-indigo-300 rounded-r-full"></span>
      <?php endif; ?>
      <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                  <?= $isActive ? 'bg-white/20' : 'bg-slate-800/70 group-hover:bg-indigo-500/10' ?> transition-all">
        <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i>
      </div>
      <span><?= $label ?></span>
    </a>
    <?php endforeach; ?>

  </nav>

  <!-- Sidebar Footer -->
  <div class="px-3 pb-4 pt-2 border-t border-slate-700/30 space-y-1">
    <a href="../portal/index.php"
      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500 hover:text-indigo-300 hover:bg-indigo-500/10 transition-all group">
      <div class="w-7 h-7 rounded-lg bg-slate-800/70 group-hover:bg-indigo-500/10 flex items-center justify-center transition-all">
        <i data-lucide="globe" class="w-3.5 h-3.5"></i>
      </div>
      <span>Lihat Portal</span>
      <i data-lucide="arrow-up-right" class="w-3 h-3 ml-auto opacity-0 group-hover:opacity-100 transition-opacity"></i>
    </a>
  </div>
</aside>

<!-- ============ MOBILE SIDEBAR ============ -->
<div id="mobileSidebar" class="hidden lg:hidden fixed inset-0 z-40" onclick="this.classList.add('hidden')">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
  <aside class="relative w-64 sidebar-bg h-full overflow-y-auto" onclick="event.stopPropagation()">
    <div class="px-4 py-4 border-b border-slate-700/40 mt-[60px]">
      <p class="text-[9px] font-bold text-slate-500 uppercase tracking-[0.18em]">Navigation</p>
    </div>
    <nav class="px-3 py-3 space-y-0.5">
      <?php
      $allMobile = [
        ['dashboard.php',         'layout-dashboard', 'Dashboard',          ['dashboard.php']],
        ['kelola_materi.php',     'file-text',        'Kelola Materi',      ['kelola_materi.php','tambah_materi.php','edit_materi.php']],
        ['kelola_catatan.php',    'notebook-text',    'Kelola Catatan',     ['kelola_catatan.php','tambah_catatan.php']],
        ['kelola_event.php',      'calendar-days',    'Kelola Event',       ['kelola_event.php','tambah_event.php','detail_event.php']],
        ['kelola_alur.php',       'git-branch',       'Kelola Alur',        ['kelola_alur.php','detail_alur.php']],
        ['kelola_organisasi.php', 'folder-archive',   'Kelola Organisasi',  ['kelola_organisasi.php','tambah_organisasi.php','edit_organisasi.php']],
        ['kelola_kepengurusan.php','calendar-range',  'Masa Kepengurusan',  ['kelola_kepengurusan.php']],
        ['user_management.php',   'users',            'Kelola Pengguna',    ['user_management.php','tambah_user.php']],
        ['kelola_pengumuman.php', 'bell',             'Kelola Pengumuman',  ['kelola_pengumuman.php']],
      ];
      foreach ($allMobile as [$href, $icon, $label, $activePages]):
        if (in_array($href, ['kelola_pengumuman.php', 'kelola_kepengurusan.php'], true) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Super Admin')) continue;
        $isActive = in_array($currentPage, $activePages, true);
      ?>
      <a href="<?= $href ?>"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all
                <?= $isActive ? 'nav-active text-white' : 'text-slate-400 nav-item' ?>">
        <i data-lucide="<?= $icon ?>" class="w-4 h-4 shrink-0"></i>
        <?= $label ?>
      </a>
      <?php endforeach; ?>
      <a href="../portal/index.php"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500 nav-item transition-all mt-2 border-t border-slate-700/30 pt-3">
        <i data-lucide="globe" class="w-4 h-4 shrink-0"></i>Lihat Portal
      </a>
    </nav>
  </aside>
</div>

<!-- ============ MAIN CONTENT AREA ============ -->
<main class="flex-1 lg:ml-64 flex flex-col bg-slate-50" style="min-height: calc(100vh - 60px);">
  <div class="flex-1 p-5 lg:p-7">
<?php if (!empty($flash)): ?>
  <div class="mb-5 fade-in flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium
    <?= $flash['type'] === 'success'
        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
        : ($flash['type'] === 'danger'
        ? 'bg-red-50 text-red-700 border border-red-200'
        : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>
<script>lucide.createIcons();</script>