<?php
/**
 * Kumpulan fungsi bantu (helper functions)
 */

// Membersihkan output agar aman dari XSS
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Membersihkan input dasar
function clean($string) {
    return trim(strip_tags($string ?? ''));
}

// Format tanggal Indonesia: 21 Agustus 2026
function format_tanggal($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $ts = strtotime($tanggal);
    return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

// Format Rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Redirect helper
function redirect($path) {
    header('Location: ' . BASE_URL . $path);
    exit;
}

// Menghasilkan kode buku otomatis (BK-0001, BK-0002, dst)
// Aman terhadap race condition bila dipanggil di dalam transaksi (pakai FOR UPDATE)
function generate_kode_buku(PDO $pdo) {
    if ($pdo->inTransaction()) {
        $stmt = $pdo->query("SELECT kode_buku FROM buku ORDER BY id_buku DESC LIMIT 1 FOR UPDATE");
    } else {
        $stmt = $pdo->query("SELECT kode_buku FROM buku ORDER BY id_buku DESC LIMIT 1");
    }
    $last = $stmt->fetchColumn();
    $next = 1;
    if ($last) {
        $angka = (int) substr($last, 3);
        $next = $angka + 1;
    }
    return 'BK-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// Menghasilkan nomor anggota otomatis (A0001, A0002, dst)
function generate_nomor_anggota(PDO $pdo) {
    if ($pdo->inTransaction()) {
        $stmt = $pdo->query("SELECT nomor_anggota FROM anggota ORDER BY id_anggota DESC LIMIT 1 FOR UPDATE");
    } else {
        $stmt = $pdo->query("SELECT nomor_anggota FROM anggota ORDER BY id_anggota DESC LIMIT 1");
    }
    $last = $stmt->fetchColumn();
    $next = 1;
    if ($last) {
        $angka = (int) substr($last, 1);
        $next = $angka + 1;
    }
    return 'A' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// Menghitung selisih hari keterlambatan (0 jika belum/tidak terlambat)
function hitung_keterlambatan($tanggal_jatuh_tempo, $tanggal_kembali = null) {
    $tgl_bandingkan = $tanggal_kembali ? $tanggal_kembali : date('Y-m-d');
    $jatuh_tempo = new DateTime($tanggal_jatuh_tempo);
    $bandingkan  = new DateTime($tgl_bandingkan);
    if ($bandingkan <= $jatuh_tempo) return 0;
    return (int) $jatuh_tempo->diff($bandingkan)->days;
}

// Menghitung denda berdasarkan jumlah hari terlambat
function hitung_denda($hari_terlambat) {
    return max(0, $hari_terlambat) * DENDA_PER_HARI;
}

// Cek apakah suatu tanggal (format Y-m-d) jatuh pada hari Minggu
function is_hari_minggu($tanggal) {
    return (int) date('w', strtotime($tanggal)) === 0;
}

// Validasi & pindahkan file gambar upload ke folder tujuan.
// Dipakai bersama oleh upload_cover() dan upload_foto_profil() agar aturan
// validasi (ekstensi, MIME, ukuran maksimal) hanya perlu dijaga di satu tempat.
// Mengembalikan nama file baru, atau null jika memang tidak ada file yang diupload.
// Melempar Exception berisi pesan error jika file tidak valid.
function validasi_dan_simpan_gambar($file, $dir_tujuan, $prefix_nama, $label = 'file') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // tidak ada file yang diupload, tidak masalah
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Terjadi kesalahan saat mengupload {$label}.");
    }

    $izin_ekstensi = ['jpg', 'jpeg', 'png', 'webp'];
    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ekstensi, $izin_ekstensi)) {
        throw new Exception("Format {$label} harus JPG, JPEG, PNG, atau WEBP.");
    }

    // Validasi tipe MIME sebenarnya (bukan hanya ekstensi)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $izin_mime = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $izin_mime)) {
        throw new Exception("File {$label} yang diupload bukan gambar yang valid.");
    }

    $maks_ukuran = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maks_ukuran) {
        throw new Exception("Ukuran {$label} maksimal 2MB.");
    }

    if (!is_dir($dir_tujuan)) {
        mkdir($dir_tujuan, 0755, true);
    }

    $nama_baru = $prefix_nama . '_' . uniqid() . '.' . $ekstensi;
    $tujuan = $dir_tujuan . $nama_baru;
    if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
        throw new Exception("Gagal menyimpan {$label}.");
    }

    // Buat thumbnail 400px (opsional, gagal thumbnail tidak menggagalkan upload)
    try {
        buat_thumbnail($tujuan, $dir_tujuan, $nama_baru, 400);
    } catch (Throwable $e) {
        error_log('Thumbnail gagal: ' . $e->getMessage());
    }

    return $nama_baru;
}

