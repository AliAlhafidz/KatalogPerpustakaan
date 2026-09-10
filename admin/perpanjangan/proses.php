<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id = (int)($_POST['id_perpanjangan'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
$catatan_admin = clean($_POST['catatan_admin'] ?? '');

if ($id <= 0 || !in_array($aksi, ['setujui','tolak'])) {
    set_flash('error', 'Aksi perpanjangan tidak valid.');
    redirect('/admin/perpanjangan/index.php');
}

$pdo->beginTransaction();
try {
    // Kunci permintaan setelah transaksi dimulai agar dua admin tidak dapat memprosesnya bersamaan.
    $lock = $pdo->prepare("SELECT pp.*, p.status AS status_peminjaman, p.tanggal_jatuh_tempo, p.id_buku, b.judul, a.nama AS nama_anggota
                           FROM perpanjangan_peminjaman pp
                           JOIN peminjaman p ON p.id_peminjaman=pp.id_peminjaman
                           JOIN buku b ON b.id_buku=p.id_buku
                           JOIN anggota a ON a.id_anggota=pp.id_anggota
                           WHERE pp.id_perpanjangan=:id AND pp.status='menunggu' FOR UPDATE");
    $lock->execute([':id'=>$id]);
    $r = $lock->fetch();
    if (!$r) throw new Exception('Permintaan sudah diproses atau tidak ditemukan.');

    // Kunci juga transaksi peminjaman agar persetujuan tidak balapan dengan proses pengembalian.
    $loanLock = $pdo->prepare("SELECT status, tanggal_jatuh_tempo FROM peminjaman WHERE id_peminjaman=:p FOR UPDATE");
    $loanLock->execute([':p'=>$r['id_peminjaman']]);
    $loan = $loanLock->fetch();
    if (!$loan) throw new Exception('Data peminjaman tidak ditemukan.');
    $r['status_peminjaman'] = $loan['status'];
    $r['tanggal_jatuh_tempo'] = $loan['tanggal_jatuh_tempo'];

    if ($r['status_peminjaman'] !== 'dipinjam') {
        $stmt = $pdo->prepare("UPDATE perpanjangan_peminjaman SET status='ditolak', catatan_admin=:catatan, diproses_oleh=:admin, diproses_at=NOW() WHERE id_perpanjangan=:id");
        $stmt->execute([':catatan'=>$catatan_admin ?: 'Peminjaman sudah tidak aktif.', ':admin'=>$_SESSION['id_admin'], ':id'=>$id]);
        buat_notifikasi_anggota($pdo, $r['id_anggota'], 'Permintaan perpanjangan ditolak', 'Permintaan perpanjangan untuk buku "'.$r['judul'].'" tidak dapat diproses karena peminjaman sudah selesai.', 'danger', '/anggota/peminjaman.php', 'perpanjangan:'.$id.':ditolak');
        $pdo->commit();
        set_flash('warning', 'Peminjaman sudah tidak aktif, permintaan ditolak.');
        redirect('/admin/perpanjangan/index.php');
    }

    if ($aksi === 'setujui') {
        // Pastikan belum pernah ada perpanjangan yang disetujui untuk transaksi ini.
        $cek = $pdo->prepare("SELECT COUNT(*) FROM perpanjangan_peminjaman WHERE id_peminjaman=:p AND status='disetujui' FOR UPDATE");
        $cek->execute([':p'=>$r['id_peminjaman']]);
        if ((int)$cek->fetchColumn() > 0) {
            throw new Exception('Peminjaman ini sudah pernah mendapatkan perpanjangan.');
        }

        $tempo_baru = date('Y-m-d', strtotime($r['tanggal_jatuh_tempo'] . ' +' . (int)$r['hari_diminta'] . ' days'));
        $stmt = $pdo->prepare("UPDATE peminjaman SET tanggal_jatuh_tempo=:tempo WHERE id_peminjaman=:p AND status='dipinjam'");
        $stmt->execute([':tempo'=>$tempo_baru, ':p'=>$r['id_peminjaman']]);
        if ($stmt->rowCount() !== 1) throw new Exception('Peminjaman sudah tidak aktif. Permintaan tidak dapat disetujui.');

        $stmt = $pdo->prepare("UPDATE perpanjangan_peminjaman SET status='disetujui', tanggal_jatuh_tempo_baru=:tempo, catatan_admin=:catatan, diproses_oleh=:admin, diproses_at=NOW() WHERE id_perpanjangan=:id");
        $stmt->execute([':tempo'=>$tempo_baru, ':catatan'=>$catatan_admin ?: null, ':admin'=>$_SESSION['id_admin'], ':id'=>$id]);
        $pesan_notif = 'Permintaan perpanjangan buku "'.$r['judul'].'" disetujui. Jatuh tempo baru: '.format_tanggal($tempo_baru).'.';
        if ($catatan_admin !== '') $pesan_notif .= ' Catatan admin: ' . $catatan_admin;
        buat_notifikasi_anggota($pdo, $r['id_anggota'], 'Perpanjangan disetujui 🎉', $pesan_notif, 'success', '/anggota/peminjaman.php', 'perpanjangan:'.$id.':disetujui');
        $pesan = 'Perpanjangan berhasil disetujui. Jatuh tempo baru: ' . format_tanggal($tempo_baru) . '.';
    } else {
        $stmt = $pdo->prepare("UPDATE perpanjangan_peminjaman SET status='ditolak', catatan_admin=:catatan, diproses_oleh=:admin, diproses_at=NOW() WHERE id_perpanjangan=:id");
        $stmt->execute([':catatan'=>$catatan_admin ?: 'Permintaan belum dapat disetujui oleh admin.', ':admin'=>$_SESSION['id_admin'], ':id'=>$id]);
        $pesan_notif = $catatan_admin !== '' ? 'Permintaan perpanjangan buku "'.$r['judul'].'" ditolak. Catatan admin: ' . $catatan_admin : 'Permintaan perpanjangan buku "'.$r['judul'].'" belum dapat disetujui oleh admin.';
        buat_notifikasi_anggota($pdo, $r['id_anggota'], 'Permintaan perpanjangan ditolak', $pesan_notif, 'danger', '/anggota/peminjaman.php', 'perpanjangan:'.$id.':ditolak');
        $pesan = 'Permintaan perpanjangan ditolak.';
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', $e->getMessage());
    redirect('/admin/perpanjangan/index.php');
}

set_flash($aksi === 'setujui' ? 'sukses' : 'warning', $pesan);
redirect('/admin/perpanjangan/index.php');
