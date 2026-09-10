<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$filter = $_GET['status'] ?? 'menunggu';
if (!in_array($filter, ['menunggu','disetujui','ditolak','semua'], true)) $filter = 'menunggu';
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 15;
$offset = ($halaman - 1) * $per_halaman;

$where = '';
$params = [];
if ($filter !== 'semua') {
    $where = 'WHERE p.status = :status';
    $params[':status'] = $filter;
}

$total = 0;
$daftar = [];
$total_halaman = 1;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_buku p $where");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $total_halaman = max(1, (int)ceil($total / $per_halaman));
    if ($halaman > $total_halaman) {
        $halaman = $total_halaman;
        $offset = ($halaman - 1) * $per_halaman;
    }
    $sql = "SELECT p.*, a.nama AS nama_anggota, a.nomor_anggota, a.email FROM pengajuan_buku p JOIN anggota a ON a.id_anggota = p.anggota_id $where ORDER BY CASE WHEN p.status='menunggu' THEN 0 ELSE 1 END, p.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $daftar = $stmt->fetchAll();
} catch (Throwable $e) {
    $daftar = [];
    $total = 0;
    // Tabel belum ada — akan tampil empty dengan pesan migrasi
}

$menu_aktif = 'pengajuan';
$page_title = 'Pengajuan Buku';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-5">
  <div>
    <p class="text-xs font-bold uppercase tracking-[.16em] text-brand-600">Usulan anggota</p>
    <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-1">Pengajuan Buku</h1>
    <p class="text-sm text-slate-500 mt-1">Tinjau usulan buku baru dari anggota (approve hanya ubah status, tidak otomatis tambah ke katalog).</p>
  </div>
  <span class="inline-flex items-center gap-2 rounded-full bg-white border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm"><?= $total ?> pengajuan</span>
</div>

<div class="flex flex-wrap gap-2 mb-5">
  <?php foreach (['menunggu'=>'Menunggu','disetujui'=>'Disetujui','ditolak'=>'Ditolak','semua'=>'Semua'] as $key=>$label): ?>
    <a href="?status=<?= $key ?>" class="px-4 py-2 rounded-xl text-sm font-bold transition <?= $filter===$key ? 'bg-brand-600 text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($daftar)): ?>
  <div class="text-center py-16 bg-white rounded-2xl border border-slate-200 shadow-sm">
    <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-3"><i class="bi bi-inbox"></i></div>
    <h3 class="font-bold text-slate-800">Tidak ada pengajuan</h3>
    <p class="text-sm text-slate-500 mt-1">Belum ada usulan pada filter ini.</p>
    <?php try { $pdo->query("SELECT 1 FROM pengajuan_buku LIMIT 1"); } catch (Throwable $e) { echo '<p class="text-xs text-amber-600 mt-2">Tabel pengajuan_buku belum ada — jalankan migrasi.</p>'; } ?>
  </div>
<?php else: ?>
  <div class="space-y-3">
    <?php foreach ($daftar as $r): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <div class="flex flex-col lg:flex-row lg:items-start gap-4">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="font-bold text-slate-800"><?= e($r['judul']) ?></h2>
              <?php if ($r['status']==='menunggu'): ?><span class="text-xs font-bold rounded-full bg-amber-100 text-amber-700 px-2.5 py-1">Menunggu</span>
              <?php elseif ($r['status']==='disetujui'): ?><span class="text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1">Disetujui</span>
              <?php else: ?><span class="text-xs font-bold rounded-full bg-red-100 text-red-700 px-2.5 py-1">Ditolak</span><?php endif; ?>
            </div>
            <p class="text-sm text-slate-600 mt-1">Penulis: <?= e($r['penulis']) ?> <?= $r['isbn'] ? '· ISBN: '.e($r['isbn']) : '' ?></p>
            <p class="text-sm text-slate-600 mt-1">Oleh: <?= e($r['nama_anggota']) ?> (<?= e($r['nomor_anggota']) ?> · <?= e($r['email']) ?>)</p>
            <p class="text-sm text-slate-500 mt-2 bg-slate-50 rounded-xl px-3 py-2">Alasan: “<?= e($r['alasan']) ?>”</p>
            <?php if (!empty($r['catatan_admin'])): ?><p class="text-sm text-slate-600 mt-2">Catatan admin: <span class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-xs"><?= e($r['catatan_admin']) ?></span></p><?php endif; ?>
            <p class="text-xs text-slate-400 mt-2"><?= e($r['created_at']) ?> · update <?= e($r['updated_at']) ?></p>
          </div>
          <?php if ($r['status']==='menunggu'): ?>
            <div class="w-full lg:w-80 shrink-0">
              <form method="post" action="<?= BASE_URL ?>/admin/pengajuan/proses.php" class="space-y-2">
                  <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="aksi" value="disetujui">
                <textarea name="catatan_admin" rows="2" maxlength="500" placeholder="Catatan untuk anggota (opsional)..." class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm px-4 py-2.5 rounded-xl" data-confirm="Setujui pengajuan “<?= e($r['judul']) ?>”?">Setujui</button>
              </form>
              <form method="post" action="<?= BASE_URL ?>/admin/pengajuan/proses.php" class="mt-2">
                  <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="aksi" value="ditolak">
                <textarea name="catatan_admin" rows="2" maxlength="500" placeholder="Alasan penolakan (disarankan)..." class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500"></textarea>
                <button type="submit" class="w-full mt-2 bg-white border border-red-200 text-red-600 hover:bg-red-50 font-bold text-sm px-4 py-2.5 rounded-xl" data-confirm-danger="true" data-confirm="Tolak pengajuan “<?= e($r['judul']) ?>”?">Tolak</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($total_halaman > 1): ?>
    <div class="flex justify-center items-center gap-1 mt-6">
      <?php for ($i=1; $i<=$total_halaman; $i++): ?>
        <a href="?status=<?= e($filter) ?>&halaman=<?= $i ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition <?= $i===$halaman ? 'bg-brand-600 text-white' : 'bg-white border border-gray-200 text-slate-600 hover:bg-slate-50' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
