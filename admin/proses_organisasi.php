<?php
session_start();
require_once '../config/koneksi.php';
require_once '../rag/auto_embed.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

function orgBack(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
    header('Location: kelola_organisasi.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id_organisasi'] ?? 0);

try {
    // ── CREATE ────────────────────────────────────────────────────────────────
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $judul = trim($_POST['judul_dokumen'] ?? '');
        $kategori = trim($_POST['kategori_organisasi'] ?? '');
        if ($judul === '') {
            throw new RuntimeException('Judul dokumen wajib diisi.');
        }
        if ($kategori === '') {
            throw new RuntimeException('Kategori wajib dipilih.');
        }

        // Upload file dokumen (PDF, DOC, DOCX, PPT, PPTX)
        $filePath = null;
        if (!empty($_FILES['file_path']['name'])) {
            if ($_FILES['file_path']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('File dokumen gagal diunggah.');
            }
            if ($_FILES['file_path']['size'] > 10 * 1024 * 1024) {
                throw new RuntimeException('Ukuran file maksimal 10 MB.');
            }
            $ext = strtolower(pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
            if (!in_array($ext, $allowed, true)) {
                throw new RuntimeException('File harus berformat PDF, DOC, DOCX, PPT, atau PPTX.');
            }
            $dir = '../assets/uploads';
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                throw new RuntimeException('Folder upload tidak dapat dibuat.');
            }
            $name = uniqid('org_doc_', true) . '.' . $ext;
            if (!move_uploaded_file($_FILES['file_path']['tmp_name'], $dir . '/' . $name)) {
                throw new RuntimeException('File dokumen tidak dapat disimpan.');
            }
            $filePath = 'assets/uploads/' . $name;
        }

        // Upload file media (gambar)
        $fileMedia = null;
        if (!empty($_FILES['file_media']['name'])) {
            if ($_FILES['file_media']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['file_media']['name'], PATHINFO_EXTENSION));
                $name = uniqid('org_img_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['file_media']['tmp_name'], '../assets/uploads/' . $name)) {
                    $fileMedia = 'assets/uploads/' . $name;
                }
            }
        }

        $conn->prepare(
            "INSERT INTO arsip_organisasi
             (id_user, judul_dokumen, kategori_organisasi, deskripsi, file_path, file_media, status, tgl_unggah)
             VALUES (:user, :judul, :kategori, :deskripsi, :file, :media, 'Pending', NOW())"
        )->execute([
            ':user'      => $_SESSION['id_user'],
            ':judul'     => $judul,
            ':kategori'  => $kategori,
            ':deskripsi' => trim($_POST['deskripsi'] ?? ''),
            ':file'      => $filePath,
            ':media'     => $fileMedia,
        ]);

        orgBack('success', 'Arsip organisasi berhasil diunggah dan menunggu approval Super Admin.');
    }

    // ── APPROVE / REJECT ──────────────────────────────────────────────────────
    if (in_array($action, ['approve', 'reject'], true)) {
        if ($_SESSION['role'] !== 'Super Admin') {
            throw new RuntimeException('Hanya Super Admin yang dapat mengubah status arsip.');
        }
        if ($id <= 0) {
            throw new RuntimeException('ID arsip tidak valid.');
        }

        $status  = $action === 'approve' ? 'Published' : 'Rejected';
        $alasan  = $action === 'reject' ? trim($_POST['alasan_reject'] ?? '') : null;

        $conn->prepare(
            "UPDATE arsip_organisasi
             SET status = :status, id_approver = :approver, alasan_reject = :alasan
             WHERE id_organisasi = :id AND status = 'Pending'"
        )->execute([
            ':status'   => $status,
            ':approver' => $_SESSION['id_user'],
            ':alasan'   => $alasan,
            ':id'       => $id,
        ]);

        if ($action === 'approve') {
            triggerEmbedOrganisasi($conn, $id);
        }

        orgBack(
            $action === 'approve' ? 'success' : 'warning',
            $action === 'approve' ? 'Arsip organisasi berhasil dipublish.' : 'Arsip organisasi telah direject.'
        );
    }

    // ── EDIT ──────────────────────────────────────────────────────────────────
    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
        $judul    = trim($_POST['judul_dokumen'] ?? '');
        $kategori = trim($_POST['kategori_organisasi'] ?? '');
        if ($judul === '') {
            throw new RuntimeException('Judul dokumen wajib diisi.');
        }

        $stmt = $conn->prepare('SELECT status, file_path, file_media FROM arsip_organisasi WHERE id_organisasi = :id');
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            throw new RuntimeException('Arsip tidak ditemukan.');
        }

        $status       = $existing['status'];
        $filePath     = $existing['file_path'];
        $fileMedia    = $existing['file_media'];
        $alasanReject = $existing['alasan_reject'] ?? null;

        // Jika status saat ini Rejected, saat Admin/Super Admin simpan → ubah ke Pending dan bersihkan alasan
        if ($existing['status'] === 'Rejected') {
            $status = 'Pending';
            $alasanReject = null;
        }

        // Super Admin bisa override status secara eksplisit
        if ($_SESSION['role'] === 'Super Admin'
            && isset($_POST['status'])
            && in_array($_POST['status'], ['Published', 'Rejected', 'Pending'], true)) {
            $status = $_POST['status'];
            if ($status !== 'Rejected') {
                $alasanReject = null;
            }
        }

        // Upload file dokumen baru (opsional)
        if (!empty($_FILES['file_path']['name'])) {
            if ($_FILES['file_path']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('File gagal diunggah.');
            }
            if ($_FILES['file_path']['size'] > 10 * 1024 * 1024) {
                throw new RuntimeException('Ukuran file maksimal 10 MB.');
            }
            $ext = strtolower(pathinfo($_FILES['file_path']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx', 'ppt', 'pptx'], true)) {
                throw new RuntimeException('File harus berformat PDF, DOC, DOCX, PPT, atau PPTX.');
            }
            $dir = '../assets/uploads';
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                throw new RuntimeException('Folder upload tidak dapat dibuat.');
            }
            if (!empty($filePath) && file_exists('../' . $filePath)) {
                @unlink('../' . $filePath);
            }
            $name     = uniqid('org_doc_', true) . '.' . $ext;
            if (!move_uploaded_file($_FILES['file_path']['tmp_name'], $dir . '/' . $name)) {
                throw new RuntimeException('File tidak dapat disimpan.');
            }
            $filePath = 'assets/uploads/' . $name;
        }

        // Upload file media baru (opsional)
        if (!empty($_FILES['file_media']['name'])) {
            if ($_FILES['file_media']['error'] === UPLOAD_ERR_OK) {
                if (!empty($fileMedia) && file_exists('../' . $fileMedia)) {
                    @unlink('../' . $fileMedia);
                }
                $ext  = strtolower(pathinfo($_FILES['file_media']['name'], PATHINFO_EXTENSION));
                $name = uniqid('org_img_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['file_media']['tmp_name'], '../assets/uploads/' . $name)) {
                    $fileMedia = 'assets/uploads/' . $name;
                }
            }
        }

        $conn->prepare(
            'UPDATE arsip_organisasi
             SET judul_dokumen = :judul, kategori_organisasi = :kategori, deskripsi = :deskripsi,
                 status = :status, alasan_reject = :alasan, file_path = :file, file_media = :media
             WHERE id_organisasi = :id'
        )->execute([
            ':judul'     => $judul,
            ':kategori'  => $kategori,
            ':deskripsi' => trim($_POST['deskripsi'] ?? ''),
            ':status'    => $status,
            ':alasan'    => $alasanReject,
            ':file'      => $filePath,
            ':media'     => $fileMedia,
            ':id'        => $id,
        ]);

        orgBack('success', $status === 'Pending'
            ? 'Arsip organisasi berhasil diperbarui dan status kembali Pending untuk ditinjau ulang.'
            : 'Arsip organisasi berhasil diperbarui.');
    }

    // ── DELETE ────────────────────────────────────────────────────────────────
    if ($action === 'delete' && $id > 0) {
        // Hapus file fisik jika ada
        $stmt = $conn->prepare('SELECT file_path, file_media FROM arsip_organisasi WHERE id_organisasi = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            if (!empty($row['file_path']) && file_exists('../' . $row['file_path'])) {
                @unlink('../' . $row['file_path']);
            }
            if (!empty($row['file_media']) && file_exists('../' . $row['file_media'])) {
                @unlink('../' . $row['file_media']);
            }
        }
        $conn->prepare('DELETE FROM arsip_organisasi WHERE id_organisasi = :id')->execute([':id' => $id]);
        orgBack('success', 'Arsip organisasi berhasil dihapus.');
    }

    throw new RuntimeException('Aksi tidak dikenali.');

} catch (Throwable $e) {
    orgBack('danger', $e->getMessage());
}
?>
