# Sistem Informasi Perpustakaan Umum

Proyek Uji Kompetensi Keahlian (UKK) — dibuat dengan PHP Native (PHP 8+), MySQL, dan Tailwind CSS.

## Fitur Utama

- **Pengunjung** (tanpa login): katalog buku, **pencarian lanjutan** (judul, penulis, ISBN, penerbit + filter kategori, filter hanya tersedia, sorting judul A-Z/terbaru/stok), Top 5 populer & Top 5 terbaru, detail buku, lokasi rak, status ketersediaan (thumb 400px), info perpustakaan, **favorit guest → modal login**, dan bisa **mendaftar mandiri** menjadi anggota (validasi nama 150, no_hp `08[0-9]{8,11}`, rate limit).
- **Anggota** (login): semua fitur pengunjung + dashboard modern (sedang dipinjam, estimasi denda, notifikasi belum dibaca + **statistik pribadi**: total pernah pinjam, total denda all-time, kategori favorit), **notifikasi jatuh tempo/keterlambatan** (lazy 5m + **simulasi email H-3/H-1** via admin trigger), pusat notifikasi, lihat peminjaman aktif + **kalender jatuh tempo bulan berjalan** (warna aman/H-3/terlambat), riwayat peminjaman, info denda, ubah profil + **foto profil** (thumb), **menandai buku favorit**, **mengajukan perpanjangan** 1-7 hari (1x) + **pengajuan buku baru** (judul/penulis/isbn/alasan, status menunggu/disetujui/ditolak + catatan admin), **lupa password** (token demo 1 jam, hash SHA256, one-time).
- **Admin** (login): dashboard (7 stat + **chart tren 30 hari** via Chart.js CDN + **pengajuan menunggu** + warning `admin123`/`setup_akun_awal.php`), CRUD buku (arsip, **ISBN cache 24j**, **bulk import ISBN batch 20**, thumb) & kategori (duplicate check `UNIQUE`), kelola anggota (no_hp validasi, transaksi `FOR UPDATE`), transaksi peminjaman (riwayat, filter, pembatalan `LEAST`, pagination clamp) & pengembalian (pagination 8 + clamp, pilih tanggal kembali, block Minggu) + **trigger notifikasi H-3/H-1 simulasi**, **persetujuan perpanjangan** + catatan admin, **pengajuan buku** (filter, approve/reject + notifikasi), **audit log** (buku/kategori/pengajuan, tambah/edit/hapus), laporan (5 jenis, periode, `LIMIT 500` + banner `500 dari X`, **export CSV** full + BOM, print A4), dan profil + foto.
- **Umum**: tombol mata (ikon Bootstrap Icons) untuk menampilkan/menyembunyikan password di semua form. Seluruh ikon di aplikasi menggunakan Bootstrap Icons (CDN), tanpa emoji. Tahun terbit 1000–Y+1, pagination clamp, `autocomplete` login, `inputmode` no_hp, `csrf_regenerate` setelah login, `.htaccess` + Nginx hardening docs.

## Struktur Folder

