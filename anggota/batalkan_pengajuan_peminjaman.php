<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/anggota/pengajuan_peminjaman.php');
}

$id_anggota = (int)($_SESSION['id_anggota'] ?? 0);
$id_pengajuan = (int)($_POST['id_pengajuan'] ?? 0);

if ($id_pengajuan <= 0) {
    set_flash('error', 'Pengajuan tidak valid.');
    redirect('/anggota/pengajuan_peminjaman.php');
}

try {
    // Pastikan milik anggota dan masih menunggu — lock untuk cegah race dengan admin proses
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT * FROM pengajuan_peminjaman WHERE id_pengajuan = :id AND id_anggota = :anggota FOR UPDATE");
    $stmt->execute([':id' => $id_pengajuan, ':anggota' => $id_anggota]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        set_flash('error', 'Pengajuan tidak ditemukan.');
        redirect('/anggota/pengajuan_peminjaman.php');
    }
    if ($row['status'] !== 'menunggu') {
        $pdo->rollBack();
        set_flash('warning', 'Hanya pengajuan dengan status menunggu yang dapat dibatalkan.');
        redirect('/anggota/pengajuan_peminjaman.php');
    }

    $stmt = $pdo->prepare("UPDATE pengajuan_peminjaman SET status = 'dibatalkan', updated_at = NOW() WHERE id_pengajuan = :id AND id_anggota = :anggota AND status = 'menunggu'");
    $stmt->execute([':id' => $id_pengajuan, ':anggota' => $id_anggota]);
    if ($stmt->rowCount() !== 1) {
        $pdo->rollBack();
        set_flash('error', 'Gagal membatalkan pengajuan. Mungkin sudah diproses admin.');
        redirect('/anggota/pengajuan_peminjaman.php');
    }

    $pdo->commit();
    set_flash('sukses', 'Pengajuan berhasil dibatalkan.');
    redirect('/anggota/pengajuan_peminjaman.php');

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (strpos($e->getMessage(), 'pengajuan_peminjaman') !== false) {
        set_flash('error', 'Tabel pengajuan belum ada. Jalankan migrasi.');
    } else {
        error_log('Batalkan pengajuan gagal: ' . $e->getMessage());
        set_flash('error', 'Gagal membatalkan pengajuan. Silakan coba lagi.');
    }
    redirect('/anggota/pengajuan_peminjaman.php');
}
