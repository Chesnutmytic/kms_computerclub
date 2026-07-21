<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$count = static fn(string $sql): int => (int)$conn->query($sql)->fetch()['total'];
$stats = [
    'materi' => $count("SELECT COUNT(*) total FROM ARSIP_MATERI WHERE status = 'Published'"),
    'catatan' => $count("SELECT COUNT(*) total FROM CATATAN_PENGALAMAN WHERE status = 'Published'"),
    'alur' => $count("SELECT COUNT(*) total FROM ALUR_PEMBELAJARAN WHERE status = 'Published'")
];

$materi = $conn->query(
    "SELECT am.judul_dokumen, am.kategori, am.deskripsi, am.tgl_unggah, p.nama_lengkap 
     FROM ARSIP_MATERI am 
     JOIN PENGGUNA p ON p.id_user = am.id_user 
     WHERE am.status = 'Published' 
     ORDER BY am.tgl_unggah DESC, am.id_arsip DESC 
     LIMIT 5"
)->fetchAll();

$catatan = $conn->query(
    "SELECT cp.judul_kegiatan, cp.jenis_kegiatan, cp.pengalaman, cp.tgl_unggah, p.nama_lengkap 
     FROM CATATAN_PENGALAMAN cp 
     JOIN PENGGUNA p ON p.id_user = cp.id_user 
     WHERE cp.status = 'Published' 
     ORDER BY cp.tgl_unggah DESC, cp.id_catatan DESC 
     LIMIT 5"
)->fetchAll();

include '../includes/header_portal.html';
?>

<main class="container py-5">
    <section class="p-4 p-md-5 mb-5 rounded-4 bg-primary text-white shadow-sm">
        <h1 class="display-6 fw-bold">Selamat datang, <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Anggota') ?>!</h1>
        <p class="lead mb-0">Temukan materi, pengalaman, dan Alur belajar dari Computer Club.</p>
    </section>

    <div class="row g-4 mb-5">
        <?php foreach ([
            ['materi', 'Materi Tersedia', 'file-earmark-text'],
            ['catatan', 'Catatan Pengalaman', 'journal-text'],
            ['alur', 'Alur Belajar', 'diagram-3']
        ] as [$key, $label, $icon]): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-<?= $icon ?> fs-1 text-primary"></i>
                        <div>
                            <div class="text-muted"><?= $label ?></div>
                            <div class="fs-2 fw-bold"><?= $stats[$key] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Materi Terbaru</h2>
            <a href="arsip_materi.php">Lihat semua</a>
        </div>
        <div class="row g-4">
            <?php foreach ($materi as $m): ?>
                <div class="col-md-6 col-lg">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <span class="badge text-bg-primary mb-2"><?= htmlspecialchars($m['kategori'] ?: 'Umum') ?></span>
                            <h3 class="h6"><?= htmlspecialchars($m['judul_dokumen']) ?></h3>
                            <p class="small text-muted mb-2">
                                oleh <?= htmlspecialchars($m['nama_lengkap']) ?> · <?= htmlspecialchars($m['tgl_unggah']) ?>
                            </p>
                            <p class="small mb-0">
                                <?= htmlspecialchars(mb_strimwidth(strip_tags($m['deskripsi'] ?? ''), 0, 100, '…')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$materi): ?>
                <p class="text-muted">Belum ada materi published.</p>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Catatan Terbaru</h2>
            <a href="catatan.php">Lihat semua</a>
        </div>
        <div class="list-group shadow-sm">
            <?php foreach ($catatan as $c): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <strong><?= htmlspecialchars($c['judul_kegiatan']) ?></strong>
                            <div class="small text-muted">
                                <?= htmlspecialchars($c['jenis_kegiatan']) ?> · <?= htmlspecialchars($c['nama_lengkap']) ?>
                            </div>
                        </div>
                        <small><?= htmlspecialchars($c['tgl_unggah']) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$catatan): ?>
                <div class="list-group-item text-muted">Belum ada catatan published.</div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '../includes/footer_portal.html'; ?>