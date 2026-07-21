<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin', 'Anggota'], true)) {
    header('Location: ../login.html');
    exit;
}

function catatanBack(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: ' . (in_array($_SESSION['role'], ['Super Admin', 'Admin'], true) ? 'kelola_catatan.php' : '../portal/tambah_catatan.php'));
    exit;
}

$action = $_GET['action'] ?? '';
$id = (int) ($_GET['id'] ?? $_POST['id_catatan'] ?? 0);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $judul = trim($_POST['judul_kegiatan'] ?? '');
        $jenis = $_POST['jenis_kegiatan'] ?? '';
        
        if ($judul === '' || !in_array($jenis, ['Lomba', 'Workshop', 'Pelatihan', 'Seminar'], true)) {
            throw new RuntimeException('Judul dan jenis kegiatan wajib diisi.');
        }
        
        $conn->prepare(
            "INSERT INTO CATATAN_PENGALAMAN 
             (id_user, judul_kegiatan, jenis_kegiatan, kategori, pengalaman, kendala, solusi, status, tgl_unggah) 
             VALUES (:user, :judul, :jenis, :kategori, :pengalaman, :kendala, :solusi, 'Pending', CURDATE())"
        )->execute([
            ':user' => $_SESSION['id_user'],
            ':judul' => $judul,
            ':jenis' => $jenis,
            ':kategori' => trim($_POST['kategori'] ?? ''),
            ':pengalaman' => trim($_POST['pengalaman'] ?? ''),
            ':kendala' => trim($_POST['kendala'] ?? ''),
            ':solusi' => trim($_POST['solusi'] ?? '')
        ]);
        
        catatanBack('success', 'Catatan berhasil diunggah dan menunggu approval.');
    }

    if (!in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
        throw new RuntimeException('Anda tidak memiliki akses untuk mengelola status catatan.');
    }

    if ($id <= 0) {
        throw new RuntimeException('ID catatan tidak valid.');
    }

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'Published' : 'Rejected';
        
        $conn->prepare(
            'UPDATE CATATAN_PENGALAMAN 
             SET status = :status, id_approver = :approver 
             WHERE id_catatan = :id'
        )->execute([
            ':status' => $status,
            ':approver' => $_SESSION['id_user'],
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
        $conn->prepare('DELETE FROM CATATAN_PENGALAMAN WHERE id_catatan = :id')->execute([':id' => $id]);
        catatanBack('success', 'Catatan berhasil dihapus.');
    }

    throw new RuntimeException('Aksi catatan tidak dikenali.');

} catch (Throwable $e) {
    catatanBack('danger', $e->getMessage());
}
?>
