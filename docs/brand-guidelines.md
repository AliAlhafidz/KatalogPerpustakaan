# Panduan Identitas Visual & Logo
## Sistem Informasi Perpustakaan

Dokumen ini merupakan panduan resmi penggunaan aset logo dan identitas visual untuk website **Sistem Informasi Perpustakaan**.

---

## 1. Konsep & Filosofi Logo

- **Nama Konsep**: *Buku Digital & Lentera Pengetahuan*
- **Tipe Mark**: *Combination Mark* (Simbol Ikonis + Tipografi Terpadu)
- **Filosofi Bentuk**:
  1. **Simbol Buku Terbuka**: Mewakili esensi utama perpustakaan sebagai pusat literasi, arsip ilmu, dan keterbukaan akses bagi seluruh anggota.
  2. **Alur Baris Data Digital**: Tiga baris data horizontal di setiap bilah buku melambangkan struktur data, sistem katalog digital, serta sirkulasi peminjaman yang terorganisir dan efisien.
  3. **Lentera Pengetahuan (Knowledge Beacon)**: Berlian geometris bersudut 60° di bagian atas tulang buku melambangkan pencerahan, inspirasi, dan arah navigasi informasi.

---

## 2. Palet Warna Resmi

| Peran | Nama Warna | Kode HEX | RGB | Penggunaan |
|---|---|---|---|---|
| **Warna Utama** | Deep Library Navy | `#1a2c4a` | `rgb(26, 44, 74)` | Bilah buku utama, navbar, teks institusi, latar gelap |
| **Aksen Primer** | Electric Digital Blue | `#0284c7` | `rgb(2, 132, 199)` | Lentera pengetahuan, alur data sekunder, tautan aktif |
| **Aksen Sekunder** | Cyan Pulse | `#38bdf8` | `rgb(56, 189, 248)` | Baris data primer, aksen kontras latar gelap |
| **Netral Gelap** | Midnight Charcoal | `#0f172a` | `rgb(15, 23, 42)` | Tipografi utama, footer base |
| **Netral Terang** | Pure Paper White | `#ffffff` | `rgb(255, 255, 255)` | Logo versi terbalik (reversed), kanvas bersih |

---

## 3. Tipografi Resmi

- **Display / Judul Buku Utama**: `EB Garamond` (opsz 6..12, wght 700) / Serif klasik berwibawa
- **UI / Label & Navigasi**: `Inter` (wght 400, 500, 600, 700) / Sans-serif modern, tegas, dan mudah dibaca
- **Fallback Web Stack**: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif`

---

## 4. Daftar File Aset Logo (`assets/img/logo/`)

| Nama File | Format | Deskripsi & Penggunaan |
|---|---|---|
| `logo-symbol.svg` | SVG | Master simbol warna penuh (Navy + Cyan) untuk navbar dan header |
| `logo-symbol-small.svg` | SVG | Master simbol dengan bobot dipertegas untuk ukuran 16–32 px |
| `logo-symbol-favicon.svg` | SVG | Master favicon SVG berdimensi presisi untuk tab browser |
| `logo-symbol-black.svg` | SVG | Versi 1 warna hitam solid untuk media cetak/stempel |
| `logo-symbol-white.svg` | SVG | Versi 1 warna putih solid untuk latar belakang gelap & footer |
| `logo-symbol-mono-1a2c4a.svg` | SVG | Versi 1 warna Navy solid `#1a2c4a` |
| `logo-horizontal.svg` | SVG | Lockup horizontal lengkap (simbol + teks) untuk latar terang |
| `logo-horizontal-dark.svg` | SVG | Lockup horizontal lengkap untuk latar gelap |
| `logo-app-icon.svg` | SVG | Ikon aplikasi dengan wadah squircle rounded (radius 22%) |
| `favicon.ico` | ICO | Favicon multi-layer untuk kompatibilitas browser lama |
| `apple-touch-icon.png` | PNG | Ikon layar beranda iOS / Safari (180×180 px) |
| `pwa-icon-192.png` | PNG | Ikon PWA standar (192×192 px) |
| `pwa-icon-512.png` | PNG | Ikon PWA resolusi tinggi / splash screen (512×512 px) |

---

## 5. Area Aman (*Clear Space*) & Ukuran Minimum

- **Clear Space**: Berikan ruang bebas minimal sebesar **0.5× tinggi lentera pengetahuan** di sekeliling logo agar tidak terganggu oleh teks atau elemen lain.
- **Ukuran Minimum di Layar**:
  - Simbol: `16 × 16 px` (gunakan `logo-symbol-favicon.svg` atau `favicon.ico`)
  - Simbol Navbar: `32 × 32 px` (gunakan `logo-symbol.svg`)
  - Lockup Horizontal: Lebar minimum `180 px` agar subtitle tetap terbaca.
- **Ukuran Minimum Cetak**:
  - Simbol: `8 mm`
  - Lockup: `30 mm`

---

## 6. Aturan Larangan (*Don'ts*)

1. **Dilarang mendistorsi proporsi**: Jangan menarik atau memipihkan logo secara non-proporsional.
2. **Dilarang mengubah warna sembarangan**: Selalu gunakan palet warna yang telah ditentukan.
3. **Dilarang menambahkan efek grafis yang tidak perlu**: Jangan menambahkan drop-shadow tebal, bevel 3D, atau outline neon.
4. **Dilarang menempatkan logo berwarna gelap di atas latar belakang gelap**: Gunakan versi `logo-symbol-white.svg` atau `logo-horizontal-dark.svg`.
5. **Dilarang memisahkan atau mengubah posisi lentera**: Posisi lentera dan bilah buku harus selalu seimbang pada sumbu simetri vertikal.
