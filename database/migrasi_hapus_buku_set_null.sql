-- Migrasi: izinkan penghapusan buku tanpa kehilangan riwayat peminjaman.
-- id_buku di peminjaman dibuat nullable + FK ON DELETE SET NULL.
-- Jalankan: mariadb perpustakaan < database/migrasi_hapus_buku_set_null.sql

ALTER TABLE `peminjaman` DROP FOREIGN KEY `peminjaman_ibfk_2`;
ALTER TABLE `peminjaman` MODIFY `id_buku` int DEFAULT NULL;
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`) ON DELETE SET NULL;