<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$kata_kunci = clean($_GET['q'] ?? '');
$id_kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$filter_tersedia = $_GET['tersedia'] ?? 'semua';
if (!in_array($filter_tersedia, ['semua', 'tersedia', 'habis'], true)) $filter_tersedia = 'semua';
$filter_status = $_GET['status'] ?? 'semua';
if (!in_array($filter_status, ['semua','aktif','arsip'], true)) $filter_status = 'semua';
$sort = $_GET['sort'] ?? '';
if (!in_array($sort, ['', 'terbaru', 'judul_asc', 'judul_desc', 'stok_desc', 'stok_asc'], true)) $sort = '';
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 24;
$offset = ($halaman - 1) * $per_halaman;

// Ambil daftar kategori untuk dropdown
$kategori_list = [];
try {
    $kategori_list = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
} catch (Throwable $e) {
    $kategori_list = [];
}

$where = [];
$params = [];
if ($kata_kunci !== '') {
    $where[] = "(b.judul LIKE :kw1 OR b.penulis LIKE :kw2 OR b.kode_buku LIKE :kw3 OR b.isbn LIKE :kw4 OR b.penerbit LIKE :kw5 OR b.lokasi_rak LIKE :kw6)";
    $kw = '%' . $kata_kunci . '%';
    $params[':kw1'] = $kw;
    $params[':kw2'] = $kw;
    $params[':kw3'] = $kw;
    $params[':kw4'] = $kw;
    $params[':kw5'] = $kw;
    $params[':kw6'] = $kw;
}
if ($id_kategori > 0) {
    $where[] = "b.id_kategori = :kategori";
    $params[':kategori'] = $id_kategori;
}
if ($filter_tersedia === 'tersedia') {
    $where[] = "b.tersedia > 0";
} elseif ($filter_tersedia === 'habis') {
    $where[] = "b.tersedia <= 0";
}
if ($filter_status === 'aktif') {
    $where[] = "b.is_arsip = 0";
} elseif ($filter_status === 'arsip') {
    $where[] = "b.is_arsip = 1";
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sort_sql = "b.is_arsip ASC, b.id_buku DESC";
if ($sort === 'judul_asc') {
    $sort_sql = "b.is_arsip ASC, b.judul ASC";
} elseif ($sort === 'judul_desc') {
    $sort_sql = "b.is_arsip ASC, b.judul DESC";
} elseif ($sort === 'stok_desc') {
    $sort_sql = "b.is_arsip ASC, b.tersedia DESC, b.id_buku DESC";
} elseif ($sort === 'stok_asc') {
    $sort_sql = "b.is_arsip ASC, b.tersedia ASC, b.id_buku DESC";
}

$total = $pdo->prepare("SELECT COUNT(*) FROM buku b $where_sql");
$total->execute($params);
$total_data = (int) $total->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $per_halaman));
if ($halaman > $total_halaman) {
    $halaman = $total_halaman;
    $offset = ($halaman - 1) * $per_halaman;
}

// Hitung ringkasan status untuk tab
try {
    $jumlah_aktif = (int) $pdo->query("SELECT COUNT(*) FROM buku WHERE is_arsip = 0")->fetchColumn();
    $jumlah_arsip = (int) $pdo->query("SELECT COUNT(*) FROM buku WHERE is_arsip = 1")->fetchColumn();
    $jumlah_semua = $jumlah_aktif + $jumlah_arsip;
} catch (Throwable $e) {
    $jumlah_aktif = $jumlah_semua = $total_data;
    $jumlah_arsip = 0;
}

$sql = "SELECT b.id_buku, b.kode_buku, b.judul, b.penulis, b.cover, b.stok, b.tersedia, b.is_arsip, b.id_kategori, k.nama_kategori
        FROM buku b
        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
        $where_sql
        ORDER BY $sort_sql
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

// Parameter query untuk menjaga state filter
$base_params = [];
if ($kata_kunci !== '') $base_params['q'] = $kata_kunci;
if ($id_kategori > 0) $base_params['kategori'] = $id_kategori;
if ($filter_tersedia !== 'semua') $base_params['tersedia'] = $filter_tersedia;
if ($sort !== '') $base_params['sort'] = $sort;

$is_filter_aktif = ($kata_kunci !== '' || $id_kategori > 0 || $filter_tersedia !== 'semua' || $filter_status !== 'semua' || $sort !== '');

