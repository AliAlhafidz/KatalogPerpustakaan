<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

$id_anggota = (int)$_SESSION['id_anggota'];

// Pagination
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 8;
$offset = ($halaman - 1) * $per_halaman;

$filter_status = $_GET['status'] ?? 'semua';
if (!in_array($filter_status, ['semua','menunggu','disetujui','ditolak','dibatalkan'], true)) $filter_status = 'semua';

$where = 'WHERE pp.id_anggota = :anggota';
$params = [':anggota' => $id_anggota];
if ($filter_status !== 'semua') {
    $where .= ' AND pp.status = :status';
    $params[':status'] = $filter_status;
}

$total_data = 0;
$total_halaman = 1;
$daftar = [];
$ringkasan = ['menunggu'=>0,'disetujui'=>0,'ditolak'=>0,'dibatalkan'=>0];

try {
    // Ringkasan counts
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS jml FROM pengajuan_peminjaman WHERE id_anggota = :anggota GROUP BY status");
    $stmt->execute([':anggota' => $id_anggota]);
    foreach ($stmt->fetchAll() as $r) {
        $ringkasan[$r['status']] = (int)$r['jml'];
    }

    // Total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_peminjaman pp $where");
    $stmt->execute($params);
    $total_data = (int)$stmt->fetchColumn();
    $total_halaman = max(1, (int)ceil($total_data / $per_halaman));
    if ($halaman > $total_halaman) {
        $halaman = $total_halaman;
        $offset = ($halaman - 1) * $per_halaman;
    }

    $sql = "SELECT pp.*, b.judul, b.penulis, b.cover, b.kode_buku, b.stok, b.tersedia, b.is_arsip, k.nama_kategori
            FROM pengajuan_peminjaman pp
            JOIN buku b ON b.id_buku = pp.id_buku
            LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
            $where
            ORDER BY pp.created_at DESC, pp.id_pengajuan DESC
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $daftar = $stmt->fetchAll();
} catch (Throwable $e) {
    // Jika tabel belum ada, tampilkan pesan migrasi
    if (strpos($e->getMessage(), 'pengajuan_peminjaman') !== false) {
        $total_data = 0;
        $daftar = [];
        $error_tabel = 'Tabel pengajuan_peminjaman belum ada. Jalankan migrasi database/migrasi_v7_pengajuan_peminjaman.sql';
    } else {
        error_log('Gagal load pengajuan_peminjaman: ' . $e->getMessage());
        $error_tabel = 'Gagal memuat data. Silakan coba lagi.';
    }
}

$page_title = 'Pengajuan Peminjaman';
$member_menu_aktif = 'pengajuan_peminjaman';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
  <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
    <div>
      <p class="text-sm font-semibold mb-1" style="color: var(--accent)">Pengajuan saya</p>
      <h1 class="text-2xl font-bold tracking-tight" style="color: var(--text)">Pengajuan Peminjaman</h1>
      <p class="text-sm mt-1" style="color: var(--text-faint)">Riwayat pengajuan peminjaman buku — menunggu persetujuan admin.</p>
    </div>
    <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2 border px-4 py-2.5 rounded-xl text-sm font-semibold" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">
      <i class="bi bi-search"></i> Cari Buku
    </a>
  </div>
</div>

<?php if (isset($error_tabel)): ?>
  <div class="mb-4 rounded-xl border px-4 py-3 text-sm" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)">
    <i class="bi bi-exclamation-triangle mr-1"></i> <?= e($error_tabel) ?>
  </div>
<?php endif; ?>

