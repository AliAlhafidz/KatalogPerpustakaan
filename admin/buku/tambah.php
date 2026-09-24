<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$kategori_list = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$errors = [];
$input = [
    'isbn' => '', 'judul' => '', 'penulis' => '', 'penerbit' => '',
    'tahun_terbit' => '', 'id_kategori' => '', 'deskripsi' => '',
    'stok' => 1, 'lokasi_rak' => ''
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
    $cover_api_url = trim($_POST['cover_api_url'] ?? '');

    // Cegah ISBN duplikat saat ISBN diisi.
    if ($input['isbn'] !== '') {
        $cek = $pdo->prepare('SELECT COUNT(*) FROM buku WHERE isbn = :isbn');
        $cek->execute([':isbn' => $input['isbn']]);
        if ((int)$cek->fetchColumn() > 0) $errors[] = 'ISBN tersebut sudah terdaftar di katalog.';
    }

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

    $nama_cover = null;
    if (empty($errors)) {
        try {
            $nama_cover = upload_cover($_FILES['cover'] ?? null);
            if (!$nama_cover && $cover_api_url !== '') {
                $nama_cover = download_remote_cover($cover_api_url);
                if (!$nama_cover) $errors[] = 'Cover otomatis tidak dapat disimpan. Silakan upload cover secara manual atau lanjut tanpa cover.';
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $kode_buku = generate_kode_buku($pdo);
            $stmt = $pdo->prepare("INSERT INTO buku (kode_buku, isbn, judul, penulis, penerbit, tahun_terbit, id_kategori, deskripsi, cover, stok, tersedia, lokasi_rak)
                                    VALUES (:kode, :isbn, :judul, :penulis, :penerbit, :tahun, :kategori, :deskripsi, :cover, :stok, :tersedia, :rak)");
            $stmt->execute([
                ':kode' => $kode_buku,
                ':isbn' => $input['isbn'] ?: null,
                ':judul' => $input['judul'],
                ':penulis' => $input['penulis'],
                ':penerbit' => $input['penerbit'] ?: null,
                ':tahun' => $input['tahun_terbit'] ?: null,
                ':kategori' => $input['id_kategori'] ?: null,
                ':deskripsi' => $input['deskripsi'] ?: null,
                ':cover' => $nama_cover,
                ':stok' => $input['stok'],
                ':tersedia' => $input['stok'],
                ':rak' => $input['lokasi_rak'] ?: null,
            ]);
            $new_id = (int)$pdo->lastInsertId();
            $pdo->commit();
            hapus_cache_buku_populer();
            catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'tambah', 'buku', $new_id, ['judul' => $input['judul'], 'kode' => $kode_buku, 'isbn' => $input['isbn']]);
            set_flash('sukses', "Buku \"{$input['judul']}\" ($kode_buku) berhasil ditambahkan.");
            redirect('/admin/buku/index.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            // Bersihkan cover yang sudah terupload jika transaksi gagal agar tidak jadi orphan
            if (!empty($nama_cover) && file_exists(UPLOAD_DIR . $nama_cover)) {
                @unlink(UPLOAD_DIR . $nama_cover);
                $thumb = UPLOAD_DIR . 'thumb_' . $nama_cover;
                if (file_exists($thumb)) @unlink($thumb);
            }
            // Tangani duplikat kode/ISBN akibat race condition
            if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 23000) {
                if (strpos($e->getMessage(), 'isbn') !== false || strpos($e->getMessage(), 'uniq_buku_isbn') !== false) {
                    $errors[] = 'ISBN tersebut sudah terdaftar (percobaan bersamaan). Silakan periksa kembali.';
                } else {
                    $errors[] = 'Kode buku bentrok (percobaan bersamaan). Silakan coba lagi.';
                }
            } else {
                $errors[] = 'Gagal menambahkan buku: ' . $e->getMessage();
            }
            error_log('Tambah buku gagal: ' . $e->getMessage());
        }
    }
}

$menu_aktif = 'buku';
$page_title = 'Tambah Buku';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
require __DIR__ . '/_form.php';
require_once __DIR__ . '/../../includes/footer.php';
