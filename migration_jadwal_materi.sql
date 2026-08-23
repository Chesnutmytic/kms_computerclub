-- ============================================================
-- migration_jadwal_materi.sql
-- Tambah kolom tgl_buka pada arsip_materi untuk jadwal pelajaran.
-- Aman untuk dijalankan ulang (IF NOT EXISTS / schema guard).
-- ============================================================

SET @col_exists = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'arsip_materi'
    AND COLUMN_NAME  = 'tgl_buka'
);

SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `arsip_materi` ADD COLUMN `tgl_buka` DATE DEFAULT NULL AFTER `tgl_unggah`',
  'SELECT "kolom tgl_buka sudah ada" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update data yang tgl_buka nya masih NULL menjadi tgl_unggah agar materi lama langsung dapat diakses
UPDATE `arsip_materi` 
SET `tgl_buka` = `tgl_unggah` 
WHERE `tgl_buka` IS NULL;

SELECT 'Migration tgl_buka materi selesai!' AS status;