```
perpustakaan/
├── config/
│   ├── database.php        # Konfigurasi koneksi database & pengaturan sistem (LAMA_PINJAM_HARI, DENDA_PER_HARI)
│   └── bootstrap.php       # Single bootstrap: session secure + PDO + helpers + auth + csrf + rate_limit + audit
├── includes/
│   ├── auth.php             # Fungsi cek login & pembatasan akses (role)
│   ├── functions.php        # Fungsi bantu + notifikasi anggota + admin_menu/member_menu + audit_menu
│   ├── header.php           # Layout atas + navbar (single source CSS: assets/css/app.css)
│   ├── footer.php           # Layout bawah + modal/JS (single source JS inline, PWA, theme 5 warna)
│   ├── admin_menu.php       # Sidebar desktop permanen untuk navigasi admin; mobile memakai drawer dari header
│   ├── csrf.php             # CSRF token + csrf_regenerate() (dipanggil setelah login)
│   ├── rate_limit.php       # Rate limit file+session (5/15m) untuk login/register/lupa_password
│   ├── audit.php            # Helper catat_audit() — side-effect, tidak gagalkan aksi utama
│   ├── isbn.php             # ISBN helper + cache 24j (isbn_cache_get/set, isbn_api_get)
│   └── email_simulasi.php   # Simulasi email H-3/H-1 (reuse notifikasi, kunci_unik jatuh_tempo)
├── assets/
│   ├── css/app.css          # Single source CSS (210 baris, tidak duplikat inline)
│   ├── img/no-cover.svg      # Cover default jika buku tidak punya cover
│   ├── img/no-avatar.svg     # Foto profil default
│   ├── uploads/covers/       # Folder upload cover buku (thumb_*.jpg 400px)
│   └── uploads/profil/       # Folder upload foto profil anggota & admin
├── database/
│   ├── perpustakaan.sql     # Skema database + data contoh (instalasi baru) — sudah include audit_log, pengajuan_buku, reset_token, UNIQUE, INDEX
│   ├── migrasi_v2.sql        # Migrasi instalasi lama (foto + favorit)
│   ├── migrasi_v3_notifikasi_perpanjangan.sql # Migrasi notifikasi + perpanjangan
│   ├── migrasi_v4_p1_fix.sql # Fix P1: UNIQUE isbn (idempotent)
│   └── migrasi_v4_p2_fix.sql # Fix P2: UNIQUE kategori + INDEX peminjaman(status,jatuh_tempo)
├── anggota/                 # Halaman khusus anggota (wajib login sebagai anggota)
│   ├── dashboard.php         # Dashboard + statistik pribadi (N9) + notifikasi
│   ├── peminjaman.php        # Peminjaman aktif + kalender jatuh tempo (N7) + perpanjangan
│   ├── riwayat.php           # Riwayat peminjaman
│   ├── favorit.php, toggle_favorit.php   # Kelola buku favorit (guest modal)
│   ├── notifikasi.php         # Pusat notifikasi anggota
│   ├── ajukan_perpanjangan.php # Pengajuan perpanjangan
│   ├── pengajuan.php         # Pengajuan buku baru (N10) — form + list status
│   └── profil.php            # Ubah profil + foto profil
├── admin/                   # Halaman khusus admin (wajib login sebagai admin)
│   ├── dashboard.php         # Dashboard + chart tren 30 hari (N4) + pending pengajuan + warning
│   ├── buku/                 # index, tambah, edit, hapus, arsip, import.php (N5 bulk ISBN batch 20) + cari_isbn.php (cache 24j)
│   ├── kategori/             # index, edit, hapus (duplicate check + UNIQUE)
│   ├── anggota/              # index, tambah, edit, hapus (no_hp validasi)
│   ├── peminjaman/           # index (filter status & riwayat + pagination clamp), tambah (FOR UPDATE), batalkan (LEAST)
│   ├── pengembalian/         # index (pilih tanggal kembali + pagination clamp), proses (LEAST)
│   ├── perpanjangan/         # daftar & proses persetujuan perpanjangan + catatan_admin
│   ├── pengajuan/            # N10: index (filter status + pagination clamp) + proses.php (approve/reject + notifikasi)
│   ├── audit_log/            # N8: index.php — list audit_log (buku/kategori/pengajuan) + filter
│   ├── laporan/              # index.php — 5 jenis + export CSV (N3) + LIMIT banner
│   ├── kirim_notifikasi_jatuh_tempo.php # N6 — trigger manual H-3/H-1 simulasi (pengganti cron)
│   └── profil.php            # Profil admin + foto profil
├── lupa_password.php         # N1 — form email + token demo (hash SHA256, 1 jam, one-time)
├── reset_password.php        # N1 — form password baru via token
├── register.php              # Registrasi anggota mandiri (auto-login, validasi nama 150/no_hp 08)
├── index.php                # Katalog buku (beranda) — pencarian lanjutan (judul/penulis/ISBN/penerbit + tersedia + sort) + thumb + pagination clamp
├── detail.php                # Detail buku — favorit guest modal
├── tentang.php               # Informasi perpustakaan
├── login.php / logout.php    # Autentikasi — autocomplete, rate limit, csrf_regenerate, pesan generik
└── README.md
```

