<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

$id_anggota = (int)$_SESSION['id_anggota'];
sinkronkan_notifikasi_anggota($pdo, $id_anggota);

$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = :id");
$stmt->execute([':id' => $id_anggota]);
$anggota = $stmt->fetch();

$stmt = $pdo->prepare("SELECT p.*, COALESCE(b.judul,'(buku dihapus)') AS judul, b.cover, b.kode_buku, b.lokasi_rak,
                       (SELECT pp.status FROM perpanjangan_peminjaman pp WHERE pp.id_peminjaman=p.id_peminjaman AND pp.status='menunggu' ORDER BY pp.id_perpanjangan DESC LIMIT 1) AS status_perpanjangan
                       FROM peminjaman p LEFT JOIN buku b ON b.id_buku=p.id_buku
                       WHERE p.id_anggota=:id AND p.status='dipinjam' ORDER BY p.tanggal_jatuh_tempo ASC");
$stmt->execute([':id'=>$id_anggota]);
$sedang_dipinjam = $stmt->fetchAll();

$total_estimasi_denda = 0;
foreach ($sedang_dipinjam as $p) $total_estimasi_denda += hitung_denda(hitung_keterlambatan($p['tanggal_jatuh_tempo']));
$stmt = $pdo->prepare("SELECT COALESCE(SUM(denda),0) FROM peminjaman WHERE id_anggota=:id AND status='dikembalikan' AND denda>0");
$stmt->execute([':id'=>$id_anggota]);
$total_denda_riwayat = (int)$stmt->fetchColumn();
$notifikasi = ambil_notifikasi_anggota($pdo, $id_anggota, 4);
$belum_dibaca = jumlah_notifikasi_belum_dibaca($pdo, $id_anggota);

// N9 Statistik Pribadi — reuse pola anggota/riwayat.php, scope ke anggota login saja
$stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota=:id");
$stmt->execute([':id'=>$id_anggota]);
$total_pernah_pinjam = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(denda),0) FROM peminjaman WHERE id_anggota=:id");
$stmt->execute([':id'=>$id_anggota]);
$total_denda_all = (int)$stmt->fetchColumn();
// Kategori favorit terbanyak: coba dari favorit dulu, fallback ke riwayat peminjaman
$kategori_favorit = null;
try {
    $stmt = $pdo->prepare("SELECT k.nama_kategori, COUNT(*) AS jml FROM favorit f JOIN buku b ON b.id_buku=f.id_buku LEFT JOIN kategori k ON k.id_kategori=b.id_kategori WHERE f.id_anggota=:id AND k.nama_kategori IS NOT NULL GROUP BY k.id_kategori, k.nama_kategori ORDER BY jml DESC LIMIT 1");
    $stmt->execute([':id'=>$id_anggota]);
    $kategori_favorit = $stmt->fetch();
    if (!$kategori_favorit) {
        $stmt = $pdo->prepare("SELECT k.nama_kategori, COUNT(*) AS jml FROM peminjaman p LEFT JOIN buku b ON b.id_buku=p.id_buku LEFT JOIN kategori k ON k.id_kategori=b.id_kategori WHERE p.id_anggota=:id AND k.nama_kategori IS NOT NULL GROUP BY k.id_kategori, k.nama_kategori ORDER BY jml DESC LIMIT 1");
        $stmt->execute([':id'=>$id_anggota]);
        $kategori_favorit = $stmt->fetch();
    }
} catch (Throwable $e) {
    $kategori_favorit = null;
}

$page_title = 'Akun Saya';
$member_menu_aktif = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
$notif_style = [
  'info'=>['bg'=>'bg-blue-50','text'=>'text-blue-600','icon'=>'bi-info-circle'],
  'success'=>['bg'=>'bg-emerald-50','text'=>'text-emerald-600','icon'=>'bi-check-circle'],
  'warning'=>['bg'=>'bg-amber-50','text'=>'text-amber-600','icon'=>'bi-exclamation-triangle'],
  'danger'=>['bg'=>'bg-red-50','text'=>'text-red-600','icon'=>'bi-exclamation-circle'],
];
?>

