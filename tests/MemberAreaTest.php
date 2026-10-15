<?php
require_once __DIR__ . '/../config/bootstrap.php';

function assert_test($condition, $name) {
    if ($condition) {
        echo "✓ $name\n";
    } else {
        echo "✗ FAILED: $name\n";
        exit(1);
    }
}

echo "=== RUNNING MEMBER AREA & PENGAJUAN TEST SUITE ===\n";

// 1. Test Notifikasi dedup and null key handling
$pdo->exec("DELETE FROM notifikasi WHERE judul LIKE 'Unit Test%'");
buat_notifikasi_anggota($pdo, 1, 'Unit Test 1', 'Message 1');
buat_notifikasi_anggota($pdo, 1, 'Unit Test 2', 'Message 2');
buat_notifikasi_anggota($pdo, 1, 'Unit Test 3', 'Message 3', 'info', null, 'key_123');
buat_notifikasi_anggota($pdo, 1, 'Unit Test 3 dup', 'Message 3 dup', 'info', null, 'key_123');

$cnt = (int)$pdo->query("SELECT COUNT(*) FROM notifikasi WHERE judul LIKE 'Unit Test%'")->fetchColumn();
assert_test($cnt === 3, "buat_notifikasi_anggota inserts null keys safely and deduplicates non-null keys");
$pdo->exec("DELETE FROM notifikasi WHERE judul LIKE 'Unit Test%'");

// 2. Test Member Dashboard execution
$_SESSION['role'] = 'anggota';
$_SESSION['id_anggota'] = 1;
$_SESSION['nama'] = 'Budi Ramsey';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/perpustakaan/anggota/dashboard.php';
$_SERVER['SCRIPT_NAME'] = '/perpustakaan/anggota/dashboard.php';
$_SERVER['HTTP_HOST'] = 'localhost';

ob_start();
include __DIR__ . '/../anggota/dashboard.php';
$dashHtml = ob_get_clean();
assert_test(strlen($dashHtml) > 500, "anggota/dashboard.php renders successfully");
assert_test(strpos($dashHtml, 'Sedang Dipinjam') !== false, "dashboard contains Sedang Dipinjam");
assert_test(strpos($dashHtml, 'Pengajuan Pinjam') !== false, "dashboard contains Pengajuan Pinjam");

// 3. Test Pengajuan Peminjaman page execution & modal presence
$_SERVER['REQUEST_URI'] = '/perpustakaan/anggota/pengajuan_peminjaman.php';
$_SERVER['SCRIPT_NAME'] = '/perpustakaan/anggota/pengajuan_peminjaman.php';

ob_start();
include __DIR__ . '/../anggota/pengajuan_peminjaman.php';
$pengajuanHtml = ob_get_clean();
assert_test(strlen($pengajuanHtml) > 500, "anggota/pengajuan_peminjaman.php renders successfully");
assert_test(strpos($pengajuanHtml, 'btnBukaModalPengajuan') !== false, "pengajuan_peminjaman contains Ajukan Peminjaman modal button");
assert_test(strpos($pengajuanHtml, 'selectBukuAjukan') !== false, "pengajuan_peminjaman contains select book dropdown");

// 4. Test ajukan_peminjaman.php GET redirect
assert_test(is_file(__DIR__ . '/../anggota/ajukan_peminjaman.php'), "anggota/ajukan_peminjaman.php exists");

// 5. Test other member pages
$pages = [
    'peminjaman.php',
    'riwayat.php',
    'favorit.php',
    'profil.php',
    'notifikasi.php'
];

foreach ($pages as $p) {
    $_SERVER['REQUEST_URI'] = '/perpustakaan/anggota/' . $p;
    $_SERVER['SCRIPT_NAME'] = '/perpustakaan/anggota/' . $p;
    ob_start();
    include __DIR__ . '/../anggota/' . $p;
    $out = ob_get_clean();
    assert_test(strlen($out) > 300, "anggota/$p renders without error");
}

echo "=== ALL TESTS PASSED! ===\n";
