<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Metode tidak valid.');
    redirect('/index.php');
}

$id_anggota = (int)($_SESSION['id_anggota'] ?? 0);
$id_buku = (int)($_POST['id_buku'] ?? 0);
$catatan_anggota = clean($_POST['catatan_anggota'] ?? '');

if ($id_buku <= 0) {
    set_flash('error', 'Buku tidak valid.');
    redirect('/index.php');
}

if (mb_strlen($catatan_anggota) > 500) {
    set_flash('error', 'Catatan terlalu panjang (maks 500 karakter).');
    redirect('/detail.php?id=' . $id_buku);
}
if ($catatan_anggota === '') $catatan_anggota = null;

try {
    // Validasi buku ada, tidak diarsip, stok tersedia
    $stmt = $pdo->prepare("SELECT * FROM buku WHERE id_buku = :id LIMIT 1");
    $stmt->execute([':id' => $id_buku]);
    $buku = $stmt->fetch();
    if (!$buku) {
        set_flash('error', 'Buku tidak ditemukan.');
        redirect('/index.php');
    }
    if (!empty($buku['is_arsip'])) {
        set_flash('error', 'Buku sedang diarsipkan dan tidak dapat diajukan untuk dipinjam.');
        redirect('/detail.php?id=' . $id_buku);
    }
    if ((int)$buku['tersedia'] < 1) {
        set_flash('error', 'Buku sedang tidak tersedia (stok habis).');
        redirect('/detail.php?id=' . $id_buku);
    }

    // Cek pengajuan aktif (menunggu) untuk buku yang sama — race safe via UNIQUE + SELECT
    $stmt = $pdo->prepare("SELECT id_pengajuan FROM pengajuan_peminjaman WHERE id_anggota = :anggota AND id_buku = :buku AND status = 'menunggu' LIMIT 1");
    $stmt->execute([':anggota' => $id_anggota, ':buku' => $id_buku]);
    if ($stmt->fetch()) {
        set_flash('warning', 'Kamu sudah memiliki pengajuan yang masih menunggu untuk buku ini.');
        redirect('/detail.php?id=' . $id_buku);
    }

    // Cek peminjaman aktif untuk buku yang sama
    $stmt = $pdo->prepare("SELECT id_peminjaman FROM peminjaman WHERE id_anggota = :anggota AND id_buku = :buku AND status = 'dipinjam' LIMIT 1");
    $stmt->execute([':anggota' => $id_anggota, ':buku' => $id_buku]);
    if ($stmt->fetch()) {
        set_flash('warning', 'Kamu sedang meminjam buku ini. Kembalikan terlebih dahulu sebelum mengajukan lagi.');
        redirect('/detail.php?id=' . $id_buku);
    }

    // Cek batas maksimal peminjaman aktif (3) — pengajuan tidak langsung dihitung, tapi jika disetujui akan melebihi batas
    // Beri peringatan dini agar admin tidak perlu menolak karena batas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :anggota AND status = 'dipinjam'");
    $stmt->execute([':anggota' => $id_anggota]);
    $aktif = (int)$stmt->fetchColumn();
    if ($aktif >= 3) {
        set_flash('warning', 'Kamu sudah meminjam 3 buku (batas maksimal). Kembalikan salah satu sebelum mengajukan peminjaman baru.');
        redirect('/detail.php?id=' . $id_buku);
    }

    // Buat pengajuan
    $stmt = $pdo->prepare("INSERT INTO pengajuan_peminjaman (id_anggota, id_buku, status, catatan_anggota) VALUES (:anggota, :buku, 'menunggu', :catatan)");
    $stmt->execute([
        ':anggota' => $id_anggota,
        ':buku' => $id_buku,
        ':catatan' => $catatan_anggota,
    ]);

    // Notifikasi untuk admin tidak direct, tapi anggota dapat notifikasi? Spec: admin dapat melihat jumlah menunggu
    // Kita tidak spam admin, cukup biarkan badge di menu. Untuk konsistensi, kita catat audit-like? Tidak perlu.

    set_flash('sukses', 'Pengajuan peminjaman berhasil dikirim dan sedang menunggu persetujuan admin.');
    redirect('/detail.php?id=' . $id_buku);

} catch (PDOException $e) {
    // Tangani duplicate key (double click / race)
    if (strpos($e->getMessage(), 'uniq_pengajuan_aktif') !== false || $e->getCode() == 23000) {
        // Cek apakah karena duplicate menunggu
        set_flash('warning', 'Pengajuan sudah dikirim sebelumnya dan masih menunggu persetujuan.');
        redirect('/detail.php?id=' . $id_buku);
    }
    // Tabel belum ada
    if (strpos($e->getMessage(), 'pengajuan_peminjaman') !== false) {
        set_flash('error', 'Fitur pengajuan peminjaman belum siap (tabel belum ada). Hubungi admin untuk menjalankan migrasi v7.');
        redirect('/detail.php?id=' . $id_buku);
    }
    error_log('Ajukan peminjaman gagal: ' . $e->getMessage());
    set_flash('error', 'Gagal mengirim pengajuan. Silakan coba lagi.');
    redirect('/detail.php?id=' . $id_buku);
} catch (Throwable $e) {
    error_log('Ajukan peminjaman gagal: ' . $e->getMessage());
    set_flash('error', 'Gagal mengirim pengajuan. Silakan coba lagi.');
    redirect('/detail.php?id=' . $id_buku);
}
