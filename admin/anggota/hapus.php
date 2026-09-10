<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/anggota/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = :id");
$stmt->execute([':id' => $id]);
$anggota = $stmt->fetch();

if (!$anggota) {
    set_flash('error', 'Data anggota tidak ditemukan.');
    redirect('/admin/anggota/index.php');
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id");
$stmt->execute([':id' => $id]);
if ((int) $stmt->fetchColumn() > 0) {
    set_flash('error', 'Anggota tidak dapat dihapus karena memiliki riwayat peminjaman. Nonaktifkan akun jika anggota sudah tidak digunakan.');
    redirect('/admin/anggota/index.php');
}

$foto_lama = $anggota['foto'] ?? null;
$stmt = $pdo->prepare("DELETE FROM anggota WHERE id_anggota = :id");
$stmt->execute([':id' => $id]);

if ($stmt->rowCount() === 1 && $foto_lama) {
    hapus_foto_profil($foto_lama);
}
set_flash('sukses', "Anggota \"{$anggota['nama']}\" berhasil dihapus.");
redirect('/admin/anggota/index.php');
