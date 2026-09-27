-- Migrasi: hapus fitur "Pengajuan Buku"
--
-- Menghapus tabel `pengajuan_buku` beserta foreign key-nya. Tabel `notifikasi`
-- dan `audit_log` SENGAJA TIDAK dihapus: keduanya masih dipakai fitur lain.
--
-- Jalankan sekali pada database yang sudah terisi:
--   mysql -u root perpustakaan < database/migrasi_hapus_pengajuan_buku.sql
--
-- Catatan: `pengajuan_peminjaman` (pengajuan peminjaman) adalah fitur BERBEDA
-- dan tidak tersentuh oleh migrasi ini.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `pengajuan_buku`;
SET FOREIGN_KEY_CHECKS = 1;

-- Bersihkan notifikasi lama yang menaut ke halaman pengajuan yang sudah dihapus.
UPDATE `notifikasi`
   SET `link` = '/anggota/peminjaman.php'
 WHERE `link` = '/anggota/pengajuan.php';
