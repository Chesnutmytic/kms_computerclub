<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

function back(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: user_management.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($id <= 0) back('danger', 'ID tidak valid.');

try {
    if ($action === 'approve') {
        $conn->prepare(
            "UPDATE pengguna SET status_akun = 'Aktif' WHERE id_user = :id"
        )->execute([':id' => $id]);
        back('success', 'Akun berhasil disetujui. Anggota dapat login sekarang.');
    }

    if ($action === 'reject') {
        $conn->prepare(
            "UPDATE pengguna SET status_akun = 'Ditolak' WHERE id_user = :id"
        )->execute([':id' => $id]);
        back('warning', 'Akun telah ditolak. Anggota tidak dapat login.');
    }

    back('danger', 'Aksi tidak dikenali.');
} catch (Throwable $e) {
    back('danger', $e->getMessage());
}
