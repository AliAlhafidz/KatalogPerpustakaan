<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id_buku = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM buku WHERE id_buku = :id");
$stmt->execute([':id' => $id_buku]);
$buku = $stmt->fetch();

if (!$buku) {
    set_flash('error', 'Data buku tidak ditemukan.');
    redirect('/admin/buku/index.php');
}

$kategori_list = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$errors = [];
$input = [
    'isbn' => $buku['isbn'], 'judul' => $buku['judul'], 'penulis' => $buku['penulis'],
    'penerbit' => $buku['penerbit'], 'tahun_terbit' => $buku['tahun_terbit'],
    'id_kategori' => $buku['id_kategori'], 'deskripsi' => $buku['deskripsi'],
    'stok' => $buku['stok'], 'lokasi_rak' => $buku['lokasi_rak']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['isbn']         = clean($_POST['isbn'] ?? '');
    $input['judul']        = clean($_POST['judul'] ?? '');
    $input['penulis']      = clean($_POST['penulis'] ?? '');
    $input['penerbit']     = clean($_POST['penerbit'] ?? '');
    $input['tahun_terbit'] = clean($_POST['tahun_terbit'] ?? '');
    $input['id_kategori']  = (int) ($_POST['id_kategori'] ?? 0);
    $input['deskripsi']    = clean($_POST['deskripsi'] ?? '');
    $input['stok']         = (int) ($_POST['stok'] ?? 0);
    $input['lokasi_rak']   = clean($_POST['lokasi_rak'] ?? '');

    if ($input['judul'] === '') $errors[] = 'Judul buku wajib diisi.';
    if ($input['penulis'] === '') $errors[] = 'Penulis wajib diisi.';
    if ($input['stok'] < 0) $errors[] = 'Stok tidak boleh negatif.';
    if ($input['tahun_terbit'] !== '') {
        if (!ctype_digit($input['tahun_terbit'])) {
            $errors[] = 'Tahun terbit tidak valid.';
        } else {
            $tahun_int = (int)$input['tahun_terbit'];
            $tahun_max = (int)date('Y') + 1;
            if ($tahun_int < 1000 || $tahun_int > $tahun_max) $errors[] = "Tahun terbit harus antara 1000 sampai $tahun_max.";
        }
    }

    // Cegah ISBN duplikat: izinkan ISBN milik buku sendiri, tolak milik buku lain.
    if ($input['isbn'] !== '' && empty($errors)) {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM buku WHERE isbn = :isbn AND id_buku != :id');
        $cek->execute([':isbn' => $input['isbn'], ':id' => $id_buku]);
        if ((int)$cek->fetchColumn() > 0) {
            $errors[] = 'ISBN tersebut sudah terdaftar untuk buku lain di katalog.';
        }
    }

    $sedang_dipinjam = $buku['stok'] - $buku['tersedia'];
    if ($input['stok'] < $sedang_dipinjam) {
        $errors[] = "Stok tidak boleh kurang dari jumlah yang sedang dipinjam ($sedang_dipinjam eksemplar).";
    }

    $nama_cover_baru = null;
    if (empty($errors)) {
        try {
            $nama_cover_baru = upload_cover($_FILES['cover'] ?? null);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $tersedia_baru = $input['stok'] - $sedang_dipinjam;
        $cover_lama = $buku['cover'];
        $cover_final = $buku['cover'];
        if ($nama_cover_baru) {
            $cover_final = $nama_cover_baru;
        }

        $stmt = $pdo->prepare("UPDATE buku SET isbn=:isbn, judul=:judul, penulis=:penulis, penerbit=:penerbit,
                                tahun_terbit=:tahun, id_kategori=:kategori, deskripsi=:deskripsi, cover=:cover,
                                stok=:stok, tersedia=:tersedia, lokasi_rak=:rak WHERE id_buku=:id");
        $stmt->execute([
            ':isbn' => $input['isbn'] ?: null,
            ':judul' => $input['judul'],
            ':penulis' => $input['penulis'],
            ':penerbit' => $input['penerbit'] ?: null,
            ':tahun' => $input['tahun_terbit'] ?: null,
            ':kategori' => $input['id_kategori'] ?: null,
            ':deskripsi' => $input['deskripsi'] ?: null,
            ':cover' => $cover_final,
            ':stok' => $input['stok'],
            ':tersedia' => $tersedia_baru,
            ':rak' => $input['lokasi_rak'] ?: null,
            ':id' => $id_buku,
        ]);
        if ($nama_cover_baru && $cover_lama && $cover_lama !== $cover_final) {
            hapus_cover($cover_lama);
        }
        // Audit: catat field penting yang berubah
        $diff = [];
        foreach (['judul','stok','id_kategori','tahun_terbit'] as $f) {
            $old = $buku[$f] ?? null;
            $new = $input[$f] ?? null;
            if ((string)$old !== (string)$new) $diff[$f] = ['dari' => $old, 'ke' => $new];
        }
        catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'edit', 'buku', $id_buku, !empty($diff) ? $diff : ['judul' => $input['judul']]);
        set_flash('sukses', "Buku \"{$input['judul']}\" berhasil diperbarui.");
        redirect('/admin/buku/index.php');
    }
}

$menu_aktif = 'buku';
$page_title = 'Ubah Buku';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
require __DIR__ . '/_form.php';
require_once __DIR__ . '/../../includes/footer.php';
