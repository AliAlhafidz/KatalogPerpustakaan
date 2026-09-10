<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$filter = $_GET['status'] ?? 'menunggu';
if (!in_array($filter, ['menunggu','disetujui','ditolak','dibatalkan','semua'], true)) $filter = 'menunggu';

$kata_kunci = clean($_GET['q'] ?? '');
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 10;
$offset = ($halaman - 1) * $per_halaman;

$where = [];
$params = [];
if ($filter !== 'semua') {
    $where[] = 'pp.status = :status';
    $params[':status'] = $filter;
}
if ($kata_kunci !== '') {
    $where[] = '(a.nama LIKE :kw OR a.nomor_anggota LIKE :kw OR b.judul LIKE :kw OR b.kode_buku LIKE :kw)';
    $params[':kw'] = '%' . $kata_kunci . '%';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = 0;
$daftar = [];
$total_halaman = 1;
$ringkasan = ['menunggu'=>0,'disetujui'=>0,'ditolak'=>0,'dibatalkan'=>0];
$error_tabel = null;

try {
    // Ringkasan
    $stmt = $pdo->query("SELECT status, COUNT(*) AS jml FROM pengajuan_peminjaman GROUP BY status");
    foreach ($stmt->fetchAll() as $r) $ringkasan[$r['status']] = (int)$r['jml'];

    // Total filtered
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_peminjaman pp JOIN anggota a ON a.id_anggota=pp.id_anggota JOIN buku b ON b.id_buku=pp.id_buku $where_sql");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $total_halaman = max(1, (int)ceil($total / $per_halaman));
    if ($halaman > $total_halaman) {
        $halaman = $total_halaman;
        $offset = ($halaman - 1) * $per_halaman;
    }

    $sql = "SELECT pp.*, a.nama AS nama_anggota, a.nomor_anggota, a.email, b.judul, b.penulis, b.kode_buku, b.cover, b.tersedia, b.stok, b.is_arsip, k.nama_kategori,
                   adm.nama AS nama_admin
            FROM pengajuan_peminjaman pp
            JOIN anggota a ON a.id_anggota = pp.id_anggota
            JOIN buku b ON b.id_buku = pp.id_buku
            LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
            LEFT JOIN admin adm ON adm.id_admin = pp.diproses_oleh
            $where_sql
            ORDER BY CASE WHEN pp.status='menunggu' THEN 0 ELSE 1 END, pp.created_at DESC, pp.id_pengajuan DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $daftar = $stmt->fetchAll();
} catch (Throwable $e) {
    if (strpos($e->getMessage(), 'pengajuan_peminjaman') !== false) {
        $error_tabel = 'Tabel pengajuan_peminjaman belum ada. Jalankan migrasi database/migrasi_v7_pengajuan_peminjaman.sql';
    } else {
        $error_tabel = 'Gagal memuat data: ' . $e->getMessage();
        error_log('Admin pengajuan_peminjaman index error: ' . $e->getMessage());
    }
    $daftar = [];
    $total = 0;
}

$menu_aktif = 'pengajuan_peminjaman';
$page_title = 'Pengajuan Peminjaman';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
  <div>
    <p class="text-sm font-semibold text-brand-600 mb-1">Persetujuan anggota</p>
    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Pengajuan Peminjaman</h1>
    <p class="text-sm text-slate-500 mt-1">Tinjau pengajuan anggota · Prioritas <b>menunggu</b> di atas.</p>
  </div>
  <div class="flex items-center gap-2">
    <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-2.5 text-sm font-bold"><i class="bi bi-hourglass-split mr-1"></i><?= (int)$ringkasan['menunggu'] ?> menunggu</div>
    <div class="hidden sm:flex rounded-xl bg-slate-50 border border-slate-200 text-slate-600 px-3 py-2.5 text-xs font-semibold"><?= $total ?> total</div>
  </div>
</div>

<?php if ($error_tabel): ?>
  <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-800 px-4 py-3 text-sm">
    <i class="bi bi-exclamation-triangle mr-1"></i> <?= e($error_tabel) ?>
  </div>
<?php endif; ?>

<!-- Ringkasan grid -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
  <div class="bg-white rounded-xl border border-slate-200 p-3">
    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Menunggu</p>
    <p class="text-lg font-extrabold text-amber-700 mt-1"><?= (int)$ringkasan['menunggu'] ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-3">
    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Disetujui</p>
    <p class="text-lg font-extrabold text-emerald-700 mt-1"><?= (int)$ringkasan['disetujui'] ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-3">
    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Ditolak</p>
    <p class="text-lg font-extrabold text-red-600 mt-1"><?= (int)$ringkasan['ditolak'] ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-3">
    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Dibatalkan</p>
    <p class="text-lg font-extrabold text-slate-500 mt-1"><?= (int)$ringkasan['dibatalkan'] ?></p>
  </div>
</div>

<!-- Filter + Search -->
<form method="get" class="mb-5 bg-white border border-slate-200 rounded-2xl p-3 shadow-sm flex flex-col sm:flex-row gap-2">
  <input type="text" name="q" value="<?= e($kata_kunci) ?>" placeholder="Cari anggota, judul, kode buku..." class="flex-1 border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
  <select name="status" class="border border-slate-200 bg-slate-50 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
    <option value="menunggu" <?= $filter==='menunggu'?'selected':'' ?>>Menunggu</option>
    <option value="disetujui" <?= $filter==='disetujui'?'selected':'' ?>>Disetujui</option>
    <option value="ditolak" <?= $filter==='ditolak'?'selected':'' ?>>Ditolak</option>
    <option value="dibatalkan" <?= $filter==='dibatalkan'?'selected':'' ?>>Dibatalkan</option>
    <option value="semua" <?= $filter==='semua'?'selected':'' ?>>Semua</option>
  </select>
  <button class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium">Terapkan</button>
</form>

<div class="space-y-4">
<?php if (empty($daftar)): ?>
  <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center text-slate-500 shadow-sm">
    Tidak ada pengajuan pada filter ini.
  </div>
<?php else: foreach ($daftar as $r): 
  $cover = cover_url($r['cover'] ?? null);
  $is_waiting = $r['status'] === 'menunggu';
?>
  <article class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="p-4 sm:p-5">
      <div class="flex flex-col lg:flex-row gap-4">
        <!-- Left: book + member -->
        <div class="flex gap-4 flex-1 min-w-0">
          <div class="w-[84px] sm:w-[96px] shrink-0">
            <div class="aspect-[3/4] rounded-xl overflow-hidden border border-slate-100 bg-slate-50">
              <img src="<?= e($cover) ?>" alt="Cover <?= e($r['judul']) ?>" class="w-full h-full object-cover" loading="lazy">
            </div>
            <div class="mt-2 text-center">
              <span class="inline-block text-[10px] font-bold px-2 py-1 rounded-full border bg-white" style="border-color: var(--border); color: var(--text-muted)"><?= e($r['kode_buku']) ?></span>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wide text-brand-600 truncate"><?= e($r['nama_kategori'] ?? 'Tanpa Kategori') ?></p>
                <h2 class="font-bold text-slate-900 text-base sm:text-lg leading-tight mt-0.5 line-clamp-2"><?= e($r['judul']) ?></h2>
                <p class="text-sm text-slate-500 mt-1 truncate"><?= e($r['penulis']) ?></p>
                <?php if (!empty($r['is_arsip'])): ?>
                  <span class="inline-flex mt-2 text-xs font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1"><i class="bi bi-archive mr-1"></i>Diarsipkan</span>
                <?php endif; ?>
                <p class="text-xs text-slate-400 mt-1">Stok tersedia: <b><?= (int)$r['tersedia'] ?>/<?= (int)$r['stok'] ?></b> <?= (int)$r['tersedia']>0 ? '' : '· <span class="text-red-600 font-bold">Habis</span>' ?></p>
              </div>
              <div class="shrink-0">
                <?= badge_pengajuan_peminjaman($r['status']) ?>
              </div>
            </div>

            <div class="mt-3 bg-slate-50 rounded-xl p-3 border border-slate-100">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-brand-50 border border-brand-100 text-brand-700 flex items-center justify-center shrink-0"><i class="bi bi-person text-sm"></i></div>
                <div class="min-w-0">
                  <p class="text-sm font-bold text-slate-800 truncate"><?= e($r['nama_anggota']) ?></p>
                  <p class="text-xs text-slate-500 truncate"><?= e($r['nomor_anggota']) ?> · <?= e($r['email']) ?></p>
                </div>
              </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
              <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                <p class="text-[10px] uppercase font-bold tracking-wide text-slate-400">Diajukan</p>
                <p class="font-semibold text-slate-700 mt-0.5"><?= format_tanggal(date('Y-m-d', strtotime($r['created_at']))) ?> <span class="text-slate-400 font-normal"><?= date('H:i', strtotime($r['created_at'])) ?></span></p>
              </div>
              <div class="rounded-xl bg-white border border-slate-200 px-3 py-2">
                <p class="text-[10px] uppercase font-bold tracking-wide text-slate-400">Diproses</p>
                <p class="font-semibold text-slate-700 mt-0.5"><?= $r['diproses_at'] ? format_tanggal(date('Y-m-d', strtotime($r['diproses_at']))) : '—' ?><?= $r['nama_admin'] ? ' · ' . e($r['nama_admin']) : '' ?></p>
              </div>
            </div>

            <?php if (!empty($r['catatan_anggota'])): ?>
              <div class="mt-3 rounded-xl bg-white border border-slate-200 px-3 py-2.5">
                <p class="text-[11px] font-bold text-slate-500">Catatan anggota:</p>
                <p class="text-sm text-slate-700 mt-1 leading-5">“<?= e($r['catatan_anggota']) ?>”</p>
              </div>
            <?php endif; ?>
            <?php if (!empty($r['catatan_admin'])): ?>
              <div class="mt-2 rounded-xl px-3 py-2.5 border text-sm" style="background:#f8fafc; border-color: var(--border); color: var(--text-muted)">
                <p class="text-[11px] font-bold">Catatan admin:</p>
                <p class="mt-1 leading-5"><?= e($r['catatan_admin']) ?></p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right: actions if menunggu -->
        <?php if ($is_waiting): ?>
          <div class="w-full lg:w-[340px] shrink-0 lg:border-l lg:border-slate-100 lg:pl-5 flex flex-col gap-3">
            <div class="rounded-xl border p-3" style="background:#fffbeb; border-color:#fde68a">
              <p class="text-xs font-bold" style="color:#92400e"><i class="bi bi-hourglass-split mr-1"></i>Perlu diproses</p>
              <p class="text-xs mt-1 leading-5" style="color:#92400e">Setujui akan membuat transaksi peminjaman (<?= LAMA_PINJAM_HARI ?> hari) & mengurangi stok. Tolak tidak mengubah stok.</p>
            </div>
            <div>
              <label for="catatan-<?= (int)$r['id_pengajuan'] ?>" class="block text-xs font-bold text-slate-600 mb-1">Catatan untuk anggota (opsional, maks 500)</label>
              <textarea id="catatan-<?= (int)$r['id_pengajuan'] ?>" rows="2" maxlength="500" placeholder="Tulis catatan untuk anggota..." class="catatan-admin-input w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
              <form method="post" action="<?= BASE_URL ?>/admin/pengajuan_peminjaman/proses.php" class="flex-1 pengajuan-aksi-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id_pengajuan" value="<?= (int)$r['id_pengajuan'] ?>">
                <input type="hidden" name="aksi" value="setujui">
                <input type="hidden" name="catatan_admin" class="catatan-hidden" value="">
                <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm" data-confirm="Setujui pengajuan “<?= e($r['judul']) ?>” oleh <?= e($r['nama_anggota']) ?>? Stok akan dikurangi & transaksi peminjaman dibuat."><i class="bi bi-check2 mr-1"></i>Setujui</button>
              </form>
              <form method="post" action="<?= BASE_URL ?>/admin/pengajuan_peminjaman/proses.php" class="flex-1 pengajuan-aksi-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id_pengajuan" value="<?= (int)$r['id_pengajuan'] ?>">
                <input type="hidden" name="aksi" value="tolak">
                <input type="hidden" name="catatan_admin" class="catatan-hidden" value="">
                <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-white border border-red-200 text-red-600 hover:bg-red-50 font-bold text-sm" data-confirm-danger="true" data-confirm="Tolak pengajuan “<?= e($r['judul']) ?>”?"><i class="bi bi-x-lg mr-1"></i>Tolak</button>
              </form>
            </div>
            <p class="text-[11px] text-slate-400">Pastikan stok tersedia & buku tidak diarsip sebelum menyetujui.</p>
          </div>
        <?php else: ?>
          <div class="w-full lg:w-[260px] shrink-0 lg:border-l lg:border-slate-100 lg:pl-5 flex flex-col justify-center">
            <div class="rounded-xl border bg-slate-50 border-slate-200 p-3 text-xs text-slate-600">
              <p class="font-bold mb-1">Detail proses</p>
              <p>ID pengajuan #<?= (int)$r['id_pengajuan'] ?></p>
              <?php if ($r['diproses_at']): ?><p>Diproses: <?= e($r['diproses_at']) ?></p><?php endif; ?>
              <?php if ($r['nama_admin']): ?><p>Oleh: <?= e($r['nama_admin']) ?></p><?php endif; ?>
              <p class="mt-2">Status: <b><?= e($r['status']) ?></b></p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </article>
<?php endforeach; endif; ?>
</div>

<?php if ($total_halaman > 1): ?>
  <div class="flex justify-center items-center gap-1 mt-6">
    <?php for ($i=1; $i<=$total_halaman; $i++): ?>
      <a href="?q=<?= urlencode($kata_kunci) ?>&status=<?= e($filter) ?>&halaman=<?= $i ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition <?= $i===$halaman ? 'bg-brand-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<script>
// Sinkronkan textarea catatan_admin ke hidden input sebelum submit (seperti perpanjangan)
document.querySelectorAll('.catatan-admin-input').forEach(function(textarea){
  var id = textarea.id.replace('catatan-','');
  var hiddenInputs = document.querySelectorAll('input[name="id_pengajuan"][value="'+id+'"]');
  hiddenInputs.forEach(function(hiddenId){
    var form = hiddenId.closest('form');
    if (!form) return;
    var hiddenCatatan = form.querySelector('input.catatan-hidden');
    if (!hiddenCatatan) return;
    form.addEventListener('submit', function(){
      hiddenCatatan.value = textarea.value;
    });
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
