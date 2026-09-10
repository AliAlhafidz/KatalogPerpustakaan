<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/book_metadata.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$hasil = null;
$errors_global = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = trim($_POST['isbn_list'] ?? '');
    if ($raw === '') {
        $errors_global[] = 'Masukkan minimal satu ISBN.';
    } else {
        // Pecah per baris, bersihkan, filter kosong, unique
        $lines = preg_split('/[\r\n]+/', $raw);
        $isbn_list = [];
        foreach ($lines as $line) {
            $isbn = preg_replace('/[^0-9Xx]/', '', trim($line));
            if ($isbn !== '') $isbn_list[] = $isbn;
        }
        $isbn_list = array_values(array_unique($isbn_list));

        if (count($isbn_list) === 0) {
            $errors_global[] = 'Tidak ada ISBN valid yang ditemukan.';
        } elseif (count($isbn_list) > 20) {
            $errors_global[] = 'Maksimal 20 ISBN per batch. Kamu memasukkan ' . count($isbn_list) . ' ISBN. Silakan bagi menjadi beberapa batch.';
        } else {
            $berhasil = [];
            $dilewati = [];
            $gagal = [];

            foreach ($isbn_list as $isbn_raw) {
                $isbn = preg_replace('/[^0-9Xx]/', '', $isbn_raw);
                // a. Validasi format
                if (!in_array(strlen($isbn), [10, 13], true)) {
                    $gagal[] = ['isbn' => $isbn_raw, 'pesan' => 'Format ISBN harus 10 atau 13 digit'];
                    continue;
                }

                // b. Cek duplicate di DB
                try {
                    $cek = $pdo->prepare('SELECT COUNT(*) FROM buku WHERE isbn = :isbn');
                    $cek->execute([':isbn' => $isbn]);
                    if ((int)$cek->fetchColumn() > 0) {
                        $dilewati[] = ['isbn' => $isbn, 'pesan' => 'Sudah ada di katalog'];
                        continue;
                    }
                } catch (Throwable $e) {
                    $gagal[] = ['isbn' => $isbn, 'pesan' => 'Gagal cek duplicate: ' . $e->getMessage()];
                    continue;
                }

                // c. Cari metadata via orchestrator multi-provider (cache 24 jam, merge, kategori)
                $payload = null;
                try {
                    $payload = search_book_by_isbn($pdo, $isbn, 86400);
                    if (!is_array($payload) || ($payload['success'] ?? false) !== true || empty($payload['data']['judul'])) {
                        $msg = $payload['message'] ?? 'Tidak ditemukan di semua provider';
                        $gagal[] = ['isbn' => $isbn, 'pesan' => $msg];
                        continue;
                    }

                    // d. INSERT dengan transaksi FOR UPDATE per-ISBN (reuse generate_kode_buku)
                    $d = $payload['data'];
                    $judul_ins = clean($d['judul'] ?? '');
                    $penulis_ins = clean($d['penulis'] ?? '');
                    if ($judul_ins === '') $judul_ins = 'Judul tidak tersedia';
                    if ($penulis_ins === '') $penulis_ins = 'Tidak diketahui';
                    $penerbit_ins = clean($d['penerbit'] ?? '');
                    $tahun_ins = $d['tahun_terbit'] ?? '';
                    $deskripsi_ins = clean($d['deskripsi'] ?? '');
                    $cover_url_ins = $d['cover_url'] ?? null;
                    // Kategori dari orchestrator sudah berupa ['id','nama'] atau null
                    $kategori_ins = null;
                    if (isset($d['kategori']['id'])) $kategori_ins = (int)$d['kategori']['id'];
                    elseif (isset($d['kategori_id'])) $kategori_ins = (int)$d['kategori_id'];

                    // Validasi tahun seperti tambah.php (1000..Y+1)
                    if ($tahun_ins !== '' && (!ctype_digit((string)$tahun_ins) || (int)$tahun_ins < 1000 || (int)$tahun_ins > (int)date('Y')+1)) $tahun_ins = '';

                    // Download cover jika ada (sudah support Google + Open Library)
                    $nama_cover = null;
                    if ($cover_url_ins) {
                        try {
                            $nama_cover = download_remote_cover($cover_url_ins);
                        } catch (Throwable $e) {
                            // Cover gagal tidak menggagalkan insert
                            error_log('Import cover gagal ISBN '.$isbn.': '.$e->getMessage());
                        }
                    }

                    $pdo->beginTransaction();
                    try {
                        $kode_buku = generate_kode_buku($pdo);
                        $stmt = $pdo->prepare("INSERT INTO buku (kode_buku, isbn, judul, penulis, penerbit, tahun_terbit, id_kategori, deskripsi, cover, stok, tersedia, lokasi_rak) VALUES (:kode, :isbn, :judul, :penulis, :penerbit, :tahun, :kategori, :deskripsi, :cover, :stok, :tersedia, :rak)");
                        $stmt->execute([
                            ':kode' => $kode_buku,
                            ':isbn' => $isbn,
                            ':judul' => $judul_ins,
                            ':penulis' => $penulis_ins,
                            ':penerbit' => $penerbit_ins ?: null,
                            ':tahun' => $tahun_ins ?: null,
                            ':kategori' => $kategori_ins ?: null,
                            ':deskripsi' => $deskripsi_ins ?: null,
                            ':cover' => $nama_cover,
                            ':stok' => 1,
                            ':tersedia' => 1,
                            ':rak' => null,
                        ]);
                        $new_id = (int)$pdo->lastInsertId();
                        $pdo->commit();
                        // Audit log — reuse pola tambah.php, side-effect tidak gagalkan import
                        try {
                            catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'tambah', 'buku', $new_id, ['judul' => $judul_ins, 'kode' => $kode_buku, 'isbn' => $isbn, 'via' => 'import_batch', 'providers' => $d['providers'] ?? $d['sumber_detail']['providers'] ?? []]);
                        } catch (Throwable $e) {
                            error_log('audit import buku gagal: ' . $e->getMessage());
                        }
                        $berhasil[] = ['isbn' => $isbn, 'judul' => $judul_ins, 'kode' => $kode_buku];
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        if (!empty($nama_cover) && file_exists(UPLOAD_DIR . $nama_cover)) {
                            @unlink(UPLOAD_DIR . $nama_cover);
                            $thumb = UPLOAD_DIR . 'thumb_' . $nama_cover;
                            if (file_exists($thumb)) @unlink($thumb);
                        }
                        if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 23000) {
                            if (strpos($e->getMessage(), 'isbn') !== false || strpos($e->getMessage(), 'uniq_buku_isbn') !== false) {
                                $dilewati[] = ['isbn' => $isbn, 'pesan' => 'Sudah ada (race)'];
                            } else {
                                $gagal[] = ['isbn' => $isbn, 'pesan' => 'Kode bentrok, coba lagi'];
                            }
                        } else {
                            $gagal[] = ['isbn' => $isbn, 'pesan' => 'Gagal insert: ' . $e->getMessage()];
                        }
                    }

                } catch (Throwable $e) {
                    $gagal[] = ['isbn' => $isbn, 'pesan' => 'Error: ' . $e->getMessage()];
                    error_log('Import ISBN '.$isbn.' error: '.$e->getMessage());
                    continue;
                }
            }

            $hasil = ['berhasil' => $berhasil, 'dilewati' => $dilewati, 'gagal' => $gagal];
        }
    }
}

