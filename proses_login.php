<?php

session_start();
require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: login.html?error=empty');
    exit;
}

try {
    $stmt = $conn->prepare(
        'SELECT id_user, nama_lengkap, username, password, role, status_akun
         FROM pengguna
         WHERE username = :username
         LIMIT 1'
    );
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        // Check account status first
        if ($user['status_akun'] === 'Pending') {
            header('Location: login.html?error=pending');
            exit;
        }
        if ($user['status_akun'] === 'Ditolak') {
            header('Location: login.html?error=rejected');
            exit;
        }

        // Status 'Aktif' — allow login
        $_SESSION['id_user']      = $user['id_user'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['role']         = $user['role'];

        if ($user['role'] === 'Super Admin' || $user['role'] === 'Admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: portal/index.php');
        }
        exit;

    } else {
        header('Location: login.html?error=invalid');
        exit;
    }

} catch (PDOException $e) {
    die('Terjadi kesalahan saat login: ' . $e->getMessage());
}
