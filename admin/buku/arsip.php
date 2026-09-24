<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/buku/index.php');
}

$id_buku = (int) ($_POST['id'] ?? 0);
$aksi = $_POST['aksi'] ?? '';

if ($id_buku <= 0 || !in_array($aksi, ['arsipkan','publikasikan'], true)) {
    set_flash('error', 'Aksi arsip tidak valid.');
    redirect('/admin/buku/index.php');
}

$stmt = $pdo->prepare("SELECT id_buku, judul, is_arsip FROM buku WHERE id_buku = :id LIMIT 1");
$stmt->execute([':id' => $id_buku]);
$buku = $stmt->fetch();

if (!$buku) {
    set_flash('error', 'Data buku tidak ditemukan.');
    redirect('/admin/buku/index.php');
}

if ($aksi === 'arsipkan' && (int)$buku['is_arsip'] === 1) {
    set_flash('warning', "Buku \"{$buku['judul']}\" sudah diarsipkan.");
    redirect('/admin/buku/index.php');
}
if ($aksi === 'publikasikan' && (int)$buku['is_arsip'] === 0) {
    set_flash('warning', "Buku \"{$buku['judul']}\" sudah aktif.");
    redirect('/admin/buku/index.php');
}

try {
    if ($aksi === 'arsipkan') {
        $stmt = $pdo->prepare("UPDATE buku SET is_arsip = 1, arsip_at = NOW() WHERE id_buku = :id");
        $stmt->execute([':id' => $id_buku]);
        set_flash('sukses', "Buku \"{$buku['judul']}\" berhasil diarsipkan. Buku tidak akan muncul di katalog dan tidak dapat dipinjam sampai dipublikasikan kembali.");
    } else {
        $stmt = $pdo->prepare("UPDATE buku SET is_arsip = 0, arsip_at = NULL WHERE id_buku = :id");
        $stmt->execute([':id' => $id_buku]);
        set_flash('sukses', "Buku \"{$buku['judul']}\" berhasil dipublikasikan kembali.");
    }
    hapus_cache_buku_populer();
} catch (Throwable $e) {
    error_log('Arsip buku #' . $id_buku . ': ' . $e->getMessage());
    set_flash('error', 'Gagal memperbarui status arsip.');
}

redirect('/admin/buku/index.php');