## Cara Instalasi (XAMPP/Laragon)

1. Salin folder `perpustakaan` ke dalam `htdocs` (XAMPP) atau `www` (Laragon).
2. Buat database baru bernama `perpustakaan` lalu import file `database/perpustakaan.sql`
   melalui phpMyAdmin (atau `mysql -u root perpustakaan < database/perpustakaan.sql`).
   Skema terbaru sudah include: `audit_log` (N8), `pengajuan_buku` (N10), `anggota.reset_token/reset_expiry` (N1), `UNIQUE isbn/kategori` + `INDEX peminjaman` (P2).
3. Buka `config/database.php`, sesuaikan `DB_USER`, `DB_PASS`, dan `BASE_URL`
   jika nama folder project berbeda.
4. Buka `http://localhost/perpustakaan/` — selesai! Akun demo di bawah ini
   sudah bisa langsung dipakai tanpa langkah tambahan apa pun.

> **Sudah pernah install versi sebelumnya?** Jalankan `database/migrasi_v2.sql`, `database/migrasi_v3_notifikasi_perpanjangan.sql`, `database/migrasi_v4_p1_fix.sql` (UNIQUE isbn), `database/migrasi_v4_p2_fix.sql` (UNIQUE kategori + INDEX) satu kali lewat phpMyAdmin. Untuk fitur baru N1/N8/N10, import ulang `perpustakaan.sql` atau jalankan manual `CREATE TABLE pengajuan_buku` + `audit_log` + `ALTER TABLE anggota ADD reset_token/reset_expiry` (lihat `perpustakaan.sql` terbaru).

## Akun Contoh (Hanya untuk Demo / Pengujian Lokal)

> ⚠️ **PERINGATAN KEAMANAN — WAJIB DIGANTI UNTUK PRODUKSI**
> Akun di bawah ini memakai password default yang sangat mudah ditebak.
> **Jangan gunakan di server publik tanpa mengganti password terlebih dahulu.**
> Setelah instalasi, segera login sebagai admin lalu buka **Admin → Profil → Keamanan Akun** untuk mengganti password.
> Untuk anggota demo, login lalu ganti via **Anggota → Profil**.
> Pada produksi, hapus atau nonaktifkan akun demo (`budi@example.com`) dan buat akun admin baru dengan password kuat.
> File `database/setup_akun_awal.php` telah **dihapus** dari repository untuk keamanan. Reset password akun contoh sekarang dilakukan via **Admin → Profil → Keamanan Akun** (atau `anggota/profil.php` untuk anggota).

| Peran   | Username / Email      | Password    | Kegunaan |
|---------|------------------------|-------------|----------|
| Admin   | `admin`                | `admin123`  | Demo UKK — ganti segera di produksi |
| Anggota | `budi@example.com`     | `anggota123`| Demo UKK — hapus/nonaktifkan di produksi |

## Aturan Sistem

