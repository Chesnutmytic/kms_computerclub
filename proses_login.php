<?php

session_start();
require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header("Location: login.html?error=empty");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id_user, nama_lengkap, username, password, role 
                            FROM PENGGUNA 
                            WHERE username = :username 
                            LIMIT 1");
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['id_user']      = $user['id_user'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['role']         = $user['role'];

        if ($user['role'] === 'Super Admin' || $user['role'] === 'Admin') {
            header("Location: admin/dashboard.html");
        } else {
            header("Location: portal/index.html");
        }
        exit;

    } else {
        header("Location: login.html?error=invalid");
        exit;
    }

} catch (PDOException $e) {
    die("Terjadi kesalahan saat login: " . $e->getMessage());
}
