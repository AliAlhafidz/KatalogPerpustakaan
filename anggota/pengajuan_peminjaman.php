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
            LEFT JOIN buku b ON b.id_buku = pp.id_buku
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
    if (strpos($e->getMessage(), 'pengajuan_peminjaman') !== false) {
        $total_data = 0;
        $daftar = [];
        $error_tabel = 'Tabel pengajuan_peminjaman belum ada. Jalankan migrasi database/migrasi_v7_pengajuan_peminjaman.sql';
    } else {
        error_log('Gagal load pengajuan_peminjaman: ' . $e->getMessage());
        $error_tabel = 'Gagal memuat data: ' . $e->getMessage();
    }
}

// Cek batas pinjam aktif anggota saat ini
$jumlah_pinjam_aktif = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id AND status = 'dipinjam'");
    $stmt->execute([':id' => $id_anggota]);
    $jumlah_pinjam_aktif = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    $jumlah_pinjam_aktif = 0;
}
$bisa_mengajukan_baru = ($jumlah_pinjam_aktif < 3);

// Ambil daftar buku yang tersedia untuk dipilih anggota di modal pengajuan
$buku_tersedia = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.id_buku, b.kode_buku, b.judul, b.penulis, b.tersedia, b.lokasi_rak, k.nama_kategori
        FROM buku b
        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
        WHERE b.is_arsip = 0 AND b.tersedia > 0
          AND b.id_buku NOT IN (
              SELECT id_buku FROM peminjaman WHERE id_anggota = :id_pinjam AND status = 'dipinjam'
          )
          AND b.id_buku NOT IN (
              SELECT id_buku FROM pengajuan_peminjaman WHERE id_anggota = :id_tunggu AND status = 'menunggu'
          )
        ORDER BY b.judul ASC
    ");
    $stmt->execute([':id_pinjam' => $id_anggota, ':id_tunggu' => $id_anggota]);
    $buku_tersedia = $stmt->fetchAll();
} catch (Throwable $e) {
    $buku_tersedia = [];
}

$preselect_id = isset($_GET['id_buku']) ? (int)$_GET['id_buku'] : 0;
$buka_modal_otomatis = (isset($_GET['ajukan']) && $_GET['ajukan'] == '1') || ($preselect_id > 0);

