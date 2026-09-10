<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id = (int)($_POST['id'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
$catatan_admin = clean($_POST['catatan_admin'] ?? '');

if ($id <= 0 || !in_array($aksi, ['disetujui','ditolak'], true)) {
    set_flash('error', 'Aksi tidak valid.');
    redirect('/admin/pengajuan/index.php');
}
if (mb_strlen($catatan_admin) > 500) {
    set_flash('error', 'Catatan terlalu panjang (maks 500).');
    redirect('/admin/pengajuan/index.php?status=menunggu');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM pengajuan_buku WHERE id = :id AND status = 'menunggu' LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('error', 'Pengajuan tidak ditemukan atau sudah diproses.');
        redirect('/admin/pengajuan/index.php');
    }

    $stmt = $pdo->prepare("UPDATE pengajuan_buku SET status = :status, catatan_admin = :catatan, updated_at = NOW() WHERE id = :id AND status = 'menunggu'");
    $stmt->execute([':status' => $aksi, ':catatan' => $catatan_admin ?: null, ':id' => $id]);
    if ($stmt->rowCount() !== 1) {
        set_flash('error', 'Pengajuan sudah diproses.');
        redirect('/admin/pengajuan/index.php');
    }

    // Notifikasi reuse (opsional, side-effect) — jangan gagalkan aksi utama
    try {
        $pesan = $aksi === 'disetujui'
            ? 'Pengajuan buku "' . $row['judul'] . '" disetujui.' . ($catatan_admin !== '' ? ' Catatan admin: ' . $catatan_admin : ' Admin akan menindaklanjuti.')
            : 'Pengajuan buku "' . $row['judul'] . '" ditolak.' . ($catatan_admin !== '' ? ' Catatan: ' . $catatan_admin : '');
        $tipe = $aksi === 'disetujui' ? 'success' : 'danger';
        buat_notifikasi_anggota($pdo, (int)$row['anggota_id'], $aksi === 'disetujui' ? 'Pengajuan disetujui' : 'Pengajuan ditolak', $pesan, $tipe, '/anggota/pengajuan.php', 'pengajuan:' . $id . ':' . $aksi);
    } catch (Throwable $e) {
        error_log('Notifikasi pengajuan gagal: ' . $e->getMessage());
    }

    // Audit log — pengajuan_buku pakai target_tabel 'pengajuan_buku' (sudah ada di whitelist audit.php, VARCHAR jadi tanpa ALTER)
    catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'edit', 'pengajuan_buku', $id, ['pengajuan' => $row['judul'], 'aksi' => $aksi, 'catatan' => $catatan_admin]);

    set_flash($aksi === 'disetujui' ? 'sukses' : 'warning', $aksi === 'disetujui' ? 'Pengajuan disetujui.' : 'Pengajuan ditolak.');
} catch (Throwable $e) {
    if (strpos($e->getMessage(), 'pengajuan_buku') !== false) {
        set_flash('error', 'Tabel pengajuan_buku belum ada. Jalankan migrasi.');
    } else {
        set_flash('error', 'Gagal memproses: ' . $e->getMessage());
    }
    error_log('Proses pengajuan gagal: ' . $e->getMessage());
}
redirect('/admin/pengajuan/index.php?status=' . urlencode($aksi === 'disetujui' ? 'disetujui' : 'ditolak'));
