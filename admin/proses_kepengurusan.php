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

$action = $_GET['action'] ?? '';
$id     = (int) ($_GET['id'] ?? $_POST['id_kepengurusan'] ?? 0);

try {
    // ── CREATE ──────────────────────────────────────────────────────────────────
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tahun  = trim($_POST['tahun_ajaran'] ?? '');
        $nama   = trim($_POST['nama_kepengurusan'] ?? '') ?: null;
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

        kepBack('success', "Masa Kepengurusan {$tahun} berhasil dibuat.");
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

        $conn->commit();

        kepBack(
            'success',
            "Masa Kepengurusan \"{$kep['tahun_ajaran']}\" berhasil diarsipkan. " .
            ($jumlahReset > 0
                ? "{$jumlahReset} item alur belajar telah direset."
                : 'Tidak ada alur yang perlu direset.')
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