<div class="rounded-xl border p-5 sm:p-6 mb-5" style="background: var(--surface); border-color: var(--border)">
  <div>
    <p class="text-[11px] font-bold tracking-widest uppercase" style="color: var(--text-faint-2)">Selamat datang</p>
    <h1 class="font-display text-[22px] font-bold tracking-tight mt-1" style="color: var(--text)"><?= e($anggota['nama']) ?></h1>
    <p class="text-sm mt-1.5" style="color: var(--text-muted)">Nomor anggota <?= e($anggota['nomor_anggota']) ?> · Kelola buku dan aktivitasmu.</p>
  </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
  <div class="rounded-lg border p-4" style="background: var(--surface); border-color: var(--border)"><p class="text-xs font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Sedang Dipinjam</p><p class="text-2xl font-bold mt-1" style="color: var(--accent)"><?= count($sedang_dipinjam) ?></p></div>
  <div class="rounded-lg border p-4" style="background: var(--surface); border-color: var(--border)"><p class="text-xs font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Estimasi Denda</p><p class="text-xl font-bold mt-1 <?= $total_estimasi_denda>0?'text-red-700':'text-emerald-700' ?>"><?= format_rupiah($total_estimasi_denda) ?></p></div>
  <div class="rounded-lg border p-4" style="background: var(--surface); border-color: var(--border)"><p class="text-xs font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Notifikasi Baru</p><p class="text-2xl font-bold mt-1" style="color: var(--text)"><?= $belum_dibaca ?></p></div>
</div>

<div class="rounded-lg border p-4 mb-5" style="background: var(--surface); border-color: var(--border)">
  <div class="flex items-center gap-2 mb-3">
    <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm" style="background: var(--accent-soft); color: var(--accent-text)"><i class="bi bi-bar-chart-line"></i></span>
    <div><h2 class="font-semibold text-sm" style="color: var(--text)">Statistik Saya</h2><p class="text-xs" style="color: var(--text-faint)">Ringkasan all-time</p></div>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div class="rounded-lg border p-3" style="background: var(--surface-2); border-color: var(--border)"><p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Total Pernah Dipinjam</p><p class="text-xl font-bold mt-1" style="color: var(--text)"><?= $total_pernah_pinjam ?></p><p class="text-xs mt-1" style="color: var(--text-faint-2)">riwayat peminjaman</p></div>
    <div class="rounded-lg border p-3" style="background: var(--surface-2); border-color: var(--border)"><p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Total Denda</p><p class="text-base font-bold mt-1 <?= $total_denda_all>0?'text-red-700':'text-emerald-700' ?>"><?= format_rupiah($total_denda_all) ?></p><p class="text-xs mt-1" style="color: var(--text-faint-2)">akumulasi transaksi</p></div>
    <div class="rounded-lg border p-3" style="background: var(--surface-2); border-color: var(--border)"><p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Kategori Favorit</p>
      <?php if ($kategori_favorit): ?>
        <p class="text-lg font-bold mt-1 truncate" style="color: var(--text)"><?= e($kategori_favorit['nama_kategori']) ?></p><p class="text-xs mt-1" style="color: var(--text-faint)"><?= (int)$kategori_favorit['jml'] ?> buku</p>
      <?php else: ?>
        <p class="text-sm font-semibold mt-1" style="color: var(--text-faint)">Belum ada</p><p class="text-xs mt-1" style="color: var(--text-faint)">Favorit/belum pinjam kategori</p>
      <?php endif; ?>
    </div>
  </div>
