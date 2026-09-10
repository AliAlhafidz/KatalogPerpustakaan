<?php
/**
 * Konfigurasi Koneksi Database
 * Sistem Informasi Perpustakaan Umum
 */

require_once __DIR__ . '/env.php';

// Ambil dari env jika ada, fallback ke default
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'perpustakaan');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// Base URL aplikasi (sesuaikan dengan folder project di htdocs/htdocs)
// Contoh: jika project berada di htdocs/perpustakaan maka BASE_URL = '/perpustakaan'
if (!defined('BASE_URL')) define('BASE_URL', getenv('BASE_URL') ?: '/perpustakaan');

// Pengaturan sistem peminjaman
define('LAMA_PINJAM_HARI', 7);      // lama peminjaman dalam hari
define('DENDA_PER_HARI', 1000);     // denda keterlambatan per hari (Rupiah)
define('PERPANJANGAN_MAKS_HARI', 7); // maksimal tambahan hari yang dapat diminta anggota
define('NOTIFIKASI_JATUH_TEMPO_HARI', 3); // beri pengingat mulai 3 hari sebelum jatuh tempo

// Folder upload cover buku
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/covers/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/covers/');

// Folder upload foto profil (anggota & admin)
define('UPLOAD_PROFIL_DIR', __DIR__ . '/../assets/uploads/profil/');
define('UPLOAD_PROFIL_URL', BASE_URL . '/assets/uploads/profil/');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB connect failed: ' . $e->getMessage());
    // Jangan expose detail DB ke user di produksi
    $msg = (getenv('APP_DEBUG') === '1') ? htmlspecialchars($e->getMessage()) : 'Terjadi kesalahan koneksi. Periksa konfigurasi database.';
    die('Koneksi database gagal: ' . $msg);
}
