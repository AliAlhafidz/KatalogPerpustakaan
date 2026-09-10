<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/kategori/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_kategori = :id");
$stmt->execute([':id' => $id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    set_flash('error', 'Kategori tidak ditemukan.');
    redirect('/admin/kategori/index.php');
}

// Buku dengan kategori ini akan otomatis menjadi "Tanpa Kategori" (ON DELETE SET NULL)
$stmt = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = :id");
$stmt->execute([':id' => $id]);

catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'hapus', 'kategori', $id, ['nama' => $kategori['nama_kategori']]);
set_flash('sukses', "Kategori \"{$kategori['nama_kategori']}\" berhasil dihapus.");
redirect('/admin/kategori/index.php');
