-- ============================================================
-- migration_kepengurusan.sql
-- Tambah tabel masa_kepengurusan dan kolom id_kepengurusan
-- pada arsip_materi.
-- Aman untuk dijalankan ulang (IF NOT EXISTS / IF NOT EXIST guard).
-- ============================================================

-- 1. Tabel masa_kepengurusan
CREATE TABLE IF NOT EXISTS `masa_kepengurusan` (
  `id_kepengurusan`   int(11)      NOT NULL AUTO_INCREMENT,
  `tahun_ajaran`      varchar(20)  NOT NULL COMMENT 'Contoh: 2024/2025',
  `nama_kepengurusan` varchar(100) DEFAULT NULL COMMENT 'Contoh: Kepengurusan Periode IV',
  `id_pembuat`        int(11)      NOT NULL,
  `status`            enum('Aktif','Diarsipkan') NOT NULL DEFAULT 'Aktif',
  `tgl_mulai`         date         NOT NULL,
  `tgl_arsip`         date         DEFAULT NULL,
  `tgl_dibuat`        datetime     DEFAULT current_timestamp(),
  PRIMARY KEY (`id_kepengurusan`),
  UNIQUE KEY `uk_tahun_ajaran` (`tahun_ajaran`),
  KEY `fk_mk_pembuat` (`id_pembuat`),
  CONSTRAINT `fk_mk_pembuat` FOREIGN KEY (`id_pembuat`)
    REFERENCES `pengguna` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Kolom id_kepengurusan pada arsip_materi (nullable → materi lama tetap valid)
SET @col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'arsip_materi'
    AND COLUMN_NAME  = 'id_kepengurusan'
);

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `arsip_materi`
     ADD COLUMN `id_kepengurusan` int(11) DEFAULT NULL AFTER `id_user`,
     ADD KEY `fk_am_kepengurusan` (`id_kepengurusan`),
     ADD CONSTRAINT `fk_am_kepengurusan` FOREIGN KEY (`id_kepengurusan`)
       REFERENCES `masa_kepengurusan` (`id_kepengurusan`)
       ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT "kolom id_kepengurusan sudah ada" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Migration masa_kepengurusan selesai!' AS status;
