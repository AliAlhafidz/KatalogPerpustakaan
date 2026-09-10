<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$kata_kunci = clean($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? 'semua';
if (!in_array($filter_status, ['semua','aktif','arsip'], true)) $filter_status = 'semua';
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 12;
$offset = ($halaman - 1) * $per_halaman;

$where = [];
$params = [];
if ($kata_kunci !== '') {
    $where[] = "(b.judul LIKE :kw OR b.penulis LIKE :kw OR b.kode_buku LIKE :kw)";
    $params[':kw'] = '%' . $kata_kunci . '%';
}
if ($filter_status === 'aktif') {
    $where[] = "b.is_arsip = 0";
} elseif ($filter_status === 'arsip') {
    $where[] = "b.is_arsip = 1";
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

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
        ORDER BY b.is_arsip ASC, b.id_buku DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

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

<form method="get" class="mb-4 bg-white rounded-lg border p-2.5" style="border-color: var(--border)">
  <div class="flex flex-col sm:flex-row gap-2">
    <div class="relative flex-1">
      <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-sm" style="color: var(--text-faint-2)"></i>
      <input type="text" name="q" value="<?= e($kata_kunci) ?>"
             placeholder="Cari kode, judul, atau penulis..."
             class="w-full h-10 border rounded-lg pl-9 pr-3 text-sm focus:outline-none" style="border-color: var(--border); background: var(--surface)">
    </div>
    <input type="hidden" name="status" value="<?= e($filter_status) ?>">
    <button type="submit" class="h-10 px-5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm inline-flex items-center justify-center gap-1.5">
      Cari
    </button>
    <?php if ($kata_kunci !== '' || $filter_status !== 'semua'): ?>
      <a href="<?= BASE_URL ?>/admin/buku/index.php" class="h-10 px-4 rounded-lg border bg-white hover:bg-slate-50 font-semibold text-sm inline-flex items-center justify-center gap-1.5" style="border-color: var(--border); color: var(--text-muted)">
        Reset
      </a>
    <?php endif; ?>
  </div>
</form>

<div class="flex flex-wrap gap-1.5 mb-4">
  <?php
    $base_q = $kata_kunci !== '' ? '&q=' . urlencode($kata_kunci) : '';
    $tabs = [
      'semua' => ['label' => 'Semua', 'count' => $jumlah_semua, 'icon' => 'bi-collection'],
      'aktif' => ['label' => 'Aktif', 'count' => $jumlah_aktif, 'icon' => 'bi-check-circle'],
      'arsip' => ['label' => 'Arsip', 'count' => $jumlah_arsip, 'icon' => 'bi-archive'],
    ];
    foreach ($tabs as $key => $tab):
      $active = $filter_status === $key;
  ?>
    <a href="?status=<?= $key ?><?= $base_q ?>"
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
  <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-4">
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
          <div class="absolute top-2 left-2">
            <span class="inline-flex items-center rounded-full bg-white border px-2 py-1 text-[10px] font-bold tracking-wide" style="border-color: var(--border); color: var(--text-muted)">
              <?= e($b['kode_buku']) ?>
            </span>
          </div>
          <div class="absolute top-2 right-2 flex flex-col gap-1 items-end">
            <?php if ($is_arsip): ?>
              <span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-[10px] font-semibold" style="background:#fef3c7; border-color:#fde68a; color:#92400e"><i class="bi bi-archive text-[10px]"></i> Arsip</span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-[10px] font-semibold" style="background: <?= $habis ? '#fef2f2' : '#ecfdf5' ?>; border-color: <?= $habis ? '#fecaca' : '#a7f3d0' ?>; color: <?= $habis ? '#991b1b' : '#065f46' ?>">
                <span class="w-1.5 h-1.5 rounded-full" style="background: <?= $habis ? '#dc2626' : '#059669' ?>"></span>
                <?= $habis ? 'Habis' : 'Tersedia ' . $tersedia ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="p-3 flex flex-col flex-1 border-t" style="border-color: var(--border-faint)">
          <p class="text-[10px] font-bold uppercase tracking-wide truncate mb-1" style="color: var(--text-faint)">
            <?= e($b['nama_kategori'] ?? 'Tanpa Kategori') ?><?= $is_arsip ? ' · Arsip' : '' ?>
          </p>
          <h2 class="font-semibold text-[13px] leading-4 line-clamp-2 mb-1" style="color: var(--text)">
            <?= e($b['judul']) ?>
          </h2>
          <p class="text-xs truncate mb-3" style="color: var(--text-faint)">
            <?= e($b['penulis']) ?>
          </p>

          <div class="grid grid-cols-2 gap-1.5 mb-3 mt-auto">
            <div class="rounded-md border px-2.5 py-2" style="background: var(--surface-2); border-color: var(--border)">
              <p class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Stok</p>
              <p class="text-sm font-bold mt-0.5" style="color: var(--text)"><?= $stok ?></p>
            </div>
            <div class="rounded-md border px-2.5 py-2" style="background: var(--surface-2); border-color: var(--border)">
              <p class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Tersedia</p>
              <p class="text-sm font-bold mt-0.5" style="color: <?= $habis ? '#991b1b' : '#065f46' ?>"><?= $tersedia ?></p>
            </div>
          </div>

          <div class="space-y-1.5 pt-2.5 border-t" style="border-color: var(--border-faint)">
            <div class="flex items-center gap-1.5">
              <a href="<?= BASE_URL ?>/admin/buku/edit.php?id=<?= (int)$b['id_buku'] ?>"
                 class="flex-1 h-8 rounded-md border font-semibold text-xs inline-flex items-center justify-center gap-1" style="background: var(--accent-soft); border-color: var(--accent-soft-2); color: var(--accent-text)">
                Ubah
              </a>
              <form method="post" action="<?= BASE_URL ?>/admin/buku/arsip.php" class="flex-1">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$b['id_buku'] ?>">
                <input type="hidden" name="aksi" value="<?= $is_arsip ? 'publikasikan' : 'arsipkan' ?>">
                <button type="submit" data-confirm="<?= $is_arsip ? 'Publikasikan kembali buku &quot;'.e($b['judul']).'&quot;? Buku akan muncul lagi di katalog.' : 'Arsipkan buku &quot;'.e($b['judul']).'&quot;? Buku tidak akan bisa dipinjam dan hilang dari katalog sampai dipublikasikan lagi.' ?>" class="w-full h-8 rounded-md border font-semibold text-xs inline-flex items-center justify-center gap-1" style="background: <?= $is_arsip ? '#ecfdf5' : '#fffbeb' ?>; border-color: <?= $is_arsip ? '#a7f3d0' : '#fde68a' ?>; color: <?= $is_arsip ? '#065f46' : '#92400e' ?>">
                  <?= $is_arsip ? 'Publikasi' : 'Arsipkan' ?>
                </button>
              </form>
            </div>
            <form method="post" action="<?= BASE_URL ?>/admin/buku/hapus.php" data-confirm-danger="true" data-confirm="Yakin ingin menghapus buku &quot;<?= e($b['judul']) ?>&quot;? Buku yang sudah memiliki riwayat peminjaman tidak dapat dihapus — gunakan Arsipkan sebagai gantinya." class="w-full">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$b['id_buku'] ?>">
              <button type="submit" class="w-full h-7 rounded-md border font-semibold text-xs inline-flex items-center justify-center gap-1" style="background:#fef2f2; border-color:#fecaca; color:#991b1b">
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
          <?php else: ?>
            <a href="?status=<?= e($filter_status) ?>&q=<?= urlencode($kata_kunci) ?>&halaman=<?= $p ?>"
               class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-sm font-semibold transition <?= $p === $halaman ? 'bg-brand-600 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' ?>" style="<?= $p === $halaman ? '' : 'border-color: var(--border)' ?>">
              <?= $p ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
