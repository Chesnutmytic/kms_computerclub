<?php
if (!isset($pageTitle)) $pageTitle = 'Admin';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> - KMS Computer Club</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-primary">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-cpu-fill me-2"></i>KMS Computer Club</a>
    <div class="d-flex align-items-center gap-3">
      <span class="text-white d-none d-sm-inline"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Admin') ?> <span class="badge text-bg-light text-dark ms-1"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span></span>
      <a class="btn btn-sm btn-outline-light" href="../logout.php">Logout</a>
    </div>
  </div>
</nav>
<div class="container-fluid"><div class="row">
