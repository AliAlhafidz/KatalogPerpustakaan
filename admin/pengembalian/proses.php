<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/pengembalian/index.php');
}

$id_peminjaman = (int) ($_POST['id_peminjaman'] ?? 0);
$tanggal_kembali = trim($_POST['tanggal_kembali'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM peminjaman WHERE id_peminjaman = :id AND status = 'dipinjam'");
$stmt->execute([':id' => $id_peminjaman]);
$peminjaman = $stmt->fetch();

if (!$peminjaman) {
    set_flash('error', 'Data peminjaman tidak ditemukan atau sudah dikembalikan.');
    redirect('/admin/pengembalian/index.php');
}

// Validasi format tanggal (harus Y-m-d yang valid)
$valid_format = false;
$d = DateTime::createFromFormat('Y-m-d', $tanggal_kembali);
if ($d && $d->format('Y-m-d') === $tanggal_kembali) {
    $valid_format = true;
}

if (!$valid_format) {
    set_flash('error', 'Tanggal pengembalian tidak valid.');
    redirect('/admin/pengembalian/index.php');
}

$hari_ini = date('Y-m-d');

// Valid: antara tanggal_pinjam s/d hari_ini (backdate diperbolehkan untuk koreksi)
if ($tanggal_kembali < $peminjaman['tanggal_pinjam']) {
    set_flash('error', 'Tanggal pengembalian tidak boleh sebelum tanggal pinjam (' . format_tanggal($peminjaman['tanggal_pinjam']) . ').');
    redirect('/admin/pengembalian/index.php');
}
if ($tanggal_kembali > $hari_ini) {
    set_flash('error', 'Tanggal pengembalian tidak boleh melebihi hari ini (' . format_tanggal($hari_ini) . ').');
    redirect('/admin/pengembalian/index.php');
}

// Perpustakaan tutup setiap hari Minggu — tanggal Minggu tidak dapat dipilih
if (is_hari_minggu($tanggal_kembali)) {
    set_flash('error', 'Perpustakaan tutup pada hari Minggu (' . format_tanggal($tanggal_kembali) . '). Silakan pilih tanggal lain.');
    redirect('/admin/pengembalian/index.php');
}

$pdo->beginTransaction();
try {
    // Kunci transaksi peminjaman setelah transaksi dimulai agar tidak diproses dua kali.
    $lock = $pdo->prepare("SELECT * FROM peminjaman WHERE id_peminjaman = :id AND status = 'dipinjam' FOR UPDATE");
    $lock->execute([':id' => $id_peminjaman]);
    $peminjaman_terkunci = $lock->fetch();
    if (!$peminjaman_terkunci) throw new Exception('Status peminjaman sudah berubah. Silakan muat ulang halaman.');
    $peminjaman = $peminjaman_terkunci;

    $telat = hitung_keterlambatan($peminjaman['tanggal_jatuh_tempo'], $tanggal_kembali);
    $denda = hitung_denda($telat);

    $stmt = $pdo->prepare("UPDATE peminjaman SET status='dikembalikan', tanggal_kembali=:tgl, denda=:denda, diproses_oleh=:admin WHERE id_peminjaman=:id AND status='dipinjam'");
    $stmt->execute([':tgl' => $tanggal_kembali, ':denda' => $denda, ':admin' => $_SESSION['id_admin'], ':id' => $id_peminjaman]);
    if ($stmt->rowCount() !== 1) throw new Exception('Status peminjaman sudah berubah. Silakan muat ulang halaman.');

    $stmt = $pdo->prepare("UPDATE buku SET tersedia = LEAST(stok, tersedia + 1) WHERE id_buku = :id");
    $stmt->execute([':id' => $peminjaman['id_buku']]);
    if ($stmt->rowCount() !== 1) throw new Exception('Stok buku gagal diperbarui.');

    $pdo->commit();
    $stmt = $pdo->prepare("SELECT judul FROM buku WHERE id_buku=:id");
    $stmt->execute([':id'=>$peminjaman['id_buku']]);
    $judul_buku = $stmt->fetchColumn() ?: 'buku';
    buat_notifikasi_anggota($pdo, $peminjaman['id_anggota'], 'Peminjaman selesai', 'Buku "' . $judul_buku . '" telah dicatat sebagai dikembalikan. ' . ($denda > 0 ? 'Denda tercatat: ' . format_rupiah($denda) . '.' : 'Tidak ada denda.'), $denda > 0 ? 'warning' : 'success', '/anggota/riwayat.php', 'kembali:' . $id_peminjaman);
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}

$pesan = $denda > 0
    ? "Buku berhasil dikembalikan (tanggal " . format_tanggal($tanggal_kembali) . "). Terlambat $telat hari, denda " . format_rupiah($denda) . '.'
    : 'Buku berhasil dikembalikan tepat waktu (tanggal ' . format_tanggal($tanggal_kembali) . ').';
set_flash($denda > 0 ? 'warning' : 'sukses', $pesan);
redirect('/admin/pengembalian/index.php');
