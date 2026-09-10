<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_admin();

$total_buku      = (int) $pdo->query("SELECT COALESCE(SUM(stok),0) FROM buku")->fetchColumn();
$total_judul     = (int) $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$total_anggota   = (int) $pdo->query("SELECT COUNT(*) FROM anggota WHERE status='aktif'")->fetchColumn();
$sedang_dipinjam = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam'")->fetchColumn();
$terlambat       = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status='dipinjam' AND tanggal_jatuh_tempo < CURDATE()")->fetchColumn();
$total_denda_bulan_ini = (int) $pdo->query("SELECT COALESCE(SUM(denda),0) FROM peminjaman WHERE status='dikembalikan' AND MONTH(tanggal_kembali)=MONTH(CURDATE()) AND YEAR(tanggal_kembali)=YEAR(CURDATE())")->fetchColumn();
$pending_perpanjangan = (int) $pdo->query("SELECT COUNT(*) FROM perpanjangan_peminjaman WHERE status='menunggu'")->fetchColumn();
try {
    $pending_pengajuan = (int) $pdo->query("SELECT COUNT(*) FROM pengajuan_buku WHERE status='menunggu'")->fetchColumn();
} catch (Throwable $e) {
    $pending_pengajuan = 0;
}
try {
    $pending_pengajuan_pinjam = (int) $pdo->query("SELECT COUNT(*) FROM pengajuan_peminjaman WHERE status='menunggu'")->fetchColumn();
} catch (Throwable $e) {
    $pending_pengajuan_pinjam = 0;
}

// Buku dengan stok tersedia paling sedikit (perlu perhatian)
$stmt = $pdo->query("SELECT judul, stok, tersedia FROM buku ORDER BY tersedia ASC, stok DESC LIMIT 5");
$stok_menipis = $stmt->fetchAll();

// Anggota yang sering terlambat mengembalikan buku (>=2 kali terlambat)
$stmt = $pdo->query("SELECT a.nama, a.nomor_anggota, COUNT(*) AS jumlah_telat
                      FROM peminjaman p
                      JOIN anggota a ON a.id_anggota = p.id_anggota
                      WHERE p.status = 'dikembalikan' AND p.denda > 0
                      GROUP BY p.id_anggota
                      HAVING jumlah_telat >= 2
                      ORDER BY jumlah_telat DESC
                      LIMIT 5");
$sering_telat = $stmt->fetchAll();

// Tren peminjaman 30 hari untuk chart (N4) — konsisten dengan pola query stat di atas (tanpa user input, pakai query langsung)
$chart_raw = $pdo->query("SELECT DATE(tanggal_pinjam) AS tgl, COUNT(*) AS jumlah FROM peminjaman WHERE tanggal_pinjam >= (CURDATE() - INTERVAL 30 DAY) GROUP BY DATE(tanggal_pinjam) ORDER BY tgl")->fetchAll(PDO::FETCH_KEY_PAIR);
$chart_labels = [];
$chart_values = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($d));
    $chart_values[] = (int)($chart_raw[$d] ?? 0);
}

$peringatan_default = false;
try {
    // Cek apakah password masih default admin123 (jangan simpan plaintext, hanya verify)
    // File setup_akun_awal.php sudah dihapus manual — pengecekan file tidak lagi relevan,
    // indikator utama keamanan adalah apakah password default masih ada di database.
    $stmt = $pdo->prepare("SELECT password FROM admin WHERE id_admin = :id");
    $stmt->execute([':id' => $_SESSION['id_admin'] ?? 0]);
    $hash = $stmt->fetchColumn();
    if ($hash && password_verify('admin123', $hash)) {
        $peringatan_default = true;
    }
} catch (Throwable $e) {
    // Abaikan error pengecekan; dashboard tetap tampil
}

$menu_aktif = 'dashboard';
$page_title = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_menu.php';
?>

