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
            primary: {
              50:  '#eef2ff',
              100: '#e0e7ff',
              200: '#c7d2fe',
              400: '#818cf8',
              500: '#6366f1',
              600: '#4f46e5',
              700: '#4338ca',
            },
          },
          boxShadow: {
            'card': '0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.04)',
            'topbar': '0 1px 0 0 rgba(0,0,0,0.06)',
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
    body { font-family: 'Inter', sans-serif; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: #1e293b; }
    ::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #4f46e5; }

    /* Sidebar dark Ynex */
    .sidebar-bg {
      background: linear-gradient(180deg, #0f172a 0%, #111827 100%);
    }

    /* Active nav item */
    .nav-active {
      background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
      box-shadow: 0 4px 12px -2px rgba(79,70,229,0.45);
    }

    /* Inactive nav hover */
    .nav-item:hover {
      background: rgba(99,102,241,0.08);
      color: #a5b4fc;
    }

    /* Topbar — same color as sidebar */
    .topbar {
      background: #0f172a;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      box-shadow: 0 1px 0 rgba(255,255,255,0.04);
    }

    /* Pulse animation */
    @keyframes pulse-ring {
      0% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
      70% { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
      100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }
    .pulse { animation: pulse-ring 2s infinite; }

    /* Page fade-in */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .fade-in { animation: fadeInUp 0.35s ease forwards; }

    /* Stat card gradient border on hover */
    .stat-card { position: relative; transition: all 0.2s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 25px -5px rgba(0,0,0,0.1), 0 4px 10px -5px rgba(0,0,0,0.04); }
  </style>
</head>
<!-- Light content area, dark sidebar -->
<body class="bg-slate-50 text-gray-900 antialiased">

<!-- ===== TOP NAVIGATION BAR ===== -->
<header class="topbar fixed top-0 left-0 right-0 z-50 h-[60px] flex items-center px-4 lg:px-6 gap-4">

  <!-- Mobile sidebar toggle -->
  <button id="sidebarToggle" onclick="document.getElementById('mobileSidebar').classList.toggle('hidden')"
    class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-700/60 transition-all">
    <i data-lucide="menu" class="w-5 h-5"></i>
  </button>

  <!-- Brand -->
  <a href="dashboard.php" class="flex items-center gap-2.5 shrink-0 group">
    <div class="relative w-8 h-8 rounded-xl overflow-hidden shadow-lg shadow-indigo-500/40 group-hover:shadow-indigo-500/60 transition-all flex-shrink-0">
      <img src="../assets/media/logo.png" alt="KMS Computer Club" class="w-full h-full object-cover">
      <div class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 border-2 border-[#0f172a] pulse"></div>
    </div>
    <div class="hidden sm:block">
      <p class="text-white font-bold text-sm leading-tight tracking-tight">KMS Computer Club</p>
      <p class="text-slate-500 text-[10px] leading-none">Admin Panel</p>
    </div>
  </a>

  <!-- Breadcrumb -->
  <div class="hidden lg:flex items-center gap-2 ml-2">
    <span class="text-slate-600">/</span>
    <span class="text-slate-400 text-sm font-medium"><?= htmlspecialchars($pageTitle) ?></span>
  </div>

  <!-- Right Actions -->
  <div class="ml-auto flex items-center gap-2 lg:gap-3">

    <!-- Portal link -->
    <a href="../portal/index.php"
       class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-slate-400 hover:text-indigo-300 hover:bg-indigo-500/10 transition-all border border-slate-700/60 hover:border-indigo-500/30">
      <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
      <span>Portal</span>
    </a>

    <!-- Divider -->
    <div class="hidden sm:block h-6 w-px bg-slate-700/60"></div>

    <!-- User info -->
    <div class="flex items-center gap-2.5">
      <div class="text-right hidden md:block">
        <p class="text-sm font-semibold text-slate-100 leading-tight"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin') ?></p>
        <p class="text-[10px] text-indigo-400 leading-none font-semibold"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></p>
      </div>
      <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-indigo-500/25">
        <?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'A', 0, 1)) ?>
      </div>
    </div>

    <!-- Logout -->
    <a href="../logout.php"
       class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-medium bg-red-500/10 text-red-400 hover:bg-red-500/20 hover:text-red-300 transition-all border border-red-500/20">
      <i data-lucide="log-out" class="w-4 h-4"></i>
      <span class="hidden sm:inline">Logout</span>
    </a>
  </div>
</header>

<!-- Layout Wrapper -->
<div class="flex pt-[60px]">