$page_title = 'Pengajuan Peminjaman';
$member_menu_aktif = 'pengajuan_peminjaman';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
  <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
    <div>
      <p class="text-sm font-semibold mb-1" style="color: var(--accent)">Pengajuan saya</p>
      <h1 class="text-2xl font-bold tracking-tight" style="color: var(--text)">Pengajuan Peminjaman</h1>
      <p class="text-sm mt-1" style="color: var(--text-faint)">Ajukan peminjaman buku perpustakaan secara online dan pantau status persetujuan.</p>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" id="btnBukaModalPengajuan" class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-sm transition">
        <i class="bi bi-plus-lg"></i> Ajukan Peminjaman
      </button>
      <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2 border px-4 py-2.5 rounded-xl text-sm font-semibold" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">
        <i class="bi bi-grid"></i> Katalog
      </a>
    </div>
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
    <h2 class="mt-4 font-bold" style="color: var(--text)">Belum ada pengajuan peminjaman</h2>
    <p class="text-sm mt-1" style="color: var(--text-faint)">Ajukan peminjaman buku pilihanmu atau cari buku dari katalog.</p>
    <div class="mt-4 flex items-center justify-center gap-2">
      <button type="button" onclick="bukaModalAjukan()" class="inline-flex px-4 py-2.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm transition">
        <i class="bi bi-plus-lg mr-1.5"></i> Ajukan Peminjaman Sekarang
      </button>
      <a href="<?= BASE_URL ?>/index.php" class="inline-flex px-4 py-2.5 rounded-lg border font-semibold text-sm" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">
        Lihat Katalog Buku
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="space-y-4">
    <?php foreach ($daftar as $p): 
      $cover = cover_url($p['cover'] ?? null);
      $is_waiting = $p['status'] === 'menunggu';
      $is_approved = $p['status'] === 'disetujui';
    ?>
      <article class="rounded-2xl border overflow-hidden" style="background: var(--surface); border-color: var(--border)">
        <div class="p-4 sm:p-5">
          <div class="flex gap-4">
            <!-- Cover -->
            <div class="w-[88px] sm:w-[96px] shrink-0">
              <div class="aspect-[3/4] rounded-xl overflow-hidden border" style="background: var(--surface-2); border-color: var(--border)">
                <img src="<?= e($cover) ?>" alt="Cover <?= e($p['judul'] ?? 'Buku') ?>" class="w-full h-full object-cover" loading="lazy">
              </div>
            </div>
            <!-- Info -->
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-[11px] font-bold uppercase tracking-wide truncate" style="color: var(--accent)"><?= e($p['nama_kategori'] ?? 'Koleksi Umum') ?></p>
                  <h2 class="font-bold text-base sm:text-lg leading-tight mt-0.5 line-clamp-2" style="color: var(--text)"><?= e($p['judul'] ?? 'Buku Tanpa Judul') ?></h2>
                  <p class="text-sm mt-1 truncate" style="color: var(--text-faint)"><?= e($p['penulis'] ?? '-') ?> · <?= e($p['kode_buku'] ?? '-') ?></p>
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

              <?php if ($is_approved && !empty($p['diproses_at'])): ?>
                <div class="mt-3 p-3 rounded-xl border flex items-center justify-between gap-3" style="background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)">
                  <p class="text-xs font-semibold">
                    <i class="bi bi-check2-circle mr-1"></i>Disetujui pada <?= format_tanggal(date('Y-m-d', strtotime($p['diproses_at']))) ?>. Silakan ambil buku di perpustakaan.
                  </p>
                  <a href="<?= BASE_URL ?>/anggota/peminjaman.php" class="text-xs font-bold underline shrink-0 hover:opacity-80">Lihat Peminjaman Saya →</a>
                </div>
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
              <?php if (!empty($p['id_buku'])): ?>
                <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$p['id_buku'] ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border text-xs font-semibold" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">
                  <i class="bi bi-eye"></i> Detail Buku
                </a>
              <?php endif; ?>
              <?php if ($is_waiting): ?>
                <form method="post" action="<?= BASE_URL ?>/anggota/batalkan_pengajuan_peminjaman.php" data-confirm-danger="true" data-confirm="Batalkan pengajuan untuk buku &quot;<?= e($p['judul'] ?? 'Buku') ?>&quot;?">
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

