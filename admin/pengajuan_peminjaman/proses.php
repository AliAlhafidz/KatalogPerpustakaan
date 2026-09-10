<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id_pengajuan = (int)($_POST['id_pengajuan'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
$catatan_admin = clean($_POST['catatan_admin'] ?? '');

if ($id_pengajuan <= 0 || !in_array($aksi, ['setujui','tolak'], true)) {
    set_flash('error', 'Aksi tidak valid.');
    redirect('/admin/pengajuan_peminjaman/index.php');
}
if (mb_strlen($catatan_admin) > 500) {
    set_flash('error', 'Catatan terlalu panjang (maks 500 karakter).');
    redirect('/admin/pengajuan_peminjaman/index.php');
}

$pdo->beginTransaction();
try {
    // Kunci pengajuan agar dua admin tidak memproses bersamaan
    $stmt = $pdo->prepare("SELECT pp.*, b.judul, b.tersedia, b.is_arsip, b.stok, a.nama AS nama_anggota
                           FROM pengajuan_peminjaman pp
                           JOIN buku b ON b.id_buku = pp.id_buku
                           JOIN anggota a ON a.id_anggota = pp.id_anggota
                           WHERE pp.id_pengajuan = :id AND pp.status = 'menunggu' FOR UPDATE");
    $stmt->execute([':id' => $id_pengajuan]);
    $pengajuan = $stmt->fetch();
    if (!$pengajuan) {
        throw new Exception('Pengajuan sudah diproses atau tidak ditemukan.');
    }

    $id_anggota = (int)$pengajuan['id_anggota'];
    $id_buku = (int)$pengajuan['id_buku'];

    // Kunci buku untuk mencegah race condition stok
    $stmt = $pdo->prepare("SELECT * FROM buku WHERE id_buku = :id FOR UPDATE");
    $stmt->execute([':id' => $id_buku]);
    $buku = $stmt->fetch();
    if (!$buku) {
        throw new Exception('Data buku tidak ditemukan.');
    }

    if ($aksi === 'tolak') {
        $stmt = $pdo->prepare("UPDATE pengajuan_peminjaman SET status = 'ditolak', catatan_admin = :catatan, diproses_oleh = :admin, diproses_at = NOW(), updated_at = NOW() WHERE id_pengajuan = :id AND status = 'menunggu'");
        $stmt->execute([
            ':catatan' => $catatan_admin !== '' ? $catatan_admin : null,
            ':admin' => $_SESSION['id_admin'],
            ':id' => $id_pengajuan,
        ]);
        if ($stmt->rowCount() !== 1) throw new Exception('Gagal menolak pengajuan. Mungkin sudah diproses.');

        // Notifikasi ke anggota
        $pesan_notif = 'Pengajuan peminjaman buku "' . $pengajuan['judul'] . '" ditolak.';
        if ($catatan_admin !== '') $pesan_notif .= ' Catatan admin: ' . $catatan_admin;
        buat_notifikasi_anggota($pdo, $id_anggota, 'Pengajuan peminjaman ditolak', $pesan_notif, 'danger', '/anggota/pengajuan_peminjaman.php', 'pengajuan_pinjam:' . $id_pengajuan . ':ditolak');

        catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'edit', 'pengajuan_peminjaman', $id_pengajuan, ['aksi' => 'tolak', 'buku' => $pengajuan['judul'], 'catatan' => $catatan_admin]);
        $pdo->commit();
        set_flash('warning', 'Pengajuan berhasil ditolak.');
        redirect('/admin/pengajuan_peminjaman/index.php?status=ditolak');
    }

    // === AKSI SETUJUI ===
    // Validasi buku
    if (!empty($buku['is_arsip'])) {
        throw new Exception('Buku "' . $buku['judul'] . '" sedang diarsipkan dan tidak dapat dipinjam.');
    }
    if ((int)$buku['tersedia'] < 1) {
        throw new Exception('Stok buku "' . $buku['judul'] . '" sudah habis. Tidak dapat menyetujui pengajuan saat ini.');
    }

    // Validasi anggota masih aktif
    $stmt = $pdo->prepare("SELECT status FROM anggota WHERE id_anggota = :id FOR UPDATE");
    $stmt->execute([':id' => $id_anggota]);
    $anggota_status = $stmt->fetchColumn();
    if ($anggota_status !== 'aktif') {
        throw new Exception('Anggota tidak aktif, tidak dapat menyetujui peminjaman.');
    }

    // Cek apakah anggota sudah memiliki peminjaman aktif untuk buku yang sama (mencegah duplikat)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :anggota AND id_buku = :buku AND status = 'dipinjam'");
    $stmt->execute([':anggota' => $id_anggota, ':buku' => $id_buku]);
    if ((int)$stmt->fetchColumn() > 0) {
        throw new Exception('Anggota sudah memiliki peminjaman aktif untuk buku ini.');
    }

    // Cek batas maksimal 3 buku aktif — konsisten dengan admin/peminjaman/tambah.php
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :anggota AND status = 'dipinjam'");
    $stmt->execute([':anggota' => $id_anggota]);
    $aktif = (int)$stmt->fetchColumn();
    if ($aktif >= 3) {
        throw new Exception('Anggota sudah meminjam 3 buku (batas maksimal) dan belum mengembalikannya. Tidak dapat menyetujui pengajuan baru.');
    }

    // Buat transaksi peminjaman — konsisten dengan tambah.php
    $tanggal_pinjam = date('Y-m-d');
    // Cek hari Minggu (konsisten dengan validasi tambah.php) — jika hari ini Minggu, tetap izinkan? tambah.php melarang peminjaman di Minggu.
    // Untuk approval, jika hari ini Minggu, gunakan logika yang sama: beri error agar admin proses hari kerja.
    if (is_hari_minggu($tanggal_pinjam)) {
        throw new Exception('Perpustakaan tutup pada hari Minggu. Proses persetujuan dapat dilakukan pada hari kerja berikutnya.');
    }
    $tanggal_jatuh_tempo = date('Y-m-d', strtotime('+' . LAMA_PINJAM_HARI . ' days', strtotime($tanggal_pinjam)));

    // Insert peminjaman
    $stmt = $pdo->prepare("INSERT INTO peminjaman (id_anggota, id_buku, tanggal_pinjam, tanggal_jatuh_tempo, status, diproses_oleh) VALUES (:anggota, :buku, :pinjam, :tempo, 'dipinjam', :admin)");
    $stmt->execute([
        ':anggota' => $id_anggota,
        ':buku' => $id_buku,
        ':pinjam' => $tanggal_pinjam,
        ':tempo' => $tanggal_jatuh_tempo,
        ':admin' => $_SESSION['id_admin'],
    ]);
    $id_peminjaman_baru = (int)$pdo->lastInsertId();

    // Kurangi stok tersedia secara aman (conditional update)
    $stmt = $pdo->prepare("UPDATE buku SET tersedia = tersedia - 1 WHERE id_buku = :id AND tersedia > 0");
    $stmt->execute([':id' => $id_buku]);
    if ($stmt->rowCount() !== 1) {
        throw new Exception('Stok buku "' . $buku['judul'] . '" berubah saat diproses. Persetujuan dibatalkan, silakan coba lagi.');
    }

    // Update pengajuan menjadi disetujui
    $stmt = $pdo->prepare("UPDATE pengajuan_peminjaman SET status = 'disetujui', catatan_admin = :catatan, diproses_oleh = :admin, diproses_at = NOW(), updated_at = NOW() WHERE id_pengajuan = :id AND status = 'menunggu'");
    $stmt->execute([
        ':catatan' => $catatan_admin !== '' ? $catatan_admin : null,
        ':admin' => $_SESSION['id_admin'],
        ':id' => $id_pengajuan,
    ]);
    if ($stmt->rowCount() !== 1) {
        throw new Exception('Gagal memperbarui status pengajuan. Mungkin sudah diproses admin lain.');
    }

    // Notifikasi ke anggota — sukses
    $pesan_notif = 'Pengajuan peminjaman buku "' . $pengajuan['judul'] . '" telah disetujui. Silakan ambil buku di perpustakaan. Jatuh tempo: ' . format_tanggal($tanggal_jatuh_tempo) . '.';
    if ($catatan_admin !== '') $pesan_notif .= ' Catatan admin: ' . $catatan_admin;
    buat_notifikasi_anggota($pdo, $id_anggota, 'Pengajuan peminjaman disetujui 🎉', $pesan_notif, 'success', '/anggota/peminjaman.php', 'pengajuan_pinjam:' . $id_pengajuan . ':disetujui');

    catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'edit', 'pengajuan_peminjaman', $id_pengajuan, ['aksi' => 'setujui', 'buku' => $pengajuan['judul'], 'id_peminjaman' => $id_peminjaman_baru, 'catatan' => $catatan_admin]);

    $pdo->commit();
    set_flash('sukses', 'Pengajuan berhasil disetujui. Transaksi peminjaman dibuat (ID #' . $id_peminjaman_baru . '). Jatuh tempo: ' . format_tanggal($tanggal_jatuh_tempo) . '.');
    redirect('/admin/pengajuan_peminjaman/index.php?status=disetujui');

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Jika error karena stok habis atau arsip, admin tetap bisa melihat dan memutuskan menolak
    set_flash('error', $e->getMessage());
    redirect('/admin/pengajuan_peminjaman/index.php?status=menunggu');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Proses pengajuan_peminjaman gagal: ' . $e->getMessage());
    if (strpos($e->getMessage(), 'pengajuan_peminjaman') !== false) {
        set_flash('error', 'Tabel pengajuan_peminjaman belum ada. Jalankan migrasi v7.');
    } else {
        set_flash('error', 'Gagal memproses: ' . $e->getMessage());
    }
    redirect('/admin/pengajuan_peminjaman/index.php');
}
