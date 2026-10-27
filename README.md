# Sistem Informasi Perpustakaan Umum

Aplikasi web perpustakaan untuk proyek UKK. Dibangun dengan **PHP 8+ native (tanpa framework)**, **MySQL/MariaDB (PDO)**, **Tailwind CSS (CDN)**, dan **Bootstrap Icons**. Mendukung PWA.

Repo: <https://github.com/AliAlhafidz/KatalogPerpustakaan>

## Daftar Isi

- [Fitur](#fitur)
- [Aturan Sistem](#aturan-sistem)
- [Struktur Folder](#struktur-folder)
- [Skema Database](#skema-database)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Akun Demo](#akun-demo)
- [Keamanan](#keamanan)
- [Fitur ISBN](#fitur-isbn)
- [PWA](#pwa)
- [Keterbatasan & Rencana](#keterbatasan--rencana)

## Fitur

### Pengunjung (tanpa login)
- Katalog buku dengan pencarian (judul, penulis, ISBN, penerbit), filter kategori, filter "hanya tersedia", dan urutan (A–Z, terbaru, stok terbanyak). Pagination 24 buku per halaman.
- Section "Buku Paling Banyak Dipinjam" (cache 5 menit) dan "Pilihan Minggu Ini". Keduanya hanya tampil jika ada datanya.
- Detail buku: cover, rak, ketersediaan, deskripsi.
- Daftar mandiri sebagai anggota (`register.php`).

### Anggota
- Dashboard: peminjaman aktif, estimasi denda, notifikasi terbaru, statistik pribadi.
- Peminjaman aktif dengan kalender jatuh tempo dan riwayat peminjaman.
- Favorit buku.
- Notifikasi pengingat jatuh tempo (H-3 sampai hari-H, dan terlambat). Dibuat otomatis saat anggota membuka halaman (maks 1x per 5 menit per sesi), tanpa cron. Ada lencana belum dibaca dan "tandai semua dibaca".
- Ajukan perpanjangan 1–7 hari (1x per peminjaman, perlu persetujuan admin; tidak bisa jika sudah lewat jatuh tempo).
- Ajukan peminjaman buku yang tersedia. Status: menunggu, disetujui, ditolak, dibatalkan. Anggota bisa membatalkan pengajuan yang masih menunggu.
- Ubah profil, foto profil, dan password.

### Admin
- Dashboard: statistik dan grafik peminjaman 30 hari (Chart.js CDN).
- Buku: tambah, ubah, hapus, arsip/publikasi ulang, upload cover (+thumbnail 400px), tambah via ISBN, dan impor batch (maks 20 ISBN per submit).
- Kategori: tambah, ubah, hapus.
- Anggota: tambah, ubah, hapus (diblokir jika punya riwayat pinjam), aktif/nonaktif.
- Peminjaman: catat peminjaman (multi-buku), batalkan peminjaman salah input.
- Pengembalian: pilih tanggal kembali, denda dihitung otomatis.
- Perpanjangan dan pengajuan peminjaman: setujui/tolak dengan catatan, anggota mendapat notifikasi.
- Laporan: 5 jenis (buku, anggota, peminjaman, pengembalian, denda), ekspor CSV, cetak, grafik. Dibatasi 500 baris per laporan.

## Aturan Sistem

| Aturan | Nilai | Lokasi |
|---|---|---|
| Lama pinjam | 7 hari | `LAMA_PINJAM_HARI` |
| Denda | Rp 1.000/hari | `DENDA_PER_HARI` |
| Maks perpanjangan | 7 hari, 1x | `PERPANJANGAN_MAKS_HARI` |
| Pengingat jatuh tempo | mulai H-3 | `NOTIFIKASI_JATUH_TEMPO_HARI` |
| Maks pinjaman aktif | 3 buku | ditulis langsung di kode (`peminjaman/tambah.php`, `pengajuan_peminjaman/proses.php`, `anggota/ajukan_peminjaman.php`) |

Konstanta ada di `config/database.php`. Aturan lain:

- Perpustakaan **tutup hari Minggu**: peminjaman, pengembalian, dan persetujuan pengajuan tidak bisa diproses pada hari Minggu.
- Tanggal pinjam/kembali tidak boleh melebihi hari ini. Tanggal kembali tidak boleh sebelum tanggal pinjam.
- Persetujuan pengajuan peminjaman memakai tanggal hari itu dan langsung mengurangi stok tersedia.
- Pengajuan hanya untuk buku yang tersedia dan tidak diarsipkan. Satu anggota hanya boleh punya satu pengajuan "menunggu" per buku.
- Membatalkan peminjaman menghapus catatannya dan mengembalikan stok.
- Buku yang diarsipkan hilang dari katalog dan tidak bisa dipinjam.
- Pendaftaran mandiri: nama maks 150 karakter, no. HP format `08xxxxxxxxxx`, password minimal 6 karakter. Nomor anggota otomatis (`A0001`, dst), kode buku otomatis (`BK-0001`, dst).

## Struktur Folder

```
perpustakaan/
├── config/
│   ├── bootstrap.php        # session aman + semua include; dipakai semua entry point
│   ├── database.php         # koneksi PDO + konstanta sistem + BASE_URL + path upload
│   └── env.php              # pembaca .env sederhana
├── includes/
│   ├── auth.php             # is_admin/is_anggota, wajib_admin/wajib_anggota
│   ├── csrf.php             # token CSRF
│   ├── rate_limit.php       # rate limit berbasis file + session
│   ├── audit.php            # catat_audit() ke tabel audit_log
│   ├── functions.php        # helper umum, upload, notifikasi, menu, duplikat buku
│   ├── helpers.php          # validasi tanggal, pagination
│   ├── isbn.php             # cache & HTTP helper ISBN
│   ├── book_metadata.php    # orkestrator metadata + deteksi kategori
│   ├── book_providers/      # google_books.php, open_library.php
│   ├── header.php / footer.php / admin_menu.php
├── admin/
│   ├── dashboard.php
│   ├── buku/                # index, tambah, edit, hapus, arsip, import, cari_isbn, _form
│   ├── kategori/            # index, edit, hapus
│   ├── anggota/             # index, tambah, edit, hapus, _form
│   ├── peminjaman/          # index, tambah, batalkan
│   ├── pengembalian/        # index, proses
│   ├── perpanjangan/        # index, proses
│   ├── pengajuan_peminjaman/# index, proses
│   └── laporan/index.php
├── anggota/                 # dashboard, peminjaman, riwayat, favorit, notifikasi,
│                            # ajukan_perpanjangan, pengajuan_peminjaman, ajukan_peminjaman,
│                            # batalkan_pengajuan_peminjaman, toggle_favorit, profil
├── assets/                  # css/app.css, js/, img/, uploads/covers, uploads/profil
├── database/
│   ├── perpustakaan.sql         # skema + data contoh
│   └── bersihkan_duplikat.php   # utilitas manual pembersihan data
├── index.php  detail.php  tentang.php
├── login.php  register.php  logout.php
├── manifest.json  sw.js     # PWA
└── composer.json
```

## Skema Database

| Tabel | Fungsi |
|---|---|
| `admin`, `anggota` | Akun. `anggota.status` = aktif/nonaktif |
| `kategori`, `buku` | Katalog. `buku.stok` total, `buku.tersedia` sisa, `is_arsip` |
| `peminjaman` | Transaksi: status `dipinjam`/`dikembalikan`, denda, admin pemroses |
| `perpanjangan_peminjaman` | Permintaan perpanjangan |
| `pengajuan_peminjaman` | Pengajuan pinjam. Kolom virtual `aktif_unik` mencegah pengajuan "menunggu" ganda |
| `notifikasi` | Notifikasi anggota. `kunci_unik` mencegah duplikat |
| `favorit` | Relasi anggota–buku |
| `audit_log` | Jejak tambah/ubah/hapus buku, kategori, pengajuan (belum ada halaman UI) |

> **Penting — hapus buku:** agar riwayat peminjaman tetap aman saat buku dihapus, `peminjaman.id_buku` harus `NULL`-able dengan FK `ON DELETE SET NULL`. Pastikan database Anda sudah begini:
>
> ```sql
> ALTER TABLE peminjaman DROP FOREIGN KEY peminjaman_ibfk_2;
> ALTER TABLE peminjaman MODIFY id_buku INT NULL;
> ALTER TABLE peminjaman ADD CONSTRAINT peminjaman_ibfk_2
>   FOREIGN KEY (id_buku) REFERENCES buku (id_buku) ON DELETE SET NULL;
> ```
>
> Verifikasi dengan `SHOW CREATE TABLE peminjaman;`. Jika belum, buku yang punya riwayat pinjam akan gagal dihapus.

## Instalasi

Kebutuhan: PHP 8.0+ (ekstensi `pdo_mysql`, `gd`, `fileinfo`, `curl` disarankan), MySQL/MariaDB, Apache (XAMPP/Laragon).

1. Salin folder `perpustakaan/` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buat database `perpustakaan`, lalu impor:
   ```bash
   mysql -u root perpustakaan < database/perpustakaan.sql
   ```
   atau lewat phpMyAdmin.
3. Terapkan perubahan FK pada bagian [Skema Database](#skema-database) bila perlu.
4. Sesuaikan konfigurasi (lihat bawah).
5. Buka `http://localhost/perpustakaan/`.

## Konfigurasi

Nilai dibaca dari environment variable atau file `.env` (diabaikan Git), dengan nilai bawaan di `config/database.php`:

```
DB_HOST=127.0.0.1
DB_NAME=perpustakaan
DB_USER=root
DB_PASS=
BASE_URL=/perpustakaan
APP_DEBUG=0
```

`BASE_URL` harus sesuai nama folder (kosongkan jika di root domain). Untuk produksi, jangan pakai `root` tanpa password.

## Akun Demo

> ⚠️ Hanya untuk lokal. Ganti atau hapus sebelum dipublikasikan.

| Peran | Login | Password |
|---|---|---|
| Admin | `admin` | `aliali123` |
| Anggota | `budi@example.com` | `budi123` |

Saat ini belum ada halaman ganti password admin. Ganti lewat database:

```sql
UPDATE admin SET password = '<hash dari password_hash()>' WHERE username = 'admin';
```

Dashboard admin menampilkan peringatan selama password masih `aliali123`.

## Keamanan

- Password: `password_hash` / `password_verify`.
- Seluruh query memakai prepared statement (`ATTR_EMULATE_PREPARES=false`). Kolom urutan/filter dinamis memakai whitelist.
- CSRF token di semua form POST. Output di-escape dengan `e()`.
- Login: rate limit 5 percobaan/15 menit per IP, pesan error generik, `session_regenerate_id`. Cookie sesi `HttpOnly`, `SameSite=Lax`, `Secure` di HTTPS.
- Upload: cek ekstensi, MIME asli (`finfo`), ukuran maks 2 MB, nama acak, `.htaccess` anti-eksekusi di folder upload.
- Transaksi memakai `FOR UPDATE` dan update stok bersyarat untuk mencegah race condition.
- Unduhan cover eksternal hanya dari host yang diizinkan (Open Library, Google Books).

Untuk Nginx (`.htaccess` hanya berlaku di Apache), tambahkan:

```nginx
location ~* ^/perpustakaan/assets/uploads/.*\.php$ { deny all; return 404; }
```

## Fitur ISBN

- **Tambah via ISBN:** Admin → Buku → Tambah. Metadata diambil dari Open Library dan Google Books, di-cache 24 jam di folder temp sistem. Periksa data sebelum menyimpan.
- **Impor batch:** Admin → Buku → Import. Maks 20 ISBN per submit; satu gagal tidak menghentikan yang lain; ringkasan berhasil/dilewati/gagal ditampilkan.
- Pengecekan duplikat memakai ISBN-10/13 dan kesamaan judul + penulis.

## PWA

Bisa dipasang di Android/Chrome lewat "Install aplikasi". Butuh HTTPS saat online (`localhost` boleh) dan `BASE_URL` yang benar. Aplikasi tetap membutuhkan PHP + MySQL, jadi tidak berfungsi penuh saat offline.

## Keterbatasan & Rencana

- Belum ada: fitur lupa/reset password, halaman profil & ganti password admin, UI audit log, pengiriman email/WhatsApp (notifikasi hanya di dalam aplikasi).
- Kolom `anggota.reset_token` dan `reset_expiry` sudah ada di skema untuk fitur reset password nanti.
- Status anggota nonaktif belum dicek ulang pada sesi yang sedang berjalan.
- Rate limit memakai IP (`REMOTE_ADDR`); di balik proxy perlu penyesuaian.
- Tailwind masih via CDN; untuk produksi sebaiknya di-build.
- Belum ada pengujian otomatis.

## Lisensi

Proyek pembelajaran (UKK). Cover buku dan metadata berasal dari sumber publik (Open Library, Google Books).
