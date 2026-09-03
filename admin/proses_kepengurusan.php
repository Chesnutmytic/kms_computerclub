<?php
/**
 * proses_kepengurusan.php
 * Handler CRUD untuk masa kepengurusan.
 * Hanya Super Admin.
 */
session_start();
require_once '../config/koneksi.php';

if (($_SESSION['role'] ?? '') !== 'Super Admin') {
    header('Location: ../login.html');
    exit;
}

function kepBack(string $type, string $msg): never
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: kelola_kepengurusan.php');
    exit;
}

/**
 * Assign pengurus ke kepengurusan dan ubah role mereka ke Admin.
 * Pengurus yang dihapus dari daftar dikembalikan ke Anggota (kecuali Super Admin).
 *
 * @param PDO   $conn
 * @param int   $idKep      ID masa kepengurusan
 * @param array $newUserIds Array id_user yang akan jadi pengurus
 */
function syncPengurus(PDO $conn, int $idKep, array $newUserIds): void
{
    // Ambil pengurus lama
    $stmtLama = $conn->prepare(
        "SELECT pk.id_user FROM pengurus_kepengurusan pk WHERE pk.id_kepengurusan = :id"
    );
    $stmtLama->execute([':id' => $idKep]);
    $oldUserIds = $stmtLama->fetchAll(PDO::FETCH_COLUMN);

    $toAdd    = array_diff($newUserIds, $oldUserIds);
    $toRemove = array_diff($oldUserIds, $newUserIds);

    // Tambah pengurus baru
    if (!empty($toAdd)) {
        $stmtIns = $conn->prepare(
            "INSERT IGNORE INTO pengurus_kepengurusan (id_kepengurusan, id_user) VALUES (:kep, :usr)"
        );
        $stmtRole = $conn->prepare(
            "UPDATE pengguna SET role = 'Admin' WHERE id_user = :usr AND role = 'Anggota'"
        );
        foreach ($toAdd as $uid) {
            $stmtIns->execute([':kep' => $idKep, ':usr' => $uid]);
            $stmtRole->execute([':usr' => $uid]);
        }
    }

    // Hapus pengurus lama (revert role ke Anggota kecuali Super Admin)
    if (!empty($toRemove)) {
        $stmtDel = $conn->prepare(
            "DELETE FROM pengurus_kepengurusan WHERE id_kepengurusan = :kep AND id_user = :usr"
        );
        $stmtRevert = $conn->prepare(
            "UPDATE pengguna SET role = 'Anggota'
             WHERE id_user = :usr AND role = 'Admin'"
        );
        foreach ($toRemove as $uid) {
            $stmtDel->execute([':kep' => $idKep, ':usr' => $uid]);
            $stmtRevert->execute([':usr' => $uid]);
        }
    }
}

$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? $_POST['id_kepengurusan'] ?? 0);