function buat_thumbnail($path_asli, $dir_tujuan, $nama_file, $lebar_max = 400) {
    if (!extension_loaded('gd')) return;
    $info = @getimagesize($path_asli);
    if (!$info) return;
    [$w, $h, $type] = $info;
    if ($w <= $lebar_max) return; // sudah kecil
    $ratio = $lebar_max / $w;
    $nw = $lebar_max;
    $nh = (int) round($h * $ratio);
    $src = null;
    if ($type === IMAGETYPE_JPEG) $src = @imagecreatefromjpeg($path_asli);
    elseif ($type === IMAGETYPE_PNG) $src = @imagecreatefrompng($path_asli);
    elseif ($type === IMAGETYPE_WEBP) $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path_asli) : null;
    if (!$src) return;
    $dst = imagecreatetruecolor($nw, $nh);
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $thumb_path = $dir_tujuan . 'thumb_' . $nama_file;
    if ($type === IMAGETYPE_JPEG) imagejpeg($dst, $thumb_path, 82);
    elseif ($type === IMAGETYPE_PNG) imagepng($dst, $thumb_path, 6);
    elseif ($type === IMAGETYPE_WEBP) imagewebp($dst, $thumb_path, 82);
    imagedestroy($src);
    imagedestroy($dst);
}

// Validasi & upload cover buku. Mengembalikan nama file baru atau null jika tidak ada file.
function upload_cover($file) {
    return validasi_dan_simpan_gambar($file, UPLOAD_DIR, 'cover', 'cover');
}


// Mengunduh cover dari sumber resmi yang diizinkan (Open Library + Google Books) dan menyimpannya sebagai cover lokal.
function download_remote_cover($url) {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return null;
    $parts = parse_url($url);
    $host = strtolower($parts['host'] ?? '');
    $scheme = strtolower($parts['scheme'] ?? '');
    // Izinkan host resmi; Google Books thumbnail kadang dari books.googleusercontent.com / lh3.googleusercontent.com
    $allowedHosts = ['covers.openlibrary.org', 'books.googleusercontent.com', 'books.google.com', 'lh3.googleusercontent.com', 'lh4.googleusercontent.com', 'lh5.googleusercontent.com', 'lh6.googleusercontent.com'];
    if ($scheme !== 'https' || !in_array($host, $allowedHosts, true)) {
        throw new Exception('Sumber cover tidak diizinkan.');
    }

    $data = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => 'Perpustakaan-Umum-Sejahtera/1.0 (educational project)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $data = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($data === false || $http < 200 || $http >= 300) $data = false;
    } else {
        $context = stream_context_create(['http' => ['timeout' => 12, 'header' => "User-Agent: Perpustakaan-Umum-Sejahtera/1.0\r\n"]]);
        $data = @file_get_contents($url, false, $context);
    }

    if ($data === false || strlen($data) > 2 * 1024 * 1024) return null;
    $tmp = tempnam(sys_get_temp_dir(), 'cover_');
    file_put_contents($tmp, $data);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) { @unlink($tmp); return null; }
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = 'cover_' . uniqid() . '.' . $allowed[$mime];
    if (!rename($tmp, UPLOAD_DIR . $name)) { @unlink($tmp); return null; }
    return $name;
}

