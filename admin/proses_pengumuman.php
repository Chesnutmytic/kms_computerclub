<?php
session_start();
require_once '../config/koneksi.php';

// Hanya Super Admin yang boleh akses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Super Admin') {
    header('Location: ../login.html');
    exit;
}

// Buat tabel jika belum ada
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS pengumuman (
            id_pengumuman INT AUTO_INCREMENT PRIMARY KEY,
            id_user INT NOT NULL,
            judul VARCHAR(255) NOT NULL,
            isi_pengumuman TEXT NOT NULL,
            tgl_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_user) REFERENCES pengguna(id_user) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}

$action = $_GET['action'] ?? '';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi_pengumuman'] ?? '');

    if ($judul === '' || $isi === '') {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Judul dan isi pengumuman wajib diisi.'];
        header('Location: kelola_pengumuman.php');
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO pengumuman (id_user, judul, isi_pengumuman) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['id_user'], $judul, $isi]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pengumuman berhasil ditambahkan.'];
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal menambahkan pengumuman.'];
    }
    header('Location: kelola_pengumuman.php');
    exit;
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM pengumuman WHERE id_pengumuman = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Pengumuman berhasil dihapus.'];
        } catch (PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Gagal menghapus pengumuman.'];
        }
    }
    header('Location: kelola_pengumuman.php');
    exit;
}

header('Location: kelola_pengumuman.php');
exit;
