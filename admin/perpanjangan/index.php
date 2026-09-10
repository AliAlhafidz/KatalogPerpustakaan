<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$filter = $_GET['status'] ?? 'menunggu';
if (!in_array($filter, ['menunggu','disetujui','ditolak','semua'])) $filter = 'menunggu';

$where = '';
$params = [];
if ($filter !== 'semua') { $where = 'WHERE pp.status = :status'; $params[':status'] = $filter; }

$stmt = $pdo->prepare("SELECT pp.*, a.nama AS nama_anggota, a.nomor_anggota, b.judul, b.kode_buku
                       FROM perpanjangan_peminjaman pp
                       JOIN anggota a ON a.id_anggota=pp.id_anggota
                       JOIN peminjaman p ON p.id_peminjaman=pp.id_peminjaman
                       JOIN buku b ON b.id_buku=p.id_buku
                       $where ORDER BY CASE WHEN pp.status='menunggu' THEN 0 ELSE 1 END, pp.created_at DESC");
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$pending = (int)$pdo->query("SELECT COUNT(*) FROM perpanjangan_peminjaman WHERE status='menunggu'")->fetchColumn();
$menu_aktif = 'perpanjangan';
$page_title = 'Perpanjangan Peminjaman';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
  <div>
    <p class="text-sm font-semibold text-brand-600 mb-1">Persetujuan anggota</p>
    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Perpanjangan Peminjaman</h1>
    <p class="text-sm text-slate-500 mt-1">Tinjau permintaan anggota sebelum tanggal jatuh tempo diperpanjang.</p>
  </div>
  <div class="rounded-xl bg-amber-50 text-amber-700 px-4 py-2.5 text-sm font-bold"><i class="bi bi-hourglass-split mr-1"></i><?= $pending ?> menunggu</div>
</div>

<div class="flex flex-wrap gap-2 mb-5">
  <?php foreach (['menunggu'=>'Menunggu','disetujui'=>'Disetujui','ditolak'=>'Ditolak','semua'=>'Semua'] as $key=>$label): ?>
    <a href="?status=<?= $key ?>" class="px-4 py-2 rounded-xl text-sm font-semibold <?= $filter===$key ? 'bg-brand-600 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="space-y-3">
<?php if (empty($daftar)): ?>
  <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center text-slate-500 shadow-soft">Tidak ada permintaan pada filter ini.</div>
<?php else: foreach ($daftar as $r): ?>
  <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
      <div class="w-11 h-11 shrink-0 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center"><i class="bi bi-arrow-repeat text-xl"></i></div>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h2 class="font-bold text-slate-900"><?= e($r['judul']) ?></h2>
          <?php if ($r['status']==='menunggu'): ?><span class="text-xs font-bold rounded-full bg-amber-50 text-amber-700 px-2.5 py-1">Menunggu</span>
          <?php elseif ($r['status']==='disetujui'): ?><span class="text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 px-2.5 py-1">Disetujui</span>
          <?php else: ?><span class="text-xs font-bold rounded-full bg-red-50 text-red-700 px-2.5 py-1">Ditolak</span><?php endif; ?>
        </div>
        <p class="text-sm text-slate-600 mt-1"><?= e($r['nama_anggota']) ?> · <?= e($r['nomor_anggota']) ?> · <?= (int)$r['hari_diminta'] ?> hari</p>
        <p class="text-xs text-slate-500 mt-1">Jatuh tempo: <b><?= format_tanggal($r['tanggal_jatuh_tempo_lama']) ?></b> → <b><?= format_tanggal($r['tanggal_jatuh_tempo_baru'] ?: date('Y-m-d', strtotime($r['tanggal_jatuh_tempo_lama'].' +'.$r['hari_diminta'].' days'))) ?></b></p>
        <?php if ($r['catatan_anggota']): ?><p class="text-sm text-slate-500 mt-2 bg-slate-50 rounded-xl px-3 py-2">“<?= e($r['catatan_anggota']) ?>”</p><?php endif; ?>
        <?php if ($r['catatan_admin']): ?><p class="text-sm text-slate-500 mt-2">Catatan admin: <?= e($r['catatan_admin']) ?></p><?php endif; ?>
      </div>
      <?php if ($r['status']==='menunggu'): ?>
        <div class="w-full lg:w-80 shrink-0">
          <label for="catatan-<?= (int)$r['id_perpanjangan'] ?>" class="block text-xs font-bold text-slate-500 mb-1">Catatan untuk anggota (opsional, maks 500)</label>
          <textarea id="catatan-<?= (int)$r['id_perpanjangan'] ?>" rows="2" maxlength="500" placeholder="Tulis catatan untuk anggota..." class="catatan-admin-input w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 mb-2"></textarea>
          <div class="flex flex-col sm:flex-row gap-2">
            <form method="post" action="<?= BASE_URL ?>/admin/perpanjangan/proses.php" class="flex-1 perpanjangan-aksi-form">
      <?= csrf_field() ?>
              <input type="hidden" name="id_perpanjangan" value="<?= (int)$r['id_perpanjangan'] ?>">
              <input type="hidden" name="aksi" value="setujui">
              <input type="hidden" name="catatan_admin" class="catatan-hidden" value="">
              <button class="w-full px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm" type="submit" data-confirm="Setujui perpanjangan buku ini?"><i class="bi bi-check2 mr-1"></i>Setujui</button>
            </form>
            <form method="post" action="<?= BASE_URL ?>/admin/perpanjangan/proses.php" class="flex-1 perpanjangan-aksi-form">
      <?= csrf_field() ?>
              <input type="hidden" name="id_perpanjangan" value="<?= (int)$r['id_perpanjangan'] ?>">
              <input type="hidden" name="aksi" value="tolak">
              <input type="hidden" name="catatan_admin" class="catatan-hidden" value="">
              <button class="w-full px-4 py-2.5 rounded-xl bg-white border border-red-200 text-red-600 hover:bg-red-50 font-bold text-sm" type="submit" data-confirm-danger="true" data-confirm="Tolak permintaan perpanjangan ini?"><i class="bi bi-x-lg mr-1"></i>Tolak</button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; endif; ?>
</div>

<script>
// Sinkronkan textarea catatan admin ke hidden input sebelum submit (agar admin bisa isi catatan untuk setujui/tolak)
document.querySelectorAll('.catatan-admin-input').forEach(function(textarea){
  var id = textarea.id.replace('catatan-','');
  var hiddenInputs = document.querySelectorAll('input[name="id_perpanjangan"][value="'+id+'"]');
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