- Lama peminjaman: **7 hari** sejak tanggal pinjam (`config/database.php` → `LAMA_PINJAM_HARI`).
- Denda keterlambatan: **Rp 1.000/hari** (`config/database.php` → `DENDA_PER_HARI`).
- Setiap anggota maksimal meminjam **3 buku** sekaligus.
- Saat memproses pengembalian, admin memilih tanggal kembali secara manual (berguna untuk mencatat pengembalian yang terjadi sebelumnya). Tanggal yang boleh dipilih dibatasi antara tanggal peminjaman sampai hari ini.
- Pengembalian tidak dapat diproses untuk tanggal yang jatuh pada hari **Minggu** (perpustakaan tutup) — divalidasi di sisi JavaScript maupun server.
- Admin dapat **membatalkan** peminjaman yang salah input dari menu Peminjaman; stok buku akan otomatis dikembalikan.
- Anggota baru bisa mendaftar mandiri lewat halaman **Daftar** (`register.php`), atau didaftarkan oleh admin melalui menu **Admin → Anggota → Tambah Anggota**.
- Setiap anggota bisa menandai buku sebagai favorit dari katalog atau halaman detail buku, lalu melihatnya di menu **Favorit**.
- Anggota menerima notifikasi otomatis ketika jatuh tempo sudah dekat, sudah lewat, atau ada perubahan status perpanjangan.
- Anggota dapat meminta tambahan **1–7 hari** untuk peminjaman yang belum jatuh tempo; admin wajib menyetujui permintaan tersebut sebelum tanggal jatuh tempo berubah. Satu transaksi hanya dapat memperoleh satu perpanjangan yang disetujui.
- Anggota dan admin bisa mengunggah **foto profil** dari halaman Profil masing-masing.

## Keamanan yang Diterapkan

- Password disimpan dengan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Seluruh query database menggunakan **prepared statement** (PDO) untuk mencegah SQL Injection.
- Semua output data melewati `htmlspecialchars()` (fungsi `e()`) untuk mencegah XSS.
- Validasi upload cover & foto profil: cek ekstensi, tipe MIME asli, dan ukuran maksimal 2MB.
- Folder upload dilindungi `.htaccess` agar file PHP tidak bisa dieksekusi di sana.
- Pembatasan akses halaman berdasarkan role melalui `wajib_admin()` / `wajib_anggota()`.
- Konfirmasi tindakan menggunakan modal JavaScript sebelum aksi penting.
- Riwayat transaksi peminjaman tidak ikut dihapus ketika data buku/anggota dikelola; data yang memiliki riwayat harus dinonaktifkan atau dipertahankan.
- **Rate limiting** login (5 percobaan/15 menit), register (5/15m) dan lupa password (5/15m) per IP untuk mencegah brute force dan spam (`includes/rate_limit.php`).
- **Peringatan kredensial default** otomatis di Dashboard Admin jika password masih `admin123` — segera ganti via Profil (file `setup_akun_awal.php` sudah dihapus).
- **Validasi upload**: ekstensi `jpg/jpeg/png/webp`, MIME asli via `finfo`, `getimagesize` implisit via `buat_thumbnail`, `basename()` untuk cegah path traversal, `is_uploaded_file` tidak diperlukan karena `move_uploaded_file` sudah aman, dan `.htaccess` untuk Apache.
- **Audit log** (`audit_log` + `includes/audit.php` `catat_audit()` side-effect) untuk tambah/edit/hapus buku/kategori/pengajuan — tidak menggagalkan aksi utama.
- **CSRF regenerate** setelah `session_regenerate_id` (`csrf_regenerate()` di login/register).

### Keamanan Upload & Hardening Nginx

Upload cover (`assets/uploads/covers/`) dan foto profil (`assets/uploads/profil/`) sudah divalidasi di `includes/functions.php:95-141` (ekstensi, MIME, 2MB, `basename`). Folder tersebut dilindungi `.htaccess` (`assets/uploads/covers/.htaccess`, `assets/uploads/profil/.htaccess`) agar file `.php` tidak dieksekusi — **namun `.htaccess` hanya berlaku di Apache** (XAMPP/Laragon). Jika deploy di **Nginx**, `.htaccess` diabaikan sehingga perlu konfigurasi manual:

```nginx
# Blokir eksekusi PHP di folder upload (Nginx)
location ~* ^/perpustakaan/assets/uploads/.*\.php$ {
    deny all;
    return 404;
}
# Opsional: hanya izinkan gambar, tolak lainnya
location ^~ /perpustakaan/assets/uploads/ {
    location ~* \.(jpg|jpeg|png|webp|svg)$ { expires 30d; }
    # Jika file bukan gambar, tetap deny php sudah di atas; untuk non-gambar lain bisa:
    # location ~* \.(php|phtml|phar|inc|sh)$ { deny all; }
}
```

