<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/peminjaman/index.php');
}

$id_peminjaman = (int) ($_POST['id_peminjaman'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM peminjaman WHERE id_peminjaman = :id AND status = 'dipinjam'");
$stmt->execute([':id' => $id_peminjaman]);
$peminjaman = $stmt->fetch();

if (!$peminjaman) {
    set_flash('error', 'Data peminjaman tidak ditemukan atau sudah selesai dikembalikan.');
    redirect('/admin/peminjaman/index.php');
}

$pdo->beginTransaction();
try {
    // Kunci transaksi agar pembatalan tidak balapan dengan pengembalian.
    $lock = $pdo->prepare("SELECT * FROM peminjaman WHERE id_peminjaman = :id AND status = 'dipinjam' FOR UPDATE");
    $lock->execute([':id' => $id_peminjaman]);
    $peminjaman_terkunci = $lock->fetch();
    if (!$peminjaman_terkunci) throw new Exception('Peminjaman sudah berubah atau sudah dikembalikan.');
    $peminjaman = $peminjaman_terkunci;

    // Hapus catatan peminjaman (dianggap tidak pernah terjadi / kesalahan input)
    $stmt = $pdo->prepare("DELETE FROM peminjaman WHERE id_peminjaman = :id");
    $stmt->execute([':id' => $id_peminjaman]);
    if ($stmt->rowCount() !== 1) throw new Exception('Peminjaman gagal dibatalkan.');

    if (!empty($peminjaman['id_buku'])) {
        $stmt = $pdo->prepare("UPDATE buku SET tersedia = LEAST(stok, tersedia + 1) WHERE id_buku = :id");
        $stmt->execute([':id' => $peminjaman['id_buku']]);
        if ($stmt->rowCount() !== 1) throw new Exception('Stok buku gagal dikembalikan.');
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    set_flash('error', $e->getMessage());
    redirect('/admin/peminjaman/index.php');
}

hapus_cache_buku_populer();
set_flash('sukses', 'Peminjaman berhasil dibatalkan dan stok buku telah dikembalikan.');
redirect('/admin/peminjaman/index.php');
