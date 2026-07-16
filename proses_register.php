<?php
session_start();
require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.html");
    exit;
}

$nama_lengkap        = trim($_POST['nama_lengkap'] ?? '');
$kelas               = trim($_POST['kelas'] ?? '');
$username            = trim($_POST['username'] ?? '');
$password            = trim($_POST['password'] ?? '');
$konfirmasi_password = trim($_POST['konfirmasi_password'] ?? '');
$alasan_masuk        = trim($_POST['alasan_masuk'] ?? '');

if ($nama_lengkap === '' || $kelas === '' || $username === '' || $password === '' || $konfirmasi_password === '') {
    header("Location: register.html?error=empty");
    exit;
}

if (strlen($password) < 6) {
    header("Location: register.html?error=password_short");
    exit;
}

if ($password !== $konfirmasi_password) {
    header("Location: register.html?error=password_mismatch");
    exit;
}

try {
    $cek = $conn->prepare("SELECT id_user FROM PENGGUNA WHERE username = :username LIMIT 1");
    $cek->bindParam(':username', $username);
    $cek->execute();

    if ($cek->rowCount() > 0) {
        header("Location: register.html?error=username_exists");
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insert = $conn->prepare("INSERT INTO PENGGUNA 
        (nama_lengkap, kelas, username, password, alasan_masuk, role) 
        VALUES 
        (:nama_lengkap, :kelas, :username, :password, :alasan_masuk, 'Anggota')");

    $insert->bindParam(':nama_lengkap', $nama_lengkap);
    $insert->bindParam(':kelas', $kelas);
    $insert->bindParam(':username', $username);
    $insert->bindParam(':password', $hashedPassword);
    $insert->bindParam(':alasan_masuk', $alasan_masuk);

    $insert->execute();

    header("Location: login.html?registered=1");
    exit;

} catch (PDOException $e) {
    die("Terjadi kesalahan saat mendaftar: " . $e->getMessage());
}