<?php
session_start();
require_once '../config/koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_arsip = (int)($data['id_arsip'] ?? 0);
$isi_notes = trim($data['isi_notes'] ?? '');

if ($id_arsip <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO catatan_pribadi (id_user, id_arsip, isi_notes) 
         VALUES (:id_user, :id_arsip, :isi_notes)
         ON DUPLICATE KEY UPDATE isi_notes = VALUES(isi_notes)"
    );
    $stmt->execute([
        ':id_user' => $_SESSION['id_user'],
        ':id_arsip' => $id_arsip,
        ':isi_notes' => $isi_notes
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
