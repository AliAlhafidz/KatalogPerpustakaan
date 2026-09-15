<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

$id_anggota = (int)$_SESSION['id_anggota'];
$stmt = $pdo->prepare("SELECT p.*, b.judul, b.cover, b.kode_buku, b.lokasi_rak, b.penulis, b.is_arsip,
                       (SELECT pp.status FROM perpanjangan_peminjaman pp WHERE pp.id_peminjaman=p.id_peminjaman AND pp.status='menunggu' ORDER BY pp.id_perpanjangan DESC LIMIT 1) AS status_perpanjangan,
                       (SELECT COUNT(*) FROM perpanjangan_peminjaman pp2 WHERE pp2.id_peminjaman=p.id_peminjaman AND pp2.status='disetujui') AS sudah_diperpanjang
                       FROM peminjaman p LEFT JOIN buku b ON b.id_buku=p.id_buku
                       WHERE p.id_anggota=:id AND p.status='dipinjam' ORDER BY p.tanggal_jatuh_tempo ASC");
$stmt->execute([':id'=>$id_anggota]);
$daftar = $stmt->fetchAll();

// Siapkan data kalender dari $daftar yang sudah ada (reuse, tanpa query baru)
$kalender_map = [];
foreach ($daftar as $p) {
    $tgl = $p['tanggal_jatuh_tempo'];
    $telat = hitung_keterlambatan($tgl);
    $hari_sisa = (int)((new DateTime(date('Y-m-d')))->diff(new DateTime($tgl))->format('%r%a'));
    if ($telat > 0) $status = 'terlambat';
    elseif ($hari_sisa <= NOTIFIKASI_JATUH_TEMPO_HARI) $status = 'peringatan';
    else $status = 'aman';
    $kalender_map[$tgl][] = ['judul' => $p['judul'] ?? 'Buku', 'status' => $status];
}
$kalender_bulan = (int)date('n');
$kalender_tahun = (int)date('Y');
$kalender_jumlah_hari = (int)date('t', mktime(0,0,0,$kalender_bulan,1,$kalender_tahun));
$kalender_hari_pertama = (int)date('w', mktime(0,0,0,$kalender_bulan,1,$kalender_tahun)); // 0=Min
$bulan_nama = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$kalender_nama_bulan = $bulan_nama[$kalender_bulan] . ' ' . $kalender_tahun;

$page_title = 'Peminjaman Saya';
$member_menu_aktif = 'peminjaman';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
  <p class="text-sm font-semibold mb-1" style="color: var(--accent)">Aktivitas akun</p>
  <h1 class="text-2xl font-bold tracking-tight" style="color: var(--text)">Peminjaman Saya</h1>
  <p class="text-sm mt-1" style="color: var(--text-faint)">Lama peminjaman <?= LAMA_PINJAM_HARI ?> hari. Denda <?= format_rupiah(DENDA_PER_HARI) ?>/hari.</p>
</div>

<?php if (!empty($daftar)): ?>
<div class="rounded-xl border p-4 sm:p-5 mb-6" style="background: var(--surface); border-color: var(--border)">
  <div class="flex items-center justify-between mb-3">
    <h2 class="font-bold flex items-center gap-2" style="color: var(--text)"><i class="bi bi-calendar3" style="color: var(--accent)"></i> Kalender Jatuh Tempo — <?= e($kalender_nama_bulan) ?></h2>
    <span class="text-xs hidden sm:inline" style="color: var(--text-faint)">Arahkan/klik tanggal berwarna untuk lihat buku</span>
  </div>
  <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-bold mb-2" style="color: var(--text-faint)">
    <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
  </div>
  <div class="grid grid-cols-7 gap-1">
    <?php for ($i=0; $i<$kalender_hari_pertama; $i++): ?><div class="h-10 sm:h-12"></div><?php endfor; ?>
    <?php for ($d=1; $d<=$kalender_jumlah_hari; $d++):
        $tgl_str = sprintf('%04d-%02d-%02d', $kalender_tahun, $kalender_bulan, $d);
        $is_today = $tgl_str === date('Y-m-d');
        $items = $kalender_map[$tgl_str] ?? [];
        $has = !empty($items);
        $status = $has ? $items[0]['status'] : '';
        if (!$has) $cell_style = 'background: var(--surface-2); border-color: var(--border); color: var(--text-muted)';
        elseif ($status==='terlambat') $cell_style = 'background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)';
        elseif ($status==='peringatan') $cell_style = 'background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)';
        else $cell_style = 'background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)';
        $titles = $has ? implode(', ', array_column($items, 'judul')) : '';
    ?>
      <div class="relative h-10 sm:h-12 rounded-xl border flex flex-col items-center justify-center p-1 <?= $is_today ? 'ring-2 ring-brand-300' : '' ?> <?= $has ? 'cursor-pointer hover:shadow-sm' : '' ?>" style="<?= $cell_style ?>"
           <?php if ($has): ?>data-judul="<?= e($titles) ?>" data-tgl="<?= e($tgl_str) ?>" onclick="if(this.dataset.judul) showNoticeModal('Jatuh tempo ' + this.dataset.tgl + ': ' + this.dataset.judul)" title="<?= e($titles) ?>"<?php endif; ?>>
        <span class="text-xs font-bold"><?= $d ?></span>
        <?php if ($has): ?><span class="w-1.5 h-1.5 rounded-full mt-1 <?= $status==='terlambat' ? 'bg-red-500' : ($status==='peringatan' ? 'bg-amber-500' : 'bg-emerald-500') ?>"></span><?php endif; ?>
        <?php if ($is_today): ?><span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-brand-600"></span><?php endif; ?>
      </div>
    <?php endfor; ?>
  </div>
  <div class="flex flex-wrap gap-2 mt-4 text-[11px]" style="color: var(--text-faint)">
    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aman</span>
    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> H-3</span>
    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span> Terlambat</span>
    <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-brand-600"></span> Hari ini</span>
  </div>
  <p class="text-xs mt-3" style="color: var(--text-faint)">Warna konsisten dengan badge di list di bawah. Klik tanggal berwarna untuk lihat judul buku.</p>
</div>
<?php endif; ?>

<?php if (empty($daftar)): ?>
  <div class="rounded-xl border p-10 text-center" style="background: var(--surface); border-color: var(--border)">
    <div class="w-14 h-14 mx-auto rounded-xl flex items-center justify-center" style="background: var(--accent-soft); color: var(--accent-text); border:1px solid var(--border)"><i class="bi bi-book text-2xl"></i></div>
    <h2 class="mt-4 font-bold" style="color: var(--text)">Belum ada buku yang dipinjam</h2>
    <p class="text-sm mt-1" style="color: var(--text-faint)">Cari buku yang ingin kamu baca dari katalog.</p>
    <a href="<?= BASE_URL ?>/index.php" class="inline-flex mt-4 px-4 py-2.5 rounded-lg bg-brand-600 text-white font-semibold text-sm">Lihat Katalog</a>
  </div>
<?php else: ?>
  <div class="space-y-4">
    <?php foreach ($daftar as $p):
      $telat = hitung_keterlambatan($p['tanggal_jatuh_tempo']);
      $denda = hitung_denda($telat);
      $hari_sisa = (int)((new DateTime(date('Y-m-d')))->diff(new DateTime($p['tanggal_jatuh_tempo']))->format('%r%a'));
      $bisa_ajukan = $hari_sisa >= 0 && !$p['status_perpanjangan'] && (int)$p['sudah_diperpanjang'] === 0;
    ?>
      <article class="member-loan-card rounded-xl p-4 sm:p-5" style="background: var(--surface); border:1px solid var(--border)">
        <div class="member-loan-layout flex flex-col sm:flex-row gap-4">
          <div class="member-loan-book flex gap-4 min-w-0 flex-1">
            <img src="<?= e(cover_url($p['cover'])) ?>" class="member-loan-cover w-16 h-28 sm:w-20 sm:h-28 object-cover rounded-lg shrink-0" style="border:1px solid var(--border)" alt="Cover buku">
            <div class="member-loan-info min-w-0 flex-1">
              <div class="flex flex-wrap gap-2 mb-2">
                <?php if ($telat > 0): ?><span class="text-xs font-bold px-2.5 py-1 rounded-full border" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)">Terlambat <?= $telat ?> hari</span>
                <?php elseif ($hari_sisa <= NOTIFIKASI_JATUH_TEMPO_HARI): ?><span class="text-xs font-bold px-2.5 py-1 rounded-full border" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)">Jatuh tempo <?= $hari_sisa === 0 ? 'hari ini' : $hari_sisa.' hari lagi' ?></span>
                <?php else: ?><span class="text-xs font-bold px-2.5 py-1 rounded-full border" style="background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)">Sedang dipinjam</span><?php endif; ?>
              </div>
              <h2 class="font-bold text-lg leading-snug" style="color: var(--text)"><?= e($p['judul'] ?? 'Buku tidak tersedia') ?></h2>
              <p class="text-sm mt-1" style="color: var(--text-faint)"><?= e($p['penulis'] ?? '-') ?></p>
              <?php if (!empty($p['is_arsip'])): ?>
                <div class="mt-3 rounded-lg border px-3 py-2.5 text-xs leading-5" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)"><i class="bi bi-archive mr-1"></i> Buku diarsipkan — tidak dapat dipinjam kembali sebelum dipublikasi admin.</div>
              <?php elseif (empty($p['judul'])): ?>
                <div class="mt-3 rounded-lg border px-3 py-2.5 text-xs leading-5" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)"><i class="bi bi-exclamation-triangle mr-1"></i> Buku tidak tersedia lagi di perpustakaan.</div>
              <?php endif; ?>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 text-sm">
                <div><p class="text-xs" style="color: var(--text-faint)">Tanggal pinjam</p><p class="font-semibold mt-0.5" style="color: var(--text)"><?= format_tanggal($p['tanggal_pinjam']) ?></p></div>
                <div><p class="text-xs" style="color: var(--text-faint)">Jatuh tempo</p><p class="font-semibold mt-0.5" style="color: <?= $telat > 0 ? 'var(--badge-red-text)' : 'var(--text)' ?>"><?= format_tanggal($p['tanggal_jatuh_tempo']) ?></p></div>
              </div>
              <?php if ($denda > 0): ?><p class="text-sm mt-3" style="color: var(--badge-red-text)">Estimasi denda saat ini: <b><?= format_rupiah($denda) ?></b></p><?php endif; ?>
            </div>
          </div>

          <div class="sm:w-64 sm:border-l sm:pl-5 flex flex-col justify-center gap-2" style="border-color: var(--border)">
            <?php if ($p['status_perpanjangan'] === 'menunggu'): ?>
              <div class="rounded-lg border p-3 text-sm" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)"><i class="bi bi-hourglass-split mr-1"></i>Permintaan perpanjangan sedang ditinjau admin.</div>
            <?php elseif ((int)$p['sudah_diperpanjang'] > 0): ?>
              <div class="rounded-lg border p-3 text-sm" style="background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)"><i class="bi bi-check-circle mr-1"></i>Perpanjangan sudah digunakan untuk peminjaman ini.</div>
            <?php elseif ($telat > 0): ?>
              <div class="rounded-lg border p-3 text-sm" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)"><i class="bi bi-exclamation-circle mr-1"></i>Perpanjangan tidak dapat diajukan setelah jatuh tempo.</div>
            <?php elseif ($bisa_ajukan): ?>
              <form method="post" action="<?= BASE_URL ?>/anggota/ajukan_perpanjangan.php" class="rounded-xl border p-3 space-y-2" style="background: var(--surface-2); border-color: var(--border)">
      <?= csrf_field() ?>
                <input type="hidden" name="id_peminjaman" value="<?= (int)$p['id_peminjaman'] ?>">
                <label class="block text-xs font-bold" style="color: var(--text-muted)">Minta tambahan waktu</label>
                <div class="flex gap-2">
                  <select name="hari_diminta" class="flex-1 rounded-lg px-3 py-2 text-sm" style="border:1px solid var(--border); background: var(--surface); color: var(--text)">
                    <?php for ($i=1; $i<=PERPANJANGAN_MAKS_HARI; $i++): ?><option value="<?= $i ?>" <?= $i === PERPANJANGAN_MAKS_HARI ? 'selected' : '' ?>>+<?= $i ?> hari</option><?php endfor; ?>
                  </select>
                  <button class="px-3 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold" type="submit" data-confirm="Kirim permintaan perpanjangan kepada admin?">Ajukan</button>
                </div>
                <input type="text" name="catatan" maxlength="500" placeholder="Catatan (opsional)" class="w-full rounded-lg px-3 py-2 text-xs" style="border:1px solid var(--border); background: var(--surface); color: var(--text)">
                <p class="text-[11px]" style="color: var(--text-faint)">Maksimal <?= PERPANJANGAN_MAKS_HARI ?> hari dan perlu persetujuan admin.</p>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
