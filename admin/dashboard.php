<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

$pageTitle = 'Dashboard';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$count = static fn(string $sql): int => (int)$conn->query($sql)->fetch()['total'];
$summary = [
    'anggota' => $count('SELECT COUNT(*) AS total FROM PENGGUNA'),
    'materi' => $count("SELECT COUNT(*) AS total FROM ARSIP_MATERI WHERE status='Published'"),
    'catatan' => $count("SELECT COUNT(*) AS total FROM CATATAN_PENGALAMAN WHERE status='Published'"),
    'alur' => $count("SELECT COUNT(*) AS total FROM ALUR_PEMBELAJARAN WHERE status='Published'")
];

$materi = $conn->query(
    'SELECT am.judul_dokumen, am.status, am.tgl_unggah, p.nama_lengkap 
     FROM ARSIP_MATERI am 
     JOIN PENGGUNA p ON p.id_user = am.id_user 
     ORDER BY am.id_arsip DESC 
     LIMIT 5'
)->fetchAll();

$catatan = $conn->query(
    'SELECT cp.judul_kegiatan, cp.status, cp.tgl_unggah, p.nama_lengkap 
     FROM CATATAN_PENGALAMAN cp 
     JOIN PENGGUNA p ON p.id_user = cp.id_user 
     ORDER BY cp.id_catatan DESC 
     LIMIT 5'
)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="mb-4">
    <h1 class="h3 mb-1">Dashboard Pengurus</h1>
    <p class="text-muted mb-0">Ringkasan aktivitas KMS Computer Club.</p>
</div>

<div class="row g-4 mb-5">
    <?php foreach ([
        ['anggota', 'Total Anggota', 'people-fill', 'primary'],
        ['materi', 'Materi Published', 'file-earmark-text-fill', 'success'],
        ['catatan', 'Catatan Published', 'journal-text', 'info'],
        ['alur', 'Alur Published', 'diagram-3-fill', 'danger']
    ] as [$key, $label, $icon, $color]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1"><?= $label ?></p>
                        <p class="display-6 fw-bold mb-0"><?= $summary[$key] ?></p>
                    </div>
                    <i class="bi bi-<?= $icon ?> fs-1 text-<?= $color ?>"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-semibold">5 Materi Terbaru</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Pengunggah</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materi as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['judul_dokumen']) ?></td>
                                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                <td><span class="badge text-bg-secondary"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= htmlspecialchars($row['tgl_unggah']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$materi): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada materi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-semibold">5 Catatan Terbaru</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Pengunggah</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($catatan as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['judul_kegiatan']) ?></td>
                                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                <td><span class="badge text-bg-secondary"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= htmlspecialchars($row['tgl_unggah']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$catatan): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada catatan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>