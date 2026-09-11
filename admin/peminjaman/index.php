<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$kata_kunci = clean($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? 'dipinjam';
if (!in_array($filter_status, ['semua', 'dipinjam', 'dikembalikan'])) $filter_status = 'dipinjam';
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 8;
$offset = ($halaman - 1) * $per_halaman;

$where = [];
$params = [];
if ($filter_status !== 'semua') {
    $where[] = 'p.status = :status';
    $params[':status'] = $filter_status;
}
if ($kata_kunci !== '') {
    $where[] = '(a.nama LIKE :kw OR b.judul LIKE :kw OR a.nomor_anggota LIKE :kw OR b.kode_buku LIKE :kw)';
    $params[':kw'] = '%' . $kata_kunci . '%';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = $pdo->prepare("SELECT COUNT(*) FROM peminjaman p
                         JOIN anggota a ON a.id_anggota = p.id_anggota
                         LEFT JOIN buku b ON b.id_buku = p.id_buku $where_sql");
$total->execute($params);
$total_data = (int) $total->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $per_halaman));
if ($halaman > $total_halaman) {
    $halaman = $total_halaman;
    $offset = ($halaman - 1) * $per_halaman;
}

$sql = "SELECT p.*, a.nama AS nama_anggota, a.nomor_anggota, b.judul, b.kode_buku, b.cover, b.penulis, k.nama_kategori
        FROM peminjaman p
        JOIN anggota a ON a.id_anggota = p.id_anggota
        LEFT JOIN buku b ON b.id_buku = p.id_buku
        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
        $where_sql
        ORDER BY p.id_peminjaman DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

$ringkasan_aktif = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'")->fetchColumn();
$ringkasan_telat = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam' AND tanggal_jatuh_tempo < CURDATE()")->fetchColumn();
$ringkasan_selesai = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dikembalikan'")->fetchColumn();

$menu_aktif = 'peminjaman';
$page_title = 'Transaksi Peminjaman';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
  <div>
    <h1 class="text-xl font-bold text-gray-800">Transaksi Peminjaman</h1>
    <p class="text-sm text-gray-500 mt-1">Kelola buku yang sedang dipinjam dan riwayat transaksi.</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/peminjaman/tambah.php" class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition text-center">+ Catat Peminjaman Baru</a>
</div>

<div class="grid grid-cols-3 gap-2 sm:gap-3 mb-4">
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-4">
    <p class="text-gray-400 text-[11px] sm:text-xs">Sedang Dipinjam</p>
    <p class="text-lg sm:text-xl font-bold text-brand-700 mt-1"><?= $ringkasan_aktif ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-4">
    <p class="text-gray-400 text-[11px] sm:text-xs">Terlambat</p>
    <p class="text-lg sm:text-xl font-bold text-red-600 mt-1"><?= $ringkasan_telat ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-4">
    <p class="text-gray-400 text-[11px] sm:text-xs">Selesai</p>
    <p class="text-lg sm:text-xl font-bold text-gray-700 mt-1"><?= $ringkasan_selesai ?></p>
  </div>
</div>

<form method="get" class="mb-5 bg-white border border-gray-100 rounded-2xl p-3 shadow-sm flex flex-col sm:flex-row gap-2">
  <input type="text" name="q" value="<?= e($kata_kunci) ?>" placeholder="Cari anggota, judul, kode buku..."
         class="flex-1 border border-gray-200 bg-gray-50 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
  <select name="status" class="border border-gray-200 bg-gray-50 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    <option value="dipinjam" <?= $filter_status==='dipinjam'?'selected':'' ?>>Sedang Dipinjam</option>
    <option value="dikembalikan" <?= $filter_status==='dikembalikan'?'selected':'' ?>>Sudah Dikembalikan</option>
    <option value="semua" <?= $filter_status==='semua'?'selected':'' ?>>Semua Status</option>
  </select>
  <button class="bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium">Terapkan</button>
</form>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
<?php if (empty($daftar)): ?>
  <div class="xl:col-span-2 bg-white border border-dashed border-gray-200 rounded-2xl p-10 text-center text-gray-400">
    Tidak ada data peminjaman untuk filter ini.
  </div>
<?php endif; ?>

<?php foreach ($daftar as $p):
    $telat = $p['status'] === 'dipinjam' ? hitung_keterlambatan($p['tanggal_jatuh_tempo']) : hitung_keterlambatan($p['tanggal_jatuh_tempo'], $p['tanggal_kembali']);
    $is_aktif = $p['status'] === 'dipinjam';
    $is_hilang = empty($p['judul']);
    $cover = cover_url($p['cover']);
?>
  <article class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition">
    <div class="p-3.5 sm:p-4">
      <div class="flex gap-3.5">
        <div class="w-[72px] sm:w-[82px] shrink-0">
          <div class="relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-100 border border-gray-100">
            <img src="<?= e($cover) ?>" alt="Cover <?= e($p['judul']) ?>" class="w-full h-full object-cover" loading="lazy">
            <span class="absolute top-1.5 left-1.5 bg-white/95 text-gray-700 text-[10px] font-bold px-1.5 py-0.5 rounded-md shadow-sm"><?= e($p['kode_buku']) ?></span>
          </div>
        </div>

        <div class="min-w-0 flex-1">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-600 truncate"><?= e($p['nama_kategori'] ?: 'Tanpa Kategori') ?></p>
              <h2 class="font-bold text-gray-800 text-base sm:text-lg leading-tight mt-0.5 line-clamp-2"><?= e($p['judul'] ?? 'Buku telah dihapus dari katalog') ?></h2>
              <p class="text-xs sm:text-sm text-gray-500 mt-1 truncate"><?= $is_hilang ? 'Tidak tersedia' : e($p['penulis']) ?></p>
            </div>
            <?php if ($is_hilang): ?>
              <span class="shrink-0 text-[10px] sm:text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full">Tidak tersedia</span>
            <?php elseif ($is_aktif && $telat > 0): ?>
              <span class="shrink-0 text-[10px] sm:text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full">Telat <?= $telat ?> hari</span>
            <?php elseif ($is_aktif): ?>
              <span class="shrink-0 text-[10px] sm:text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">Aktif</span>
            <?php else: ?>
              <span class="shrink-0 text-[10px] sm:text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded-full">Selesai</span>
            <?php endif; ?>
          </div>

          <?php if ($is_hilang): ?><div class="mt-3 rounded-lg border px-3 py-2.5 text-xs leading-5" style="background:#fef2f2; border-color:#fecaca; color:#991b1b"><i class="bi bi-exclamation-triangle mr-1"></i> Buku telah dihapus dari katalog — tidak tersedia. Riwayat tetap ditampilkan untuk audit.</div><?php endif; ?>
          <div class="mt-3 bg-gray-50 rounded-xl p-2.5 sm:p-3">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-bold shrink-0">👤</div>
              <div class="min-w-0">
                <p class="text-xs sm:text-sm font-semibold text-gray-700 truncate"><?= e($p['nama_anggota']) ?></p>
                <p class="text-[11px] text-gray-400"><?= e($p['nomor_anggota']) ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3">
        <div class="rounded-xl bg-gray-50 px-3 py-2">
          <p class="text-[10px] uppercase tracking-wide text-gray-400">Dipinjam</p>
          <p class="text-xs font-semibold text-gray-700 mt-0.5"><?= format_tanggal($p['tanggal_pinjam']) ?></p>
        </div>
        <div class="rounded-xl bg-gray-50 px-3 py-2">
          <p class="text-[10px] uppercase tracking-wide text-gray-400">Jatuh Tempo</p>
          <p class="text-xs font-semibold <?= $is_aktif && $telat > 0 ? 'text-red-600' : 'text-gray-700' ?> mt-0.5"><?= format_tanggal($p['tanggal_jatuh_tempo']) ?></p>
        </div>
        <div class="rounded-xl bg-gray-50 px-3 py-2">
          <p class="text-[10px] uppercase tracking-wide text-gray-400">Kembali</p>
          <p class="text-xs font-semibold text-gray-700 mt-0.5"><?= $p['tanggal_kembali'] ? format_tanggal($p['tanggal_kembali']) : 'Belum' ?></p>
        </div>
        <div class="rounded-xl <?= $p['denda'] > 0 ? 'bg-red-50' : 'bg-gray-50' ?> px-3 py-2">
          <p class="text-[10px] uppercase tracking-wide text-gray-400">Denda</p>
          <p class="text-xs font-semibold <?= $p['denda'] > 0 ? 'text-red-600' : 'text-gray-500' ?> mt-0.5"><?= format_rupiah($p['denda']) ?></p>
        </div>
      </div>

      <div class="flex items-center justify-between gap-3 mt-3 pt-3 border-t border-gray-100">
        <span class="text-[11px] text-gray-400">ID transaksi #<?= (int)$p['id_peminjaman'] ?></span>
        <?php if ($is_aktif): ?>
          <form method="post" action="<?= BASE_URL ?>/admin/peminjaman/batalkan.php"
                data-confirm-danger="true" data-confirm="Batalkan peminjaman &quot;<?= e($p['judul']) ?>&quot; oleh <?= e($p['nama_anggota']) ?>? Stok buku akan dikembalikan.">
      <?= csrf_field() ?>
            <input type="hidden" name="id_peminjaman" value="<?= (int)$p['id_peminjaman'] ?>">
            <button type="submit" class="text-red-600 hover:bg-red-50 px-3 py-2 rounded-lg text-xs font-semibold transition">Batalkan Peminjaman</button>
          </form>
        <?php else: ?>
          <span class="text-xs font-medium text-gray-400">Transaksi selesai</span>
        <?php endif; ?>
      </div>
    </div>
  </article>
<?php endforeach; ?>
</div>

<?php if ($total_halaman > 1): ?>
  <div class="flex justify-center items-center gap-1 mt-6">
    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
      <a href="?q=<?= urlencode($kata_kunci) ?>&status=<?= e($filter_status) ?>&halaman=<?= $i ?>"
         class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition <?= $i === $halaman ? 'bg-brand-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<p class="text-xs text-gray-400 mt-4">Untuk memproses pengembalian buku, buka menu <a href="<?= BASE_URL ?>/admin/pengembalian/index.php" class="text-brand-600 hover:underline">Pengembalian</a>.</p>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