<!-- Ringkasan -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
  <div class="rounded-xl border p-4" style="background: var(--surface); border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Menunggu</p>
    <p class="text-xl font-bold mt-1" style="color: var(--badge-amber-text)"><?= (int)$ringkasan['menunggu'] ?></p>
  </div>
  <div class="rounded-xl border p-4" style="background: var(--surface); border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Disetujui</p>
    <p class="text-xl font-bold mt-1" style="color: var(--badge-emerald-text)"><?= (int)$ringkasan['disetujui'] ?></p>
  </div>
  <div class="rounded-xl border p-4" style="background: var(--surface); border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Ditolak</p>
    <p class="text-xl font-bold mt-1" style="color: var(--badge-red-text)"><?= (int)$ringkasan['ditolak'] ?></p>
  </div>
  <div class="rounded-xl border p-4" style="background: var(--surface); border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Dibatalkan</p>
    <p class="text-xl font-bold mt-1" style="color: var(--text-faint)"><?= (int)$ringkasan['dibatalkan'] ?></p>
  </div>
</div>

<!-- Filter -->
<div class="flex flex-wrap gap-2 mb-5">
  <?php foreach (['semua'=>'Semua','menunggu'=>'Menunggu','disetujui'=>'Disetujui','ditolak'=>'Ditolak','dibatalkan'=>'Dibatalkan'] as $k => $label): ?>
    <a href="?status=<?= e($k) ?>" class="px-4 py-2 rounded-xl text-sm font-semibold transition <?= $filter_status===$k ? 'bg-brand-600 text-white shadow-sm' : 'border hover:bg-slate-50' ?>" style="<?= $filter_status===$k ? '' : 'background: var(--surface); border:1px solid var(--border); color: var(--text-muted)' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php if (empty($daftar)): ?>
  <div class="rounded-2xl border p-10 text-center" style="background: var(--surface); border-color: var(--border)">
    <div class="w-14 h-14 mx-auto rounded-xl flex items-center justify-center text-2xl" style="background: var(--surface-2); color: var(--text-faint); border:1px solid var(--border)"><i class="bi bi-journal-arrow-up"></i></div>
    <h2 class="mt-4 font-bold" style="color: var(--text)">Belum ada pengajuan</h2>
    <p class="text-sm mt-1" style="color: var(--text-faint)">Pengajuan yang kamu kirim akan muncul di sini.</p>
    <a href="<?= BASE_URL ?>/index.php" class="inline-flex mt-4 px-4 py-2.5 rounded-lg bg-brand-600 text-white font-semibold text-sm">Lihat Katalog Buku</a>
  </div>
