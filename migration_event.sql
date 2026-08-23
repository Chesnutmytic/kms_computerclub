-- =============================================================================
-- Migration: Event Management & Pembatasan Catatan Pengalaman
-- =============================================================================

-- 1. Buat tabel event
CREATE TABLE IF NOT EXISTS `event` (
  `id_event` int(11) NOT NULL AUTO_INCREMENT,
  `id_pembuat` int(11) NOT NULL,
  `nama_event` varchar(255) NOT NULL,
  `jenis_event` enum('Lomba','Workshop','Pelatihan','Seminar','Lainnya') NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `status` enum('Aktif','Selesai') DEFAULT 'Aktif',
  `tgl_dibuat` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_event`),
  KEY `id_pembuat` (`id_pembuat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Buat tabel event_peserta
CREATE TABLE IF NOT EXISTS `event_peserta` (
  `id_event` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  PRIMARY KEY (`id_event`,`id_user`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Buat tabel event_materi (materi prasyarat per event)
CREATE TABLE IF NOT EXISTS `event_materi` (
  `id_event` int(11) NOT NULL,
  `id_arsip` int(11) NOT NULL,
  PRIMARY KEY (`id_event`,`id_arsip`),
  KEY `id_arsip` (`id_arsip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Tambah kolom id_event ke catatan_pengalaman (jika belum ada)
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'catatan_pengalaman'
    AND column_name = 'id_event'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `catatan_pengalaman` ADD COLUMN `id_event` int(11) DEFAULT NULL AFTER `id_approver`',
  'SELECT "kolom id_event sudah ada" AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Modifikasi enum jenis_kegiatan tambah Lainnya
ALTER TABLE `catatan_pengalaman`
  MODIFY `jenis_kegiatan` enum('Lomba','Workshop','Pelatihan','Seminar','Lainnya') NOT NULL;

-- 6. FK untuk event
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`id_pembuat`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE;

-- 7. FK untuk event_peserta
ALTER TABLE `event_peserta`
  ADD CONSTRAINT `event_peserta_ibfk_1` FOREIGN KEY (`id_event`) REFERENCES `event` (`id_event`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_peserta_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE;

-- 8. FK untuk event_materi
ALTER TABLE `event_materi`
  ADD CONSTRAINT `event_materi_ibfk_1` FOREIGN KEY (`id_event`) REFERENCES `event` (`id_event`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_materi_ibfk_2` FOREIGN KEY (`id_arsip`) REFERENCES `arsip_materi` (`id_arsip`) ON DELETE CASCADE;

-- 9. FK catatan_pengalaman -> event
SET @fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'catatan_pengalaman'
    AND CONSTRAINT_NAME = 'catatan_pengalaman_ibfk_3'
);
SET @fk_sql = IF(@fk_exists = 0,
  'ALTER TABLE `catatan_pengalaman` ADD CONSTRAINT `catatan_pengalaman_ibfk_3` FOREIGN KEY (`id_event`) REFERENCES `event` (`id_event`) ON DELETE SET NULL',
  'SELECT "FK sudah ada" AS info'
);
PREPARE fk_stmt FROM @fk_sql; EXECUTE fk_stmt; DEALLOCATE PREPARE fk_stmt;

SELECT 'Migration selesai dengan sukses!' AS status;
