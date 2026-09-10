<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id_anggota = (int)$_SESSION['id_anggota'];
$id_peminjaman = (int)($_POST['id_peminjaman'] ?? 0);
$hari_diminta = (int)($_POST['hari_diminta'] ?? 0);
$catatan = clean($_POST['catatan'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id_peminjaman <= 0) {
    set_flash('error', 'Permintaan perpanjangan tidak valid.');
    redirect('/anggota/peminjaman.php');
}

if ($hari_diminta < 1 || $hari_diminta > PERPANJANGAN_MAKS_HARI) {
    set_flash('error', 'Perpanjangan harus antara 1 sampai ' . PERPANJANGAN_MAKS_HARI . ' hari.');
    redirect('/anggota/peminjaman.php');
}

$stmt = $pdo->prepare("SELECT p.*, b.judul FROM peminjaman p JOIN buku b ON b.id_buku=p.id_buku WHERE p.id_peminjaman=:p AND p.id_anggota=:a AND p.status='dipinjam'");
$stmt->execute([':p'=>$id_peminjaman, ':a'=>$id_anggota]);
$peminjaman = $stmt->fetch();

if (!$peminjaman) {
    set_flash('error', 'Peminjaman tidak ditemukan atau sudah dikembalikan.');
    redirect('/anggota/peminjaman.php');
}

if ($peminjaman['tanggal_jatuh_tempo'] < date('Y-m-d')) {
    set_flash('error', 'Peminjaman yang sudah lewat jatuh tempo tidak dapat diajukan perpanjangan.');
    redirect('/anggota/peminjaman.php');
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM perpanjangan_peminjaman WHERE id_peminjaman=:p AND status='menunggu'");
$stmt->execute([':p'=>$id_peminjaman]);
if ((int)$stmt->fetchColumn() > 0) {
    set_flash('warning', 'Permintaan perpanjangan untuk buku ini masih menunggu persetujuan admin.');
    redirect('/anggota/peminjaman.php');
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM perpanjangan_peminjaman WHERE id_peminjaman=:p AND status='disetujui'");
$stmt->execute([':p'=>$id_peminjaman]);
if ((int)$stmt->fetchColumn() > 0) {
    set_flash('warning', 'Buku ini sudah pernah mendapatkan perpanjangan.');
    redirect('/anggota/peminjaman.php');
}

$tempo_baru = date('Y-m-d', strtotime($peminjaman['tanggal_jatuh_tempo'] . ' +' . $hari_diminta . ' days'));
$stmt = $pdo->prepare("INSERT INTO perpanjangan_peminjaman
    (id_peminjaman,id_anggota,tanggal_pengajuan,hari_diminta,tanggal_jatuh_tempo_lama,catatan_anggota,status)
    VALUES (:p,:a,:tgl,:hari,:lama,:catatan,'menunggu')");
$stmt->execute([
    ':p'=>$id_peminjaman, ':a'=>$id_anggota, ':tgl'=>date('Y-m-d'), ':hari'=>$hari_diminta,
    ':lama'=>$peminjaman['tanggal_jatuh_tempo'], ':catatan'=>$catatan !== '' ? $catatan : null,
]);

set_flash('sukses', 'Permintaan perpanjangan berhasil dikirim. Admin akan meninjau permintaanmu.');
redirect('/anggota/peminjaman.php');
