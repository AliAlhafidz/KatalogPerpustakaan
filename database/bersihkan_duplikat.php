<?php
/**
 * Script CLI untuk membersihkan buku duplikat di database.
 * Dapat dijalankan via terminal Termux: php database/bersihkan_duplikat.php
 * atau otomatis dipanggil jika MySQL aktif.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "=== PEMBERSIHAN DATA BUKU DUPLIKAT ===\n";

// Daftar ID buku yang merupakan duplikat dan akan dihapus:
// ID 4: The Great Gatsby (ISBN 9780684717609) -> sudah ada ID 16 (ISBN 9780743273565)
// ID 5: The Hobbit (ISBN 9780008376055) -> sudah ada ID 17 (ISBN 9780345534835)
// ID 8: The Catcher in the Rye (ISBN 9780553239768) -> sudah ada ID 19 (ISBN 9780553117226, stok 4, Rak A1)
$hapus_ids = [4, 5, 8];

foreach ($hapus_ids as $id) {
    try {
        $stmt = $pdo->prepare("SELECT id_buku, kode_buku, judul, cover FROM buku WHERE id_buku = :id");
        $stmt->execute([':id' => $id]);
        $buku = $stmt->fetch();

        if (!$buku) {
            echo "[-] ID $id tidak ditemukan di database (mungkin sudah dihapus).\n";
            continue;
        }

        // Hapus relasi favorit jika ada
        $pdo->prepare("DELETE FROM favorit WHERE id_buku = :id")->execute([':id' => $id]);
        // Hapus dari buku
        $pdo->prepare("DELETE FROM buku WHERE id_buku = :id")->execute([':id' => $id]);

        // Hapus cover file jika ada
        if (!empty($buku['cover'])) {
            hapus_cover($buku['cover']);
        }

        echo "[+] Berhasil menghapus buku duplikat: #{$buku['id_buku']} ({$buku['kode_buku']}) - {$buku['judul']}\n";
    } catch (Throwable $e) {
        echo "[!] Gagal menghapus ID $id: " . $e->getMessage() . "\n";
    }
}

hapus_cache_buku_populer();
echo "=== SELESAI ===\n";
