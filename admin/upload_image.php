<?php
/**
 * Shared Quill image upload endpoint.
 * Returns JSON { "success": true, "url": "..." } or { "success": false, "error": "..." }
 */
session_start();
if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'No image uploaded.']);
    exit;
}

$file  = $_FILES['image'];
$maxSz = 5 * 1024 * 1024; // 5 MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload error: ' . $file['error']]);
    exit;
}
if ($file['size'] > $maxSz) {
    echo json_encode(['success' => false, 'error' => 'Ukuran gambar maksimal 5 MB.']);
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo   = finfo_open(FILEINFO_MIME_TYPE);
$mime    = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Format hanya JPG, PNG, GIF, WebP.']);
    exit;
}

$ext = match($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    default      => 'jpg',
};

$dir = __DIR__ . '/../assets/uploads/editor';
if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
    echo json_encode(['success' => false, 'error' => 'Folder upload tidak dapat dibuat.']);
    exit;
}

$name = 'img_' . uniqid('', true) . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
    echo json_encode(['success' => false, 'error' => 'Gagal menyimpan gambar.']);
    exit;
}

// return URL relative to project root
echo json_encode(['success' => true, 'url' => 'assets/uploads/editor/' . $name]);
