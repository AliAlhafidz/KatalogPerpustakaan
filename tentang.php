<?php
require_once __DIR__ . '/config/bootstrap.php';

$page_title = 'Tentang Kandaga Bandung';

$total_buku = 0;
$total_kategori = 0;
$total_anggota = 0;
try {
    $total_buku = (int)$pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
    $total_kategori = (int)$pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
    $total_anggota = (int)$pdo->query("SELECT COUNT(*) FROM anggota WHERE status = 'aktif'")->fetchColumn();
} catch (Throwable $e) {
    // fallback senyap
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
  <!-- Header Card -->
  <div class="bg-white rounded-xl border p-6 sm:p-8" style="border-color: var(--border)">
    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold mb-3" style="background: var(--surface-2); color: var(--accent)">
      <i class="bi bi-building"></i> Perpustakaan Kota Bandung
    </div>
    <h1 class="font-display text-2xl sm:text-3xl font-bold mb-3" style="color: var(--text)">Kandaga Bandung</h1>
    <p class="text-sm sm:text-base leading-relaxed mb-6" style="color: var(--text-muted)">
      <strong>Kandaga Bandung</strong> merupakan sistem informasi perpustakaan Perpustakaan Kota Bandung. Terinspirasi dari kata <em>Kandaga</em> dalam bahasa Sunda yang bermakna wadah penyimpanan pustaka dan ilmu pengetahuan berharga, sistem ini hadir menyediakan akses literatur yang inklusif, modern, dan terbuka bagi seluruh masyarakat Kota Bandung. Berlokasi di Jl. Seram No. 2, Citarum, perpustakaan ini menghadirkan ribuan koleksi bahan pustaka bermutu guna menunjang pendidikan, penelitian, dan rekreasi edukatif yang dapat diakses serta dipinjam secara terpadu melalui sistem ini.
    </p>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="border rounded-lg p-3 text-center" style="border-color: var(--border); background: var(--surface-2)">
        <div class="text-xl sm:text-2xl font-bold font-display" style="color: var(--text)"><?= number_format($total_buku) ?></div>
        <div class="text-[12px] font-medium" style="color: var(--text-muted)">Judul Buku</div>
      </div>
      <div class="border rounded-lg p-3 text-center" style="border-color: var(--border); background: var(--surface-2)">
        <div class="text-xl sm:text-2xl font-bold font-display" style="color: var(--text)"><?= number_format($total_kategori) ?></div>
        <div class="text-[12px] font-medium" style="color: var(--text-muted)">Kategori Koleksi</div>
      </div>
      <div class="border rounded-lg p-3 text-center" style="border-color: var(--border); background: var(--surface-2)">
        <div class="text-xl sm:text-2xl font-bold font-display" style="color: var(--text)"><?= LAMA_PINJAM_HARI ?> Hari</div>
        <div class="text-[12px] font-medium" style="color: var(--text-muted)">Batas Pinjam</div>
      </div>
      <div class="border rounded-lg p-3 text-center" style="border-color: var(--border); background: var(--surface-2)">
        <div class="text-xl sm:text-2xl font-bold font-display" style="color: var(--text)"><?= format_rupiah(DENDA_PER_HARI) ?></div>
        <div class="text-[12px] font-medium" style="color: var(--text-muted)">Denda / Hari</div>
      </div>
    </div>
  </div>

  <!-- Visi & Misi -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border p-6" style="border-color: var(--border)">
      <div class="flex items-center gap-2 mb-3">
        <i class="bi bi-compass text-lg" style="color: var(--accent)"></i>
        <h2 class="font-display text-lg font-bold" style="color: var(--text)">Visi</h2>
      </div>
      <p class="text-sm leading-relaxed" style="color: var(--text-muted)">
        &ldquo;Terwujudnya Peningkatan Minat dan Budaya Baca Masyarakat Melalui Layanan Perpustakaan yang Modern, Inklusif, dan Unggul.&rdquo;
      </p>
      <p class="text-xs leading-relaxed mt-2" style="color: var(--text-muted)">
        Landasan visi layanan perpustakaan dalam memperluas literasi dan kemudahan akses informasi bagi seluruh warga.
      </p>
    </div>

    <div class="bg-white rounded-xl border p-6" style="border-color: var(--border)">
      <div class="flex items-center gap-2 mb-3">
        <i class="bi bi-award text-lg" style="color: var(--accent)"></i>
        <h2 class="font-display text-lg font-bold" style="color: var(--text)">Misi</h2>
      </div>
      <ul class="text-sm space-y-2.5 leading-relaxed" style="color: var(--text-muted)">
        <li class="flex items-start gap-2.5">
          <i class="bi bi-check2 text-emerald-600 font-bold mt-1 shrink-0"></i>
          <span>Mewujudkan peningkatan minat dan budaya baca masyarakat melalui penyediaan bahan pustaka berkualitas dan inklusif.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-check2 text-emerald-600 font-bold mt-1 shrink-0"></i>
          <span>Meningkatkan mutu pelayanan perpustakaan umum yang prima, ramah, cepat, dan berbasis teknologi informasi.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-check2 text-emerald-600 font-bold mt-1 shrink-0"></i>
          <span>Menyediakan dan memelihara koleksi buku serta bahan bacaan yang relevan, mutakhir, dan bermanfaat secara berkelanjutan.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-check2 text-emerald-600 font-bold mt-1 shrink-0"></i>
          <span>Mewujudkan kinerja kelembagaan perpustakaan yang akuntabel, transparan, dan berorientasi pada kepuasan pemustaka.</span>
        </li>
      </ul>
    </div>
  </div>

  <!-- Alur Peminjaman Buku -->
  <div class="bg-white rounded-xl border p-6 sm:p-8" style="border-color: var(--border)">
    <div class="flex items-center gap-2 mb-4">
      <i class="bi bi-diagram-3 text-lg" style="color: var(--accent)"></i>
      <h2 class="font-display text-lg sm:text-xl font-bold" style="color: var(--text)">Alur Layanan Peminjaman</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
      <div class="border rounded-lg p-4 relative" style="border-color: var(--border); background: var(--surface-2)">
        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded bg-brand-600 text-white mb-2">Langkah 1</span>
        <h3 class="font-semibold text-sm mb-1" style="color: var(--text)">Daftar / Masuk</h3>
        <p class="text-xs leading-relaxed" style="color: var(--text-muted)">Pemustaka melakukan registrasi akun anggota atau login ke sistem perpustakaan.</p>
      </div>

      <div class="border rounded-lg p-4 relative" style="border-color: var(--border); background: var(--surface-2)">
        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded bg-brand-600 text-white mb-2">Langkah 2</span>
        <h3 class="font-semibold text-sm mb-1" style="color: var(--text)">Pilih Buku</h3>
        <p class="text-xs leading-relaxed" style="color: var(--text-muted)">Cari buku di katalog digital, periksa nomor rak, sinopsis, dan ketersediaan stok fisik.</p>
      </div>

      <div class="border rounded-lg p-4 relative" style="border-color: var(--border); background: var(--surface-2)">
        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded bg-brand-600 text-white mb-2">Langkah 3</span>
        <h3 class="font-semibold text-sm mb-1" style="color: var(--text)">Sirkulasi &amp; Verifikasi</h3>
        <p class="text-xs leading-relaxed" style="color: var(--text-muted)">Kunjungi meja sirkulasi Perpustakaan Kota Bandung dan tunjukkan kartu anggota untuk verifikasi peminjaman.</p>
      </div>

      <div class="border rounded-lg p-4 relative" style="border-color: var(--border); background: var(--surface-2)">
        <span class="inline-block text-xs font-bold px-2 py-0.5 rounded bg-brand-600 text-white mb-2">Langkah 4</span>
        <h3 class="font-semibold text-sm mb-1" style="color: var(--text)">Pengembalian</h3>
        <p class="text-xs leading-relaxed" style="color: var(--text-muted)">Kembalikan buku sebelum <?= LAMA_PINJAM_HARI ?> hari atau ajukan perpanjangan online jika masih berlaku.</p>
      </div>
    </div>
  </div>

  <!-- Ketentuan & Tata Tertib -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border p-6" style="border-color: var(--border)">
      <div class="flex items-center gap-2 mb-3">
        <i class="bi bi-file-earmark-check text-lg" style="color: var(--accent)"></i>
        <h2 class="font-display text-lg font-bold" style="color: var(--text)">Ketentuan Peminjaman</h2>
      </div>
      <ul class="text-sm space-y-2.5 leading-relaxed" style="color: var(--text-muted)">
        <li class="flex items-start gap-2.5">
          <i class="bi bi-chevron-right text-xs mt-1 shrink-0 font-bold" style="color: var(--accent)"></i>
          <span>Hanya anggota aktif dengan status terverifikasi yang dapat meminjam.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-chevron-right text-xs mt-1 shrink-0 font-bold" style="color: var(--accent)"></i>
          <span>Durasi pinjam standar adalah <strong><?= LAMA_PINJAM_HARI ?> hari kalender</strong> sejak peminjaman.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-chevron-right text-xs mt-1 shrink-0 font-bold" style="color: var(--accent)"></i>
          <span>Perpanjangan mandiri dapat diajukan maksimal <strong><?= PERPANJANGAN_MAKS_HARI ?> hari tambahan</strong> sebelum jatuh tempo.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-chevron-right text-xs mt-1 shrink-0 font-bold" style="color: var(--accent)"></i>
          <span>Pengingat jatuh tempo dikirim otomatis mulai <strong><?= NOTIFIKASI_JATUH_TEMPO_HARI ?> hari</strong> sebelum batas waktu.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-chevron-right text-xs mt-1 shrink-0 font-bold" style="color: var(--accent)"></i>
          <span>Keterlambatan dikenakan denda sebesar <strong><?= format_rupiah(DENDA_PER_HARI) ?></strong> per hari per buku.</span>
        </li>
      </ul>
    </div>

    <div class="bg-white rounded-xl border p-6" style="border-color: var(--border)">
      <div class="flex items-center gap-2 mb-3">
        <i class="bi bi-shield-check text-lg" style="color: var(--accent)"></i>
        <h2 class="font-display text-lg font-bold" style="color: var(--text)">Tata Tertib Ruang Baca</h2>
      </div>
      <ul class="text-sm space-y-2.5 leading-relaxed" style="color: var(--text-muted)">
        <li class="flex items-start gap-2.5">
          <i class="bi bi-shield-check text-xs mt-1 shrink-0" style="color: var(--accent)"></i>
          <span>Wajib menjaga ketenangan, ketertiban, dan kebersihan di seluruh lingkungan Perpustakaan Kota Bandung.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-shield-check text-xs mt-1 shrink-0" style="color: var(--accent)"></i>
          <span>Dilarang melipat halaman, mencoret, atau merusak koleksi buku perpustakaan.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-shield-check text-xs mt-1 shrink-0" style="color: var(--accent)"></i>
          <span>Buku yang selesai dibaca di tempat harap diletakkan di meja pengembalian buku (bukan dikembalikan ke rak sembarangan).</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-shield-check text-xs mt-1 shrink-0" style="color: var(--accent)"></i>
          <span>Ponsel diatur dalam mode senyap (silent) selama berada di area ruang baca.</span>
        </li>
        <li class="flex items-start gap-2.5">
          <i class="bi bi-shield-check text-xs mt-1 shrink-0" style="color: var(--accent)"></i>
          <span>Kehilangan atau kerusakan buku menjadi tanggung jawab peminjam untuk diganti dengan edisi yang setara.</span>
        </li>
      </ul>
    </div>
  </div>

  <!-- Fasilitas & Jam Layanan / Kontak -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border p-6" style="border-color: var(--border)">
      <div class="flex items-center gap-2 mb-3">
        <i class="bi bi-building-check text-lg" style="color: var(--accent)"></i>
        <h2 class="font-display text-lg font-bold" style="color: var(--text)">Fasilitas Perpustakaan</h2>
      </div>
      <div class="grid grid-cols-2 gap-2 text-sm" style="color: var(--text-muted)">
        <div class="flex items-center gap-2"><i class="bi bi-check2-circle text-emerald-600"></i> Ruang Baca Ber-AC</div>
        <div class="flex items-center gap-2"><i class="bi bi-check2-circle text-emerald-600"></i> Akses Wi-Fi Gratis</div>
        <div class="flex items-center gap-2"><i class="bi bi-check2-circle text-emerald-600"></i> Katalog Online (OPAC)</div>
        <div class="flex items-center gap-2"><i class="bi bi-check2-circle text-emerald-600"></i> Area Diskusi</div>
        <div class="flex items-center gap-2"><i class="bi bi-check2-circle text-emerald-600"></i> Stopkontak &amp; Meja Belajar</div>
        <div class="flex items-center gap-2"><i class="bi bi-check2-circle text-emerald-600"></i> Loker Penitipan Barang</div>
      </div>
    </div>

    <div class="bg-white rounded-xl border p-6" style="border-color: var(--border)">
      <div class="flex items-center gap-2 mb-3">
        <i class="bi bi-clock-history text-lg" style="color: var(--accent)"></i>
        <h2 class="font-display text-lg font-bold" style="color: var(--text)">Jam Layanan &amp; Kontak</h2>
      </div>
      <div class="space-y-3 text-sm" style="color: var(--text-muted)">
        <div>
          <div class="font-semibold text-xs uppercase tracking-wider mb-1" style="color: var(--text)">Jadwal Buka:</div>
          <div class="flex justify-between py-0.5 border-b" style="border-color: var(--border)">
            <span>Senin &ndash; Kamis:</span>
            <span class="font-medium" style="color: var(--text)">08.00 &ndash; 15.30 WIB</span>
          </div>
          <div class="flex justify-between py-0.5 border-b" style="border-color: var(--border)">
            <span>Jumat:</span>
            <span class="font-medium" style="color: var(--text)">08.00 &ndash; 15.30 WIB <span class="text-xs text-stone-400 font-normal">(Istirahat 11.30–13.00)</span></span>
          </div>
          <div class="flex justify-between py-0.5 border-b" style="border-color: var(--border)">
            <span>Sabtu:</span>
            <span class="font-medium" style="color: var(--text)">08.00 &ndash; 12.00 WIB</span>
          </div>
          <div class="flex justify-between py-0.5">
            <span>Minggu &amp; Libur Nasional:</span>
            <span class="font-medium text-rose-600">Tutup</span>
          </div>
        </div>

        <div class="pt-2 border-t" style="border-color: var(--border)">
          <div class="font-semibold text-xs uppercase tracking-wider mb-1" style="color: var(--text)">Alamat &amp; Kontak Resmi:</div>
          <p class="leading-relaxed"><i class="bi bi-geo-alt me-1.5" style="color: var(--accent)"></i> Jl. Seram No. 2, Citarum, Kec. Bandung Wetan, Kota Bandung, Jawa Barat 40115 <span class="text-xs text-stone-400 block ml-5">(Sebelah Kantor Disdukcapil Kota Bandung)</span></p>
          <p><i class="bi bi-telephone me-1.5" style="color: var(--accent)"></i> (022) 423-1988 / (022) 731-8609</p>
          <p><i class="bi bi-envelope me-1.5" style="color: var(--accent)"></i> layanan@kandaga-bandung.id</p>
        </div>
      </div>
    </div>
  </div>

  <!-- FAQ Lengkap Interaktif -->
  <div class="bg-white rounded-xl border p-6 sm:p-8" style="border-color: var(--border)">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
      <div class="flex items-center gap-2">
        <i class="bi bi-question-circle-fill text-xl" style="color: var(--accent)"></i>
        <div>
          <h2 class="font-display text-lg sm:text-xl font-bold" style="color: var(--text)">Pusat Bantuan &amp; FAQ</h2>
          <p class="text-xs" style="color: var(--text-muted)">Klik pertanyaan untuk membuka dan membaca penjelasan lengkap.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" onclick="document.querySelectorAll('.faq-accordion details').forEach(d => d.open = true)" class="px-2.5 py-1 text-xs rounded font-medium border hover:bg-slate-50 transition" style="border-color: var(--border); color: var(--text)">Buka Semua</button>
        <button type="button" onclick="document.querySelectorAll('.faq-accordion details').forEach(d => d.open = false)" class="px-2.5 py-1 text-xs rounded font-medium border hover:bg-slate-50 transition" style="border-color: var(--border); color: var(--text)">Tutup Semua</button>
      </div>
    </div>

    <div class="faq-accordion space-y-6">
      <!-- Bagian 1: Keanggotaan & Akun -->
      <div>
        <div class="flex items-center gap-2 mb-3">
          <i class="bi bi-person-badge text-sm" style="color: var(--accent)"></i>
          <h3 class="text-xs font-bold uppercase tracking-wider" style="color: var(--text)">1. Keanggotaan &amp; Akun</h3>
        </div>
        <div class="space-y-2.5">
          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Bagaimana cara mendaftar menjadi anggota perpustakaan?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Pendaftaran dapat dilakukan secara mandiri lewat formulir online pada menu <strong>Daftar</strong> di halaman utama. Isi nama lengkap, alamat email aktif, nomor telepon, alamat domisili, dan buat kata sandi aman. Setelah terdaftar, akun Anda dapat langsung digunakan untuk menjelajahi layanan anggota.
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Apakah pendaftaran keanggotaan dipungut biaya?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Tidak ada biaya sama sekali. Pembuatan akun, penelusuran katalog digital, dan peminjaman buku fisik sepenuhnya gratis untuk seluruh anggota masyarakat yang memenuhi ketentuan.
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Bagaimana jika mengalami kendala login atau lupa kata sandi?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Silakan hubungi petugas layanan di gedung Perpustakaan Kota Bandung (Jl. Seram No. 2) atau melalui email layanan@kandaga-bandung.id dengan menunjukkan kartu identitas untuk verifikasi kepemilikan akun dan pembaruan kata sandi.
            </div>
          </details>
        </div>
      </div>

      <!-- Bagian 2: Peminjaman & Pengembalian -->
      <div>
        <div class="flex items-center gap-2 mb-3">
          <i class="bi bi-journal-bookmark text-sm" style="color: var(--accent)"></i>
          <h3 class="text-xs font-bold uppercase tracking-wider" style="color: var(--text)">2. Peminjaman &amp; Pengembalian</h3>
        </div>
        <div class="space-y-2.5">
          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Berapa lama batas waktu peminjaman buku?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Batas waktu peminjaman standar adalah <strong><?= LAMA_PINJAM_HARI ?> hari kalender</strong> terhitung mulai tanggal peminjaman buku diverifikasi oleh sistem sirkulasi.
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Bagaimana langkah meminjam buku fisik di perpustakaan?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t space-y-2" style="border-color: var(--border); color: var(--text-muted)">
              <div class="flex items-start gap-2">
                <span class="font-bold text-xs px-1.5 py-0.5 rounded bg-brand-100 text-brand-800">1</span>
                <span>Cari buku di katalog online untuk memeriksa letak rak dan ketersediaan stok fisik.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="font-bold text-xs px-1.5 py-0.5 rounded bg-brand-100 text-brand-800">2</span>
                <span>Ambil buku dari rak koleksi sesuai kode rak yang tertera pada detail buku.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="font-bold text-xs px-1.5 py-0.5 rounded bg-brand-100 text-brand-800">3</span>
                <span>Bawa buku ke meja layanan sirkulasi Perpustakaan Kota Bandung dan tunjukkan kartu identitas anggota untuk dipindai.</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="font-bold text-xs px-1.5 py-0.5 rounded bg-brand-100 text-brand-800">4</span>
                <span>Petugas memproses peminjaman dan tanggal jatuh tempo akan tercatat otomatis di dashboard Anda.</span>
              </div>
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Bagaimana cara memperpanjang masa peminjaman?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Perpanjangan dapat diajukan secara online melalui dashboard anggota. Buka daftar pinjaman aktif, lalu klik tombol <em>"Ajukan Perpanjangan"</em> sebelum tanggal jatuh tempo tiba. Setiap buku dapat diperpanjang maksimal hingga <strong><?= PERPANJANGAN_MAKS_HARI ?> hari tambahan</strong>.
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Kapan perpanjangan tidak dapat diajukan?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t space-y-1.5" style="border-color: var(--border); color: var(--text-muted)">
              <p>Pengajuan perpanjangan otomatis tidak dapat diproses jika:</p>
              <div class="flex items-start gap-2">
                <i class="bi bi-x-circle text-rose-500 mt-0.5 shrink-0"></i>
                <span>Buku sudah melewati tanggal jatuh tempo (status terlambat).</span>
              </div>
              <div class="flex items-start gap-2">
                <i class="bi bi-x-circle text-rose-500 mt-0.5 shrink-0"></i>
                <span>Batas maksimal kuota perpanjangan telah habis digunakan.</span>
              </div>
              <div class="flex items-start gap-2">
                <i class="bi bi-x-circle text-rose-500 mt-0.5 shrink-0"></i>
                <span>Anggota masih memiliki tanggungan denda yang belum terselesaikan.</span>
              </div>
            </div>
          </details>
        </div>
      </div>

      <!-- Bagian 3: Denda & Sanksi -->
      <div>
        <div class="flex items-center gap-2 mb-3">
          <i class="bi bi-cash-coin text-sm" style="color: var(--accent)"></i>
          <h3 class="text-xs font-bold uppercase tracking-wider" style="color: var(--text)">3. Denda &amp; Sanksi Keterlambatan</h3>
        </div>
        <div class="space-y-2.5">
          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Berapa denda keterlambatan pengembalian buku?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Denda keterlambatan dihitung otomatis oleh sistem sebesar <strong><?= format_rupiah(DENDA_PER_HARI) ?></strong> per hari untuk setiap buku yang terlambat dikembalikan.
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Kapan pengingat jatuh tempo dikirimkan?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Sistem akan memunculkan notifikasi pengingat mulai <strong><?= NOTIFIKASI_JATUH_TEMPO_HARI ?> hari</strong> sebelum jatuh tempo langsung di lonceng notifikasi dan beranda dashboard anggota.
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Bagaimana ketentuan jika buku hilang atau rusak parah?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Peminjam wajib segera melapor ke petugas sirkulasi. Sesuai tata tertib, peminjam wajib mengganti buku fisik dengan judul, penulis, dan penerbit yang sama atau edisi terbaru. Jika buku sudah tidak beredar, penggantian disesuaikan dengan nilai taksir koleksi oleh perpustakaan.
            </div>
          </details>
        </div>
      </div>

      <!-- Bagian 4: Fasilitas & Akses Digital -->
      <div>
        <div class="flex items-center gap-2 mb-3">
          <i class="bi bi-laptop text-sm" style="color: var(--accent)"></i>
          <h3 class="text-xs font-bold uppercase tracking-wider" style="color: var(--text)">4. Fasilitas &amp; Akses Digital</h3>
        </div>
        <div class="space-y-2.5">
          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Apakah non-anggota boleh membaca buku di tempat?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Boleh. Seluruh pengunjung diperbolehkan membaca koleksi di area ruang baca umum tanpa perlu login. Namun, untuk membawa buku pulang ke rumah, pengunjung wajib mendaftar sebagai anggota resmi terlebih dahulu.
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Bagaimana cara menginstal aplikasi ini ke layar smartphone (PWA)?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Aplikasi ini mendukung Progressive Web App (PWA). Buka situs lewat peramban Chrome atau Safari di ponsel Anda, lalu pilih opsi <em>"Tambahkan ke Layar Utama" (Add to Home screen)</em> pada menu peramban. Ikon aplikasi akan muncul di beranda ponsel Anda dan dapat dibuka selayaknya aplikasi asli.
            </div>
          </details>

          <details class="group border rounded-lg overflow-hidden transition" style="border-color: var(--border); background: var(--surface-2)">
            <summary class="flex items-center justify-between p-3.5 sm:p-4 cursor-pointer select-none font-semibold text-sm list-none" style="color: var(--text)">
              <span class="flex items-center gap-2.5">
                <i class="bi bi-patch-question text-xs shrink-0" style="color: var(--accent)"></i>
                Apakah disediakan jaringan internet dan ruang diskusi?
              </span>
              <i class="bi bi-chevron-down text-xs transition-transform duration-200 group-open:rotate-180" style="color: var(--text-muted)"></i>
            </summary>
            <div class="px-4 pb-4 pt-2 text-xs sm:text-sm leading-relaxed border-t" style="border-color: var(--border); color: var(--text-muted)">
              Tersedia akses Wi-Fi gratis berkecepatan tinggi bagi seluruh pengunjung, stopkontak meja baca, serta ruangan khusus diskusi kelompok agar suasana ruang baca utama tetap hening dan nyaman.
            </div>
          </details>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigasi Bawah -->
  <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
    <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-2 text-sm font-semibold hover:underline" style="color: var(--accent)">
      <i class="bi bi-arrow-left"></i> Kembali ke Katalog Koleksi
    </a>
    <?php if (!function_exists('is_logged_in') || !is_logged_in()): ?>
      <div class="flex items-center gap-2">
        <a href="<?= BASE_URL ?>/register.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 transition">
          <i class="bi bi-person-plus"></i> Daftar Anggota
        </a>
        <a href="<?= BASE_URL ?>/login.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold border hover:bg-slate-50 transition" style="border-color: var(--border); color: var(--text)">
          <i class="bi bi-box-arrow-in-right"></i> Masuk
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
