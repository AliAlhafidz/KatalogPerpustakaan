<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

$id_anggota = $_SESSION['id_anggota'];

$stmt = $pdo->prepare("SELECT p.*, b.judul, b.cover, b.kode_buku, b.penulis, b.is_arsip, k.nama_kategori
                        FROM peminjaman p
                        LEFT JOIN buku b ON b.id_buku = p.id_buku
                        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
                        WHERE p.id_anggota = :id
                        ORDER BY p.tanggal_pinjam DESC, p.id_peminjaman DESC");
$stmt->execute([':id' => $id_anggota]);
$riwayat = $stmt->fetchAll();

$total = count($riwayat);
$aktif = 0;
$selesai = 0;
$total_denda = 0;
foreach ($riwayat as $r) {
    if ($r['status'] === 'dipinjam') $aktif++;
    else $selesai++;
    $total_denda += (int)$r['denda'];
}

$page_title = 'Riwayat Peminjaman';
$member_menu_aktif = 'riwayat';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
  .history-hero { background: var(--surface); border:1px solid var(--border); }
  .history-stat { background: var(--surface); border:1px solid var(--border); }
  .history-card { border:1px solid var(--border); background: var(--surface); border-radius:1rem; overflow:hidden; box-shadow: var(--shadow-xs); }
  .history-cover { width:118px; height:164px; object-fit:cover; border-radius: var(--radius); box-shadow: var(--shadow-xs); background: var(--surface-2); border:1px solid var(--border); }
  .history-label { color: var(--text-faint); font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
  .history-value { color: var(--text); font-size:.83rem; font-weight:700; }
  @media (max-width:639px) {
    .history-cover { width:96px; height:136px; border-radius: var(--radius); }
    .history-card { border-radius: var(--radius-md); }
  }
</style>

<div class="max-w-6xl mx-auto space-y-5">
  <section class="history-hero rounded-xl p-5 sm:p-6 flex items-start justify-between gap-4">
    <div class="min-w-0">
      <div class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold" style="background: var(--accent-soft); color: var(--accent-text); border:1px solid var(--border)">
        <i class="bi bi-clock-history"></i> Aktivitas Buku
      </div>
      <h1 class="mt-3 text-2xl sm:text-[26px] font-bold tracking-tight font-display" style="color: var(--text)">Riwayat Peminjaman</h1>
      <p class="mt-1.5 text-sm" style="color: var(--text-muted)">Semua perjalanan peminjaman buku kamu, tersusun rapi.</p>
    </div>
    <span class="hidden sm:flex w-11 h-11 rounded-xl items-center justify-center text-lg shrink-0" style="background: var(--accent-soft); color: var(--accent-text); border:1px solid var(--border)"><i class="bi bi-journal-bookmark-fill"></i></span>
  </section>

  <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="history-stat rounded-xl p-4"><div class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Total</div><div class="mt-1 text-2xl font-bold" style="color: var(--text)"><?= $total ?></div><div class="text-xs mt-1" style="color: var(--text-faint)">transaksi</div></div>
    <div class="history-stat rounded-xl p-4"><div class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Aktif</div><div class="mt-1 text-2xl font-bold" style="color: var(--accent)"><?= $aktif ?></div><div class="text-xs mt-1" style="color: var(--text-faint)">sedang dipinjam</div></div>
    <div class="history-stat rounded-xl p-4"><div class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Selesai</div><div class="mt-1 text-2xl font-bold" style="color: var(--text)"><?= $selesai ?></div><div class="text-xs mt-1" style="color: var(--text-faint)">sudah kembali</div></div>
    <div class="history-stat rounded-xl p-4"><div class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Total Denda</div><div class="mt-1 text-lg sm:text-xl font-bold <?= $total_denda > 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= format_rupiah($total_denda) ?></div><div class="text-xs mt-1" style="color: var(--text-faint)">dari seluruh riwayat</div></div>
  </section>

  <?php if (empty($riwayat)): ?>
    <div class="rounded-xl border p-10 text-center" style="background: var(--surface); border-color: var(--border)">
      <div class="mx-auto w-14 h-14 rounded-xl flex items-center justify-center text-xl" style="background:var(--accent-soft);color:var(--accent-text); border:1px solid var(--border)"><i class="bi bi-journal-x"></i></div>
      <h2 class="mt-4 text-base font-bold" style="color: var(--text)">Belum ada riwayat</h2>
      <p class="mt-1 text-sm" style="color: var(--text-faint)">Transaksi peminjaman kamu akan muncul di halaman ini.</p>
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
      <?php foreach ($riwayat as $r):
        $aktif_transaksi = $r['status'] === 'dipinjam';
        $terlambat = $aktif_transaksi && hitung_keterlambatan($r['tanggal_jatuh_tempo']) > 0;
        $is_arsip = !empty($r['is_arsip']);
        $is_hilang = empty($r['judul']); // buku sudah dihapus fisik
      ?>
        <article class="history-card p-4 sm:p-5" style="<?= $is_hilang ? 'border-color: var(--badge-red-border); background: var(--surface)' : ($is_arsip ? 'border-color: var(--badge-amber-border)' : '') ?>">
          <div class="flex gap-4">
            <img src="<?= e(cover_url($r['cover'] ?? null)) ?>" alt="Cover <?= e($r['judul'] ?? 'Buku') ?>" class="history-cover flex-none <?= $is_arsip ? 'grayscale-[0.4]' : '' ?>">
            <div class="min-w-0 flex-1 flex flex-col">
              <div class="flex items-start justify-between gap-2">
                <span class="inline-flex max-w-[72%] truncate rounded-full px-2.5 py-1 text-[11px] font-extrabold" style="background:var(--theme-soft);color:var(--theme-primary); border:1px solid var(--border)"><?= e($r['kode_buku'] ?? '—') ?></span>
                <?php if ($is_hilang): ?>
                  <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-extrabold" style="background: var(--badge-red-bg); color: var(--badge-red-text); border:1px solid var(--badge-red-border)">Tidak tersedia</span>
                <?php elseif ($is_arsip): ?>
                  <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-extrabold" style="background: var(--badge-amber-bg); color: var(--badge-amber-text); border:1px solid var(--badge-amber-border)">Diarsipkan</span>
                <?php elseif ($terlambat): ?>
                  <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-extrabold" style="background: var(--badge-red-bg); color: var(--badge-red-text); border:1px solid var(--badge-red-border)">Terlambat</span>
                <?php elseif ($aktif_transaksi): ?>
                  <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-extrabold" style="background: var(--badge-amber-bg); color: var(--badge-amber-text); border:1px solid var(--badge-amber-border)">Dipinjam</span>
                <?php else: ?>
                  <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-extrabold" style="background: var(--badge-emerald-bg); color: var(--badge-emerald-text); border:1px solid var(--badge-emerald-border)">Selesai</span>
                <?php endif; ?>
              </div>

              <h2 class="mt-2 text-base sm:text-[15px] font-bold leading-snug line-clamp-2" style="color: var(--text)"><?= e($r['judul'] ?? 'Buku telah dihapus dari katalog') ?></h2>
              <p class="mt-1 text-xs sm:text-sm truncate" style="color: var(--text-muted)"><?= e($r['penulis'] ?? '-') ?></p>
              <?php if (!empty($r['nama_kategori'])): ?><p class="mt-1 text-[11px] font-semibold" style="color: var(--text-faint)"><?= e($r['nama_kategori']) ?></p><?php endif; ?>

              <?php if ($is_hilang): ?>
                <div class="mt-3 rounded-lg border px-3 py-2.5 text-xs leading-5" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)"><i class="bi bi-exclamation-triangle mr-1"></i> Buku ini sudah dihapus atau tidak tersedia lagi di perpustakaan. Riwayat tetap ditampilkan untuk keperluan audit.</div>
              <?php elseif ($is_arsip): ?>
                <div class="mt-3 rounded-lg border px-3 py-2.5 text-xs leading-5" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)"><i class="bi bi-archive mr-1"></i> Buku diarsipkan — tidak dapat dipinjam kembali sebelum dipublikasi admin.</div>
              <?php endif; ?>

              <div class="mt-auto pt-3 grid grid-cols-2 gap-x-3 gap-y-2">
                <div><div class="history-label">Dipinjam</div><div class="history-value mt-0.5"><?= format_tanggal($r['tanggal_pinjam']) ?></div></div>
                <div><div class="history-label">Jatuh Tempo</div><div class="history-value mt-0.5 <?= $terlambat ? 'text-red-600' : '' ?>"><?= format_tanggal($r['tanggal_jatuh_tempo']) ?></div></div>
                <div><div class="history-label">Dikembalikan</div><div class="history-value mt-0.5"><?= $r['tanggal_kembali'] ? format_tanggal($r['tanggal_kembali']) : '-' ?></div></div>
                <div><div class="history-label">Denda</div><div class="history-value mt-0.5 <?= $r['denda'] > 0 ? 'text-red-600' : 'text-slate-400' ?>"><?= format_rupiah($r['denda']) ?></div></div>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
