<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin', 'Anggota'], true)) {
    header('Location: ../login.html');
    exit;
}

function catatanBack(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: ' . (in_array($_SESSION['role'], ['Super Admin', 'Admin'], true) ? 'kelola_catatan.php' : '../portal/catatan.php?tab=saya'));
    exit;
}

$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? $_POST['id_catatan'] ?? 0);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // ── Validasi akses via event ─────────────────────────────────────────────
        $idEvent = (int) ($_POST['id_event'] ?? 0);

        // Non-admin wajib menyertakan id_event yang valid
        if (!in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
            if ($idEvent <= 0) {
                throw new RuntimeException('Kamu belum memiliki akses untuk menambahkan catatan pengalaman. Ikuti event terlebih dahulu.');
            }

            $stmtAkses = $conn->prepare(
                "SELECT e.id_event, e.nama_event, e.jenis_event
                 FROM `event` e
                 JOIN event_peserta ep ON ep.id_event = e.id_event
                 WHERE e.id_event = :ev
                   AND ep.id_user = :u
                   AND e.status = 'Selesai'"
            );
            $stmtAkses->execute([':ev' => $idEvent, ':u' => $_SESSION['id_user']]);
            $eventData = $stmtAkses->fetch(PDO::FETCH_ASSOC);

            if (!$eventData) {
                throw new RuntimeException('Akses ditolak: kamu bukan peserta event ini atau event belum selesai.');
            }

            // Cegah duplikasi catatan untuk event yang sama oleh user yang sama
            $stmtDup = $conn->prepare(
                "SELECT id_catatan FROM catatan_pengalaman WHERE id_user = :u AND id_event = :ev LIMIT 1"
            );
            $stmtDup->execute([':u' => $_SESSION['id_user'], ':ev' => $idEvent]);
            if ($stmtDup->fetch()) {
                throw new RuntimeException('Kamu sudah pernah menambahkan catatan untuk event ini.');
            }

            // Judul & jenis diambil dari event, tidak dari input bebas user
            $judul = $eventData['nama_event'];
            $jenis = $eventData['jenis_event'];
        } else {
            // Admin boleh mengisi bebas
            $judul   = trim($_POST['judul_kegiatan'] ?? '');
            $jenis   = $_POST['jenis_kegiatan'] ?? '';
            $idEvent = $idEvent > 0 ? $idEvent : null;
        }

        if ($judul === '' || !in_array($jenis, ['Lomba', 'Workshop', 'Pelatihan', 'Seminar', 'Lainnya'], true)) {
            throw new RuntimeException('Judul dan jenis kegiatan wajib diisi.');
        }

        $gambar = null;
        if (!empty($_FILES['gambar_dokumentasi']['name'])) {
            if ($_FILES['gambar_dokumentasi']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['gambar_dokumentasi']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $name = uniqid('dok_', true) . '.' . $ext;
                    $dir = '../assets/uploads/catatan';
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    if (move_uploaded_file($_FILES['gambar_dokumentasi']['tmp_name'], $dir . '/' . $name)) {
                        $gambar = 'assets/uploads/catatan/' . $name;
                    }
                }
            }
        }

        $conn->prepare(
            "INSERT INTO catatan_pengalaman
             (id_user, id_event, judul_kegiatan, jenis_kegiatan, kategori, pengalaman, kendala, solusi, gambar_dokumentasi, link_tautan, tags, status, tgl_unggah)
             VALUES (:user, :id_event, :judul, :jenis, :kategori, :pengalaman, :kendala, :solusi, :gambar, :link, :tags, 'Pending', CURDATE())"
        )->execute([
            ':user'      => $_SESSION['id_user'],
            ':id_event'  => $idEvent,
            ':judul'     => $judul,
            ':jenis'     => $jenis,
            ':kategori'  => trim($_POST['kategori'] ?? ''),
            ':pengalaman'=> trim($_POST['pengalaman'] ?? ''),
            ':kendala'   => trim($_POST['kendala'] ?? ''),
            ':solusi'    => trim($_POST['solusi'] ?? ''),
            ':gambar'    => $gambar,
            ':link'      => trim($_POST['link_tautan'] ?? '') ?: null,
            ':tags'      => trim($_POST['tags'] ?? '')
        ]);

        catatanBack('success', 'Catatan berhasil diunggah dan menunggu approval.');
    }

    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = (int) ($_POST['id_catatan'] ?? 0);
        $judul = trim($_POST['judul_kegiatan'] ?? '');
        $jenis = $_POST['jenis_kegiatan'] ?? '';

        if ($id <= 0 || $judul === '' || !in_array($jenis, ['Lomba', 'Workshop', 'Pelatihan', 'Seminar'], true)) {
            throw new RuntimeException('Judul dan jenis kegiatan wajib diisi.');
        }

        $stmtCheck = $conn->prepare("SELECT * FROM catatan_pengalaman WHERE id_catatan = :id");
        $stmtCheck->execute([':id' => $id]);
        $existing = $stmtCheck->fetch();

        if (!$existing) {
            throw new RuntimeException('Catatan tidak ditemukan.');
        }

        if (!in_array($_SESSION['role'], ['Super Admin', 'Admin'], true) && $existing['id_user'] != $_SESSION['id_user']) {
            throw new RuntimeException('Anda tidak memiliki akses untuk mengedit catatan ini.');
        }

        $gambar = $existing['gambar_dokumentasi'];
        if (!empty($_FILES['gambar_dokumentasi']['name'])) {
            if ($_FILES['gambar_dokumentasi']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['gambar_dokumentasi']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $name = uniqid('dok_', true) . '.' . $ext;
                    $dir = '../assets/uploads/catatan';
                    if (!is_dir($dir)) mkdir($dir, 0775, true);
                    if (move_uploaded_file($_FILES['gambar_dokumentasi']['tmp_name'], $dir . '/' . $name)) {
                        $gambar = 'assets/uploads/catatan/' . $name;
                    }
                }
            }
        }

        $conn->prepare(
            "UPDATE catatan_pengalaman 
             SET judul_kegiatan = :judul, 
                 jenis_kegiatan = :jenis, 
                 kategori = :kategori, 
                 pengalaman = :pengalaman, 
                 kendala = :kendala, 
                 solusi = :solusi, 
                 gambar_dokumentasi = :gambar, 
                 link_tautan = :link, 
                 tags = :tags, 
                 status = 'Pending', 
                 alasan_reject = NULL 
             WHERE id_catatan = :id"
        )->execute([
            ':judul' => $judul,
            ':jenis' => $jenis,
            ':kategori' => trim($_POST['kategori'] ?? ''),
            ':pengalaman' => trim($_POST['pengalaman'] ?? ''),
            ':kendala' => trim($_POST['kendala'] ?? ''),
            ':solusi' => trim($_POST['solusi'] ?? ''),
            ':gambar' => $gambar,
            ':link'   => trim($_POST['link_tautan'] ?? '') ?: null,
            ':tags'   => trim($_POST['tags'] ?? ''),
            ':id'     => $id
        ]);

        catatanBack('success', 'Catatan berhasil diperbarui dan status kembali Pending untuk ditinjau.');
    }

    if (!in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
        throw new RuntimeException('Anda tidak memiliki akses untuk mengelola status catatan.');
    }

    if ($id <= 0) {
        throw new RuntimeException('ID catatan tidak valid.');
    }

    if ($action === 'approve' || ($action === 'reject' && $_SERVER['REQUEST_METHOD'] === 'POST')) {
        $status  = $action === 'approve' ? 'Published' : 'Rejected';
        $alasan  = $action === 'reject' ? trim($_POST['alasan_reject'] ?? '') : null;

        $conn->prepare(
            'UPDATE catatan_pengalaman 
             SET status = :status, id_approver = :approver, alasan_reject = :alasan 
             WHERE id_catatan = :id'
        )->execute([
            ':status' => $status,
            ':approver' => $_SESSION['id_user'],
            ':alasan' => $alasan,
            ':id' => $id
        ]);
        if ($action === 'approve') {
            require_once '../rag/auto_embed.php';
            triggerEmbedCatatan($conn, $id); 
        }
        catatanBack(
            $action === 'approve' ? 'success' : 'warning',
            $action === 'approve' ? 'Catatan berhasil dipublish.' : 'Catatan telah direject.'
        );
    }

    if ($action === 'delete') {
        $conn->prepare('DELETE FROM catatan_pengalaman WHERE id_catatan = :id')->execute([':id' => $id]);
        catatanBack('success', 'Catatan berhasil dihapus.');
    }

    throw new RuntimeException('Aksi catatan tidak dikenali.');

} catch (Throwable $e) {
    catatanBack('danger', $e->getMessage());
}
?>