$menu_aktif = 'buku';
$page_title = 'Data Buku';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-5 border-b pb-4" style="border-color: var(--border)">
  <div>
    <p class="text-[11px] font-bold tracking-[.14em] uppercase" style="color: var(--text-faint-2)">Koleksi perpustakaan</p>
    <h1 class="font-display text-[22px] font-bold tracking-tight mt-1" style="color: var(--text)">Data Buku</h1>
    <p class="text-sm mt-1" style="color: var(--text-faint)">Kelola koleksi dengan tampilan katalog yang ringkas.</p>
  </div>
  <div class="flex flex-col sm:flex-row gap-2">
    <a href="<?= BASE_URL ?>/admin/buku/import.php"
       class="inline-flex items-center justify-center gap-1.5 border bg-white hover:bg-slate-50 text-sm font-semibold px-4 py-2 rounded-lg" style="border-color: var(--border); color: var(--text-muted)">
      <i class="bi bi-cloud-arrow-down text-xs"></i> Import
    </a>
    <a href="<?= BASE_URL ?>/admin/buku/tambah.php"
       class="mobile-full-btn bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg inline-flex items-center justify-center gap-1.5">
      <i class="bi bi-plus-lg text-xs"></i> Tambah Buku
    </a>
  </div>
</div>

<form method="get" class="mb-3 bg-white rounded-xl border p-3 sm:p-4 space-y-3" style="border-color: var(--border)">
  <input type="hidden" name="status" value="<?= e($filter_status) ?>">

  <!-- Baris 1: Pencarian & Kategori -->
  <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
    <div class="relative sm:col-span-6 md:col-span-7">
      <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-sm" style="color: var(--text-faint-2)"></i>
      <input type="text" name="q" value="<?= e($kata_kunci) ?>"
             placeholder="Cari judul, penulis, kode, ISBN, penerbit, rak..."
             class="w-full h-10 border rounded-lg pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color: var(--border); background: var(--surface)">
    </div>

    <div class="sm:col-span-3 md:col-span-3">
      <select name="kategori" onchange="this.form.submit()" class="w-full h-10 border rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 cursor-pointer" style="border-color: var(--border); background: var(--surface-2); color: var(--text)">
        <option value="0">Semua Kategori</option>
        <?php foreach ($kategori_list as $kat): ?>
          <option value="<?= (int)$kat['id_kategori'] ?>" <?= $id_kategori === (int)$kat['id_kategori'] ? 'selected' : '' ?>>
            <?= e($kat['nama_kategori']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="sm:col-span-3 md:col-span-2 flex gap-1.5">
      <button type="submit" class="flex-1 h-10 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm inline-flex items-center justify-center gap-1.5 transition">
        <i class="bi bi-search text-xs"></i> Cari
      </button>
      <?php if ($is_filter_aktif): ?>
        <a href="<?= BASE_URL ?>/admin/buku/index.php" title="Reset Semua Filter" class="h-10 px-3 rounded-lg border bg-white hover:bg-slate-50 font-semibold text-sm inline-flex items-center justify-center text-rose-600 hover:text-rose-700 transition" style="border-color: var(--border)">
          <i class="bi bi-x-lg text-xs"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Baris 2: Sub-filter Ketersediaan Stok & Pengurutan -->
  <div class="flex flex-wrap items-center justify-between gap-2.5 pt-2.5 border-t text-xs" style="border-color: var(--border-faint)">
    <div class="flex flex-wrap items-center gap-2">
      <span class="font-semibold" style="color: var(--text-faint-2)"><i class="bi bi-funnel me-1"></i>Filter Stok:</span>
      <select name="tersedia" onchange="this.form.submit()" class="h-8 border rounded-lg px-2.5 font-semibold focus:outline-none cursor-pointer" style="border-color: var(--border); background: var(--surface-2); color: var(--text-muted)">
        <option value="semua" <?= $filter_tersedia === 'semua' ? 'selected' : '' ?>>Semua Stok</option>
        <option value="tersedia" <?= $filter_tersedia === 'tersedia' ? 'selected' : '' ?>>Stok Tersedia (&gt; 0)</option>
        <option value="habis" <?= $filter_tersedia === 'habis' ? 'selected' : '' ?>>Stok Habis (0)</option>
      </select>
    </div>

    <div class="flex items-center gap-2">
      <span class="font-medium" style="color: var(--text-faint)">Urutkan:</span>
      <select name="sort" onchange="this.form.submit()" class="h-8 border rounded-lg px-2.5 font-semibold focus:outline-none cursor-pointer" style="border-color: var(--border); background: var(--surface-2); color: var(--text-muted)">
        <option value="" <?= $sort === '' ? 'selected' : '' ?>>Terbaru</option>
        <option value="judul_asc" <?= $sort === 'judul_asc' ? 'selected' : '' ?>>Judul (A–Z)</option>
        <option value="judul_desc" <?= $sort === 'judul_desc' ? 'selected' : '' ?>>Judul (Z–A)</option>
        <option value="stok_desc" <?= $sort === 'stok_desc' ? 'selected' : '' ?>>Stok Terbanyak</option>
        <option value="stok_asc" <?= $sort === 'stok_asc' ? 'selected' : '' ?>>Stok Paling Sedikit</option>
      </select>
    </div>
  </div>
</form>

<?php if ($is_filter_aktif): ?>
  <div class="flex flex-wrap items-center gap-1.5 mb-3 text-xs">
    <span class="font-medium" style="color: var(--text-faint)">Filter aktif:</span>
    <?php if ($kata_kunci !== ''): ?>
      <?php
        $p = $base_params;
        unset($p['q']);
        if ($filter_status !== 'semua') $p['status'] = $filter_status;
      ?>
      <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border bg-white" style="border-color: var(--border); color: var(--text)">
        <span>"<?= e($kata_kunci) ?>"</span>
        <a href="?<?= http_build_query($p) ?>" class="text-rose-500 hover:text-rose-700 ml-0.5"><i class="bi bi-x text-xs"></i></a>
      </span>
    <?php endif; ?>
    <?php if ($id_kategori > 0): ?>
      <?php
        $kat_nama = 'Kategori #' . $id_kategori;
        foreach ($kategori_list as $k) {
          if ((int)$k['id_kategori'] === $id_kategori) {
            $kat_nama = $k['nama_kategori'];
            break;
          }
        }
        $p = $base_params;
        unset($p['kategori']);
        if ($filter_status !== 'semua') $p['status'] = $filter_status;
      ?>
      <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border bg-white" style="border-color: var(--border); color: var(--text)">
        <i class="bi bi-tag text-[10px]" style="color: var(--accent)"></i>
        <span><?= e($kat_nama) ?></span>
        <a href="?<?= http_build_query($p) ?>" class="text-rose-500 hover:text-rose-700 ml-0.5"><i class="bi bi-x text-xs"></i></a>
      </span>
    <?php endif; ?>
    <?php if ($filter_tersedia !== 'semua'): ?>
      <?php
        $p = $base_params;
        unset($p['tersedia']);
        if ($filter_status !== 'semua') $p['status'] = $filter_status;
      ?>
      <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border bg-white" style="border-color: var(--border); color: var(--text)">
        <span><?= $filter_tersedia === 'tersedia' ? 'Stok Tersedia' : 'Stok Habis' ?></span>
        <a href="?<?= http_build_query($p) ?>" class="text-rose-500 hover:text-rose-700 ml-0.5"><i class="bi bi-x text-xs"></i></a>
      </span>
    <?php endif; ?>
    <?php if ($sort !== ''): ?>
      <?php
        $p = $base_params;
        unset($p['sort']);
        if ($filter_status !== 'semua') $p['status'] = $filter_status;
      ?>
      <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full border bg-white" style="border-color: var(--border); color: var(--text)">
        <i class="bi bi-arrow-down-up text-[10px]" style="color: var(--accent)"></i>
        <span><?= $sort === 'judul_asc' ? 'A-Z' : ($sort === 'judul_desc' ? 'Z-A' : ($sort === 'stok_desc' ? 'Stok Banyak' : 'Stok Sedikit')) ?></span>
        <a href="?<?= http_build_query($p) ?>" class="text-rose-500 hover:text-rose-700 ml-0.5"><i class="bi bi-x text-xs"></i></a>
      </span>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/admin/buku/index.php" class="text-xs text-rose-600 hover:underline font-semibold ml-1">Reset Semua</a>
  </div>
<?php endif; ?>

<div class="flex flex-wrap gap-1.5 mb-4">
  <?php
    $tabs = [
      'semua' => ['label' => 'Semua', 'count' => $jumlah_semua, 'icon' => 'bi-collection'],
      'aktif' => ['label' => 'Aktif', 'count' => $jumlah_aktif, 'icon' => 'bi-check-circle'],
      'arsip' => ['label' => 'Arsip', 'count' => $jumlah_arsip, 'icon' => 'bi-archive'],
    ];
    foreach ($tabs as $key => $tab):
      $active = $filter_status === $key;
      $tab_params = $base_params;
      if ($key !== 'semua') $tab_params['status'] = $key;
      $tab_url = '?' . http_build_query($tab_params);
  ?>
    <a href="<?= $tab_url ?>"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold transition <?= $active ? 'bg-brand-600 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' ?>" style="<?= $active ? '' : 'border-color: var(--border)' ?>">
      <i class="bi <?= $tab['icon'] ?> text-xs"></i> <?= $tab['label'] ?> <span class="px-1.5 py-0.5 rounded-full text-xs <?= $active ? 'bg-white/20' : '' ?>" style="<?= $active ? '' : 'background: var(--surface-2)' ?>"><?= $tab['count'] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="flex items-center justify-between gap-3 mb-3">
  <p class="text-xs" style="color: var(--text-faint)"><?= $total_data ?> buku ditemukan</p>
  <span class="text-xs" style="color: var(--text-faint-2)">Tampilan katalog</span>
</div>

<?php if (empty($daftar)): ?>
  <div class="text-center py-14 border rounded-lg bg-white" style="border-color: var(--border)">
    <div class="w-10 h-10 mx-auto rounded-lg flex items-center justify-center text-lg mb-3" style="background: var(--surface-2); color: var(--text-faint)"><i class="bi bi-search"></i></div>
    <h3 class="font-display font-semibold text-sm" style="color: var(--text)">Buku tidak ditemukan</h3>
    <p class="text-sm mt-1 px-6" style="color: var(--text-faint)">Coba kata kunci lain.</p>
  </div>
<?php else: ?>
  <div class="grid grid-cols-2 min-[480px]:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-3">
    <?php foreach ($daftar as $b): ?>
      <?php
        $tersedia = (int)$b['tersedia'];
        $stok = (int)$b['stok'];
        $habis = $tersedia <= 0;
        $is_arsip = !empty($b['is_arsip']);
      ?>
      <article class="bg-white rounded-lg border overflow-hidden flex flex-col <?= $is_arsip ? 'opacity-75' : '' ?>" style="border-color: <?= $is_arsip ? '#fde68a' : 'var(--border)' ?>; <?= $is_arsip ? 'background:#fffbeb' : '' ?>">
        <div class="aspect-[3/4] overflow-hidden relative <?= $is_arsip ? 'grayscale-[0.2]' : '' ?>" style="background: var(--paper-2)">
          <img src="<?= e(cover_thumb_url($b['cover'])) ?>"
               alt="Cover <?= e($b['judul']) ?>"
               loading="lazy" decoding="async" width="400" height="600" onerror="this.src='<?= BASE_URL ?>/assets/img/no-cover.svg'"
               class="w-full h-full object-cover">
          <div class="absolute top-1 left-1">
            <span class="inline-flex items-center rounded-full bg-white/95 border px-1.5 py-0.5 text-[9px] font-bold tracking-wide" style="border-color: var(--border); color: var(--text-muted)">
              <?= e($b['kode_buku']) ?>
            </span>
          </div>
          <div class="absolute top-1 right-1 flex flex-col gap-1 items-end">
            <?php if ($is_arsip): ?>
              <span class="inline-flex items-center gap-0.5 rounded-full border px-1.5 py-0.5 text-[9px] font-semibold" style="background:#fef3c7; border-color:#fde68a; color:#92400e"><i class="bi bi-archive text-[9px]"></i> Arsip</span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[9px] font-semibold" style="background: <?= $habis ? '#fef2f2' : '#ecfdf5' ?>; border-color: <?= $habis ? '#fecaca' : '#a7f3d0' ?>; color: <?= $habis ? '#991b1b' : '#065f46' ?>">
                <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $habis ? '#dc2626' : '#059669' ?>"></span>
                <?= $habis ? 'Habis' : 'Sisa ' . $tersedia ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="p-2 sm:p-2.5 flex flex-col flex-1 border-t" style="border-color: var(--border-faint)">
          <p class="text-[9px] font-bold uppercase tracking-wide truncate mb-0.5" style="color: var(--text-faint)">
            <?= e($b['nama_kategori'] ?? 'Tanpa Kategori') ?><?= $is_arsip ? ' · Arsip' : '' ?>
          </p>
          <h2 class="font-semibold text-[11px] sm:text-[12px] leading-snug line-clamp-2 min-h-[2rem] sm:min-h-0 mb-0.5" style="color: var(--text)">
            <?= e($b['judul']) ?>
          </h2>
          <p class="text-[10px] sm:text-[11px] truncate mb-2" style="color: var(--text-faint)">
            <?= e($b['penulis']) ?>
          </p>

          <div class="grid grid-cols-2 gap-1 mb-2 mt-auto text-center">
            <div class="rounded border py-1 px-1" style="background: var(--surface-2); border-color: var(--border)">
              <p class="text-[9px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Stok</p>
              <p class="text-xs font-bold leading-tight" style="color: var(--text)"><?= $stok ?></p>
            </div>
            <div class="rounded border py-1 px-1" style="background: var(--surface-2); border-color: var(--border)">
              <p class="text-[9px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Tersedia</p>
              <p class="text-xs font-bold leading-tight" style="color: <?= $habis ? '#991b1b' : '#065f46' ?>"><?= $tersedia ?></p>
            </div>
          </div>

          <div class="space-y-1 pt-1.5 border-t" style="border-color: var(--border-faint)">
            <div class="flex items-center gap-1">
              <a href="<?= BASE_URL ?>/admin/buku/edit.php?id=<?= (int)$b['id_buku'] ?>"
                 class="flex-1 h-7 rounded border font-semibold text-[11px] inline-flex items-center justify-center gap-1" style="background: var(--accent-soft); border-color: var(--accent-soft-2); color: var(--accent-text)">
                Ubah
              </a>
              <form method="post" action="<?= BASE_URL ?>/admin/buku/arsip.php" class="flex-1">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$b['id_buku'] ?>">
                <input type="hidden" name="aksi" value="<?= $is_arsip ? 'publikasikan' : 'arsipkan' ?>">
                <button type="submit" data-confirm="<?= $is_arsip ? 'Publikasikan kembali buku &quot;'.e($b['judul']).'&quot;? Buku akan muncul lagi di katalog.' : 'Arsipkan buku &quot;'.e($b['judul']).'&quot;? Buku tidak akan bisa dipinjam dan hilang dari katalog sampai dipublikasikan lagi.' ?>" class="w-full h-7 rounded border font-semibold text-[11px] inline-flex items-center justify-center gap-0.5" style="background: <?= $is_arsip ? '#ecfdf5' : '#fffbeb' ?>; border-color: <?= $is_arsip ? '#a7f3d0' : '#fde68a' ?>; color: <?= $is_arsip ? '#065f46' : '#92400e' ?>">
                  <?= $is_arsip ? 'Publikasi' : 'Arsip' ?>
                </button>
              </form>
            </div>
            <form method="post" action="<?= BASE_URL ?>/admin/buku/hapus.php" data-confirm-danger="true" data-confirm="Yakin ingin menghapus buku &quot;<?= e($b['judul']) ?>&quot;? Riwayat peminjaman buku ini akan tetap tersimpan untuk anggota, tapi buku tidak bisa dikembalikan/diproses lagi lewat sistem. Tindakan ini tidak dapat dibatalkan." class="w-full">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$b['id_buku'] ?>">
              <button type="submit" class="w-full h-6 rounded border font-semibold text-[11px] inline-flex items-center justify-center gap-1" style="background:#fef2f2; border-color:#fecaca; color:#991b1b">
                Hapus
              </button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>


  <?php if ($total_halaman > 1): ?>
    <div class="flex justify-center items-center gap-1 mt-6 overflow-x-auto py-1">
      <?php
        $pages = [];
        if ($total_halaman <= 7) {
          for ($i=1;$i<=$total_halaman;$i++) $pages[]=$i;
        } else {
          $pages[] = 1;
          if ($halaman > 4) $pages[] = '...';
          $start = max(2, $halaman - 2);
          $end = min($total_halaman-1, $halaman + 2);
          for ($i=$start;$i<=$end;$i++) $pages[]=$i;
          if ($halaman < $total_halaman - 3) $pages[] = '...';
          $pages[] = $total_halaman;
        }
        foreach ($pages as $p):
          if ($p === '...'): ?>
            <span class="shrink-0 w-9 h-9 flex items-center justify-center text-xs" style="color: var(--text-faint)">…</span>
          <?php else:
            $pg_params = $base_params;
            if ($filter_status !== 'semua') $pg_params['status'] = $filter_status;
            $pg_params['halaman'] = $p;
          ?>
            <a href="?<?= http_build_query($pg_params) ?>"
               class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-sm font-semibold transition <?= $p === $halaman ? 'bg-brand-600 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' ?>" style="<?= $p === $halaman ? '' : 'border-color: var(--border)' ?>">
              <?= $p ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
