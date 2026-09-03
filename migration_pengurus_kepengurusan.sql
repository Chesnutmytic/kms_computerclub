-- ============================================================
-- migration_pengurus_kepengurusan.sql
-- Tambah tabel pengurus_kepengurusan untuk menyimpan relasi
-- siapa saja pengurus pada setiap masa kepengurusan.
-- Aman untuk dijalankan ulang (IF NOT EXISTS guard).
-- ============================================================

CREATE TABLE IF NOT EXISTS `pengurus_kepengurusan` (
  `id`              int(11)      NOT NULL AUTO_INCREMENT,
  `id_kepengurusan` int(11)      NOT NULL,
  `id_user`         int(11)      NOT NULL,
  `tgl_assign`      datetime     DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_kep_user` (`id_kepengurusan`, `id_user`),
  KEY `fk_pk_kep`  (`id_kepengurusan`),
  KEY `fk_pk_user` (`id_user`),
  CONSTRAINT `fk_pk_kep`  FOREIGN KEY (`id_kepengurusan`)
    REFERENCES `masa_kepengurusan` (`id_kepengurusan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pk_user` FOREIGN KEY (`id_user`)
    REFERENCES `pengguna` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Migration pengurus_kepengurusan selesai!' AS status;