<?php if ($peringatan_default): ?>
<div class="mb-5 rounded-lg border px-4 py-3.5 flex gap-3 items-start" style="background:#fffbeb; border-color:#fde68a">
  <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center text-sm" style="background:#fef3c7; color:#92400e"><i class="bi bi-shield-exclamation"></i></span>
  <div class="min-w-0 flex-1">
    <h2 class="text-sm font-bold" style="color:#92400e">Password default masih digunakan</h2>
    <p class="text-sm mt-1 leading-6" style="color:#92400e">Akun admin masih memakai <code>admin123</code>. Ganti via <a href="<?= BASE_URL ?>/admin/profil.php" class="font-bold underline">Profil → Keamanan Akun</a>.</p>
  </div>
</div>
<?php endif; ?>

<div class="mb-5 border-b pb-4" style="border-color: var(--border)">
  <p class="text-[11px] font-bold tracking-widest uppercase" style="color: var(--text-faint-2)">Panel Pengelola</p>
  <h1 class="font-display text-[22px] font-bold tracking-tight mt-1" style="color: var(--text)">Dashboard</h1>
  <p class="text-sm mt-1" style="color: var(--text-faint)">Ringkasan operasional perpustakaan hari ini.</p>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
  <div class="bg-white rounded-lg border p-4" style="border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Judul Buku</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color: var(--text)"><?= $total_judul ?></p>
    <p class="text-xs mt-0.5" style="color: var(--text-faint)">koleksi</p>
  </div>
  <div class="bg-white rounded-lg border p-4" style="border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Eksemplar</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color: var(--text)"><?= $total_buku ?></p>
    <p class="text-xs mt-0.5" style="color: var(--text-faint)">total stok</p>
  </div>
  <div class="bg-white rounded-lg border p-4" style="border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Anggota Aktif</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color: var(--text)"><?= $total_anggota ?></p>
    <p class="text-xs mt-0.5" style="color: var(--text-faint)">terdaftar</p>
  </div>
  <div class="bg-white rounded-lg border p-4" style="border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Dipinjam</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color: var(--accent)"><?= $sedang_dipinjam ?></p>
    <p class="text-xs mt-0.5" style="color: var(--text-faint)">sedang dipinjam</p>
  </div>
  <div class="bg-white rounded-lg border p-4" style="border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Terlambat</p>
    <p class="text-[22px] font-bold tracking-tight mt-1 text-red-700"><?= $terlambat ?></p>
    <p class="text-xs mt-0.5" style="color: var(--text-faint)">perlu perhatian</p>
  </div>
  <div class="bg-white rounded-lg border p-4" style="border-color: var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Denda Bulan Ini</p>
    <p class="text-base font-bold tracking-tight mt-1.5" style="color: #065f46"><?= format_rupiah($total_denda_bulan_ini) ?></p>
    <p class="text-xs mt-0.5" style="color: var(--text-faint)">terkumpul</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/perpanjangan/index.php" class="bg-white rounded-lg border p-4 hover:bg-stone-50 transition flex flex-col justify-between" style="border-color: #fde68a; background:#fffbeb">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color:#92400e">Perpanjangan</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color:#92400e"><?= $pending_perpanjangan ?></p>
    <p class="text-xs mt-0.5 font-medium" style="color:#b45309">Perlu ditinjau →</p>
  </a>
  <a href="<?= BASE_URL ?>/admin/pengajuan/index.php" class="bg-white rounded-lg border p-4 hover:bg-slate-50 transition flex flex-col justify-between" style="border-color: var(--accent-soft-2); background: var(--accent-soft)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--accent-text)">Pengajuan Buku</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color: var(--accent)"><?= $pending_pengajuan ?></p>
    <p class="text-xs mt-0.5 font-medium" style="color: var(--accent)">Menunggu →</p>
  </a>
  <a href="<?= BASE_URL ?>/admin/pengajuan_peminjaman/index.php" class="bg-white rounded-lg border p-4 hover:bg-slate-50 transition flex flex-col justify-between" style="border-color:#fde68a; background:#fffbeb">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color:#92400e">Pengajuan Pinjam</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color:#92400e"><?= $pending_pengajuan_pinjam ?></p>
    <p class="text-xs mt-0.5 font-medium" style="color:#b45309">Menunggu →</p>
  </a>
