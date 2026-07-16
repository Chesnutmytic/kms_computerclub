<?php
/**
 * admin/proses_materi.php
 * -----------------------------------------------------------
 * Menangani aksi: approve, reject, edit, delete untuk ARSIP_MATERI.
 * Diakses via ?action=approve|reject|edit|delete
 */
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'])) {
    header('Location: ../portal/index.html');
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

try {
    if ($action === 'approve') {

        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE ARSIP_MATERI SET status = 'Published', id_approver = :approver WHERE id_arsip = :id");
        $stmt->execute([':approver' => $_SESSION['id_user'], ':id' => $id]);

        setFlash('success', 'Materi berhasil di-approve dan dipublish.');

    } elseif ($action === 'reject') {

        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("UPDATE ARSIP_MATERI SET status = 'Rejected', id_approver = :approver WHERE id_arsip = :id");
        $stmt->execute([':approver' => $_SESSION['id_user'], ':id' => $id]);

        // Catatan: alasan reject untuk saat ini hanya disimpan sebagai flash message,
        // belum ada kolom khusus alasan_reject di skema database.
        setFlash('warning', 'Materi telah direject.');

    } elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {

        $id             = (int)($_POST['id_arsip'] ?? 0);
        $judul_dokumen  = trim($_POST['judul_dokumen'] ?? '');
        $kategori       = trim($_POST['kategori'] ?? '');
        $deskripsi      = trim($_POST['deskripsi'] ?? '');
        $status         = trim($_POST['status'] ?? 'Published');

        if ($id <= 0 || $judul_dokumen === '') {
            setFlash('warning', 'Data tidak lengkap.');
            header('Location: kelola_materi.php');
            exit;
        }

        $stmt = $conn->prepare("UPDATE ARSIP_MATERI 
                                 SET judul_dokumen = :judul, kategori = :kategori, deskripsi = :deskripsi, status = :status 
                                 WHERE id_arsip = :id");
        $stmt->execute([
            ':judul'     => $judul_dokumen,
            ':kategori'  => $kategori,
            ':deskripsi' => $deskripsi,
            ':status'    => $status,
            ':id'        => $id,
        ]);

        setFlash('success', 'Materi berhasil diperbarui.');

    } elseif ($action === 'delete') {

        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM ARSIP_MATERI WHERE id_arsip = :id");
        $stmt->execute([':id' => $id]);

        setFlash('success', 'Materi berhasil dihapus.');

    } else {
        setFlash('warning', 'Aksi tidak dikenali.');
    }

} catch (PDOException $e) {
    setFlash('danger', 'Terjadi kesalahan: ' . $e->getMessage());
}

header('Location: kelola_materi.php');
exit;
