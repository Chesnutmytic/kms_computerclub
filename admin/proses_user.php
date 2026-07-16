<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html'); exit;
}

function back(string $message): void { $_SESSION['flash'] = ['type' => 'success', 'msg' => $message]; header('Location: user_management.php'); exit; }
function validRole(string $role): bool { return in_array($role, ['Super Admin', 'Admin', 'Anggota'], true); }

$action = $_GET['action'] ?? '';
$id = (int) ($_POST['id_user'] ?? $_GET['id'] ?? 0);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $role = $_POST['role'] ?? 'Anggota';
        if ($_SESSION['role'] !== 'Super Admin' && $role !== 'Anggota') throw new RuntimeException('Hanya Super Admin yang dapat membuat Admin.');
        if (!validRole($role)) throw new RuntimeException('Role tidak valid.');
        $stmt = $conn->prepare('INSERT INTO PENGGUNA (nama_lengkap, kelas, username, password, role) VALUES (:nama, :kelas, :username, :password, :role)');
        $stmt->execute([':nama' => trim($_POST['nama_lengkap'] ?? ''), ':kelas' => trim($_POST['kelas'] ?? ''), ':username' => trim($_POST['username'] ?? ''), ':password' => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT), ':role' => $role]);
        back('Pengguna berhasil ditambahkan.');
    }
    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
        $role = $_POST['role'] ?? 'Anggota';
        $current = $conn->prepare('SELECT role FROM PENGGUNA WHERE id_user=:id');
        $current->execute([':id' => $id]);
        $currentRole = $current->fetchColumn();
        if ($currentRole === false) throw new RuntimeException('Pengguna tidak ditemukan.');
        if ($_SESSION['role'] !== 'Super Admin' && in_array($currentRole, ['Super Admin', 'Admin'], true)) throw new RuntimeException('Hanya Super Admin yang dapat mengubah akun Admin.');
        if ($_SESSION['role'] !== 'Super Admin' && $role !== 'Anggota') throw new RuntimeException('Hanya Super Admin yang dapat mengubah role Admin.');
        if (!validRole($role)) throw new RuntimeException('Role tidak valid.');
        $params = [':id' => $id, ':nama' => trim($_POST['nama_lengkap'] ?? ''), ':kelas' => trim($_POST['kelas'] ?? ''), ':username' => trim($_POST['username'] ?? ''), ':role' => $role];
        $sql = 'UPDATE PENGGUNA SET nama_lengkap=:nama, kelas=:kelas, username=:username, role=:role';
        if (($_POST['password'] ?? '') !== '') { $sql .= ', password=:password'; $params[':password'] = password_hash($_POST['password'], PASSWORD_DEFAULT); }
        $stmt = $conn->prepare($sql . ' WHERE id_user=:id'); $stmt->execute($params); back('Pengguna berhasil diperbarui.');
    }
    if ($action === 'delete' && $id > 0) {
        if ($id === (int) $_SESSION['id_user']) throw new RuntimeException('Akun yang sedang digunakan tidak dapat dihapus.');
        $conn->prepare('DELETE FROM PENGGUNA WHERE id_user=:id')->execute([':id' => $id]); back('Pengguna berhasil dihapus.');
    }
    throw new RuntimeException('Aksi pengguna tidak dikenali.');
} catch (Throwable $e) { $_SESSION['flash'] = ['type' => 'danger', 'msg' => $e->getMessage()]; header('Location: user_management.php'); exit; }