</div>

  <div class="grid grid-cols-1 lg:grid-cols-[1.35fr_.65fr] gap-5">
  <section>
    <div class="flex items-baseline justify-between gap-3 mb-3 border-b pb-2" style="border-color: var(--border)">
      <div><h2 class="font-display font-bold text-[16px]" style="color: var(--text)">Notifikasi terbaru</h2><p class="text-xs" style="color: var(--text-faint)">Pengingat penting</p></div>
      <a href="<?= BASE_URL ?>/anggota/notifikasi.php" class="text-xs font-semibold hover:underline" style="color: var(--accent)">Lihat semua →</a>
    </div>
    <?php if (empty($notifikasi)): ?>
      <div class="rounded-lg border p-5 text-sm" style="background: var(--surface); border-color: var(--border); color: var(--text-faint)">Belum ada notifikasi.</div>
    <?php else: ?>
      <div class="space-y-2.5">
        <?php foreach ($notifikasi as $n): $v=$notif_style[$n['tipe']] ?? $notif_style['info']; ?>
          <a href="<?= BASE_URL ?>/anggota/notifikasi.php" class="block border rounded-lg p-3.5 transition" style="background: var(--surface); border-color: <?= $n['dibaca'] ? 'var(--border)' : 'var(--accent-soft-2)' ?>">
            <div class="flex gap-3"><div class="w-8 h-8 shrink-0 rounded-lg <?= $v['bg'] ?> <?= $v['text'] ?> flex items-center justify-center text-sm"><i class="bi <?= $v['icon'] ?>"></i></div><div class="min-w-0"><div class="flex items-center gap-2"><h3 class="font-semibold text-sm" style="color: var(--text)"><?= e($n['judul']) ?></h3><?php if(!$n['dibaca']): ?><span class="w-2 h-2 rounded-full bg-brand-600"></span><?php endif; ?></div><p class="text-sm mt-1 line-clamp-2" style="color: var(--text-faint)"><?= e($n['pesan']) ?></p></div></div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section>
    <div class="flex items-baseline justify-between mb-3 border-b pb-2" style="border-color: var(--border)"><div><h2 class="font-display font-bold text-[16px]" style="color: var(--text)">Buku yang dipinjam</h2><p class="text-xs" style="color: var(--text-faint)">Prioritas jatuh tempo</p></div></div>
    <?php if (empty($sedang_dipinjam)): ?>
      <div class="rounded-lg border p-5 text-sm" style="background: var(--surface); border-color: var(--border); color: var(--text-faint)">Kamu belum meminjam buku.</div>
    <?php else: ?>
      <div class="rounded-lg border divide-y overflow-hidden" style="background: var(--surface); border-color: var(--border)">
        <?php foreach (array_slice($sedang_dipinjam,0,4) as $p): $telat=hitung_keterlambatan($p['tanggal_jatuh_tempo']); ?>
          <div class="p-3.5 flex gap-3"><img src="<?= e(cover_url($p['cover'])) ?>" class="w-10 h-14 object-cover rounded-md border" style="border-color: var(--border)" alt=""><div class="min-w-0"><p class="font-semibold text-sm line-clamp-2" style="color: var(--text)"><?= e($p['judul']) ?></p><p class="text-xs mt-1" style="color: var(--text-faint)">Jatuh tempo <?= format_tanggal($p['tanggal_jatuh_tempo']) ?></p><?php if($p['status_perpanjangan']==='menunggu'): ?><span class="inline-block mt-2 text-[11px] font-semibold px-2 py-1 rounded-full border" style="background:var(--badge-amber-bg); border-color:var(--badge-amber-border); color:var(--badge-amber-text)">Perpanjangan ditinjau</span><?php elseif($telat>0): ?><span class="inline-block mt-2 text-[11px] font-semibold px-2 py-1 rounded-full border" style="background:var(--badge-red-bg); border-color:var(--badge-red-border); color:var(--badge-red-text)">Telat <?= $telat ?> hari</span><?php endif; ?></div></div>
        <?php endforeach; ?>
      </div>
      <a href="<?= BASE_URL ?>/anggota/peminjaman.php" class="block text-center mt-3 text-sm font-semibold hover:underline" style="color: var(--accent)">Kelola peminjaman →</a>
    <?php endif; ?>
  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
