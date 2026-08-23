<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

function back(string $message): void {
    $_SESSION['flash'] = ['type' => 'success', 'msg' => $message];
    header('Location: user_management.php');
    exit;
}

function validRole(string $role): bool {
    return in_array($role, ['Super Admin', 'Admin', 'Anggota'], true);
}

function handleKartuPelajarUpload(): ?string {
    if (empty($_FILES['kartu_pelajar']['name'])) return null;
    $file = $_FILES['kartu_pelajar'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Gagal mengunggah kartu pelajar atau ukuran terlalu besar.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        throw new RuntimeException('Format kartu pelajar harus JPG atau PNG.');
    }
    $ext    = $mime === 'image/png' ? 'png' : 'jpg';
    $dir    = '../assets/uploads/id_cards';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        throw new RuntimeException('Gagal membuat folder upload.');
    }
    $fname  = 'kp_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $fname)) {
        throw new RuntimeException('Gagal menyimpan kartu pelajar.');
    }
    return 'assets/uploads/id_cards/' . $fname;
}

$action = $_GET['action'] ?? '';
$id = (int) ($_POST['id_user'] ?? $_GET['id'] ?? 0);

try {
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $role = $_POST['role'] ?? 'Anggota';
        
        if ($_SESSION['role'] !== 'Super Admin' && $role !== 'Anggota') {
            throw new RuntimeException('Hanya Super Admin yang dapat membuat Admin.');
        }
        if (!validRole($role)) {
            throw new RuntimeException('Role tidak valid.');
        }
        
        $kartuPath = handleKartuPelajarUpload();
        $alasan = trim($_POST['alasan_masuk'] ?? '');
        
        $stmt = $conn->prepare(
            'INSERT INTO pengguna (nama_lengkap, kelas, username, password, role, kartu_pelajar, alasan_masuk, status_akun) 
             VALUES (:nama, :kelas, :username, :password, :role, :kartu, :alasan, "Aktif")'
        );
        $stmt->execute([
            ':nama' => trim($_POST['nama_lengkap'] ?? ''),
            ':kelas' => trim($_POST['kelas'] ?? ''),
            ':username' => trim($_POST['username'] ?? ''),
            ':password' => password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT),
            ':role' => $role,
            ':kartu' => $kartuPath,
            ':alasan' => $alasan
        ]);
        
        back('Pengguna berhasil ditambahkan.');
    }

    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
        $role = $_POST['role'] ?? 'Anggota';
        
        $current = $conn->prepare('SELECT role FROM pengguna WHERE id_user = :id');
        $current->execute([':id' => $id]);
        $currentRole = $current->fetchColumn();
        
        if ($currentRole === false) {
            throw new RuntimeException('Pengguna tidak ditemukan.');
        }
        if ($_SESSION['role'] !== 'Super Admin' && in_array($currentRole, ['Super Admin', 'Admin'], true)) {
            throw new RuntimeException('Hanya Super Admin yang dapat mengubah akun Admin.');
        }
        if ($_SESSION['role'] !== 'Super Admin' && $role !== 'Anggota') {
            throw new RuntimeException('Hanya Super Admin yang dapat mengubah role Admin.');
        }
        if (!validRole($role)) {
            throw new RuntimeException('Role tidak valid.');
        }
        
        $kartuPath = handleKartuPelajarUpload();
        $alasan = trim($_POST['alasan_masuk'] ?? '');

        $params = [
            ':id' => $id,
            ':nama' => trim($_POST['nama_lengkap'] ?? ''),
            ':kelas' => trim($_POST['kelas'] ?? ''),
            ':username' => trim($_POST['username'] ?? ''),
            ':role' => $role,
            ':alasan' => $alasan
        ];
        
        $sql = 'UPDATE pengguna SET nama_lengkap = :nama, kelas = :kelas, username = :username, role = :role, alasan_masuk = :alasan';
        
        if (($_POST['password'] ?? '') !== '') {
            $sql .= ', password = :password';
            $params[':password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        if ($kartuPath !== null) {
            $sql .= ', kartu_pelajar = :kartu';
            $params[':kartu'] = $kartuPath;
        }
        
        $stmt = $conn->prepare($sql . ' WHERE id_user = :id');
        $stmt->execute($params);
        
        back('Pengguna berhasil diperbarui.');
    }

    if ($action === 'delete' && $id > 0) {
        if ($id === (int) $_SESSION['id_user']) {
            throw new RuntimeException('Akun yang sedang digunakan tidak dapat dihapus.');
        }
        
        $conn->prepare('DELETE FROM pengguna WHERE id_user = :id')->execute([':id' => $id]);
        back('Pengguna berhasil dihapus.');
    }

    throw new RuntimeException('Aksi pengguna tidak dikenali.');

} catch (Throwable $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => $e->getMessage()];
    header('Location: user_management.php');
    exit;
}
?>