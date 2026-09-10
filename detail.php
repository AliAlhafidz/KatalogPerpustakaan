<?php
require_once __DIR__ . '/config/bootstrap.php';

$id_buku = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT b.*, k.nama_kategori
                        FROM buku b
                        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
                        WHERE b.id_buku = :id");
$stmt->execute([':id' => $id_buku]);
$buku = $stmt->fetch();

if (!$buku) {
    http_response_code(404);
    $page_title = 'Buku Tidak Ditemukan';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="text-center py-20"><p class="text-5xl mb-3 text-gray-300"><i class="bi bi-search"></i></p><p class="text-gray-500 mb-4">Buku yang kamu cari tidak ditemukan.</p>
          <a href="' . BASE_URL . '/index.php" class="text-brand-600 font-medium hover:underline">Kembali ke Katalog</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$sudah_favorit = false;
$pengajuan_menunggu = false;
$has_active_loan = false;
$can_ajukan = false;
$pengajuan_menunggu_id = null;
if (is_anggota()) {
    $sudah_favorit = is_favorit($pdo, $_SESSION['id_anggota'], $id_buku);
    $id_anggota_cur = (int)($_SESSION['id_anggota'] ?? 0);
    try {
        // cek pengajuan menunggu untuk buku yang sama
        $stmt = $pdo->prepare("SELECT id_pengajuan FROM pengajuan_peminjaman WHERE id_anggota = :anggota AND id_buku = :buku AND status = 'menunggu' LIMIT 1");
        $stmt->execute([':anggota' => $id_anggota_cur, ':buku' => $id_buku]);
        $row_wait = $stmt->fetch();
        if ($row_wait) {
            $pengajuan_menunggu = true;
            $pengajuan_menunggu_id = (int)$row_wait['id_pengajuan'];
        }
        // cek peminjaman aktif untuk buku yang sama
        $stmt = $pdo->prepare("SELECT id_peminjaman FROM peminjaman WHERE id_anggota = :anggota AND id_buku = :buku AND status = 'dipinjam' LIMIT 1");
        $stmt->execute([':anggota' => $id_anggota_cur, ':buku' => $id_buku]);
        if ($stmt->fetch()) {
            $has_active_loan = true;
        }
        // tentukan apakah boleh ajukan
        $is_arsip_buku = !empty($buku['is_arsip']);
        $tersedia = (int)($buku['tersedia'] ?? 0);
        if (!$is_arsip_buku && $tersedia > 0 && !$pengajuan_menunggu && !$has_active_loan) {
            $can_ajukan = true;
        }
    } catch (Throwable $e) {
        // tabel pengajuan_peminjaman belum ada (migrasi belum dijalankan) -> fallback aman
        $can_ajukan = false;
        error_log('detail pengajuan check gagal: ' . $e->getMessage());
    }
}

$page_title = $buku['judul'];
require_once __DIR__ . '/includes/header.php';
?>

<nav class="text-xs mb-4 flex items-center gap-1.5" style="color: var(--text-faint)">
  <a href="<?= BASE_URL ?>/index.php" class="hover:underline" style="color: var(--text-faint)">Katalog</a>
  <span class="opacity-40">/</span>
  <span class="truncate" style="color: var(--text-muted)"><?= e($buku['judul']) ?></span>
</nav>

<div class="bg-white rounded-xl border p-5 sm:p-6 grid grid-cols-1 md:grid-cols-[280px_1fr] gap-6 sm:gap-8" style="border-color: var(--border)">
  <div class="md:col-span-1">
    <div class="aspect-[3/4] rounded-lg overflow-hidden border" style="background: var(--paper-2); border-color: var(--border)">
      <img src="<?= e(cover_url($buku['cover'])) ?>" alt="Cover <?= e($buku['judul']) ?>" class="w-full h-full object-cover">
    </div>
    <p class="text-xs text-center mt-2" style="color: var(--text-faint)">Cover buku · Rasio 3:4</p>
  </div>

  <div class="md:col-span-1 min-w-0">
    <p class="text-[11px] font-bold tracking-widest uppercase mb-1.5" style="color: var(--text-faint-2)"><?= e($buku['nama_kategori'] ?? 'Tanpa Kategori') ?></p>
    <h1 class="font-display text-[22px] sm:text-[26px] font-bold leading-tight mb-1.5" style="color: var(--text)"><?= e($buku['judul']) ?></h1>
    <p class="text-sm mb-4" style="color: var(--text-muted)">oleh <span class="font-semibold" style="color: var(--text)"><?= e($buku['penulis']) ?></span></p>

    <?php if (!empty($buku['is_arsip'])): ?>
      <div class="mb-4 rounded-lg border px-3.5 py-3 text-sm flex items-start gap-2" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)">
        <i class="bi bi-archive text-base mt-0.5"></i>
        <div><span class="font-bold">Diarsipkan</span> — tidak dapat dipinjam. Hubungi petugas.</div>
      </div>
    <?php endif; ?>

    <div class="mb-5 flex flex-wrap items-center gap-2">
      <?= badge_ketersediaan((int)$buku['tersedia'], !empty($buku['is_arsip'])) ?>
      <?php if (is_anggota()): ?>
        <form method="post" action="<?= BASE_URL ?>/anggota/toggle_favorit.php">
      <?= csrf_field() ?>
          <input type="hidden" name="id_buku" value="<?= (int)$buku['id_buku'] ?>">
          <input type="hidden" name="redirect" value="detail">
          <button type="submit"
            class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full border transition
            <?= $sudah_favorit ? 'bg-red-50 border-red-200 text-red-700' : 'bg-white hover:bg-slate-50' ?>" style="<?= $sudah_favorit ? '' : 'border-color: var(--border); color: var(--text-muted)' ?>">
            <?= $sudah_favorit ? '<i class="bi bi-heart-fill"></i> Difavoritkan' : '<i class="bi bi-heart"></i> Favorit' ?>
          </button>
        </form>
      <?php else: ?>
        <button type="button" onclick="showNoticeModal('Silakan login sebagai anggota untuk menambah buku ke Favorit.')"
            class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full border bg-white hover:bg-slate-50 transition" style="border-color: var(--border); color: var(--text-muted)">
            <i class="bi bi-heart"></i> Favorit
        </button>
      <?php endif; ?>
    </div>

    <div class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm mb-6 border-y py-4" style="border-color: var(--border-faint)">
      <div>
        <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Kode Buku</p>
        <p class="font-medium mt-0.5" style="color: var(--text)"><?= e($buku['kode_buku']) ?></p>
      </div>
      <div>
        <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">ISBN</p>
        <p class="font-medium mt-0.5" style="color: var(--text)"><?= e($buku['isbn'] ?: '—') ?></p>
      </div>
      <div>
        <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Penerbit</p>
        <p class="font-medium mt-0.5" style="color: var(--text)"><?= e($buku['penerbit'] ?: '—') ?></p>
      </div>
      <div>
        <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Tahun Terbit</p>
        <p class="font-medium mt-0.5" style="color: var(--text)"><?= e($buku['tahun_terbit'] ?: '—') ?></p>
      </div>
      <div>
        <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Stok Total</p>
        <p class="font-medium mt-0.5" style="color: var(--text)"><?= (int)$buku['stok'] ?> eks</p>
      </div>
      <div>
        <p class="text-[11px] font-bold uppercase tracking-wide" style="color: var(--text-faint-2)">Lokasi / Rak</p>
        <p class="font-semibold mt-0.5 flex items-center gap-1" style="color: var(--accent)">
          <i class="bi bi-geo-alt text-xs"></i>
          <?= e($buku['lokasi_rak'] ?: 'Belum ditentukan') ?>
        </p>
      </div>
    </div>

    <div class="mb-6">
      <p class="text-[11px] font-bold uppercase tracking-wide mb-2" style="color: var(--text-faint-2)">Deskripsi</p>
      <p class="text-sm leading-7" style="color: var(--text-muted)"><?= nl2br(e($buku['deskripsi'] ?: 'Belum ada deskripsi untuk buku ini.')) ?></p>
    </div>

    <?php if (!is_login()): ?>
      <div class="border rounded-xl p-4" style="background: var(--accent-soft); border-color: var(--accent-soft-2); color: var(--accent-text)">
        <div class="flex gap-3">
          <span class="w-9 h-9 rounded-xl bg-white border flex items-center justify-center shrink-0" style="border-color: var(--accent-soft-2); color: var(--accent-text)"><i class="bi bi-box-arrow-in-right"></i></span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold" style="color: var(--accent-text)">Ingin meminjam?</p>
            <p class="text-sm mt-1 leading-6" style="color: var(--accent-text)">Silakan masuk sebagai anggota untuk mengajukan peminjaman buku ini.</p>
            <a href="<?= BASE_URL ?>/login.php" class="inline-flex items-center gap-2 mt-3 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold transition">Masuk untuk Meminjam <i class="bi bi-arrow-right text-xs"></i></a>
          </div>
        </div>
      </div>
    <?php elseif (is_anggota()): ?>
      <?php if (!empty($buku['is_arsip'])): ?>
        <div class="border rounded-xl p-4 flex gap-3" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)">
          <span class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0" style="background: var(--surface); border-color: var(--badge-amber-border); color: var(--badge-amber-text)"><i class="bi bi-archive"></i></span>
          <div class="min-w-0">
            <p class="text-sm font-bold">Buku Diarsipkan</p>
            <p class="text-sm mt-1 leading-6">Buku ini sedang diarsipkan dan tidak dapat diajukan untuk dipinjam. Hubungi petugas untuk informasi lebih lanjut.</p>
          </div>
        </div>
      <?php elseif ($has_active_loan): ?>
        <div class="border rounded-xl p-4 flex gap-3" style="background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)">
          <span class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0" style="background: var(--surface); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)"><i class="bi bi-check2-circle"></i></span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold">Sedang Kamu Pinjam</p>
            <p class="text-sm mt-1 leading-6">Kamu sedang meminjam buku ini. Kembalikan tepat waktu untuk menghindari denda.</p>
            <a href="<?= BASE_URL ?>/anggota/peminjaman.php" class="inline-flex items-center gap-1.5 mt-3 text-sm font-bold underline" style="color: var(--badge-emerald-text)">Lihat Peminjaman Saya <i class="bi bi-arrow-right text-xs"></i></a>
          </div>
        </div>
      <?php elseif ($pengajuan_menunggu): ?>
        <div class="border rounded-xl p-4 flex gap-3" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)">
          <span class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0" style="background: var(--surface); border-color: var(--badge-amber-border); color: var(--badge-amber-text)"><i class="bi bi-hourglass-split"></i></span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold">Pengajuan Sedang Menunggu Persetujuan</p>
            <p class="text-sm mt-1 leading-6">Pengajuan peminjaman untuk buku ini sedang ditinjau admin. Kamu akan mendapat notifikasi saat diproses.</p>
            <a href="<?= BASE_URL ?>/anggota/pengajuan_peminjaman.php" class="inline-flex items-center gap-1.5 mt-3 text-sm font-bold underline" style="color: var(--badge-amber-text)">Lihat Riwayat Pengajuan <i class="bi bi-arrow-right text-xs"></i></a>
          </div>
        </div>
      <?php elseif ((int)$buku['tersedia'] <= 0): ?>
        <div class="border rounded-xl p-4 flex gap-3" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)">
          <span class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0" style="background: var(--surface); border-color: var(--badge-red-border); color: var(--badge-red-text)"><i class="bi bi-x-circle"></i></span>
          <div class="min-w-0">
            <p class="text-sm font-bold">Buku Sedang Tidak Tersedia</p>
            <p class="text-sm mt-1 leading-6">Stok tersedia saat ini habis. Silakan cek kembali nanti atau hubungi petugas.</p>
          </div>
        </div>
      <?php elseif ($can_ajukan): ?>
        <div class="border rounded-xl p-4 sm:p-5" style="background: var(--accent-soft); border-color: var(--accent-soft-2)">
          <div class="flex gap-3 mb-3">
            <span class="w-9 h-9 rounded-xl bg-white border flex items-center justify-center shrink-0" style="border-color: var(--accent-soft-2); color: var(--accent-text)"><i class="bi bi-journal-arrow-up"></i></span>
            <div class="min-w-0">
              <h3 class="text-sm font-bold" style="color: var(--accent-text)">Ajukan Peminjaman</h3>
              <p class="text-xs mt-1 leading-5" style="color: var(--accent-text)">Stok tersedia: <b><?= (int)$buku['tersedia'] ?></b> · Admin akan meninjau pengajuanmu (<?= LAMA_PINJAM_HARI ?> hari peminjaman).</p>
            </div>
          </div>
          <form method="post" action="<?= BASE_URL ?>/anggota/ajukan_peminjaman.php" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="id_buku" value="<?= (int)$buku['id_buku'] ?>">
            <div>
              <label for="catatan_anggota_detail" class="block text-xs font-semibold mb-1" style="color: var(--accent-text)">Catatan untuk admin (opsional)</label>
              <textarea id="catatan_anggota_detail" name="catatan_anggota" rows="2" maxlength="500" placeholder="Contoh: Untuk tugas kuliah minggu ini..." class="w-full border rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color: var(--accent-soft-2); background: var(--surface); color: var(--text)"></textarea>
              <p class="text-[11px] mt-1" style="color: var(--text-faint)">Maks 500 karakter.</p>
            </div>
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold transition">
              <i class="bi bi-send"></i> Ajukan Peminjaman
            </button>
            <p class="text-xs text-center" style="color: var(--accent-text)">Dengan mengajukan, kamu menyetujui untuk mengambil buku di perpustakaan jika disetujui.</p>
          </form>
          <div class="mt-3 pt-3 border-t flex items-center justify-between gap-2" style="border-color: var(--accent-soft-2)">
            <span class="text-[11px] font-semibold" style="color: var(--text-faint)">Butuh bantuan?</span>
            <a href="<?= BASE_URL ?>/anggota/pengajuan_peminjaman.php" class="text-xs font-bold hover:underline" style="color: var(--accent-text)">Riwayat Pengajuan →</a>
          </div>
        </div>
      <?php else: ?>
        <div class="border rounded-lg p-3.5 text-sm" style="background: var(--accent-soft); border-color: var(--accent-soft-2); color: var(--accent-text)">
          Datang ke meja layanan dengan kartu anggota untuk meminjam buku ini.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
