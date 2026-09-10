<?php
// Variabel yang diharapkan tersedia: $input (array data form), $errors (array),
// $kategori_list, dan opsional $buku (untuk mode edit, berisi data lama termasuk cover).
$mode = isset($buku) ? 'edit' : 'tambah';
?>

<div class="w-full max-w-4xl">
  <div class="flex items-center gap-3 mb-6">
    <a href="<?= BASE_URL ?>/admin/buku/index.php" class="text-gray-400 hover:text-gray-600">&larr;</a>
    <h1 class="text-xl font-bold text-gray-800"><?= $mode === 'edit' ? 'Ubah Buku' : 'Tambah Buku Baru' ?></h1>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($mode === 'tambah'): ?>
  <div class="mb-4 flex gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2">
    <button type="button" id="tab-isbn" class="flex-1 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-brand-700 shadow-sm ring-1 ring-slate-200">
      <i class="bi bi-upc-scan mr-1"></i> Via ISBN
    </button>
    <button type="button" id="tab-manual" class="flex-1 rounded-xl px-4 py-3 text-sm font-semibold text-slate-500 hover:bg-white">
      <i class="bi bi-pencil-square mr-1"></i> Manual
    </button>
  </div>
  <?php endif; ?>

  <form method="post" action="" enctype="multipart/form-data" class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4 sm:p-6 space-y-4">
      <?= csrf_field() ?>
    <?php if ($mode === 'edit'): ?>
      <input type="hidden" name="id" value="<?= (int) $buku['id_buku'] ?>">
    <?php endif; ?>
    <?php if ($mode === 'tambah'): ?>
      <input type="hidden" name="cover_api_url" id="cover_api_url" value="">
      <div id="isbn-panel" class="rounded-2xl border border-brand-100 bg-brand-50 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="flex-1">
            <label class="block text-sm font-semibold text-slate-700 mb-1">ISBN</label>
            <input type="text" id="isbn-search" inputmode="numeric" autocomplete="off" placeholder="Masukkan ISBN-10 atau ISBN-13"
              class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500">
            <p class="text-xs text-slate-500 mt-1">Contoh: 9786020333176. Data akan diambil dari Google Books + Open Library (otomatis fallback &amp; merge).</p>
          </div>
          <div class="sm:self-end">
            <button type="button" id="btn-cari-isbn" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white font-semibold px-5 py-3 rounded-xl">
              <i class="bi bi-search mr-1"></i> Cari Buku
            </button>
          </div>
        </div>
        <div id="isbn-status" class="hidden mt-3 text-sm rounded-xl px-3 py-2"></div>
      </div>
    <?php endif; ?>

    <div id="detail-form" class="<?= $mode === 'tambah' ? 'hidden' : '' ?>">
      <div class="mb-4 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-sm text-slate-600 <?= $mode === 'edit' ? 'hidden' : '' ?>" id="manual-hint">
        <i class="bi bi-info-circle mr-1"></i> Isi data buku secara manual. Semua data tetap bisa diedit setelah pencarian ISBN.
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Judul Buku *</label>
          <input type="text" name="judul" required value="<?= e($input['judul']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Penulis *</label>
          <input type="text" name="penulis" required value="<?= e($input['penulis']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Penerbit</label>
          <input type="text" name="penerbit" value="<?= e($input['penerbit']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
          <input type="text" name="isbn" id="isbn" value="<?= e($input['isbn']) ?>" <?= $mode === 'tambah' ? 'readonly' : '' ?> class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Terbit</label>
          <input type="number" name="tahun_terbit" value="<?= e($input['tahun_terbit']) ?>" min="1000" max="<?= (int)date('Y')+1 ?>" placeholder="cth: 2020" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
          <p class="text-xs text-gray-400 mt-1">1000–<?= (int)date('Y')+1 ?></p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
          <select name="id_kategori" id="id_kategori" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($kategori_list as $kat): ?>
              <option value="<?= (int)$kat['id_kategori'] ?>" <?= (int)$input['id_kategori'] === (int)$kat['id_kategori'] ? 'selected' : '' ?>><?= e($kat['nama_kategori']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi / Rak</label>
          <input type="text" name="lokasi_rak" value="<?= e($input['lokasi_rak']) ?>" placeholder="cth: Rak A1" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Stok Total *</label>
          <input type="number" name="stok" min="0" required value="<?= e($input['stok']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
          <?php if ($mode === 'edit'): ?><p class="text-xs text-gray-400 mt-1">Tersedia saat ini: <?= (int)$buku['tersedia'] ?>.</p><?php endif; ?>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi / Sinopsis</label>
          <textarea name="deskripsi" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500"><?= e($input['deskripsi']) ?></textarea>
        </div>
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Cover Buku</label>
          <div id="cover-preview-wrap" class="<?= $mode === 'edit' && $buku['cover'] ? '' : 'hidden' ?> mb-3">
            <?php if ($mode === 'edit' && $buku['cover']): ?><img src="<?= e(cover_url($buku['cover'])) ?>" class="w-24 h-36 object-cover rounded-xl shadow-sm border border-slate-200"><?php endif; ?>
          </div>
          <input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm">
          <p class="text-xs text-gray-400 mt-1">Cover dari ISBN akan otomatis disimpan. Upload manual boleh digunakan untuk menggantinya.</p>
        </div>
      </div>

      <div class="flex gap-3 pt-4">
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium px-6 py-2.5 rounded-lg transition"><?= $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Buku' ?></button>
        <a href="<?= BASE_URL ?>/admin/buku/index.php" class="border border-gray-300 hover:bg-gray-50 px-6 py-2.5 rounded-lg transition text-gray-600">Batal</a>
      </div>
    </div>
  </form>
</div>

<?php if ($mode === 'tambah'): ?>
<script>document.body.dataset.baseUrl = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>/assets/js/buku-isbn.js" defer></script>
<?php endif; ?>
