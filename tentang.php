<?php
require_once __DIR__ . '/config/bootstrap.php';

$page_title = 'Tentang Perpustakaan';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white rounded-xl border p-6 sm:p-8 max-w-3xl mx-auto" style="border-color: var(--border)">
  <p class="text-[11px] font-bold tracking-widest uppercase mb-2" style="color: var(--text-faint-2)">Tentang</p>
  <h1 class="font-display text-[22px] sm:text-[24px] font-bold mb-3" style="color: var(--text)">Tentang Perpustakaan Umum Sejahtera</h1>
  <p class="text-sm leading-7 mb-6" style="color: var(--text-muted)">
    Perpustakaan Umum Sejahtera adalah perpustakaan umum yang melayani masyarakat dalam
    mengakses berbagai koleksi buku, mulai dari fiksi, non-fiksi, sains, sejarah, hingga
    bacaan anak dan remaja. Kami berkomitmen untuk mendukung budaya membaca dan literasi
    di lingkungan sekitar.
  </p>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="border rounded-lg p-4" style="border-color: var(--border); background: var(--surface-2)">
      <h2 class="font-semibold text-sm mb-2 flex items-center gap-2" style="color: var(--text)"><i class="bi bi-clock" style="color: var(--accent)"></i> Jam Layanan</h2>
      <ul class="text-sm space-y-1" style="color: var(--text-muted)">
        <li>Senin – Jumat: 08.00 – 17.00 WIB</li>
        <li>Sabtu: 09.00 – 14.00 WIB</li>
        <li>Minggu &amp; Hari Libur: Tutup</li>
      </ul>
    </div>
    <div class="border rounded-lg p-4" style="border-color: var(--border); background: var(--surface-2)">
      <h2 class="font-semibold text-sm mb-2 flex items-center gap-2" style="color: var(--text)"><i class="bi bi-geo-alt" style="color: var(--accent)"></i> Alamat &amp; Kontak</h2>
      <ul class="text-sm space-y-1" style="color: var(--text-muted)">
        <li>Jl. Pendidikan No. 5, Kotamu</li>
        <li>Telepon: (021) 555-0123</li>
        <li>Email: perpusumum@contoh.id</li>
      </ul>
    </div>
  </div>

  <div class="border rounded-lg p-4 mb-6" style="border-color: var(--border); background: var(--surface-2)">
    <h2 class="font-semibold text-sm mb-2 flex items-center gap-2" style="color: var(--text)"><i class="bi bi-journal-text" style="color: var(--accent)"></i> Ketentuan Peminjaman</h2>
    <ul class="text-sm space-y-1.5 list-disc list-inside" style="color: var(--text-muted)">
      <li>Peminjaman hanya dapat dilakukan oleh anggota terdaftar.</li>
      <li>Lama peminjaman maksimal <?= LAMA_PINJAM_HARI ?> hari sejak tanggal peminjaman.</li>
      <li>Keterlambatan dikenakan denda sebesar <?= format_rupiah(DENDA_PER_HARI) ?> per hari.</li>
      <li>Pengembalian tidak dilayani pada hari Minggu dan hari libur.</li>
    </ul>
  </div>

  <a href="<?= BASE_URL ?>/index.php" class="text-sm font-medium hover:underline" style="color: var(--accent)">&larr; Kembali ke Katalog</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