try {
    // ── CREATE ──────────────────────────────────────────────────────────────────
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tahun    = trim($_POST['tahun_ajaran'] ?? '');
        $nama     = trim($_POST['nama_kepengurusan'] ?? '') ?: null;
        $tglMulai = trim($_POST['tgl_mulai'] ?? '');

        if ($tahun === '') {
            throw new RuntimeException('Tahun ajaran wajib diisi. Contoh: 2025/2026');
        }
        if (!preg_match('/^\d{4}\/\d{4}$/', $tahun)) {
            throw new RuntimeException('Format tahun ajaran tidak valid. Gunakan format: 2025/2026');
        }
        [$y1, $y2] = array_map('intval', explode('/', $tahun));
        if ($y2 !== $y1 + 1) {
            throw new RuntimeException('Tahun ajaran harus selisih 1 tahun. Contoh: 2025/2026');
        }
        if ($tglMulai === '') {
            throw new RuntimeException('Tanggal mulai wajib diisi.');
        }

        // Pastikan tidak ada kepengurusan Aktif lain
        $cekAktif = $conn->query(
            "SELECT id_kepengurusan, tahun_ajaran FROM masa_kepengurusan WHERE status = 'Aktif' LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        if ($cekAktif) {
            throw new RuntimeException(
                "Sudah ada masa kepengurusan Aktif: \"{$cekAktif['tahun_ajaran']}\". " .
                'Arsipkan terlebih dahulu sebelum membuat yang baru.'
            );
        }

        $conn->beginTransaction();

        $conn->prepare(
            "INSERT INTO masa_kepengurusan
             (tahun_ajaran, nama_kepengurusan, id_pembuat, tgl_mulai)
             VALUES (:tahun, :nama, :pembuat, :tgl)"
        )->execute([
            ':tahun'   => $tahun,
            ':nama'    => $nama,
            ':pembuat' => $_SESSION['id_user'],
            ':tgl'     => $tglMulai,
        ]);

        $newKepId = (int) $conn->lastInsertId();

        // Assign pengurus jika ada yang dipilih
        $pengurusIds = array_map('intval', (array) ($_POST['pengurus'] ?? []));
        $pengurusIds = array_filter($pengurusIds, fn($v) => $v > 0);
        if (!empty($pengurusIds)) {
            syncPengurus($conn, $newKepId, array_values($pengurusIds));
        }

        $conn->commit();

        $jumlahPengurus = count($pengurusIds);
        $msgPengurus    = $jumlahPengurus > 0 ? " {$jumlahPengurus} pengurus berhasil di-assign." : '';
        kepBack('success', "Masa Kepengurusan {$tahun} berhasil dibuat.{$msgPengurus}");
    }

    // ── ASSIGN PENGURUS ───────────────────────────────────────────────────────
    if ($action === 'assign_pengurus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($id <= 0) {
            throw new RuntimeException('ID kepengurusan tidak valid.');
        }

        $kep = $conn->prepare(
            "SELECT id_kepengurusan, tahun_ajaran, status FROM masa_kepengurusan WHERE id_kepengurusan = :id"
        );
        $kep->execute([':id' => $id]);
        $kep = $kep->fetch(PDO::FETCH_ASSOC);

        if (!$kep) {
            throw new RuntimeException('Masa kepengurusan tidak ditemukan.');
        }
        if ($kep['status'] !== 'Aktif') {
            throw new RuntimeException('Pengurus hanya dapat dikelola pada masa kepengurusan yang Aktif.');
        }

        $pengurusIds = array_map('intval', (array) ($_POST['pengurus'] ?? []));
        $pengurusIds = array_filter($pengurusIds, fn($v) => $v > 0);

        $conn->beginTransaction();
        syncPengurus($conn, $id, array_values($pengurusIds));
        $conn->commit();

        kepBack('success', "Daftar pengurus kepengurusan \"{$kep['tahun_ajaran']}\" berhasil diperbarui.");
    }

    // ── ARSIPKAN ─────────────────────────────────────────────────────────────────
    if ($action === 'arsipkan') {
        if ($id <= 0) {
            throw new RuntimeException('ID kepengurusan tidak valid.');
        }

        $kep = $conn->prepare(
            "SELECT id_kepengurusan, tahun_ajaran, status FROM masa_kepengurusan WHERE id_kepengurusan = :id"
        );
        $kep->execute([':id' => $id]);
        $kep = $kep->fetch(PDO::FETCH_ASSOC);

        if (!$kep) {
            throw new RuntimeException('Masa kepengurusan tidak ditemukan.');
        }
        if ($kep['status'] === 'Diarsipkan') {
            kepBack('warning', 'Masa kepengurusan ini sudah diarsipkan.');
        }

        $conn->beginTransaction();

        // 1. Tandai sebagai Diarsipkan
        $conn->prepare(
            "UPDATE masa_kepengurusan
             SET status = 'Diarsipkan', tgl_arsip = CURDATE()
             WHERE id_kepengurusan = :id"
        )->execute([':id' => $id]);

        // 2. Ambil semua id_arsip yang termasuk kepengurusan ini
        $materiIds = $conn->prepare(
            "SELECT id_arsip FROM arsip_materi WHERE id_kepengurusan = :id"
        );
        $materiIds->execute([':id' => $id]);
        $materiIds = $materiIds->fetchAll(PDO::FETCH_COLUMN);

        // 3. Reset alur: hapus detail_alur yang menggunakan materi dari kepengurusan ini
        if (!empty($materiIds)) {
            $in   = implode(',', array_fill(0, count($materiIds), '?'));
            $stmt = $conn->prepare("DELETE FROM detail_alur WHERE id_arsip IN ($in)");
            $stmt->execute($materiIds);
            $jumlahReset = $stmt->rowCount();
        } else {
            $jumlahReset = 0;
        }

        // 4. Revert role pengurus ke Anggota (kecuali Super Admin)
        $pengurusIds = $conn->prepare(
            "SELECT id_user FROM pengurus_kepengurusan WHERE id_kepengurusan = :id"
        );
        $pengurusIds->execute([':id' => $id]);
        $pengurusIds = $pengurusIds->fetchAll(PDO::FETCH_COLUMN);

        $jumlahRevert = 0;
        if (!empty($pengurusIds)) {
            $inPengurus = implode(',', array_fill(0, count($pengurusIds), '?'));
            $stmtRevert = $conn->prepare(
                "UPDATE pengguna SET role = 'Anggota'
                 WHERE id_user IN ($inPengurus) AND role = 'Admin'"
            );
            $stmtRevert->execute($pengurusIds);
            $jumlahRevert = $stmtRevert->rowCount();
        }

        $conn->commit();

        $msgAlur    = $jumlahReset > 0 ? " {$jumlahReset} item alur belajar telah direset." : ' Tidak ada alur yang perlu direset.';
        $msgRevert  = $jumlahRevert > 0 ? " {$jumlahRevert} pengurus dikembalikan menjadi Anggota." : '';
        kepBack(
            'success',
            "Masa Kepengurusan \"{$kep['tahun_ajaran']}\" berhasil diarsipkan.{$msgAlur}{$msgRevert}"
        );
    }

    // ── DELETE ──────────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        if ($id <= 0) {
            throw new RuntimeException('ID kepengurusan tidak valid.');
        }

        $kep = $conn->prepare(
            "SELECT id_kepengurusan, tahun_ajaran FROM masa_kepengurusan WHERE id_kepengurusan = :id"
        );
        $kep->execute([':id' => $id]);
        $kep = $kep->fetch(PDO::FETCH_ASSOC);

        if (!$kep) {
            throw new RuntimeException('Masa kepengurusan tidak ditemukan.');
        }

        // Cek apakah masih ada materi yang terikat
        $cekMateri = $conn->prepare(
            "SELECT COUNT(*) FROM arsip_materi WHERE id_kepengurusan = :id"
        );
        $cekMateri->execute([':id' => $id]);
        if ((int) $cekMateri->fetchColumn() > 0) {
            throw new RuntimeException(
                'Tidak dapat menghapus masa kepengurusan yang masih memiliki materi terkait. ' .
                'Hapus atau pindahkan materi terlebih dahulu.'
            );
        }

        $conn->prepare("DELETE FROM masa_kepengurusan WHERE id_kepengurusan = :id")->execute([':id' => $id]);
        kepBack('success', "Masa Kepengurusan \"{$kep['tahun_ajaran']}\" berhasil dihapus.");
    }

    throw new RuntimeException('Aksi tidak dikenali.');

} catch (Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    kepBack('danger', $e->getMessage());
}
