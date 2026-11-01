-- =====================================================================
-- Migrasi v8 — peminjaman.id_buku boleh NULL + ON DELETE SET NULL
-- =====================================================================
-- Tujuan:
--   Buku yang sudah punya riwayat peminjaman tetap bisa dihapus, tanpa
--   ikut menghapus riwayat transaksi anggota. Saat buku dihapus, kolom
--   peminjaman.id_buku otomatis diisi NULL dan halaman riwayat
--   menampilkan "Buku telah dihapus dari katalog".
--
-- Kapan dijalankan:
--   Hanya untuk database lama yang dibuat sebelum perbaikan ini.
--   Instalasi baru dari database/perpustakaan.sql sudah benar, TIDAK
--   perlu menjalankan migrasi ini.
--
-- Cara menjalankan (dari folder project):
--   mysql -u root perpustakaan < database/migrasi_v8_peminjaman_fk_set_null.sql
--
-- Catatan:
--   - Jalankan saat aplikasi tidak sedang dipakai.
--   - Nama constraint di bawah mengikuti dump bawaan (peminjaman_ibfk_2).
--     Jika nama constraint di database Anda berbeda, sesuaikan lebih dulu:
--       SHOW CREATE TABLE peminjaman;
--   - Aplikasi sudah menolak penghapusan buku yang masih berstatus
--     "dipinjam", jadi tidak ada pinjaman aktif yang kehilangan id_buku.
-- =====================================================================

ALTER TABLE `peminjaman`
  DROP FOREIGN KEY `peminjaman_ibfk_2`;

ALTER TABLE `peminjaman`
  MODIFY `id_buku` int DEFAULT NULL;

ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_2`
  FOREIGN KEY (`id_buku`) REFERENCES `buku` (`id_buku`)
  ON DELETE SET NULL;

-- Verifikasi: kolom id_buku harus "YES" pada kolom Null, dan constraint
-- harus memuat ON DELETE SET NULL.
SHOW CREATE TABLE `peminjaman`;