> **Catatan deployment:** Project ini ditujukan untuk XAMPP/Laragon (Apache) sesuai panduan instalasi, sehingga `.htaccess` sudah cukup. Blok Nginx di atas **hanya dokumentasi hardening** untuk server Nginx dan **tidak otomatis diterapkan** — admin perlu menambahkannya manual di `nginx.conf` jika pakai Nginx. Validasi aplikasi (MIME, `basename`, `move_uploaded_file`) tetap menjadi lapisan utama.


## Riwayat Perubahan — P1-P4 + N1-N10 (Terverifikasi via `php -l` & code review)

**P1 Critical (Bug & Security):** ISBN duplicate fix + `UNIQUE`, rate limit 5/15m (login/register/lupa_password), transaksi `FOR UPDATE` untuk `generate_kode_buku`/`nomor_anggota`, `LIMIT` bind `PARAM_INT`.
**P2 High (Stability & DB):** Pagination pengembalian 8 + clamp, laporan `LIMIT 500` + banner `500 dari X` + `total_all`, login enumeration generik, `UNIQUE kategori`, `INDEX peminjaman(status,jatuh_tempo)`, `thumb` orphan `hapus_cover` + `basename`.
**P3 Medium (Quality & Perf):** Hapus dead code (`profile.php`, `app.js`, `cron/`, `require_post`), single source CSS (`app.css`, header 409→198), thumb katalog `cover_thumb_url`, ISBN cache 24j (`sys_get_temp_dir`), validasi kategori/no_hp/nama, `LEAST` stok, catatan admin perpanjangan + notifikasi.
**P4 Polish:** Tahun 1000–Y+1 + `min/max`, clamp pagination 4 halaman, `csrf_regenerate()` setelah `session_regenerate_id`, Nginx hardening docs, `autocomplete` login, `inputmode` no_hp, guest favorit modal.
**N1 Lupa Password (Fallback Token di Layar):** `anggota.reset_token` (hash SHA256, 64) + `reset_expiry` (1 jam, one-time) + `lupa_password.php` (rate limit, pesan generik) + `reset_password.php` (validasi token + expiry + `password_hash`) + link `login.php: Lupa password?` — demo: token tampil di layar, di prod via email.
**N3 Export CSV:** `admin/laporan/index.php?export=csv` — query tanpa `LIMIT`, header `text/csv` + BOM, `fputcsv` reuse `$kolom`, full data (bukan 500).
**N2 Advanced Search:** `index.php` — `WHERE (judul OR penulis OR isbn OR penerbit) LIKE`, filter `tersedia>0`, sort whitelist `judul_asc/terbaru/stok_desc` → `ORDER BY $order_by`, pill `Hanya tersedia` + dropdown, pagination preserve `q/kategori/tersedia/sort`.
**N4 Chart:** `admin/dashboard.php` — 30 hari `GROUP BY DATE(tanggal_pinjam)` + `Chart.js CDN` line chart, data inline `json_encode`, fallback `try/catch`.
**N5 Bulk Import:** `admin/buku/import.php` — textarea 20 ISBN/batch, loop per-ISBN `validasi → duplicate check → isbn_cache_get → isbn_api_get → generate_kode_buku FOR UPDATE per-ISBN (bukan 1 transaksi besar) → INSERT stok 1` + ringkasan berhasil/dilewati/gagal.
**N8 Audit Log:** `audit_log` (id, user_id FK admin, aksi tambah/edit/hapus, target_tabel buku/kategori/pengajuan_buku, target_id, detail JSON, created_at) + `includes/audit.php` `catat_audit()` (try/catch, side-effect) + 6 titik (buku tambah/edit/hapus, kategori tambah/edit/hapus, pengajuan approve/reject) + `admin/audit_log/index.php` (filter tabel/aksi, pagination clamp).
**N6 Notifikasi H-3/H-1 Simulasi:** `includes/email_simulasi.php` `proses_notifikasi_h3_h1()` — reuse `notifikasi` (kunci_unik `jatuh_tempo:<id>:<tgl>` sama dengan `sinkronkan_notifikasi_anggota`, `warning`, `INSERT IGNORE` dedup lintas mekanisme) + `admin/kirim_notifikasi_jatuh_tempo.php` trigger manual (pengganti cron) + preview simulasi.
**N7 Kalender:** `anggota/peminjaman.php` — reuse `$daftar` (tanpa query baru), map `jatuh_tempo → status aman/peringatan/terlambat` (warna `emerald/amber/red` konsisten badge), grid 7 `Min-Sab` bulan berjalan, klik → `showNoticeModal` judul.
**N9 Statistik Pribadi:** `anggota/dashboard.php` — `COUNT(*) peminjaman WHERE anggota_id`, `SUM(denda)`, kategori favorit `favorit JOIN kategori` fallback `peminjaman JOIN kategori` + section `Statistik Saya` di bawah 3 stat lama.
**N10 Pengajuan Buku:** `pengajuan_buku` (anggota_id FK, judul, penulis, isbn, alasan 500, status menunggu/disetujui/ditolak, catatan_admin 500) + `anggota/pengajuan.php` (form + list saya, pagination clamp) + `admin/pengajuan/index.php` (filter status, pagination) + `admin/pengajuan/proses.php` (approve/reject + notifikasi reuse) — approve **tidak** auto-tambah ke `buku`.
**Security Fix:** `setup_akun_awal.php` dihapus manual — `admin/dashboard.php` hapus `is_file` check + banner merah, fokus ke `password_verify('admin123')` + `README` update.
**3 Bugfix Regression:** Bulk import audit (`import.php` → `catat_audit` tambah buku), dedup notifikasi H-3/H-1 lintas mekanisme (`email_h3` → `jatuh_tempo`), audit `pengajuan_buku` target_tabel (`buku` → `pengajuan_buku`, whitelist `audit.php` + filter `audit_log`).

