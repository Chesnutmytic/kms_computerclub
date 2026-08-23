<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.html');
    exit;
}

$id_arsip = (int)($_GET['id'] ?? 0);
$url = $_GET['url'] ?? '';

if ($id_arsip > 0) {
    try {
        $stmt = $conn->prepare("INSERT IGNORE INTO progress_belajar (id_user, id_arsip) VALUES (?, ?)");
        $stmt->execute([$_SESSION['id_user'], $id_arsip]);
    } catch (PDOException $e) {
        // Ignore duplicate key errors
    }
}

if ($url) {
    header('Location: ' . $url);
} else {
    header('Location: alur_belajar.php');
}
exit;