<?php else: ?>
  <div class="space-y-4">
    <?php foreach ($daftar as $p): 
      $cover = cover_url($p['cover'] ?? null);
      $is_waiting = $p['status'] === 'menunggu';
    ?>
      <article class="rounded-2xl border overflow-hidden" style="background: var(--surface); border-color: var(--border)">
        <div class="p-4 sm:p-5">
          <div class="flex gap-4">
            <!-- Cover -->
            <div class="w-[88px] sm:w-[96px] shrink-0">
              <div class="aspect-[3/4] rounded-xl overflow-hidden border" style="background: var(--surface-2); border-color: var(--border)">
                <img src="<?= e($cover) ?>" alt="Cover <?= e($p['judul']) ?>" class="w-full h-full object-cover" loading="lazy">
              </div>
            </div>
            <!-- Info -->
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-[11px] font-bold uppercase tracking-wide truncate" style="color: var(--accent)"><?= e($p['nama_kategori'] ?? 'Tanpa Kategori') ?></p>
                  <h2 class="font-bold text-base sm:text-lg leading-tight mt-0.5 line-clamp-2" style="color: var(--text)"><?= e($p['judul']) ?></h2>
                  <p class="text-sm mt-1 truncate" style="color: var(--text-faint)"><?= e($p['penulis']) ?> · <?= e($p['kode_buku']) ?></p>
                </div>
                <div class="shrink-0">
                  <?= badge_pengajuan_peminjaman($p['status']) ?>
                </div>
              </div>

              <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                <div class="rounded-xl border px-3 py-2.5" style="background: var(--surface-2); border-color: var(--border)">
                  <p class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--text-faint)">Tanggal Pengajuan</p>
                  <p class="font-semibold mt-0.5" style="color: var(--text)"><?= format_tanggal(date('Y-m-d', strtotime($p['created_at']))) ?> · <?= date('H:i', strtotime($p['created_at'])) ?></p>
                </div>
                <div class="rounded-xl border px-3 py-2.5" style="background: var(--surface-2); border-color: var(--border)">
                  <p class="text-[10px] font-bold uppercase tracking-wide" style="color: var(--text-faint)">Status</p>
                  <p class="font-semibold mt-0.5 capitalize" style="color: var(--text)"><?= e($p['status']) ?></p>
                </div>
              </div>

              <?php if (!empty($p['catatan_anggota'])): ?>
                <div class="mt-3 rounded-xl border px-3 py-2.5" style="background: var(--surface); border-color: var(--border)">
                  <p class="text-[11px] font-bold" style="color: var(--text-faint)">Catatan kamu:</p>
                  <p class="text-sm mt-1" style="color: var(--text-muted)">“<?= e($p['catatan_anggota']) ?>”</p>
                </div>
              <?php endif; ?>

              <?php if (!empty($p['catatan_admin'])): ?>
                <div class="mt-3 rounded-xl px-3 py-2.5 border text-sm" style="<?= $p['status']==='ditolak' ? 'background:var(--badge-red-bg); border-color:var(--badge-red-border); color:var(--badge-red-text)' : ($p['status']==='disetujui' ? 'background:var(--badge-emerald-bg); border-color:var(--badge-emerald-border); color:var(--badge-emerald-text)' : 'background:var(--badge-slate-bg); border-color:var(--badge-slate-border); color:var(--badge-slate-text)') ?>">
                  <p class="text-xs font-bold opacity-80">Catatan admin:</p>
                  <p class="mt-1 leading-5"><?= e($p['catatan_admin']) ?></p>
                </div>
              <?php endif; ?>

              <?php if ($p['status']==='disetujui' && !empty($p['diproses_at'])): ?>
                <p class="text-xs text-emerald-700 mt-3"><i class="bi bi-check2-circle mr-1"></i>Disetujui pada <?= format_tanggal(date('Y-m-d', strtotime($p['diproses_at']))) ?> — silakan ambil buku di perpustakaan. Jatuh tempo <?= LAMA_PINJAM_HARI ?> hari setelah pengambilan.</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-4 pt-3 border-t flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="border-color: var(--border-faint)">
            <div class="flex items-center gap-2 text-xs" style="color: var(--text-faint)">
              <span>#<?= (int)$p['id_pengajuan'] ?></span>
              <span>·</span>
              <span><?= e(date('d M Y H:i', strtotime($p['created_at']))) ?></span>
              <?php if (!empty($p['diproses_at'])): ?>
                <span>·</span>
                <span>diproses <?= e(date('d M Y', strtotime($p['diproses_at']))) ?></span>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
              <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$p['id_buku'] ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border text-xs font-semibold" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">
                <i class="bi bi-eye"></i> Detail Buku
              </a>
              <?php if ($is_waiting): ?>
                <form method="post" action="<?= BASE_URL ?>/anggota/batalkan_pengajuan_peminjaman.php" data-confirm-danger="true" data-confirm="Batalkan pengajuan untuk buku &quot;<?= e($p['judul']) ?>&quot;?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id_pengajuan" value="<?= (int)$p['id_pengajuan'] ?>">
                  <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border text-xs font-bold" style="background: var(--surface); border-color: var(--badge-red-border); color: var(--badge-red-text)">
                    <i class="bi bi-x-lg"></i> Batalkan
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($total_halaman > 1): ?>
    <div class="flex justify-center items-center gap-1 mt-6">
      <?php for ($i=1; $i<=$total_halaman; $i++): ?>
        <a href="?status=<?= e($filter_status) ?>&halaman=<?= $i ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition <?= $i===$halaman ? 'bg-brand-600 text-white' : 'border' ?>" style="<?= $i===$halaman ? '' : 'background: var(--surface); border-color: var(--border); color: var(--text-muted)' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