// Menghapus file cover lama (jika ada dan bukan default) + thumbnail terkait
function hapus_cover($nama_file) {
    if (empty($nama_file)) return;
    // Batasi path traversal: hanya basename yang diperbolehkan, tetap di UPLOAD_DIR
    $nama_file = basename($nama_file);
    // Hapus cover utama
    $path_utama = UPLOAD_DIR . $nama_file;
    if (file_exists($path_utama)) {
        @unlink($path_utama);
    }
    // Hapus thumbnail yang dibuat oleh buat_thumbnail() (nama: thumb_$nama_file)
    // Cek eksistensi dengan basename yang sama untuk cegah path traversal
    $path_thumb = UPLOAD_DIR . 'thumb_' . $nama_file;
    if (file_exists($path_thumb)) {
        @unlink($path_thumb);
    }
}

// URL cover, fallback ke gambar default jika kosong
// Jika thumbnail tersedia, gunakan thumb untuk katalog agar lebih cepat
function cover_url($nama_file, $use_thumb = false) {
    if (empty($nama_file)) {
        return BASE_URL . '/assets/img/no-cover.svg';
    }
    if ($use_thumb) {
        $thumb = 'thumb_' . $nama_file;
        if (file_exists(UPLOAD_DIR . $thumb)) {
            return UPLOAD_URL . $thumb;
        }
    }
    return UPLOAD_URL . $nama_file;
}

function cover_thumb_url($nama_file) {
    return cover_url($nama_file, true);
}

// Validasi & upload foto profil (anggota/admin). Mengembalikan nama file baru atau null jika tidak ada file.
function upload_foto_profil($file) {
    return validasi_dan_simpan_gambar($file, UPLOAD_PROFIL_DIR, 'profil', 'foto profil');
}

// Menghapus file foto profil lama (jika ada)
function hapus_foto_profil($nama_file) {
    if ($nama_file && file_exists(UPLOAD_PROFIL_DIR . $nama_file)) {
        @unlink(UPLOAD_PROFIL_DIR . $nama_file);
    }
}

// URL foto profil, fallback ke avatar default jika kosong
function foto_profil_url($nama_file) {
    if (empty($nama_file)) {
        return BASE_URL . '/assets/img/no-avatar.svg';
    }
    return UPLOAD_PROFIL_URL . $nama_file;
}

// Badge status ketersediaan buku (dipakai berulang di banyak halaman)
// $is_arsip: jika true, tampilkan badge Arsip menggantikan badge ketersediaan
function badge_ketersediaan($tersedia, $is_arsip = false) {
    if ($is_arsip) {
        return '<span class="inline-flex items-center gap-1 rounded-full border text-[11px] font-semibold px-2.5 py-1" style="background: var(--badge-slate-bg); border-color: var(--badge-slate-border); color: var(--badge-slate-text)"><i class="bi bi-archive text-[11px]"></i>Diarsipkan</span>';
    }
    if ($tersedia > 0) {
        return '<span class="inline-flex items-center gap-1.5 rounded-full border text-[11px] font-semibold px-2.5 py-1" style="background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)"><span class="w-1.5 h-1.5 rounded-full" style="background:#10b981"></span>Tersedia · ' . (int)$tersedia . '</span>';
    }
    return '<span class="inline-flex items-center gap-1.5 rounded-full border text-[11px] font-semibold px-2.5 py-1" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)"><span class="w-1.5 h-1.5 rounded-full" style="background:#ef4444"></span>Habis</span>';
}

function badge_arsip($is_arsip) {
    if ($is_arsip) return badge_ketersediaan(0, true);
    return '';
}

function is_buku_arsip($buku) {
    return !empty($buku['is_arsip']);
}

