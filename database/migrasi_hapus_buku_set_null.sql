-- Histori peminjaman tetap ada saat buku dihapus (id_buku jadi NULL).
ALTER TABLE peminjaman
MODIFY id_buku INT NULL;

ALTER TABLE peminjaman
DROP FOREIGN KEY peminjaman_ibfk_2;

ALTER TABLE peminjaman
ADD CONSTRAINT peminjaman_ibfk_2
FOREIGN KEY (id_buku)
REFERENCES buku (id_buku)
ON DELETE SET NULL;
