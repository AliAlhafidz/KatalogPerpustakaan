-- Migrasi tabel pengajuan_peminjaman
-- Sistem Informasi Perpustakaan

CREATE TABLE IF NOT EXISTS `pengajuan_peminjaman` (
  `id_pengajuan` int NOT NULL AUTO_INCREMENT,
  `id_anggota` int NOT NULL,
  `id_buku` int NOT NULL,
  `status` enum('menunggu','disetujui','ditolak','dibatalkan') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'menunggu',
  `catatan_anggota` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan_admin` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `diproses_at` datetime DEFAULT NULL,
  `diproses_oleh` int DEFAULT NULL,
  `aktif_unik` varchar(40) COLLATE utf8mb4_general_ci GENERATED ALWAYS AS ((case when (`status` = _utf8mb4'menunggu') then concat(`id_anggota`,_utf8mb4':',`id_buku`) else NULL end)) VIRTUAL,
  PRIMARY KEY (`id_pengajuan`),
  UNIQUE KEY `uniq_pengajuan_aktif` (`aktif_unik`),
  KEY `idx_pengajuan_status` (`status`,`created_at`),
  KEY `idx_pengajuan_anggota` (`id_anggota`,`created_at`),
  KEY `idx_pengajuan_buku` (`id_buku`),
  KEY `idx_pengajuan_created` (`created_at`),
  KEY `idx_pengajuan_diproses_oleh` (`diproses_oleh`),
  KEY `idx_pengajuan_anggota_buku` (`id_anggota`,`id_buku`),
  CONSTRAINT `pengajuan_peminjaman_ibfk_1` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id_anggota`) ON DELETE CASCADE,
  CONSTRAINT `pengajuan_peminjaman_ibfk_2` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE CASCADE,
  CONSTRAINT `pengajuan_peminjaman_ibfk_3` FOREIGN KEY (`diproses_oleh`) REFERENCES `admin` (`id_admin`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