// Cek apakah sebuah buku sudah difavoritkan oleh anggota tertentu
function is_favorit(PDO $pdo, $id_anggota, $id_buku) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorit WHERE id_anggota = :a AND id_buku = :b");
    $stmt->execute([':a' => $id_anggota, ':b' => $id_buku]);
    return (int) $stmt->fetchColumn() > 0;
}

// Flash message sederhana via session
function set_flash($tipe, $pesan) {
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function tampilkan_flash() {
    if (!empty($_SESSION['flash'])) {
        $tipe = $_SESSION['flash']['tipe'] === 'error' ? 'red' : ($_SESSION['flash']['tipe'] === 'warning' ? 'amber' : 'emerald');
        $pesan = e($_SESSION['flash']['pesan']);
        $map = [
            'red' => 'background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)',
            'amber' => 'background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)',
            'emerald' => 'background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)',
        ];
        $style = $map[$tipe] ?? $map['emerald'];
        echo "<div class=\"mb-4 rounded-lg border px-4 py-3 text-sm\" style=\"{$style}\">{$pesan}</div>";
        unset($_SESSION['flash']);
    }
}

// ========================= Daftar menu navigasi =========================
// Sumber tunggal untuk daftar menu Panel Pengelola (admin), dipakai bersama oleh
// drawer mobile (includes/header.php) dan sidebar desktop (includes/admin_menu.php)
// agar keduanya selalu sinkron saat ada menu yang ditambah/diubah.
function admin_menu_items() {
    return [
        'dashboard'             => ['label' => 'Dashboard',         'icon' => 'bi-speedometer2',       'url' => '/admin/dashboard.php'],
        'buku'                  => ['label' => 'Data Buku',         'icon' => 'bi-book',               'url' => '/admin/buku/index.php'],
        'kategori'              => ['label' => 'Kategori',          'icon' => 'bi-tags',               'url' => '/admin/kategori/index.php'],
        'anggota'               => ['label' => 'Anggota',           'icon' => 'bi-people',             'url' => '/admin/anggota/index.php'],
        'peminjaman'            => ['label' => 'Peminjaman',        'icon' => 'bi-journal-plus',       'url' => '/admin/peminjaman/index.php'],
        'perpanjangan'          => ['label' => 'Perpanjangan',      'icon' => 'bi-arrow-repeat',       'url' => '/admin/perpanjangan/index.php'],
        'pengajuan_peminjaman'  => ['label' => 'Pengajuan Pinjam',  'icon' => 'bi-journal-arrow-up',   'url' => '/admin/pengajuan_peminjaman/index.php'],
        'pengembalian'          => ['label' => 'Pengembalian',      'icon' => 'bi-arrow-return-left',  'url' => '/admin/pengembalian/index.php'],
        'laporan'               => ['label' => 'Laporan',           'icon' => 'bi-bar-chart',          'url' => '/admin/laporan/index.php'],
        'pengajuan'             => ['label' => 'Pengajuan Buku',    'icon' => 'bi-file-earmark-plus',  'url' => '/admin/pengajuan/index.php'],
        'audit_log'             => ['label' => 'Audit Log',         'icon' => 'bi-journal-text',       'url' => '/admin/audit_log/index.php'],
    ];
}

// Sumber tunggal untuk daftar menu Area Anggota, dipakai bersama oleh drawer
// mobile dan sidebar desktop di includes/header.php.
function member_menu_items() {
    return [
        'dashboard'            => ['label' => 'Dashboard',         'icon' => 'bi-house-door',        'url' => '/anggota/dashboard.php'],
        'peminjaman'           => ['label' => 'Peminjaman',        'icon' => 'bi-bookmark',          'url' => '/anggota/peminjaman.php'],
        'pengajuan_peminjaman' => ['label' => 'Pengajuan Pinjam',  'icon' => 'bi-journal-arrow-up',   'url' => '/anggota/pengajuan_peminjaman.php'],
        'riwayat'              => ['label' => 'Riwayat',           'icon' => 'bi-clock-history',     'url' => '/anggota/riwayat.php'],
        'favorit'              => ['label' => 'Favorit',           'icon' => 'bi-heart',             'url' => '/anggota/favorit.php'],
        'pengajuan'            => ['label' => 'Pengajuan Buku',    'icon' => 'bi-file-earmark-plus', 'url' => '/anggota/pengajuan.php'],
        'notifikasi'           => ['label' => 'Notifikasi',        'icon' => 'bi-bell',              'url' => '/anggota/notifikasi.php'],
        'profil'               => ['label' => 'Profil',            'icon' => 'bi-person',            'url' => '/anggota/profil.php'],
    ];
}

// Jumlah pengajuan perpanjangan yang masih menunggu persetujuan admin.
// Dipakai untuk lencana notifikasi di menu "Perpanjangan" (drawer & sidebar admin).
function jumlah_perpanjangan_menunggu(PDO $pdo) {
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM perpanjangan_peminjaman WHERE status='menunggu'")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

// Jumlah pengajuan peminjaman yang masih menunggu (untuk badge admin).
function jumlah_pengajuan_peminjaman_menunggu(PDO $pdo) {
    try {
        return (int) $pdo->query("SELECT COUNT(*) FROM pengajuan_peminjaman WHERE status='menunggu'")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

// Badge status pengajuan peminjaman (menunggu/disetujui/ditolak/dibatalkan)
function badge_pengajuan_peminjaman($status) {
    $s = strtolower($status ?? '');
    if ($s === 'menunggu') return '<span class="inline-flex items-center gap-1.5 rounded-full border text-[11px] font-bold px-2.5 py-1" style="background: var(--badge-amber-bg); border-color: var(--badge-amber-border); color: var(--badge-amber-text)"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Menunggu</span>';
    if ($s === 'disetujui') return '<span class="inline-flex items-center gap-1.5 rounded-full border text-[11px] font-bold px-2.5 py-1" style="background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border); color: var(--badge-emerald-text)"><span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>Disetujui</span>';
    if ($s === 'ditolak') return '<span class="inline-flex items-center gap-1.5 rounded-full border text-[11px] font-bold px-2.5 py-1" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)"><span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>Ditolak</span>';
    if ($s === 'dibatalkan') return '<span class="inline-flex items-center gap-1.5 rounded-full border text-[11px] font-bold px-2.5 py-1" style="background: var(--badge-slate-bg); border-color: var(--badge-slate-border); color: var(--badge-slate-text)"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Dibatalkan</span>';
    return '<span class="inline-flex items-center gap-1.5 rounded-full border text-[11px] font-bold px-2.5 py-1" style="background:var(--surface-2); border-color:var(--border); color:var(--text-faint)">'.e($status).'</span>';
}

// ========================= Notifikasi & Perpanjangan =========================
function buat_notifikasi_anggota(PDO $pdo, $id_anggota, $judul, $pesan, $tipe = 'info', $link = null, $kunci_unik = null) {
    // Normalisasi kunci_unik: '' untuk notifikasi tanpa dedup, hindari NULL yang tidak ter-dedup oleh UNIQUE
    if ($kunci_unik === null) $kunci_unik = '';
    // Jika kunci kosong, pakai INSERT biasa (tidak perlu IGNORE karena tidak ada dedup)
    if ($kunci_unik === '') {
        $stmt = $pdo->prepare("INSERT INTO notifikasi (id_anggota, judul, pesan, tipe, link, kunci_unik)
                               VALUES (:anggota, :judul, :pesan, :tipe, :link, :kunci)");
    } else {
        $stmt = $pdo->prepare("INSERT IGNORE INTO notifikasi (id_anggota, judul, pesan, tipe, link, kunci_unik)
                               VALUES (:anggota, :judul, :pesan, :tipe, :link, :kunci)");
    }
    $stmt->execute([
        ':anggota' => $id_anggota,
        ':judul' => $judul,
        ':pesan' => $pesan,
        ':tipe' => $tipe,
        ':link' => $link,
        ':kunci' => $kunci_unik,
    ]);
}

function sinkronkan_notifikasi_anggota(PDO $pdo, $id_anggota) {
    // Throttle: jangan sinkron lebih dari 1x per 5 menit per session untuk kurangi write
    $key = 'notif_sync_' . $id_anggota;
    if (isset($_SESSION[$key]) && (time() - $_SESSION[$key] < 300)) {
        return;
    }
    $_SESSION[$key] = time();

    // Membuat pengingat otomatis saat halaman anggota dibuka. Ini tidak membutuhkan cron job.
    $stmt = $pdo->prepare("SELECT p.id_peminjaman, p.tanggal_jatuh_tempo, COALESCE(b.judul,'(buku dihapus)') AS judul
                           FROM peminjaman p
                           LEFT JOIN buku b ON b.id_buku = p.id_buku
                           WHERE p.id_anggota = :id AND p.status = 'dipinjam'");
    $stmt->execute([':id' => $id_anggota]);
    $pinjaman = $stmt->fetchAll();
    $hari_ini = new DateTime(date('Y-m-d'));

    foreach ($pinjaman as $p) {
        $jatuh = new DateTime($p['tanggal_jatuh_tempo']);
        $selisih = (int)$hari_ini->diff($jatuh)->format('%r%a');

        if ($selisih < 0) {
            $telat = abs($selisih);
            buat_notifikasi_anggota(
                $pdo, $id_anggota,
                'Peminjaman sudah melewati jatuh tempo',
                'Buku "' . $p['judul'] . '" sudah terlambat ' . $telat . ' hari. Segera kembalikan buku untuk mencegah denda bertambah.',
                'danger',
                '/anggota/peminjaman.php',
                'telat:' . $p['id_peminjaman']
            );
        } elseif ($selisih <= NOTIFIKASI_JATUH_TEMPO_HARI) {
            $teks = $selisih === 0 ? 'hari ini' : ($selisih === 1 ? 'besok' : $selisih . ' hari lagi');
            buat_notifikasi_anggota(
                $pdo, $id_anggota,
                'Jatuh tempo semakin dekat',
                'Buku "' . $p['judul'] . '" jatuh tempo ' . $teks . ' (' . format_tanggal($p['tanggal_jatuh_tempo']) . ').',
                'warning',
                '/anggota/peminjaman.php',
                'jatuh_tempo:' . $p['id_peminjaman'] . ':' . $p['tanggal_jatuh_tempo']
            );
        }
    }
}

function ambil_notifikasi_anggota(PDO $pdo, $id_anggota, $limit = 8) {
    $limit = max(1, min(50, (int)$limit));
    // Gunakan binding integer untuk LIMIT agar konsisten dengan prepared statement lain.
    // PDO MySQL dengan ATTR_EMULATE_PREPARES=false mendukung LIMIT :limit via PARAM_INT.
    // Jika driver tidak mendukung, fallback ke interpolasi int yang sudah di-clamp (aman).
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifikasi WHERE id_anggota = :id ORDER BY created_at DESC, id_notifikasi DESC LIMIT :limit");
        $stmt->bindValue(':id', (int)$id_anggota, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        // Fallback: interpolasi dengan casting eksplisit (nilai sudah di-clamp 1-50)
        $limit = (int)$limit;
        $stmt = $pdo->prepare("SELECT * FROM notifikasi WHERE id_anggota = :id ORDER BY created_at DESC, id_notifikasi DESC LIMIT $limit");
        $stmt->bindValue(':id', (int)$id_anggota, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

function jumlah_notifikasi_belum_dibaca(PDO $pdo, $id_anggota) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE id_anggota = :id AND dibaca = 0");
    $stmt->execute([':id' => $id_anggota]);
    return (int)$stmt->fetchColumn();
}
