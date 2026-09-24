# Sistem Informasi Perpustakaan Umum

Proyek UKK — PHP Native 8+, PDO MySQL, Tailwind CSS, Bootstrap Icons.

## Fitur

**Pengunjung (tanpa login):** katalog + pencarian lanjutan (judul/penulis/ISBN/penerbit), filter kategori & "hanya tersedia", sort (A–Z/terbaru/stok), Top 5 populer & terbaru, detail buku (rak/ketersediaan/cover thumb 400px), empty state, favorit guest → modal login, daftar mandiri.

**Anggota:** semua di atas + dashboard (sedang dipinjam, estimasi denda, notifikasi, statistik: total pinjam/total denda/kategori favorit), peminjaman aktif + kalender jatuh tempo, riwayat (`LEFT JOIN` — histori tetap tampil meski buku dihapus), notifikasi H-3/H-1, favorit, ajukan perpanjangan 1–7 hari (1×), pengajuan buku baru, ubah profil + foto profil (thumb), lupa password token demo 1 jam.

**Admin:** dashboard (7 stat + chart 30 hari via Chart.js CDN) + warning `aliali123`, CRUD buku (arsip/hapus/ISBN cache 24j/bulk import 20/batch + thumb) & kategori, kelola anggota, peminjaman & pengembalian (filter/pagination clamp/LEAST stok/validasi Minggu) + trigger simulasi H-3/H-1, perpanjangan & pengajuan (approve/reject + notifikasi), audit log (buku/kategori/pengajuan), laporan 5 jenis (`LIMIT 500` + export CSV BOM + print A4), profil + foto.

> **Hapus buku:** histori `peminjaman` tidak ikut terhapus. FK `peminjaman.id_buku` → `ON DELETE SET NULL` (NULL → tampil "Buku telah dihapus dari katalog / Tidak tersedia" di `anggota/riwayat.php` + halaman admin). `favorit` & `pengajuan_peminjaman` tetap `CASCADE` (workflow). Admin `hapus.php` tidak lagi blokir buku berhistori; ada `warning` bila buku masih `dipinjam`.

**Umum:** `password_hash`, prepared statement, `e()` (`htmlspecialchars`), validasi upload (ext/MIME 2MB, thumb), `.htaccess` upload, `wajib_admin`/`wajib_anggota`, modal konfirmasi, rate limit 5/15m, `csrf_regenerate` setelah login, PWA (HTTPS, `BASE_URL`).

## Struktur Folder

```
perpustakaan/
├── config/
│   ├── database.php        # PDO + LAMA_PINJAM_HARI=7, DENDA_PER_HARI=1000, BASE_URL, UPLOAD_*
│   └── bootstrap.php       # session secure + PDO + helpers + auth/csrf/rate_limit/audit
├── includes/
│   ├── auth.php            # wajib_admin / wajib_anggota
│   ├── functions.php       # helpers + notifikasi + menu + audit helpers
│   ├── header.php          # navbar + drawer + CSS (assets/css/app.css)
│   ├── footer.php          # modal/JS + PWA + theme 5 warna
│   ├── admin_menu.php      # sidebar desktop admin
│   ├── csrf.php            # CSRF + csrf_regenerate()
│   ├── rate_limit.php      # 5/15m file+session
│   ├── audit.php           # catat_audit() side-effect
│   ├── isbn.php            # isbn_cache_get/set + isbn_api_get (24j)
│   └── email_simulasi.php  # simulasi H-3/H-1 (INSERT IGNORE, kunci_unik)
├── assets/
│   ├── css/app.css         # single source CSS
│   ├── img/no-cover.svg | no-avatar.svg
│   ├── uploads/covers/     # thumb_*.jpg 400px + .htaccess
│   └── uploads/profil/     # foto profil + .htaccess
├── database/
│   ├── perpustakaan.sql                    # skema + data contoh (fresh install)
│   └── migrasi_hapus_buku_set_null.sql    # peminjaman.id_buku → SET NULL (jaga histori)
├── anggota/                # dashboard, peminjaman, riwayat, favorit, notifikasi, ajukan_perpanjangan, pengajuan, profil
├── admin/
│   ├── dashboard.php
│   ├── buku/               # index, tambah, edit, hapus, arsip, import, cari_isbn, _form
│   ├── kategori/           # index, edit, hapus
│   ├── anggota/            # index, tambah, edit, hapus
│   ├── peminjaman/         # index, tambah, batalkan
│   ├── pengembalian/       # index, proses
│   ├── perpanjangan/       # index, proses
│   ├── pengajuan/          # index, proses
│   ├── pengajuan_peminjaman/ # index, proses
│   ├── audit_log/index.php
│   ├── laporan/index.php   # 5 jenis + CSV + chart
│   ├── kirim_notifikasi_jatuh_tempo.php
│   └── profil.php
├── index.php               # katalog (pencarian+filter+sort, Pilihan Minggu Ini, Buku Paling Banyak Dipinjam, grid 3 kolom mobile)
├── detail.php | tentang.php | login.php | logout.php | register.php
├── lupa_password.php | reset_password.php
└── README.md
```

> `index.php` — "Pilihan Minggu Ini" (`$featured`) & "Buku Paling Banyak Dipinjam" (`$buku_populer`) hanya dirender `if (!empty(...))`, jadi tidak muncul saat DB kosong. Cache populer 5 menit di-invalidate saat buku ditambah/diedit/dihapus/diarsipkan (`hapus_cache_buku_populer()`). Grid katalog & populer `grid-cols-3` di mobile (compact `p-2`, `aspect-[2/3]`, `line-clamp-2`).

