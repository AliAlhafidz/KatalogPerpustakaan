<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

$id_anggota = (int)($_SESSION['id_anggota'] ?? 0);

// Jika diakses via GET, arahkan ke halaman pengajuan peminjaman (dengan pre-select buku jika ada)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $id_buku_get = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['id_buku']) ? (int)$_GET['id_buku'] : 0);
    if ($id_buku_get > 0) {
        redirect('/anggota/pengajuan_peminjaman.php?ajukan=1&id_buku=' . $id_buku_get);
    } else {
        redirect('/anggota/pengajuan_peminjaman.php?ajukan=1');
    }
}

require_csrf();

$id_buku = (int)($_POST['id_buku'] ?? 0);
$catatan_anggota = clean($_POST['catatan_anggota'] ?? '');
$redirect_to = clean($_POST['redirect'] ?? 'pengajuan');

// Tentukan target redirect kembali jika terjadi error
$back_url = ($redirect_to === 'detail' && $id_buku > 0)
    ? '/detail.php?id=' . $id_buku
    : '/anggota/pengajuan_peminjaman.php';

if ($id_buku <= 0) {
    set_flash('error', 'Silakan pilih buku yang ingin diajukan.');
    redirect($back_url);
}

if (mb_strlen($catatan_anggota) > 500) {
    set_flash('error', 'Catatan terlalu panjang (maks 500 karakter).');
    redirect($back_url);
}
if ($catatan_anggota === '') $catatan_anggota = null;

try {
    // Validasi buku ada, tidak diarsip, stok tersedia
    $stmt = $pdo->prepare("SELECT * FROM buku WHERE id_buku = :id LIMIT 1");
    $stmt->execute([':id' => $id_buku]);
    $buku = $stmt->fetch();
    if (!$buku) {
        set_flash('error', 'Buku tidak ditemukan.');
        redirect($back_url);
    }
    if (!empty($buku['is_arsip'])) {
        set_flash('error', 'Buku sedang diarsipkan dan tidak dapat diajukan untuk dipinjam.');
        redirect($back_url);
    }
    if ((int)$buku['tersedia'] < 1) {
        set_flash('error', 'Buku "' . $buku['judul'] . '" sedang tidak tersedia (stok habis).');
        redirect($back_url);
    }

    // Cek pengajuan aktif (menunggu) untuk buku yang sama
    $stmt = $pdo->prepare("SELECT id_pengajuan FROM pengajuan_peminjaman WHERE id_anggota = :anggota AND id_buku = :buku AND status = 'menunggu' LIMIT 1");
    $stmt->execute([':anggota' => $id_anggota, ':buku' => $id_buku]);
    if ($stmt->fetch()) {
        set_flash('warning', 'Kamu sudah memiliki pengajuan yang masih menunggu untuk buku "' . $buku['judul'] . '".');
        redirect($back_url);
    }

    // Cek peminjaman aktif untuk buku yang sama
    $stmt = $pdo->prepare("SELECT id_peminjaman FROM peminjaman WHERE id_anggota = :anggota AND id_buku = :buku AND status = 'dipinjam' LIMIT 1");
    $stmt->execute([':anggota' => $id_anggota, ':buku' => $id_buku]);
    if ($stmt->fetch()) {
        set_flash('warning', 'Kamu sedang meminjam buku "' . $buku['judul'] . '". Kembalikan terlebih dahulu sebelum mengajukan lagi.');
        redirect($back_url);
    }

    // Cek batas maksimal peminjaman aktif (3)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :anggota AND status = 'dipinjam'");
    $stmt->execute([':anggota' => $id_anggota]);
    $aktif = (int)$stmt->fetchColumn();
    if ($aktif >= 3) {
        set_flash('warning', 'Kamu sudah meminjam 3 buku (batas maksimal). Kembalikan salah satu buku sebelum mengajukan peminjaman baru.');
        redirect($back_url);
    }

    // Buat pengajuan
    $stmt = $pdo->prepare("INSERT INTO pengajuan_peminjaman (id_anggota, id_buku, status, catatan_anggota) VALUES (:anggota, :buku, 'menunggu', :catatan)");
    $stmt->execute([
        ':anggota' => $id_anggota,
        ':buku' => $id_buku,
        ':catatan' => $catatan_anggota,
    ]);
    $id_pengajuan_baru = (int)$pdo->lastInsertId();

    // Beri konfirmasi notifikasi internal untuk anggota
    try {
        buat_notifikasi_anggota(
            $pdo,
            $id_anggota,
            'Pengajuan peminjaman terkirim',
            'Pengajuan peminjaman untuk buku "' . $buku['judul'] . '" berhasil dikirim dan sedang menunggu peninjauan petugas.',
            'info',
            '/anggota/pengajuan_peminjaman.php',
            'pengajuan_baru:' . $id_pengajuan_baru
        );
    } catch (Throwable $e) {
        // Jangan hentikan proses jika notifikasi gagal
        error_log('Notif pengajuan gagal: ' . $e->getMessage());
    }

    set_flash('sukses', 'Pengajuan peminjaman buku "' . $buku['judul'] . '" berhasil dikirim dan sedang menunggu persetujuan admin.');
    
    // Redirect ke halaman riwayat pengajuan agar user dapat melihat pengajuannya
    if ($redirect_to === 'detail') {
        redirect('/detail.php?id=' . $id_buku);
    } else {
        redirect('/anggota/pengajuan_peminjaman.php');
    }

} catch (PDOException $e) {
    // Tangani duplicate key (double click / race condition)
    if (strpos($e->getMessage(), 'uniq_pengajuan_aktif') !== false || $e->getCode() == 23000) {
        set_flash('warning', 'Pengajuan untuk buku ini sudah dikirim sebelumnya dan masih menunggu persetujuan.');
        redirect($back_url);
    }
    // Tabel belum ada
    if (strpos($e->getMessage(), 'pengajuan_peminjaman') !== false) {
        set_flash('error', 'Fitur pengajuan peminjaman belum siap (tabel database belum ada). Hubungi admin.');
        redirect($back_url);
    }
    error_log('Ajukan peminjaman gagal: ' . $e->getMessage());
    set_flash('error', 'Gagal mengirim pengajuan: ' . $e->getMessage());
    redirect($back_url);
} catch (Throwable $e) {
    error_log('Ajukan peminjaman gagal: ' . $e->getMessage());
    set_flash('error', 'Gagal mengirim pengajuan: ' . $e->getMessage());
    redirect($back_url);
}
