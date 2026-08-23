<?php
session_start();
require_once 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit;
}

$nama_lengkap        = trim($_POST['nama_lengkap'] ?? '');
$kelas               = trim($_POST['kelas'] ?? '');
$username            = trim($_POST['username'] ?? '');
$password            = trim($_POST['password'] ?? '');
$konfirmasi_password = trim($_POST['konfirmasi_password'] ?? '');
$alasan_masuk        = trim($_POST['alasan_masuk'] ?? '');

if ($nama_lengkap === '' || $kelas === '' || $username === '' || $password === '' || $konfirmasi_password === '') {
    header('Location: register.html?error=empty');
    exit;
}
if (strlen($password) < 6) {
    header('Location: register.html?error=password_short');
    exit;
}
if ($password !== $konfirmasi_password) {
    header('Location: register.html?error=password_mismatch');
    exit;
}
if (empty($_FILES['kartu_pelajar']['name'])) {
    header('Location: register.html?error=no_id_card');
    exit;
}

try {
    // Check username unique
    $cek = $conn->prepare('SELECT id_user FROM pengguna WHERE username = :username LIMIT 1');
    $cek->execute([':username' => $username]);
    if ($cek->rowCount() > 0) {
        header('Location: register.html?error=username_exists');
        exit;
    }

    // Handle kartu pelajar upload
    $file  = $_FILES['kartu_pelajar'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
        header('Location: register.html?error=upload_error');
        exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
        header('Location: register.html?error=upload_error');
        exit;
    }
    $ext    = $mime === 'image/png' ? 'png' : 'jpg';
    $dir    = __DIR__ . '/assets/uploads/id_cards';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        header('Location: register.html?error=upload_error');
        exit;
    }
    $fname  = 'kp_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $fname)) {
        header('Location: register.html?error=upload_error');
        exit;
    }
    $kartuPath = 'assets/uploads/id_cards/' . $fname;

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $conn->prepare(
        "INSERT INTO pengguna (nama_lengkap, kelas, username, password, kartu_pelajar, status_akun, alasan_masuk, role)
         VALUES (:nama, :kelas, :username, :password, :kartu, 'Pending', :alasan, 'Anggota')"
    )->execute([
        ':nama'     => $nama_lengkap,
        ':kelas'    => $kelas,
        ':username' => $username,
        ':password' => $hashedPassword,
        ':kartu'    => $kartuPath,
        ':alasan'   => $alasan_masuk,
    ]);

    header('Location: login.html?registered=1');
    exit;

} catch (PDOException $e) {
    die('Terjadi kesalahan saat mendaftar: ' . $e->getMessage());
}