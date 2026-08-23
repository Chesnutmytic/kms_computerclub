<?php
/**
 * proses_event.php
 * Handler untuk aksi event: create, update, selesai, delete.
 * Hanya Admin dan Super Admin yang boleh mengakses.
 */
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Super Admin', 'Admin'], true)) {
    header('Location: ../login.html');
    exit;
}

function eventBack(string $type, string $msg): never {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: kelola_event.php');
    exit;
}

$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? $_POST['id_event'] ?? 0);

try {
    // ── CREATE ──────────────────────────────────────────────────────────────────
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama  = trim($_POST['nama_event']  ?? '');
        $jenis = $_POST['jenis_event']      ?? '';
        $tglMulai = trim($_POST['tgl_mulai'] ?? '');

        if ($nama === '') {
            throw new RuntimeException('Nama event wajib diisi.');
        }
        if (!in_array($jenis, ['Lomba', 'Workshop', 'Pelatihan', 'Seminar', 'Lainnya'], true)) {
            throw new RuntimeException('Jenis event tidak valid.');
        }
        if ($tglMulai === '') {
            throw new RuntimeException('Tanggal mulai wajib diisi.');
        }

        $conn->beginTransaction();

        $stmt = $conn->prepare(
            "INSERT INTO `event`
             (id_pembuat, nama_event, jenis_event, deskripsi, tgl_mulai)
             VALUES (:pembuat, :nama, :jenis, :deskripsi, :tgl_mulai)"
        );
        $stmt->execute([
            ':pembuat'   => $_SESSION['id_user'],
            ':nama'      => $nama,
            ':jenis'     => $jenis,
            ':deskripsi' => trim($_POST['deskripsi'] ?? '') ?: null,
            ':tgl_mulai' => $tglMulai,
        ]);
        $idEvent = (int) $conn->lastInsertId();

        // Sync peserta
        $pesertaIds = array_filter(array_map('intval', $_POST['peserta'] ?? []));
        if (!empty($pesertaIds)) {
            $stmtP = $conn->prepare(
                "INSERT IGNORE INTO event_peserta (id_event, id_user) VALUES (:ev, :u)"
            );
            foreach ($pesertaIds as $uid) {
                $stmtP->execute([':ev' => $idEvent, ':u' => $uid]);
            }
        }

        // Sync materi prasyarat
        $materiIds = array_filter(array_map('intval', $_POST['materi'] ?? []));
        if (!empty($materiIds)) {
            $stmtM = $conn->prepare(
                "INSERT IGNORE INTO event_materi (id_event, id_arsip) VALUES (:ev, :a)"
            );
            foreach ($materiIds as $aid) {
                $stmtM->execute([':ev' => $idEvent, ':a' => $aid]);
            }
        }

        $conn->commit();
        eventBack('success', "Event \"{$nama}\" berhasil dibuat.");
    }

    // ── UPDATE ──────────────────────────────────────────────────────────────────
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($id <= 0) {
            throw new RuntimeException('ID event tidak valid.');
        }
        $nama  = trim($_POST['nama_event']  ?? '');
        $jenis = $_POST['jenis_event']      ?? '';
        $tglMulai = trim($_POST['tgl_mulai'] ?? '');

        if ($nama === '') {
            throw new RuntimeException('Nama event wajib diisi.');
        }
        if (!in_array($jenis, ['Lomba', 'Workshop', 'Pelatihan', 'Seminar', 'Lainnya'], true)) {
            throw new RuntimeException('Jenis event tidak valid.');
        }
        if ($tglMulai === '') {
            throw new RuntimeException('Tanggal mulai wajib diisi.');
        }

        // Pastikan event ada dan belum selesai
        $ev = $conn->prepare("SELECT * FROM `event` WHERE id_event = :id");
        $ev->execute([':id' => $id]);
        $event = $ev->fetch();
        if (!$event) {
            throw new RuntimeException('Event tidak ditemukan.');
        }
        if ($event['status'] === 'Selesai') {
            throw new RuntimeException('Event yang sudah selesai tidak dapat diedit.');
        }

        $conn->beginTransaction();

        $conn->prepare(
            "UPDATE `event`
             SET nama_event = :nama, jenis_event = :jenis, deskripsi = :deskripsi, tgl_mulai = :tgl_mulai
             WHERE id_event = :id"
        )->execute([
            ':nama'      => $nama,
            ':jenis'     => $jenis,
            ':deskripsi' => trim($_POST['deskripsi'] ?? '') ?: null,
            ':tgl_mulai' => $tglMulai,
            ':id'        => $id,
        ]);

        // Re-sync peserta: hapus semua → insert ulang
        $conn->prepare("DELETE FROM event_peserta WHERE id_event = :ev")->execute([':ev' => $id]);
        $pesertaIds = array_filter(array_map('intval', $_POST['peserta'] ?? []));
        if (!empty($pesertaIds)) {
            $stmtP = $conn->prepare(
                "INSERT IGNORE INTO event_peserta (id_event, id_user) VALUES (:ev, :u)"
            );
            foreach ($pesertaIds as $uid) {
                $stmtP->execute([':ev' => $id, ':u' => $uid]);
            }
        }

        // Re-sync materi: hapus semua → insert ulang
        $conn->prepare("DELETE FROM event_materi WHERE id_event = :ev")->execute([':ev' => $id]);
        $materiIds = array_filter(array_map('intval', $_POST['materi'] ?? []));
        if (!empty($materiIds)) {
            $stmtM = $conn->prepare(
                "INSERT IGNORE INTO event_materi (id_event, id_arsip) VALUES (:ev, :a)"
            );
            foreach ($materiIds as $aid) {
                $stmtM->execute([':ev' => $id, ':a' => $aid]);
            }
        }

        $conn->commit();
        eventBack('success', "Event \"{$nama}\" berhasil diperbarui.");
    }

    // ── TANDAI SELESAI ───────────────────────────────────────────────────────────
    if ($action === 'selesai') {
        if ($id <= 0) {
            throw new RuntimeException('ID event tidak valid.');
        }
        $ev = $conn->prepare("SELECT * FROM `event` WHERE id_event = :id");
        $ev->execute([':id' => $id]);
        $event = $ev->fetch();
        if (!$event) {
            throw new RuntimeException('Event tidak ditemukan.');
        }
        if ($event['status'] === 'Selesai') {
            eventBack('warning', 'Event ini sudah ditandai selesai.');
        }

        $conn->prepare(
            "UPDATE `event`
             SET status = 'Selesai', tgl_selesai = CURDATE()
             WHERE id_event = :id"
        )->execute([':id' => $id]);

        eventBack('success', "Event \"{$event['nama_event']}\" ditandai selesai. Peserta kini dapat menambahkan catatan pengalaman.");
    }

    // ── DELETE ──────────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        if ($id <= 0) {
            throw new RuntimeException('ID event tidak valid.');
        }
        $ev = $conn->prepare("SELECT nama_event FROM `event` WHERE id_event = :id");
        $ev->execute([':id' => $id]);
        $event = $ev->fetch();
        if (!$event) {
            throw new RuntimeException('Event tidak ditemukan.');
        }

        $conn->prepare("DELETE FROM `event` WHERE id_event = :id")->execute([':id' => $id]);
        eventBack('success', "Event \"{$event['nama_event']}\" berhasil dihapus.");
    }

    throw new RuntimeException('Aksi event tidak dikenali.');

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    eventBack('danger', $e->getMessage());
}