<!-- Modal Form Ajukan Peminjaman Baru -->
<div id="modalAjukanPinjam" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modalAjukanTitle">
  <div class="relative w-full max-w-lg bg-white rounded-2xl border p-5 sm:p-6 shadow-xl" style="border-color: var(--border); background: var(--surface)">
    <div class="flex items-center justify-between pb-3 mb-4 border-b" style="border-color: var(--border)">
      <div class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm" style="background: var(--accent-soft); color: var(--accent-text)">
          <i class="bi bi-journal-plus"></i>
        </span>
        <div>
          <h2 id="modalAjukanTitle" class="font-bold text-base" style="color: var(--text)">Ajukan Peminjaman Buku</h2>
          <p class="text-xs" style="color: var(--text-faint)">Pilih buku dari katalog perpustakaan</p>
        </div>
      </div>
      <button type="button" id="btnTutupModalAjukan" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100" aria-label="Tutup">
        <i class="bi bi-x-lg text-sm"></i>
      </button>
    </div>

    <?php if (!$bisa_mengajukan_baru): ?>
      <div class="rounded-xl border p-4 mb-4 text-sm" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)">
        <i class="bi bi-exclamation-triangle mr-1"></i> Kamu sedang meminjam 3 buku (batas maksimal). Kembalikan buku terlebih dahulu untuk mengajukan peminjaman baru.
      </div>
      <div class="flex justify-end">
        <button type="button" onclick="tutupModalAjukan()" class="px-4 py-2.5 rounded-xl border text-xs font-semibold hover:bg-slate-50" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">
          Tutup
        </button>
      </div>
    <?php elseif (empty($buku_tersedia)): ?>
      <div class="rounded-xl border p-5 mb-4 text-center" style="background: var(--surface-2); border-color: var(--border); color: var(--text-muted)">
        <div class="w-10 h-10 mx-auto rounded-xl flex items-center justify-center text-xl mb-2" style="background: var(--surface); color: var(--text-faint); border: 1px solid var(--border)">
          <i class="bi bi-inbox"></i>
        </div>
        <p class="font-medium text-sm" style="color: var(--text)">Tidak ada buku baru yang dapat diajukan saat ini.</p>
        <p class="text-xs mt-1" style="color: var(--text-faint)">Semua buku yang tersedia sedang kamu pinjam atau memiliki pengajuan yang sedang menunggu persetujuan.</p>
      </div>
      <div class="flex justify-end">
        <button type="button" onclick="tutupModalAjukan()" class="px-4 py-2.5 rounded-xl border text-xs font-semibold hover:bg-slate-50" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">
          Tutup
        </button>
      </div>
    <?php else: ?>
      <form method="post" action="<?= BASE_URL ?>/anggota/ajukan_peminjaman.php" class="space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="redirect" value="pengajuan">
        <div>
          <label for="selectBukuAjukan" class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--text-muted)">Pilih Buku <span class="text-red-500">*</span></label>
          <select id="selectBukuAjukan" name="id_buku" required data-no-custom="1" class="w-full border rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color: var(--border); background: var(--surface); color: var(--text)">
            <option value="">-- Pilih Buku yang Tersedia --</option>
            <?php foreach ($buku_tersedia as $b): ?>
              <option value="<?= (int)$b['id_buku'] ?>" <?= ($preselect_id === (int)$b['id_buku']) ? 'selected' : '' ?>>
                <?= e($b['judul']) ?> — <?= e($b['penulis'] ?: 'Tanpa Penulis') ?> (Stok: <?= (int)$b['tersedia'] ?><?= $b['lokasi_rak'] ? ', Rak: ' . e($b['lokasi_rak']) : '' ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="catatanAnggotaModal" class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--text-muted)">Catatan untuk Petugas (opsional)</label>
          <textarea id="catatanAnggotaModal" name="catatan_anggota" rows="2" maxlength="500" placeholder="Contoh: Untuk keperluan tugas kuliah, rencana diambil siang ini..." class="w-full border rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color: var(--border); background: var(--surface); color: var(--text)"></textarea>
          <p class="text-[11px] mt-1" style="color: var(--text-faint)">Maksimal 500 karakter.</p>
        </div>

        <div class="rounded-xl border p-3 text-xs leading-5" style="background: var(--surface-2); border-color: var(--border); color: var(--text-muted)">
          <i class="bi bi-info-circle mr-1" style="color: var(--accent)"></i>
          Lama peminjaman adalah <b><?= LAMA_PINJAM_HARI ?> hari</b> setelah disetujui. Silakan ambil buku di meja sirkulasi setelah admin memberikan persetujuan.
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" id="btnBatalModalAjukan" class="px-4 py-2.5 rounded-xl border text-xs font-semibold hover:bg-slate-50" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">
            Batal
          </button>
          <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition shadow-sm">
            <i class="bi bi-send"></i> Kirim Pengajuan
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
  const modalAjukan = document.getElementById('modalAjukanPinjam');
  const btnBuka = document.getElementById('btnBukaModalPengajuan');
  const btnTutup = document.getElementById('btnTutupModalAjukan');
  const btnBatal = document.getElementById('btnBatalModalAjukan');

  function bukaModalAjukan() {
    if (!modalAjukan) return;
    modalAjukan.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }

  function tutupModalAjukan() {
    if (!modalAjukan) return;
    modalAjukan.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  btnBuka?.addEventListener('click', bukaModalAjukan);
  btnTutup?.addEventListener('click', tutupModalAjukan);
  btnBatal?.addEventListener('click', tutupModalAjukan);

  // Tutup jika klik backdrop
  modalAjukan?.addEventListener('click', (e) => {
    if (e.target === modalAjukan) tutupModalAjukan();
  });

  // Tutup dengan Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalAjukan && !modalAjukan.classList.contains('hidden')) {
      tutupModalAjukan();
    }
  });

  <?php if ($buka_modal_otomatis): ?>
    document.addEventListener('DOMContentLoaded', () => {
      bukaModalAjukan();
    });
  <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
