<?php
// Unit test sederhana tanpa PHPUnit — jalankan via: php tests/FunctionsTest.php
if (!defined('DENDA_PER_HARI')) define('DENDA_PER_HARI', 1000);
if (!defined('LAMA_PINJAM_HARI')) define('LAMA_PINJAM_HARI', 7);
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', '/tmp/');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', '/tmp/');
if (!defined('UPLOAD_PROFIL_DIR')) define('UPLOAD_PROFIL_DIR', '/tmp/');
if (!defined('UPLOAD_PROFIL_URL')) define('UPLOAD_PROFIL_URL', '/tmp/');
if (!defined('BASE_URL')) define('BASE_URL', '/perpustakaan');
require_once __DIR__ . '/../includes/functions.php';

function assert_eq($a, $b, $msg) {
    if ($a === $b) echo "✓ $msg\n";
    else echo "✗ $msg — expected " . var_export($b, true) . " got " . var_export($a, true) . "\n";
}

// hitung_keterlambatan
assert_eq(hitung_keterlambatan('2026-08-20', '2026-08-25'), 5, 'telat 5 hari');
assert_eq(hitung_keterlambatan('2026-08-25', '2026-08-25'), 0, 'tepat waktu 0');
assert_eq(hitung_keterlambatan('2026-08-25', '2026-08-20'), 0, 'belum jatuh tempo 0');

// hitung_denda
assert_eq(hitung_denda(3), 3000, 'denda 3 hari');
assert_eq(hitung_denda(0), 0, 'denda 0');
assert_eq(hitung_denda(-5), 0, 'denda negatif 0');

// is_hari_minggu — 2026-08-23 adalah Minggu
assert_eq(is_hari_minggu('2026-08-23'), true, '2026-08-23 Minggu');
assert_eq(is_hari_minggu('2026-08-24'), false, '2026-08-24 Senin');

// format_rupiah
assert_eq(format_rupiah(1000), 'Rp 1.000', 'rupiah 1000');
assert_eq(format_rupiah(1000000), 'Rp 1.000.000', 'rupiah 1jt');

// hapus_cache_buku_populer
$cache_path = cache_buku_populer_path();
file_put_contents($cache_path, '[]');
assert_eq(is_file($cache_path), true, 'cache buku populer dibuat');
hapus_cache_buku_populer();
assert_eq(is_file($cache_path), false, 'cache buku populer dihapus');
hapus_cache_buku_populer();
assert_eq(true, true, 'hapus cache saat file tidak ada aman');

// ISBN normalization & conversion
assert_eq(isbn_normalize('978-0-7432-7356-5'), '9780743273565', 'isbn normalize clean hyphens');
assert_eq(isbn10_to_isbn13('0743273567'), '9780743273565', 'isbn10 to isbn13');
assert_eq(isbn13_to_isbn10('9780743273565'), '0743273567', 'isbn13 to isbn10');
assert_eq(isbn_alternatives('0743273567'), ['0743273567', '9780743273565'], 'isbn alternatives from 10');
assert_eq(isbn_alternatives('9780743273565'), ['9780743273565', '0743273567'], 'isbn alternatives from 13');

echo "Selesai.\n";

