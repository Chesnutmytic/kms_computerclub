<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin', 'Ketua'], true)) {
    header('Location: ../login.html');
    exit;
}

function materiBack(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
    header('Location: kelola_materi.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? $_POST['id_arsip'] ?? 0);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $judul = trim($_POST['judul_dokumen'] ?? '');
        if ($judul === '') {
            throw new RuntimeException('Judul materi wajib diisi.');
        }
        
        $filePath = null;
        if (!empty($_FILES['dokumen']['name'])) {
            if ($_FILES['dokumen']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('File materi gagal diunggah.');
            }
            if ($_FILES['dokumen']['size'] > 10 * 1024 * 1024) {
                throw new RuntimeException('Ukuran file maksimal 10 MB.');
            }
            $extension = strtolower(pathinfo($_FILES['dokumen']['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'ppt', 'pptx'], true)) {
                throw new RuntimeException('File materi harus berformat PDF, PPT, atau PPTX.');
            }
            
            $dir = '../assets/uploads';
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                throw new RuntimeException('Folder upload tidak dapat dibuat.');
            }
            
            $name = uniqid('materi_', true) . '.' . $extension;
            if (!move_uploaded_file($_FILES['dokumen']['tmp_name'], $dir . '/' . $name)) {
                throw new RuntimeException('File materi tidak dapat disimpan.');
            }
            $filePath = 'assets/uploads/' . $name;
        }
        
        $fileMedia = null;
        if (!empty($_FILES['file_media']['name'])) {
            if ($_FILES['file_media']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['file_media']['name'], PATHINFO_EXTENSION));
                $name = uniqid('media_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['file_media']['tmp_name'], '../assets/uploads/' . $name)) {
                    $fileMedia = 'assets/uploads/' . $name;
                }
            }
        }
        
        // Cek masa kepengurusan aktif
        $idKepengurusan = (int)($_POST['id_kepengurusan'] ?? 0);
        if ($idKepengurusan <= 0) {
            $stmtKep = $conn->query("SELECT id_kepengurusan FROM masa_kepengurusan WHERE status = 'Aktif' LIMIT 1");
            $idKepengurusan = (int)($stmtKep->fetchColumn() ?: 0);
        }

        if ($idKepengurusan <= 0) {
            throw new RuntimeException('Tidak ada masa kepengurusan aktif. Materi tidak dapat dibuat.');
        }

        $tglBuka = trim($_POST['tgl_buka'] ?? '');
        if ($tglBuka === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglBuka)) {
            $tglBuka = date('Y-m-d');
        }

        $conn->prepare(
            "INSERT INTO arsip_materi 
             (id_user, id_kepengurusan, judul_dokumen, deskripsi, kategori, file_path, file_media, link_tautan, tags, status, tgl_unggah, tgl_buka) 
             VALUES (:user, :kep, :judul, :deskripsi, :kategori, :file, :media, :link, :tags, 'Pending', CURDATE(), :tgl_buka)"
        )->execute([
            ':user' => $_SESSION['id_user'],
            ':kep'  => $idKepengurusan,
            ':judul' => $judul,
            ':deskripsi' => trim($_POST['deskripsi'] ?? ''),
            ':kategori' => trim($_POST['kategori'] ?? ''),
            ':file' => $filePath,
            ':media' => $fileMedia,
            ':link' => trim($_POST['link_tautan'] ?? ''),
            ':tags' => trim($_POST['tags'] ?? ''),
            ':tgl_buka' => $tglBuka
        ]);
        
        materiBack('success', 'Materi berhasil diunggah dan menunggu approval.');
    }

    if (in_array($action, ['approve', 'reject'], true)) {
        if ($_SESSION['role'] !== 'Super Admin') {
            throw new RuntimeException('Hanya Super Admin yang dapat menyetujui atau menolak materi.');
        }
        if ($id <= 0) {
            throw new RuntimeException('ID materi tidak valid.');
        }
        
        $status = $action === 'approve' ? 'Published' : 'Rejected';
        $alasan = $action === 'reject' ? trim($_POST['alasan_reject'] ?? '') : null;

        $conn->prepare(
            "UPDATE arsip_materi 
             SET status = :status, id_approver = :approver, alasan_reject = :alasan
             WHERE id_arsip = :id AND status = 'Pending'"
        )->execute([
            ':status' => $status,
            ':approver' => $_SESSION['id_user'],
            ':alasan' => $alasan,
            ':id' => $id
        ]);
        
        if ($action === 'approve') {
            require_once '../rag/auto_embed.php';
            triggerEmbedMateri($conn, $id); 
        }

        materiBack(
            $action === 'approve' ? 'success' : 'warning',
            $action === 'approve' ? 'Materi berhasil dipublish.' : 'Materi telah direject.'
        );
    }

    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
        $judul = trim($_POST['judul_dokumen'] ?? '');
        if ($judul === '') {
            throw new RuntimeException('Judul materi wajib diisi.');
        }

        $stmt = $conn->prepare(
            "SELECT am.status, am.file_path, am.file_media, am.alasan_reject, mk.status AS status_kepengurusan
             FROM arsip_materi am
             LEFT JOIN masa_kepengurusan mk ON mk.id_kepengurusan = am.id_kepengurusan
             WHERE am.id_arsip = :id"
        );
        $stmt->execute([':id' => $id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            throw new RuntimeException('Materi tidak ditemukan.');
        }

        if (($existing['status_kepengurusan'] ?? '') === 'Diarsipkan') {
            throw new RuntimeException('Materi pada masa kepengurusan yang sudah diarsipkan tidak dapat diedit.');
        }

        $status       = $existing['status'];
        $alasanReject = $existing['alasan_reject'];
        $filePath     = $existing['file_path'];
        $fileMedia    = $existing['file_media'] ?? null;

        // Jika materi berstatus 'Rejected', saat disimpan ubah status kembali menjadi 'Pending' dan reset alasan_reject
        if ($existing['status'] === 'Rejected') {
            $status = 'Pending';
            $alasanReject = null;
        }

        // Izin ubah status secara eksplisit jika dikirim oleh Super Admin
        if ($_SESSION['role'] === 'Super Admin'
            && isset($_POST['status'])
            && in_array($_POST['status'], ['Published', 'Rejected', 'Pending'], true)) {
            $status = $_POST['status'];
            if ($status !== 'Rejected') {
                $alasanReject = null;
            }
        }

        // Proses upload file baru (opsional — jika dikirim)
        if (!empty($_FILES['dokumen']['name'])) {
            if ($_FILES['dokumen']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('File gagal diunggah.');
            }
            if ($_FILES['dokumen']['size'] > 10 * 1024 * 1024) {
                throw new RuntimeException('Ukuran file maksimal 10 MB.');
            }
            $ext = strtolower(pathinfo($_FILES['dokumen']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'ppt', 'pptx'], true)) {
                throw new RuntimeException('File harus berformat PDF, PPT, atau PPTX.');
            }

            $dir = '../assets/uploads';
            if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                throw new RuntimeException('Folder upload tidak dapat dibuat.');
            }

            // Hapus file lama jika ada
            if (!empty($filePath) && file_exists('../' . $filePath)) {
                @unlink('../' . $filePath);
            }

            $name     = uniqid('materi_', true) . '.' . $ext;
            if (!move_uploaded_file($_FILES['dokumen']['tmp_name'], $dir . '/' . $name)) {
                throw new RuntimeException('File tidak dapat disimpan.');
            }
            $filePath = 'assets/uploads/' . $name;
        }

        if (!empty($_FILES['file_media']['name'])) {
            if ($_FILES['file_media']['error'] === UPLOAD_ERR_OK) {
                if (!empty($fileMedia) && file_exists('../' . $fileMedia)) {
                    @unlink('../' . $fileMedia);
                }
                $ext = strtolower(pathinfo($_FILES['file_media']['name'], PATHINFO_EXTENSION));
                $name = uniqid('media_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['file_media']['tmp_name'], '../assets/uploads/' . $name)) {
                    $fileMedia = 'assets/uploads/' . $name;
                }
            }
        }

        $tglBuka = trim($_POST['tgl_buka'] ?? '');
        if ($tglBuka === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglBuka)) {
            $tglBuka = date('Y-m-d');
        }

        $conn->prepare(
            'UPDATE arsip_materi
             SET judul_dokumen = :judul, kategori = :kategori, deskripsi = :deskripsi,
                 status = :status, alasan_reject = :alasan, file_path = :file, file_media = :media, link_tautan = :link, tags = :tags, tgl_buka = :tgl_buka
             WHERE id_arsip = :id'
        )->execute([
            ':judul'     => $judul,
            ':kategori'  => trim($_POST['kategori'] ?? ''),
            ':deskripsi' => trim($_POST['deskripsi'] ?? ''),
            ':status'    => $status,
            ':alasan'    => $alasanReject,
            ':file'      => $filePath,
            ':media'     => $fileMedia,
            ':link'      => trim($_POST['link_tautan'] ?? ''),
            ':tags'      => trim($_POST['tags'] ?? ''),
            ':tgl_buka'  => $tglBuka,
            ':id'        => $id,
        ]);

        materiBack('success', $status === 'Pending' ? 'Materi berhasil diperbarui dan status kembali Pending untuk ditinjau.' : 'Materi berhasil diperbarui.');
    }

    if ($action === 'delete' && $id > 0) {
        $conn->prepare('DELETE FROM arsip_materi WHERE id_arsip = :id')->execute([':id' => $id]);
        materiBack('success', 'Materi berhasil dihapus.');
    }

    throw new RuntimeException('Aksi materi tidak dikenali.');

} catch (Throwable $e) {
    materiBack('danger', $e->getMessage());
}
?>
