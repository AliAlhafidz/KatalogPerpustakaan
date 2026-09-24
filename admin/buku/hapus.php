<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/buku/index.php');
}

$id_buku = (int) ($_POST['id'] ?? 0);

if ($id_buku <= 0) {
    set_flash('error', 'ID buku tidak valid.');
    redirect('/admin/buku/index.php');
}

// Ambil data buku terlebih dahulu supaya cover lama bisa ikut dibersihkan.
$stmt = $pdo->prepare("SELECT id_buku, judul, cover FROM buku WHERE id_buku = :id LIMIT 1");
$stmt->execute([':id' => $id_buku]);
$buku = $stmt->fetch();

if (!$buku) {
    set_flash('error', 'Data buku tidak ditemukan.');
    redirect('/admin/buku/index.php');
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_buku = :id AND status = 'dipinjam'");
    $stmt->execute([':id' => $id_buku]);
    $masih_dipinjam = (int)$stmt->fetchColumn() > 0;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM favorit WHERE id_buku = :id");
    $stmt->execute([':id' => $id_buku]);

    $stmt = $pdo->prepare("DELETE FROM buku WHERE id_buku = :id");
    $stmt->execute([':id' => $id_buku]);
    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Data buku gagal dihapus dari database.');
    }
    $pdo->commit();

    hapus_cover($buku['cover']);
    hapus_cache_buku_populer();
    catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'hapus', 'buku', $id_buku, ['judul' => $buku['judul']]);

    if ($masih_dipinjam) {
        set_flash('warning', "Buku \"{$buku['judul']}\" berhasil dihapus. Perhatian: buku ini masih dipinjam oleh anggota. Transaksi pengembaliannya nanti tidak akan bisa diproses otomatis lewat sistem karena data buku sudah dihapus — cek riwayat peminjaman manual.");
    } else {
        set_flash('sukses', "Buku \"{$buku['judul']}\" berhasil dihapus.");
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Jangan tampilkan detail error database kepada pengguna.
    error_log('Gagal menghapus buku #' . $id_buku . ': ' . $e->getMessage());
    set_flash('error', 'Buku gagal dihapus. Pastikan struktur database sudah sesuai dengan aplikasi.');
}

redirect('/admin/buku/index.php');
