<?php
if (!isset($pageTitle)) $pageTitle = 'Admin';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> — KMS Computer Club Admin</title>
  <meta name="description" content="Panel Admin KMS Computer Club SMAN 1 Rancaekek">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: {
            accent:  '#6366F1',
            'accent-light': '#EEF2FF',
            'accent-mid':   '#C7D2FE',
            green:   '#22C55E',
            ink:     '#0F172A',
            'ink-2': '#334155',
            muted:   '#64748B',
            border:  '#E2E8F0',
            surface: '#F8FAFC',
          },
          boxShadow: {
            'card':   '0 1px 4px 0 rgba(15,23,42,0.06), 0 1px 2px -1px rgba(15,23,42,0.04)',
            'topbar': '0 1px 0 0 rgba(15,23,42,0.07)',
          }
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    [x-cloak] { display: none !important; }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: #F1F5F9; }
    ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #6366F1; }

    /* ── SIDEBAR — Light ── */
    .sidebar-bg {
      background: #FFFFFF;
      border-right: 1px solid #E2E8F0;
    }

    /* ── ACTIVE NAV ITEM ── */
    .nav-active {
      background: #EEF2FF;
      color: #4F46E5 !important;
    }
    .nav-active .nav-icon { background: #C7D2FE !important; color: #4F46E5 !important; }

    /* ── INACTIVE NAV HOVER ── */
    .nav-item { color: #64748B; }
    .nav-item:hover {
      background: #F8FAFC;
      color: #0F172A;
    }
    .nav-item:hover .nav-icon { background: #EEF2FF !important; }

    /* ── TOPBAR ── */
    .topbar {
      background: #FFFFFF;
      border-bottom: 1px solid #E2E8F0;
      box-shadow: 0 1px 0 rgba(15,23,42,0.05);
    }

    /* ── PULSE (green dot) ── */
    @keyframes pulse-ring {
      0%   { box-shadow: 0 0 0 0 rgba(34,197,94,0.4); }
      70%  { box-shadow: 0 0 0 5px rgba(34,197,94,0); }
      100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }
    .pulse { animation: pulse-ring 2s infinite; }

    /* ── PAGE FADE-IN ── */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-in { animation: fadeInUp 0.3s ease forwards; }

    /* ── STAT CARD HOVER ── */
    .stat-card { position: relative; transition: all 0.2s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -4px rgba(99,102,241,0.12); }

    /* ── SECTION LABEL ── */
    .nav-section-label {
      font-size: 0.65rem;
      font-weight: 700;
      color: #94A3B8;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      padding: 10px 12px 4px;
    }

    /* ── ACTIVE INDICATOR LINE ── */
    .active-line {
      position: absolute;
      left: 0; top: 50%;
      transform: translateY(-50%);
      width: 3px; height: 20px;
      background: #6366F1;
      border-radius: 0 2px 2px 0;
    }
  </style>
</head>
<!-- Light content area -->
<body class="bg-surface text-ink antialiased">

<!-- ===== TOP NAVIGATION BAR ===== -->
<header class="topbar fixed top-0 left-0 right-0 z-50 h-[60px] flex items-center px-4 lg:px-6 gap-3">

  <!-- Mobile sidebar toggle -->
  <button id="sidebarToggle" onclick="document.getElementById('mobileSidebar').classList.toggle('hidden')"
    class="lg:hidden p-2 rounded-xl text-muted hover:text-ink hover:bg-surface transition-all cursor-pointer">
    <i data-lucide="menu" class="w-5 h-5"></i>
  </button>

  <!-- Brand -->
  <a href="dashboard.php" class="flex items-center gap-2.5 shrink-0 group cursor-pointer">
    <div class="relative w-8 h-8 rounded-xl overflow-hidden shadow-sm border border-border group-hover:border-accent-mid transition-all flex-shrink-0">
      <img src="../assets/media/logo.png" alt="KMS Computer Club" class="w-full h-full object-cover">
      <div class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-green border-2 border-white pulse"></div>
    </div>
    <div class="hidden sm:block">
      <p class="text-ink font-bold text-sm leading-tight tracking-tight">KMS Computer Club</p>
      <p class="text-muted text-[10px] leading-none font-medium">Admin Panel</p>
    </div>
  </a>

  <!-- Breadcrumb -->
  <div class="hidden lg:flex items-center gap-1.5 ml-1">
    <span class="text-border text-sm">/</span>
    <span class="text-muted text-sm font-medium"><?= htmlspecialchars($pageTitle) ?></span>
  </div>

  <!-- Right Actions -->
  <div class="ml-auto flex items-center gap-2">

    <!-- Portal link -->
    <a href="../portal/index.php"
       class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-muted hover:text-accent hover:bg-accent-light transition-all border border-border hover:border-accent-mid cursor-pointer">
      <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
      <span>Portal</span>
    </a>

    <!-- Divider -->
    <div class="hidden sm:block h-5 w-px bg-border"></div>

    <!-- User info -->
    <div class="flex items-center gap-2">
      <div class="text-right hidden md:block">
        <p class="text-sm font-semibold text-ink leading-tight"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin') ?></p>
        <p class="text-[10px] text-accent leading-none font-semibold"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></p>
      </div>
      <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-accent to-indigo-700 flex items-center justify-center text-white font-bold text-sm shadow-sm">
        <?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'A', 0, 1)) ?>
      </div>
    </div>

    <!-- Logout -->
    <a href="../logout.php"
       class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 transition-all border border-red-100 cursor-pointer">
      <i data-lucide="log-out" class="w-4 h-4"></i>
      <span class="hidden sm:inline">Logout</span>
    </a>
  </div>
</header>

<!-- Layout Wrapper -->
<div class="flex pt-[60px]">