$menu_aktif = 'buku';
$page_title = 'Import Buku Batch';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="w-full max-w-3xl">
  <div class="flex items-center gap-3 mb-6">
    <a href="<?= BASE_URL ?>/admin/buku/index.php" class="text-gray-400 hover:text-gray-600">&larr;</a>
    <h1 class="text-xl font-bold text-gray-800">Import Buku Batch via ISBN</h1>
  </div>

  <?php if (!empty($errors_global)): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
      <ul class="list-disc list-inside"><?php foreach ($errors_global as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <?php if ($hasil): ?>
    <div class="mb-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
      <h2 class="font-bold text-slate-800 mb-3">Ringkasan Import</h2>
      <div class="grid grid-cols-3 gap-3 mb-4 text-center">
        <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3"><p class="text-xs text-emerald-600">Berhasil</p><p class="text-xl font-black text-emerald-700"><?= count($hasil['berhasil']) ?></p></div>
        <div class="rounded-xl bg-amber-50 border border-amber-100 p-3"><p class="text-xs text-amber-600">Dilewati</p><p class="text-xl font-black text-amber-700"><?= count($hasil['dilewati']) ?></p></div>
        <div class="rounded-xl bg-red-50 border border-red-100 p-3"><p class="text-xs text-red-600">Gagal</p><p class="text-xl font-black text-red-700"><?= count($hasil['gagal']) ?></p></div>
      </div>
      <?php if (!empty($hasil['berhasil'])): ?>
        <div class="mb-3">
          <h3 class="text-sm font-bold text-emerald-700 mb-1">Berhasil (<?= count($hasil['berhasil']) ?>):</h3>
          <ul class="text-sm text-slate-600 space-y-1">
            <?php foreach ($hasil['berhasil'] as $b): ?><li><span class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded"><?= e($b['isbn']) ?></span> — <?= e($b['judul']) ?> <span class="text-xs text-slate-400">(<?= e($b['kode']) ?>)</span></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if (!empty($hasil['dilewati'])): ?>
        <div class="mb-3">
          <h3 class="text-sm font-bold text-amber-700 mb-1">Dilewati (duplicate):</h3>
          <ul class="text-sm text-slate-600 space-y-1">
            <?php foreach ($hasil['dilewati'] as $d): ?><li><span class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded"><?= e($d['isbn']) ?></span> — <?= e($d['pesan']) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <?php if (!empty($hasil['gagal'])): ?>
        <div class="mb-3">
          <h3 class="text-sm font-bold text-red-700 mb-1">Gagal:</h3>
          <ul class="text-sm text-slate-600 space-y-1">
            <?php foreach ($hasil['gagal'] as $g): ?><li><span class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded"><?= e($g['isbn']) ?></span> — <?= e($g['pesan']) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/admin/buku/index.php" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600 hover:underline mt-2">Lihat Data Buku →</a>
    </div>
  <?php endif; ?>

  <form method="post" action="" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-6 space-y-4">
      <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Daftar ISBN (satu per baris, maksimal 20)</label>
      <textarea name="isbn_list" rows="8" required placeholder="9786020332581
9786022910200
9780134685991"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 font-mono text-sm"></textarea>
      <p class="text-xs text-gray-400 mt-2">Maksimal 20 ISBN per submit untuk menghindari timeout & rate limit OpenLibrary. ISBN 10 atau 13 digit (boleh dengan strip). Satu gagal tidak menghentikan yang lain.</p>
    </div>
    <div class="flex gap-3">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium px-6 py-2.5 rounded-lg transition">Proses Import</button>
      <a href="<?= BASE_URL ?>/admin/buku/index.php" class="border border-gray-300 hover:bg-gray-50 px-6 py-2.5 rounded-lg transition text-gray-600">Batal</a>
    </div>
  </form>

  <div class="mt-6 bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-700">
    <p class="font-bold mb-1">Catatan:</p>
    <ul class="list-disc list-inside space-y-1 text-xs">
      <li>Reuse cache ISBN 24 jam (`includes/book_metadata.php` + `isbn_cache_get/set`) — ISBN yang sama tidak hit API lagi, hasil sudah ternormalisasi &amp; merge.</li>
      <li>Multi-provider: Google Books → Open Library → ISBNdb (jika key tersedia), merge field terbaik, ISBN divalidasi.</li>
      <li>Deskripsi prioritas Google Books → Open Library Work description; <code>first_sentence</code> tidak pernah dipakai.</li>
      <li>Kategori otomatis via scoring; cover dari Google atau Open Library (https) akan di-download otomatis.</li>
    </ul>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
