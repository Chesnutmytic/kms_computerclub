<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<aside class="hidden lg:flex flex-col w-64 sidebar-bg fixed top-[60px] bottom-0 left-0 z-30 overflow-y-auto">

  <nav class="flex-1 px-3 py-3">

    <!-- ── Dashboard ── -->
    <a href="dashboard.php"
      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group relative mb-0.5
        <?= $currentPage === 'dashboard.php' ? 'nav-active' : 'nav-item' ?>">
      <?php if ($currentPage === 'dashboard.php'): ?>
        <span class="active-line"></span>
      <?php endif; ?>
      <div class="nav-icon w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all
        <?= $currentPage === 'dashboard.php' ? 'bg-accent-mid text-accent' : 'bg-surface text-muted' ?>">
        <i data-lucide="layout-dashboard" class="w-3.5 h-3.5"></i>
      </div>
      <span>Dashboard</span>
      <?php if ($currentPage === 'dashboard.php'): ?>
        <span class="ml-auto w-1.5 h-1.5 rounded-full bg-accent"></span>
      <?php endif; ?>
    </a>

    <!-- ── Organisasi ── -->
    <p class="nav-section-label">Organisasi</p>

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
        <?= $isActive ? 'nav-active' : 'nav-item' ?>">
      <?php if ($isActive): ?>
        <span class="active-line"></span>
      <?php endif; ?>
      <div class="nav-icon w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all
        <?= $isActive ? 'bg-accent-mid text-accent' : 'bg-surface text-muted' ?>">
        <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i>
      </div>
      <span><?= $label ?></span>
    </a>
    <?php endforeach; ?>

    <!-- ── Manajemen ── -->
    <p class="nav-section-label">Manajemen</p>

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
              <?= $isActive ? 'nav-active' : 'nav-item' ?>">
      <?php if ($isActive): ?>
        <span class="active-line"></span>
      <?php endif; ?>
      <div class="nav-icon w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-all
                  <?= $isActive ? 'bg-accent-mid text-accent' : 'bg-surface text-muted' ?>">
        <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i>
      </div>
      <span><?= $label ?></span>
    </a>
    <?php endforeach; ?>

  </nav>

  <!-- Sidebar Footer -->
  <div class="px-3 pb-4 pt-2 border-t border-border space-y-1">
    <a href="../portal/index.php"
      class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-muted hover:text-accent hover:bg-accent-light transition-all group cursor-pointer">
      <div class="nav-icon w-7 h-7 rounded-lg bg-surface group-hover:bg-accent-light flex items-center justify-center transition-all text-muted group-hover:text-accent">
        <i data-lucide="globe" class="w-3.5 h-3.5"></i>
      </div>
      <span>Lihat Portal</span>
      <i data-lucide="arrow-up-right" class="w-3 h-3 ml-auto opacity-0 group-hover:opacity-100 transition-opacity text-accent"></i>
    </a>
  </div>
</aside>

<!-- ============ MOBILE SIDEBAR ============ -->
<div id="mobileSidebar" class="hidden lg:hidden fixed inset-0 z-40" onclick="this.classList.add('hidden')">
  <div class="absolute inset-0 bg-ink/30 backdrop-blur-sm"></div>
  <aside class="relative w-64 sidebar-bg h-full overflow-y-auto" onclick="event.stopPropagation()">
    <div class="px-4 py-4 border-b border-border mt-[60px]">
      <p class="nav-section-label" style="padding:0;">Navigation</p>
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
                <?= $isActive ? 'nav-active' : 'nav-item' ?>">
        <i data-lucide="<?= $icon ?>" class="w-4 h-4 shrink-0 <?= $isActive ? 'text-accent' : 'text-muted' ?>"></i>
        <?= $label ?>
      </a>
      <?php endforeach; ?>
      <a href="../portal/index.php"
        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-muted nav-item transition-all mt-2 border-t border-border pt-3 cursor-pointer">
        <i data-lucide="globe" class="w-4 h-4 shrink-0"></i>Lihat Portal
      </a>
    </nav>
  </aside>
</div>

<!-- ============ MAIN CONTENT AREA ============ -->
<main class="flex-1 lg:ml-64 flex flex-col bg-surface" style="min-height: calc(100vh - 60px);">
  <div class="flex-1 p-5 lg:p-7">
<?php if (!empty($flash)): ?>
  <div class="mb-5 fade-in flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium
    <?= $flash['type'] === 'success'
        ? 'bg-green-50 text-green-700 border border-green-200'
        : ($flash['type'] === 'danger'
        ? 'bg-red-50 text-red-700 border border-red-200'
        : 'bg-amber-50 text-amber-700 border border-amber-200') ?>">
    <i data-lucide="<?= $flash['type'] === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-4 h-4 shrink-0"></i>
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>
<script>lucide.createIcons();</script>