</div>

<div class="mb-5 flex flex-wrap gap-2">
  <a href="<?= BASE_URL ?>/admin/kirim_notifikasi_jatuh_tempo.php" class="inline-flex items-center gap-2 bg-white border hover:bg-slate-50 text-sm font-semibold px-3.5 py-2 rounded-lg" style="border-color: var(--border); color: var(--text-muted)">
    <i class="bi bi-send text-xs"></i> Kirim Notifikasi H-3/H-1
  </a>
  <span class="inline-flex items-center text-xs px-2 py-2" style="color: var(--text-faint-2)">Pengganti cron — trigger manual</span>
</div>

<div class="bg-white rounded-lg border p-4 mb-5" style="border-color: var(--border)">
  <div class="flex items-center justify-between mb-3">
    <h2 class="font-display font-semibold text-sm" style="color: var(--text)">Tren Peminjaman 30 Hari</h2>
    <span class="text-xs" style="color: var(--text-faint-2)">30 hari terakhir</span>
  </div>
  <div class="relative h-56">
    <canvas id="peminjamanChart"></canvas>
  </div>
  <p class="text-xs mt-2.5" style="color: var(--text-faint-2)">Jumlah peminjaman per hari (0 jika tidak ada).</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <div class="bg-white rounded-lg border p-4" style="border-color: var(--border)">
    <h2 class="font-semibold text-sm mb-3" style="color: var(--text)">Stok Perlu Perhatian</h2>
    <?php if (empty($stok_menipis)): ?>
      <p class="text-sm" style="color: var(--text-faint)">Belum ada data.</p>
    <?php else: ?>
      <ul class="divide-y" style="border-color: var(--border-faint)">
        <?php foreach ($stok_menipis as $b): ?>
          <li class="py-2 flex justify-between text-sm" style="border-color: var(--border-faint)">
            <span style="color: var(--text)"><?= e($b['judul']) ?></span>
            <span class="<?= $b['tersedia'] == 0 ? 'text-red-600 font-semibold' : '' ?>" style="<?= $b['tersedia'] == 0 ? '' : 'color: var(--text-faint)' ?>"><?= (int)$b['tersedia'] ?>/<?= (int)$b['stok'] ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-lg border p-4" style="border-color: var(--border)">
    <h2 class="font-semibold text-sm mb-3" style="color: var(--text)">Anggota Sering Terlambat</h2>
    <?php if (empty($sering_telat)): ?>
      <p class="text-sm" style="color: var(--text-faint)">Belum ada keterlambatan berulang.</p>
    <?php else: ?>
      <ul class="divide-y" style="border-color: var(--border-faint)">
        <?php foreach ($sering_telat as $a): ?>
          <li class="py-2 flex justify-between text-sm">
            <span style="color: var(--text)"><?= e($a['nama']) ?> <span style="color: var(--text-faint)">(<?= e($a['nomor_anggota']) ?>)</span></span>
            <span class="font-medium" style="color:#b45309"><?= (int)$a['jumlah_telat'] ?>×</span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function(){
  try {
    if (typeof Chart === 'undefined') return;
    var ctx = document.getElementById('peminjamanChart');
    if (!ctx) return;
    var labels = <?= json_encode($chart_labels, JSON_UNESCAPED_UNICODE) ?>;
    var values = <?= json_encode($chart_values) ?>;
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Peminjaman',
          data: values,
          borderColor: '#243a5e',
          backgroundColor: 'rgba(36,58,94,0.08)',
          tension: 0.3,
          fill: true,
          pointRadius: 2,
          pointHoverRadius: 5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: { ticks: { maxRotation: 45, minRotation: 0, maxTicksLimit: 10 } }
        }
      }
    });
  } catch(e) { console.warn('Chart gagal:', e); }
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
