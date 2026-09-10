<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$id_anggota = $_SESSION['id_anggota'];
$id_buku = (int) ($_POST['id_buku'] ?? 0);
$redirect_ke = $_POST['redirect'] ?? '';

// Pastikan buku benar-benar ada
$stmt = $pdo->prepare("SELECT id_buku FROM buku WHERE id_buku = :id");
$stmt->execute([':id' => $id_buku]);
if (!$stmt->fetch()) {
    set_flash('error', 'Buku tidak ditemukan.');
    redirect('/index.php');
}

if (is_favorit($pdo, $id_anggota, $id_buku)) {
    $stmt = $pdo->prepare("DELETE FROM favorit WHERE id_anggota = :a AND id_buku = :b");
    $stmt->execute([':a' => $id_anggota, ':b' => $id_buku]);
    set_flash('sukses', 'Buku dihapus dari favorit.');
} else {
    $stmt = $pdo->prepare("INSERT INTO favorit (id_anggota, id_buku) VALUES (:a, :b)");
    $stmt->execute([':a' => $id_anggota, ':b' => $id_buku]);
    set_flash('sukses', 'Buku ditambahkan ke favorit.');
}

if ($redirect_ke === 'favorit') {
    redirect('/anggota/favorit.php');
} elseif ($redirect_ke === 'detail') {
    redirect('/detail.php?id=' . $id_buku);
} else {
    // Jangan memakai HTTP_REFERER karena nilainya dapat berasal dari luar aplikasi.
    redirect('/index.php');
}
