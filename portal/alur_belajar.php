<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$alur = $conn->query(
    "SELECT a.*, p.nama_lengkap 
     FROM ALUR_PEMBELAJARAN a 
     JOIN PENGGUNA p ON p.id_user = a.id_user 
     WHERE a.status = 'Published' 
     ORDER BY a.tgl_dibuat DESC, a.id_alur DESC"
)->fetchAll();

include '../includes/header_portal.html';
?>

<main class="container py-5">
    <div class="mb-4">
        <h1 class="h3 mb-1">Alur Belajar</h1>
        <p class="text-muted mb-0">Pilih jalur untuk melihat materi yang direkomendasikan.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($alur as $a): ?>
            <div class="col-md-6 col-lg-4">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="badge text-bg-primary align-self-start mb-3">
                            <?= htmlspecialchars($a['tingkat_level'] ?: 'Semua Level') ?>
                        </span>
                        <h2 class="h5"><?= htmlspecialchars($a['nama_alur']) ?></h2>
                        <p class="small text-muted flex-grow-1">
                            Dibuat oleh <?= htmlspecialchars($a['nama_lengkap']) ?><br>
                            <?= htmlspecialchars($a['tgl_dibuat']) ?>
                        </p>
                        <a class="btn btn-outline-primary" href="detail_alur.php?id=<?= $a['id_alur'] ?>">Lihat Detail</a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>

        <?php if (!$alur): ?>
            <p class="text-muted">Belum ada alur belajar yang dipublikasikan.</p>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer_portal.html'; ?>