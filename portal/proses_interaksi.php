<?php
session_start();
require_once '../config/koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$id_catatan = (int)($data['id_catatan'] ?? 0);

if ($id_catatan <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    if ($action === 'like') {
        // Cek apakah sudah like
        $stmt = $conn->prepare("SELECT id_like FROM like_catatan WHERE id_catatan = ? AND id_user = ?");
        $stmt->execute([$id_catatan, $_SESSION['id_user']]);
        $sudah = $stmt->fetch();

        if ($sudah) {
            // Unlike
            $conn->prepare("DELETE FROM like_catatan WHERE id_like = ?")->execute([$sudah['id_like']]);
            $liked = false;
        } else {
            // Like
            $conn->prepare("INSERT INTO like_catatan (id_catatan, id_user) VALUES (?, ?)")->execute([$id_catatan, $_SESSION['id_user']]);
            $liked = true;
        }

        // Hitung total like baru
        $stmt = $conn->prepare("SELECT COUNT(*) FROM like_catatan WHERE id_catatan = ?");
        $stmt->execute([$id_catatan]);
        $likes = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'liked' => $liked, 'likes' => $likes]);
        exit;
    }

    if ($action === 'komentar') {
        $komentar = trim($data['komentar'] ?? '');
        if ($komentar === '') {
            echo json_encode(['success' => false, 'error' => 'Komentar kosong']);
            exit;
        }

        $conn->prepare("INSERT INTO komentar_catatan (id_catatan, id_user, komentar) VALUES (?, ?, ?)")
             ->execute([$id_catatan, $_SESSION['id_user'], $komentar]);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
