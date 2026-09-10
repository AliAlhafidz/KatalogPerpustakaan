<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/email_simulasi.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$hasil = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses'])) {
    $hasil = proses_notifikasi_h3_h1($pdo);
    if (($hasil['ringkasan']['h3_dibuat'] + $hasil['ringkasan']['h1_dibuat']) > 0) {
        set_flash('sukses', 'Proses selesai: H-3 dibuat ' . $hasil['ringkasan']['h3_dibuat'] . ', H-1 dibuat ' . $hasil['ringkasan']['h1_dibuat'] . ', skip duplicate ' . $hasil['ringkasan']['skip_duplicate'] . '.');
    } else {
        set_flash('info', 'Proses selesai: tidak ada notifikasi baru (H-3/H-1 ' . $hasil['h3'] . '/' . $hasil['h1'] . ', skip duplicate ' . $hasil['ringkasan']['skip_duplicate'] . '). Cek apakah ada peminjaman jatuh tempo H-3/H-1 hari ini.');
    }
    // Tetap tampilkan hasil di halaman (tidak redirect) agar preview terlihat
}

$menu_aktif = 'dashboard';
$page_title = 'Kirim Notifikasi Jatuh Tempo';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_menu.php';
?>

<div class="max-w-3xl">
  <div class="flex items-center gap-3 mb-6">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="text-gray-400 hover:text-gray-600">&larr;</a>
    <h1 class="text-xl font-bold text-gray-800">Kirim Notifikasi H-3 / H-1</h1>
  </div>

  <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-6 text-sm text-blue-800">
    <p class="font-bold mb-1">Mode Demo — Simulasi Pengganti Cron</p>
    <p class="leading-6">Halaman ini <b>tidak mengirim email sungguhan</b> dan <b>tidak pakai cron aktif</b> (cron sengaja dihapus di P3, sesuai keputusan arsitektur). Fungsi ini <b>reuse tabel <code>notifikasi</code></b> yang sudah ada (tipe <code>warning</code>, <code>kunci_unik</code> <code>email_h3/email_h1:id:tgl</code> + <code>INSERT IGNORE</code>) — jadi anggota lihat di <a href="<?= BASE_URL ?>/anggota/notifikasi.php" class="underline font-bold">Pusat Notifikasi</a> seperti biasa. Preview “email” di bawah hanya simulasi untuk demo.</p>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
    <h2 class="font-bold text-slate-800 mb-2">Trigger Manual (Pengganti Cron)</h2>
    <p class="text-sm text-slate-500 mb-4">Query: <code>status='dipinjam' AND tanggal_jatuh_tempo IN (CURDATE()+3, CURDATE()+1)</code>. Duplikat dicegah via <code>kunci_unik</code> per peminjaman per hari — jalan 2x tidak dobel.</p>
    <form method="post" action="">
        <?= csrf_field() ?>
      <input type="hidden" name="proses" value="1">
      <button type="submit" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl shadow-sm">
        <i class="bi bi-send"></i> Proses Notifikasi H-3/H-1 Sekarang
      </button>
    </form>
  </div>

  <?php if ($hasil): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
      <h3 class="font-bold text-slate-800 mb-3">Ringkasan</h3>
      <div class="grid grid-cols-3 gap-3 text-center">
        <div class="rounded-xl bg-amber-50 border border-amber-100 p-3"><p class="text-xs text-amber-600">H-3 dibuat</p><p class="text-xl font-black text-amber-700"><?= (int)$hasil['ringkasan']['h3_dibuat'] ?></p><p class="text-[11px] text-slate-400"><?= e($hasil['h3']) ?></p></div>
        <div class="rounded-xl bg-orange-50 border border-orange-100 p-3"><p class="text-xs text-orange-600">H-1 dibuat</p><p class="text-xl font-black text-orange-700"><?= (int)$hasil['ringkasan']['h1_dibuat'] ?></p><p class="text-[11px] text-slate-400"><?= e($hasil['h1']) ?></p></div>
        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3"><p class="text-xs text-slate-500">Skip duplicate</p><p class="text-xl font-black text-slate-700"><?= (int)$hasil['ringkasan']['skip_duplicate'] ?></p><p class="text-[11px] text-slate-400">sudah ada hari ini</p></div>
      </div>

      <?php if (!empty($hasil['preview'])): ?>
        <h4 class="font-bold text-slate-800 mt-6 mb-2">Preview “Email” Simulasi (tidak benar-benar terkirim)</h4>
        <div class="space-y-3">
          <?php foreach ($hasil['preview'] as $p): ?>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
              <p class="text-xs font-bold text-slate-500"><?= e($p['tipe']) ?> — <?= e($p['anggota']) ?> — <?= e($p['buku']) ?> (jatuh <?= e(format_tanggal($p['jatuh_tempo'])) ?>)</p>
              <p class="text-sm font-bold text-slate-800 mt-1">Subjek: <?= e($p['subjek']) ?></p>
              <p class="text-xs text-slate-500 mt-1">Kunci: <code><?= e($p['kunci']) ?></code></p>
              <div class="mt-2 bg-white rounded-lg border border-slate-200 p-3 text-sm text-slate-700 leading-6"><?= nl2br(e($p['body'])) ?><br><span class="text-xs text-amber-600 font-bold">SIMULASI — mode demo</span></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-sm text-slate-500 mt-4">Tidak ada preview — tidak ada H-3/H-1 baru hari ini, atau semua sudah diproses (cek <a href="<?= BASE_URL ?>/anggota/notifikasi.php" class="text-brand-600 underline">notifikasi anggota</a>).</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
