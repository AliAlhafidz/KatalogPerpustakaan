<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$kata_kunci = clean($_GET['q'] ?? '');
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 8;
$offset = ($halaman - 1) * $per_halaman;

$where = "WHERE p.status = 'dipinjam'";
$params = [];
if ($kata_kunci !== '') {
    $where .= " AND (a.nama LIKE :kw OR b.judul LIKE :kw OR a.nomor_anggota LIKE :kw OR b.kode_buku LIKE :kw)";
    $params[':kw'] = '%' . $kata_kunci . '%';
}

// Hitung total data untuk pagination (konsisten dengan peminjaman/index.php)
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM peminjaman p
                       JOIN anggota a ON a.id_anggota = p.id_anggota
                       LEFT JOIN buku b ON b.id_buku = p.id_buku
                       $where");
$stmt_count->execute($params);
$total_data = (int) $stmt_count->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $per_halaman));
// Clamp halaman atas agar tidak menghasilkan OFFSET negatif/berlebih
if ($halaman > $total_halaman) {
    $halaman = $total_halaman;
    $offset = ($halaman - 1) * $per_halaman;
}

$stmt = $pdo->prepare("SELECT p.*, a.nama AS nama_anggota, a.nomor_anggota,
                              b.judul, b.kode_buku, b.cover, b.penulis, k.nama_kategori
                       FROM peminjaman p
                       JOIN anggota a ON a.id_anggota = p.id_anggota
                       LEFT JOIN buku b ON b.id_buku = p.id_buku
                       LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
                       $where
                       ORDER BY p.tanggal_jatuh_tempo ASC, p.id_peminjaman ASC
                       LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

$hari_ini = date('Y-m-d');
// Statistik global (sesuai filter pencarian, bukan hanya halaman saat ini) — hitung via query agar tidak perlu fetch semua baris
$jumlah_aktif = $total_data;
if ($total_data > 0) {
    // Jumlah terlambat & estimasi denda untuk seluruh data terfilter
    $stmt_stat = $pdo->prepare("SELECT p.tanggal_jatuh_tempo FROM peminjaman p
                          JOIN anggota a ON a.id_anggota = p.id_anggota
                          LEFT JOIN buku b ON b.id_buku = p.id_buku
                          $where");
    $stmt_stat->execute($params);
    $all_jatuh = $stmt_stat->fetchAll(PDO::FETCH_COLUMN);
    $jumlah_telat = 0;
    $total_estimasi_denda = 0;
    foreach ($all_jatuh as $jt) {
        $telat = hitung_keterlambatan($jt, $hari_ini);
        if ($telat > 0) $jumlah_telat++;
        $total_estimasi_denda += hitung_denda($telat);
    }
} else {
    $jumlah_telat = 0;
    $total_estimasi_denda = 0;
}

$menu_aktif = 'pengembalian';
$page_title = 'Transaksi Pengembalian';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-4">
  <div>
    <h1 class="text-xl font-bold text-gray-800">Pengembalian Buku</h1>
    <p class="text-sm text-gray-500 mt-1">Periksa kondisi transaksi, tentukan tanggal kembali, lalu proses pengembalian.</p>
  </div>
  <div class="text-xs text-gray-400">Hari ini: <span class="font-semibold text-gray-600"><?= format_tanggal($hari_ini) ?></span></div>
</div>

<div class="grid grid-cols-3 gap-2 sm:gap-3 mb-4">
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 sm:p-4">
    <p class="text-gray-400 text-[11px] sm:text-xs">Menunggu Kembali</p>
    <p class="text-lg sm:text-xl font-bold text-brand-700 mt-1"><?= $jumlah_aktif ?></p>
  </div>
  <div class="bg-white rounded-xl border border-red-100 shadow-sm p-3 sm:p-4">
    <p class="text-gray-400 text-[11px] sm:text-xs">Terlambat</p>
    <p class="text-lg sm:text-xl font-bold text-red-600 mt-1"><?= $jumlah_telat ?></p>
  </div>
  <div class="bg-white rounded-xl border border-amber-100 shadow-sm p-3 sm:p-4">
    <p class="text-gray-400 text-[11px] sm:text-xs">Estimasi Denda</p>
    <p class="text-sm sm:text-base font-bold text-amber-600 mt-1"><?= format_rupiah($total_estimasi_denda) ?></p>
  </div>
</div>

<div class="bg-blue-50 border border-blue-100 rounded-2xl px-4 py-3 mb-4 text-xs sm:text-sm text-blue-700">
  Pilih tanggal pengembalian sesuai kondisi sebenarnya. Rentang yang valid: <b>tanggal pinjam → hari ini</b>. Hari <b>Minggu</b> tidak dapat digunakan.
</div>

<form method="get" class="mb-5 bg-white border border-gray-100 rounded-2xl p-3 shadow-sm flex gap-2">
  <input type="text" name="q" value="<?= e($kata_kunci) ?>" placeholder="Cari anggota, judul, atau kode buku..."
         class="flex-1 min-w-0 border border-gray-200 bg-gray-50 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white">
  <button class="bg-gray-900 hover:bg-gray-800 text-white px-4 sm:px-5 py-2.5 rounded-xl text-sm font-medium">Cari</button>
</form>

<div class="space-y-4">
<?php if (empty($daftar)): ?>
  <div class="bg-white border border-dashed border-gray-200 rounded-2xl p-10 text-center">
    <p class="font-semibold text-gray-600">Semua transaksi sudah beres 🎉</p>
    <p class="text-sm text-gray-400 mt-1">Tidak ada peminjaman yang sedang menunggu pengembalian.</p>
  </div>
<?php endif; ?>

<?php foreach ($daftar as $p):
    $telat_awal = hitung_keterlambatan($p['tanggal_jatuh_tempo'], $hari_ini);
    $denda_awal = hitung_denda($telat_awal);
    $is_hilang_peng = empty($p['judul']);
    $cover = cover_url($p['cover']);
?>
  <article class="bg-white rounded-2xl border <?= $telat_awal > 0 ? 'border-red-100' : 'border-gray-100' ?> shadow-sm overflow-hidden">
    <div class="p-3.5 sm:p-4">
      <div class="flex gap-3.5 sm:gap-4">
        <div class="w-[76px] sm:w-[92px] shrink-0">
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
              <p class="text-xs sm:text-sm text-gray-500 mt-1 truncate"><?= $is_hilang_peng ? 'Tidak tersedia' : e($p['penulis']) ?></p>
            </div>
            <?php if ($is_hilang_peng): ?>
              <span class="shrink-0 text-[10px] sm:text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full">Tidak tersedia</span>
            <?php elseif ($telat_awal > 0): ?>
              <span class="shrink-0 text-[10px] sm:text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full">Telat <?= $telat_awal ?> hari</span>
            <?php else: ?>
              <span class="shrink-0 text-[10px] sm:text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-full">Aman</span>
            <?php endif; ?>
          </div>

          <?php if ($is_hilang_peng): ?><div class="mt-3 rounded-lg border px-3 py-2.5 text-xs leading-5" style="background:#fef2f2; border-color:#fecaca; color:#991b1b"><i class="bi bi-exclamation-triangle mr-1"></i> Buku telah dihapus dari katalog — transaksi ini tidak bisa diproses otomatis. Cek riwayat manual.</div><?php endif; ?>
          <div class="mt-3 bg-gray-50 rounded-xl p-2.5 sm:p-3">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-bold shrink-0">👤</div>
              <div class="min-w-0 flex-1">
                <p class="text-xs sm:text-sm font-semibold text-gray-700 truncate"><?= e($p['nama_anggota']) ?></p>
                <p class="text-[11px] text-gray-400"><?= e($p['nomor_anggota']) ?></p>
              </div>
              <div class="text-right shrink-0">
                <p class="text-[10px] uppercase text-gray-400">ID</p>
                <p class="text-[11px] font-semibold text-gray-600">#<?= (int)$p['id_peminjaman'] ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <form method="post" action="<?= BASE_URL ?>/admin/pengembalian/proses.php" class="pengembalian-form mt-3" data-jatuh-tempo="<?= e($p['tanggal_jatuh_tempo']) ?>" data-judul="<?= e($p['judul']) ?>" data-anggota="<?= e($p['nama_anggota']) ?>">
      <?= csrf_field() ?>
        <input type="hidden" name="id_peminjaman" value="<?= (int)$p['id_peminjaman'] ?>">

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
          <div class="rounded-xl bg-gray-50 px-3 py-2">
            <p class="text-[10px] uppercase tracking-wide text-gray-400">Tanggal Pinjam</p>
            <p class="text-xs font-semibold text-gray-700 mt-0.5"><?= format_tanggal($p['tanggal_pinjam']) ?></p>
          </div>
          <div class="rounded-xl <?= $telat_awal > 0 ? 'bg-red-50' : 'bg-gray-50' ?> px-3 py-2">
            <p class="text-[10px] uppercase tracking-wide text-gray-400">Jatuh Tempo</p>
            <p class="text-xs font-semibold <?= $telat_awal > 0 ? 'text-red-600' : 'text-gray-700' ?> mt-0.5"><?= format_tanggal($p['tanggal_jatuh_tempo']) ?></p>
          </div>
          <label class="rounded-xl bg-brand-50 px-3 py-2 cursor-pointer">
            <p class="text-[10px] uppercase tracking-wide text-brand-600">Tanggal Kembali</p>
            <input type="date" name="tanggal_kembali" value="<?= e($hari_ini) ?>"
                   min="<?= e($p['tanggal_pinjam']) ?>"
                   max="<?= e($hari_ini) ?>"
                   class="tanggal-kembali-input mt-0.5 w-full bg-transparent border-0 p-0 text-xs font-semibold text-gray-800 focus:outline-none focus:ring-0"
                   aria-label="Tanggal pengembalian">
          </label>
          <div class="rounded-xl <?= $denda_awal > 0 ? 'bg-red-50' : 'bg-gray-50' ?> px-3 py-2">
            <p class="text-[10px] uppercase tracking-wide text-gray-400">Estimasi Denda</p>
            <p class="text-xs font-semibold <?= $denda_awal > 0 ? 'text-red-600' : 'text-gray-500' ?> mt-0.5 denda-tampil" data-denda="<?= $denda_awal ?>"><?= format_rupiah($denda_awal) ?></p>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mt-3 pt-3 border-t border-gray-100">
          <p class="text-[11px] text-gray-400">Denda dihitung otomatis berdasarkan keterlambatan.</p>
          <button type="submit" class="submit-kembalikan w-full sm:w-auto text-sm font-semibold px-5 py-2.5 rounded-xl transition bg-brand-600 hover:bg-brand-700 text-white shadow-sm">
            ✓ Proses Pengembalian
          </button>
        </div>
      </form>
    </div>
  </article>
<?php endforeach; ?>
</div>

<?php if ($total_halaman > 1): ?>
  <div class="flex justify-center items-center gap-1 mt-6">
    <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
      <a href="?q=<?= urlencode($kata_kunci) ?>&halaman=<?= $i ?>"
         class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition <?= $i === $halaman ? 'bg-brand-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<script>
  const DENDA_PER_HARI = <?= (int) DENDA_PER_HARI ?>;

  function hariMinggu(tanggalStr) {
    const [y, m, d] = tanggalStr.split('-').map(Number);
    const tgl = new Date(y, m - 1, d);
    return tgl.getDay() === 0;
  }

  function hitungHariTelatJS(jatuhTempoStr, kembaliStr) {
    const [jy, jm, jd] = jatuhTempoStr.split('-').map(Number);
    const [ky, km, kd] = kembaliStr.split('-').map(Number);
    const jatuhTempo = new Date(jy, jm - 1, jd);
    const kembali = new Date(ky, km - 1, kd);
    const selisihMs = kembali - jatuhTempo;
    const hari = Math.round(selisihMs / (1000 * 60 * 60 * 24));
    return hari > 0 ? hari : 0;
  }

  document.querySelectorAll('.pengembalian-form').forEach(form => {
    const input = form.querySelector('.tanggal-kembali-input');
    const dendaSel = form.querySelector('.denda-tampil');
    const jatuhTempo = form.dataset.jatuhTempo;

    function perbaruiDenda() {
      const telat = hitungHariTelatJS(jatuhTempo, input.value);
      const denda = telat * DENDA_PER_HARI;
      dendaSel.textContent = 'Rp ' + denda.toLocaleString('id-ID');
      dendaSel.classList.toggle('text-red-600', denda > 0);
      dendaSel.classList.toggle('text-gray-500', denda === 0);
      dendaSel.closest('div').classList.toggle('bg-red-50', denda > 0);
      dendaSel.closest('div').classList.toggle('bg-gray-50', denda === 0);
    }

    input.addEventListener('change', function () {
      if (input.value && hariMinggu(input.value)) {
        input.value = '';
        dendaSel.textContent = 'Rp 0';
        dendaSel.classList.remove('text-red-600');
        dendaSel.classList.add('text-gray-500');
        dendaSel.closest('div').classList.remove('bg-red-50');
        dendaSel.closest('div').classList.add('bg-gray-50');
        showNoticeModal('Perpustakaan tutup pada hari Minggu. Silakan pilih tanggal lain.');
        return;
      }
      perbaruiDenda();
    });

    form.addEventListener('submit', function (e) {
      if (!input.value) {
        e.preventDefault();
        showNoticeModal('Pilih tanggal pengembalian terlebih dahulu.');
        return;
      }
      if (hariMinggu(input.value)) {
        e.preventDefault();
        showNoticeModal('Perpustakaan tutup pada hari Minggu. Silakan pilih tanggal lain.');
        return;
      }
      const telat = hitungHariTelatJS(jatuhTempo, input.value);
      const denda = telat * DENDA_PER_HARI;
      const pesanDenda = denda > 0 ? ` Terlambat ${telat} hari, denda Rp ${denda.toLocaleString('id-ID')}.` : ' Tepat waktu, tanpa denda.';
      e.preventDefault();
      if (form.dataset.modalBypass === '1') {
        form.dataset.modalBypass = '0';
        form.submit();
        return;
      }
      showConfirmModal(`Proses pengembalian buku "${form.dataset.judul}" oleh ${form.dataset.anggota}?${pesanDenda}`, {
        title: 'Konfirmasi Pengembalian',
        confirmText: 'Kembalikan',
        danger: denda > 0
      }).then(ok => {
        if (ok) {
          form.dataset.modalBypass = '1';
          form.requestSubmit();
        }
      });
    });
  });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