## V4 UI Navigation

## Instal sebagai Aplikasi (PWA)

Versi ini sudah dilengkapi **Progressive Web App (PWA)** sehingga dapat dipasang di Android/Chrome sebagai aplikasi, bukan sekadar shortcut.

Syarat:
- Project harus dijalankan melalui **HTTPS** saat online. `http://localhost` tetap dapat digunakan untuk pengujian di komputer.
- `BASE_URL` di `config/database.php` harus sesuai dengan lokasi folder project.
- Database MySQL dan PHP tetap diperlukan karena aplikasi ini menggunakan PHP + MySQL.

Cara di Android:
1. Buka website melalui Chrome.
2. Tunggu sampai tombol **Install aplikasi** muncul, lalu tekan tombol tersebut.
3. Ikuti dialog pemasangan.
4. Setelah terpasang, aplikasi akan muncul di daftar aplikasi dan terbuka dalam mode standalone.

Catatan: PWA membuat website dapat dipasang seperti aplikasi, tetapi **tidak mengubah PHP + MySQL menjadi aplikasi yang sepenuhnya offline**. Fitur yang membutuhkan database tetap memerlukan server PHP/MySQL.

## Fitur Tambah Buku via ISBN
Pada menu Admin > Data Buku > Tambah Buku tersedia dua mode: **Via ISBN** dan **Manual**. Mode ISBN mengambil metadata buku dari Open Library (cache 24 jam via `sys_get_temp_dir`, `isbn_cache_get/set`), lalu admin tetap dapat memeriksa/mengoreksi data sebelum menyimpan. Cover yang ditemukan akan disimpan ke folder cover lokal (`covers.openlibrary.org` saja). Jika ISBN tidak ditemukan, gunakan mode Manual. **Bulk Import** tersedia di `Admin → Buku → Import Batch` — paste 20 ISBN/batch, reuse `generate_kode_buku FOR UPDATE` per-ISBN, ringkasan berhasil/dilewati/gagal.
