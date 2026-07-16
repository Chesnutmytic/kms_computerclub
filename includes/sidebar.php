<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<aside class="col-md-3 col-lg-2 bg-dark p-3 min-vh-100">
  <nav class="nav nav-pills flex-column gap-1">
    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : 'text-white' ?>" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
    <a class="nav-link <?= $currentPage === 'kelola_materi.php' ? 'active' : 'text-white' ?>" href="kelola_materi.php"><i class="bi bi-file-earmark-text me-2"></i>Kelola Materi</a>
    <a class="nav-link <?= $currentPage === 'kelola_catatan.php' ? 'active' : 'text-white' ?>" href="kelola_catatan.php"><i class="bi bi-journal-text me-2"></i>Kelola Catatan</a>
    <a class="nav-link <?= in_array($currentPage, ['kelola_alur.php', 'detail_alur.php'], true) ? 'active' : 'text-white' ?>" href="kelola_alur.php"><i class="bi bi-diagram-3 me-2"></i>Kelola Alur</a>
    <a class="nav-link <?= $currentPage === 'user_management.php' ? 'active' : 'text-white' ?>" href="user_management.php"><i class="bi bi-people me-2"></i>Kelola Pengguna</a>
    <hr class="border-secondary"><a class="nav-link text-white" href="../portal/index.html"><i class="bi bi-box-arrow-left me-2"></i>Lihat Portal</a>
  </nav>
</aside>
<main class="col-md-9 col-lg-10 p-4 p-lg-5">
<?php if (!empty($flash)): ?><div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert"><?= htmlspecialchars($flash['msg']) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