## Instalasi (XAMPP/Laragon)

1. Salin `perpustakaan/` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buat DB `perpustakaan` → import `database/perpustakaan.sql` via phpMyAdmin atau `mysql -u root perpustakaan < database/perpustakaan.sql`.
   Jika DB sudah ada dan ingin jaga histori saat hapus buku: `mariadb --ssl=0 -h 127.0.0.1 -u root perpustakaan < database/migrasi_hapus_buku_set_null.sql`.
3. Sesuaikan `DB_USER`/`DB_PASS`/`BASE_URL` di `config/database.php` jika folder/DB berbeda.
4. Buka `http://localhost/perpustakaan/` — akun demo siap pakai.

## Akun Demo (lokal saja — jangan dipakai di produksi)

> ⚠️ Ganti segera setelah instal! Admin → Profil → Keamanan Akun. Hapus/nonaktifkan `budi@example.com` di produksi. `database/setup_akun_awal.php` sudah dihapus.

| Peran | Username / Email | Password | Catatan |
|-------|------------------|----------|---------|
| Admin | `admin` | `aliali123` | Demo — ganti segera |
| Anggota | `budi@example.com` | `budi123` | Demo — hapus di produksi |

## Aturan Sistem

- Pinjam 7 hari, denda Rp 1.000/hari (`config/database.php`).
- Maks 3 buku/anggota bersamaan.
- Pengembalian: admin pilih `tanggal_kembali` (`tanggal_pinjam`–hari ini), tidak boleh Minggu (validasi JS+server).
- Batalkan peminjaman (salah input) → stok `LEAST(stok, tersedia+1)`.
- Daftar mandiri via `register.php` (validasi `nama` 150, `no_hp` `08…`, rate limit) atau admin daftarkan.
- Favorit dari katalog/detail → lihat di Favorit.
- Perpanjangan 1–7 hari, 1× per transaksi, butuh approve admin.
- Pengajuan buku: judul/penulis/isbn/alasan → menunggu/disetujui/ditolak (+ catatan admin); approve tidak auto-tambah ke katalog.
- Hapus buku tidak hapus histori; `peminjaman.id_buku` jadi `NULL` → riwayat tampil "Buku telah dihapus dari katalog / Tidak tersedia" (+ badge/notice konsisten `riwayat.php`).

## Keamanan

- `password_hash`/`password_verify`, PDO prepared, `e()` XSS, validasi upload (ext/MIME/`finfo`/`getimagesize` via thumb, `basename`, `move_uploaded_file`, 2MB), `.htaccess` upload, `wajib_admin`/`wajib_anggota`, modal konfirmasi, rate limit 5/15m, `csrf_regenerate`, audit side-effect, warning `aliali123`.

### Hardening Nginx (opsional — `.htaccess` hanya Apache)

```nginx
location ~* ^/perpustakaan/assets/uploads/.*\.php$ { deny all; return 404; }
location ^~ /perpustakaan/assets/uploads/ {
  location ~* \.(jpg|jpeg|png|webp|svg)$ { expires 30d; }
}
```
Validasi aplikasi tetap lapisan utama; blok di atas hanya dokumentasi (tambah manual di `nginx.conf` jika pakai Nginx).

## PWA

Sudah PWA (pasang di Android/Chrome via "Install aplikasi"). Syarat: HTTPS saat online (`http://localhost` OK untuk dev), `BASE_URL` benar, tetap butuh PHP+MySQL (tidak offline penuh).

## Fitur ISBN

Admin → Buku → Tambah: mode **Via ISBN** (Open Library/Google Books, cache 24j `sys_get_temp_dir`) — dicek/dikoreksi sebelum simpan. Bulk Import: Admin → Buku → Import Batch — 20 ISBN/batch, `FOR UPDATE` per-ISBN, ringkasan berhasil/dilewati/gagal.

## Riwayat Perubahan

- **Hapus buku (histori aman):** `migrasi_hapus_buku_set_null.sql` (`MODIFY id_buku NULL` + `FK SET NULL`), `admin/buku/hapus.php` (hapus guard `COUNT(*) >0`, `warning` jika masih `dipinjam`, `DELETE favorit`+`DELETE buku`), `admin/buku/index.php` (`data-confirm` baru), `admin/peminjaman/index.php` & `admin/pengembalian/index.php` & `admin/laporan/index.php` (`JOIN`→`LEFT JOIN`, fallback "Buku telah dihapus dari katalog"/"Tidak tersedia"), `admin/pengembalian/proses.php` & `admin/peminjaman/batalkan.php` (skip `UPDATE buku` jika `id_buku IS NULL`).
- **Homepage grid:** `index.php` `grid-cols-2`→`grid-cols-3` (populer & katalog), card compact (`p-2`, `aspect-[2/3]`, `text-[11px]`, `line-clamp-2`).
- Sebelumnya: P1–P4 + N1–N10 (lihat `git log`; ringkas: UNIQUE ISBN/kategori, rate limit, `FOR UPDATE`, pagination clamp, `LIMIT 500` laporan, login generik, thumb orphan, cache ISBN, `LEAST`, audit, CSV, pencarian lanjutan, chart 30 hari, bulk import, notifikasi H-3/H-1 simulasi, kalender jatuh tempo, statistik pribadi, pengajuan buku).
