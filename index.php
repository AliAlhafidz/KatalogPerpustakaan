<?php
require_once __DIR__ . '/config/bootstrap.php';

// ---- Ambil parameter pencarian & filter ----
$kata_kunci  = clean($_GET['q'] ?? '');
$id_kategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$filter_tersedia = isset($_GET['tersedia']) && $_GET['tersedia'] === '1';
$sort_raw    = $_GET['sort'] ?? '';
$sort_whitelist = ['judul_asc' => 'b.judul ASC', 'terbaru' => 'b.created_at DESC, b.id_buku DESC', 'stok_desc' => 'b.stok DESC, b.judul ASC'];
$sort = isset($sort_whitelist[$sort_raw]) ? $sort_raw : '';
$order_by = $sort !== '' ? $sort_whitelist[$sort] : 'b.judul ASC';
$halaman     = isset($_GET['halaman']) ? max(1, (int) $_GET['halaman']) : 1;
$per_halaman = 12;
$offset      = ($halaman - 1) * $per_halaman;

// ---- Susun query dinamis dengan prepared statement ----
$where  = ['b.is_arsip = 0'];
$params = [];

if ($kata_kunci !== '') {
    $where[] = '(b.judul LIKE :kw OR b.penulis LIKE :kw2 OR b.isbn LIKE :kw3 OR b.penerbit LIKE :kw4)';
    $params[':kw']  = '%' . $kata_kunci . '%';
    $params[':kw2'] = '%' . $kata_kunci . '%';
    $params[':kw3'] = '%' . $kata_kunci . '%';
    $params[':kw4'] = '%' . $kata_kunci . '%';
}
if ($id_kategori > 0) {
    $where[] = 'b.id_kategori = :kategori';
    $params[':kategori'] = $id_kategori;
}
if ($filter_tersedia) {
    $where[] = 'b.tersedia > 0';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Hitung total data untuk pagination
$sql_total = "SELECT COUNT(*) FROM buku b $where_sql";
$stmt = $pdo->prepare($sql_total);
$stmt->execute($params);
$total_data = (int) $stmt->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $per_halaman));
if ($halaman > $total_halaman) {
    $halaman = $total_halaman;
    $offset = ($halaman - 1) * $per_halaman;
}

// Ambil data buku
$sql = "SELECT b.id_buku, b.judul, b.penulis, b.cover, b.tersedia, b.is_arsip, b.id_kategori, k.nama_kategori
        FROM buku b
        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
        $where_sql
        ORDER BY $order_by
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar_buku = $stmt->fetchAll();

// Ambil daftar kategori
$kategori_list = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC")->fetchAll();

// Hitung jumlah buku per kategori untuk chips
$kategori_counts = [];
try {
    $stmtCnt = $pdo->query("SELECT k.id_kategori, COUNT(b.id_buku) AS jml FROM kategori k LEFT JOIN buku b ON b.id_kategori=k.id_kategori AND b.is_arsip=0 GROUP BY k.id_kategori");
    foreach ($stmtCnt->fetchAll() as $r) $kategori_counts[(int)$r['id_kategori']] = (int)$r['jml'];
} catch (Throwable $e) {}

// Ambil buku terpopuler — hanya di katalog utama tanpa filter, dengan cache file 5 menit
$buku_populer = [];
if ($kata_kunci === '' && $id_kategori === 0 && !$filter_tersedia && $sort === '') {
    $cache_populer = rtrim(sys_get_temp_dir(), '/\\') . '/perpus_populer_' . md5(BASE_URL) . '.json';
    $use_cache = false;
    if (is_file($cache_populer) && (time() - filemtime($cache_populer) < 300)) {
        $raw = @file_get_contents($cache_populer);
        $cached = json_decode($raw, true);
        if (is_array($cached)) { $buku_populer = $cached; $use_cache = true; }
    }
    if (!$use_cache) {
        $stmt_populer = $pdo->query("SELECT b.id_buku, b.judul, b.penulis, b.cover, b.tersedia, b.is_arsip, k.nama_kategori,
            COUNT(DISTINCT p.id_peminjaman) AS jumlah_dipinjam,
            COUNT(DISTINCT f.id_favorit) AS jumlah_favorit
            FROM buku b
            LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
            LEFT JOIN peminjaman p ON p.id_buku = b.id_buku
            LEFT JOIN favorit f ON f.id_buku = b.id_buku
            WHERE b.is_arsip = 0
            GROUP BY b.id_buku, b.judul, b.penulis, b.cover, b.tersedia, b.is_arsip, k.nama_kategori
            ORDER BY jumlah_dipinjam DESC, jumlah_favorit DESC, b.judul ASC
            LIMIT 5");
        $buku_populer = $stmt_populer->fetchAll();
        @file_put_contents($cache_populer, json_encode($buku_populer, JSON_UNESCAPED_UNICODE), LOCK_EX);
        @chmod($cache_populer, 0600);
    }
}

// Ambil daftar id buku favorit anggota yang sedang login
$favorit_ids = [];
if (is_anggota()) {
    $stmt = $pdo->prepare("SELECT id_buku FROM favorit WHERE id_anggota = :id");
    $stmt->execute([':id' => $_SESSION['id_anggota']]);
    $favorit_ids = array_column($stmt->fetchAll(), 'id_buku');
}

// Stats editorial — data real
$stats_total_buku = 0; $stats_total_kategori = 0; $stats_tersedia = 0;
try {
    $stats_total_buku = (int)$pdo->query("SELECT COUNT(*) FROM buku WHERE is_arsip=0")->fetchColumn();
    $stats_total_kategori = (int)$pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
    $stats_tersedia = (int)$pdo->query("SELECT COALESCE(SUM(tersedia),0) FROM buku WHERE is_arsip=0")->fetchColumn();
} catch (Throwable $e) {}

// Featured book — pilihan minggu ini (populer pertama atau terbaru)
$featured = null;
$featured_desc = null;
$featured_tahun = null;
if (!empty($buku_populer)) {
    $featured = $buku_populer[0];
    // ambil deskripsi & tahun untuk featured jika ada
    try {
        $stmtF = $pdo->prepare("SELECT deskripsi, tahun_terbit, penerbit FROM buku WHERE id_buku=:id");
        $stmtF->execute([':id'=>$featured['id_buku']]);
        $extra = $stmtF->fetch();
        if ($extra) { $featured_desc = $extra['deskripsi']; $featured_tahun = $extra['tahun_terbit']; $featured['penerbit'] = $extra['penerbit']; }
    } catch (Throwable $e) {}
} else {
    try {
        $stmtF = $pdo->query("SELECT b.id_buku, b.judul, b.penulis, b.cover, b.tersedia, b.is_arsip, k.nama_kategori, b.deskripsi, b.tahun_terbit, b.penerbit FROM buku b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori WHERE b.is_arsip=0 ORDER BY b.created_at DESC, b.id_buku DESC LIMIT 1");
        $featured = $stmtF->fetch();
        if ($featured) { $featured_desc = $featured['deskripsi']; $featured_tahun = $featured['tahun_terbit']; }
    } catch (Throwable $e) {}
}

$page_title = 'Katalog Buku';
require_once __DIR__ . '/includes/header.php';
?>

<style>
  /* Hero discovery */
  .hero-discovery{position:relative;overflow:hidden;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-xs)}
  .hero-discovery::before{content:"";position:absolute;inset:0;background:
    radial-gradient(420px 320px at 85% -10%, rgba(36,58,94,.06), transparent 60%),
    radial-gradient(520px 380px at -10% 100%, rgba(36,58,94,.04), transparent 65%);
    pointer-events:none}
  .hero-illust{position:relative;width:100%;height:100%;min-height:220px;display:flex;align-items:center;justify-content:center;pointer-events:none}
  .hero-illust svg{width:min(100%,340px);height:auto}
  .hero-pattern{position:absolute;inset:0;opacity:.035;pointer-events:none;background-image:
    radial-gradient(circle at 1px 1px, var(--text) 1px, transparent 0);
    background-size:22px 22px}
  /* Category chips */
  .cat-row{display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch;padding-bottom:4px}
  .cat-row::-webkit-scrollbar{display:none}
  .cat-chip{display:inline-flex;align-items:center;gap:7px;padding:8px 14px;border-radius:999px;border:1px solid var(--border);background:var(--surface);color:var(--text-muted);font-size:13px;font-weight:600;white-space:nowrap;transition:all 140ms ease;flex-shrink:0}
  .cat-chip:hover{border-color:var(--border-strong);background:var(--surface-2);color:var(--text);transform:translateY(-1px)}
  .cat-chip.is-active{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(36,58,94,.12)}
  .cat-chip .cat-icon{width:22px;height:22px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;background:var(--surface-2);border:1px solid var(--border);flex-shrink:0}
  .cat-chip.is-active .cat-icon{background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.22);color:#fff}
  /* Featured */
  .featured-card{position:relative;overflow:hidden;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-xs)}
  .featured-card::before{content:"";position:absolute;inset:0;background:linear-gradient(135deg, var(--accent-soft) 0%, transparent 55%);opacity:.9;pointer-events:none}
  .featured-cover{width:148px;height:210px;object-fit:cover;border-radius:10px;border:1px solid var(--border);box-shadow:0 8px 24px rgba(16,24,40,.10);background:var(--paper-2);flex-shrink:0}
  @media(max-width:640px){ .featured-cover{width:124px;height:176px} }
  /* Book cards polish */
  .book-card{transition:border-color 160ms ease, box-shadow 160ms ease, transform 160ms ease}
  @media (prefers-reduced-motion: no-preference){
    .book-card:hover{transform:translateY(-2px);border-color:var(--border-strong);box-shadow:0 4px 16px rgba(16,24,40,.06)}
    .book-card:hover img{transform:scale(1.015)}
  }
  .book-card img{transition:transform 220ms ease, opacity 160ms ease}
  .book-card:hover img{opacity:.97}
  .fav-btn{transition:transform 140ms ease, background 140ms ease, border-color 140ms ease, color 140ms ease}
  .fav-btn:hover{transform:scale(1.06)}
  .fav-btn:active{transform:scale(.96)}
  /* Stats editorial */
  .stats-editorial{border:1px solid var(--border);background:var(--surface);border-radius:var(--radius-lg);overflow:hidden}
  .stats-editorial .stat{position:relative;padding:18px 16px;text-align:center}
  .stats-editorial .stat + .stat{border-left:1px solid var(--border)}
  @media(max-width:640px){ .stats-editorial .stat + .stat{border-left:0;border-top:1px solid var(--border)} .stats-editorial{flex-direction:column} }
  /* Dark mode tweaks */
  html[data-theme="dark"] .hero-discovery{border-color:var(--border)}
  html[data-theme="dark"] .cat-chip{background:var(--surface-2);border-color:var(--border)}
  html[data-theme="dark"] .cat-chip.is-active{background:var(--accent);border-color:var(--accent);color:#fff}
  html[data-theme="dark"] .featured-card::before{opacity:.5}
</style>

<!-- HERO -->
<section class="hero-discovery mb-6">
  <div class="hero-pattern"></div>
  <div class="relative grid lg:grid-cols-[1.15fr_0.85fr] gap-6 p-5 sm:p-7 lg:p-8 items-center">
    <!-- Left -->
    <div class="min-w-0">
      <p class="text-[11px] font-bold tracking-[.14em] uppercase inline-flex items-center gap-2" style="color:var(--text-faint-2)">
        <span class="w-6 h-px" style="background:var(--border-strong)"></span> Katalog Perpustakaan · Klasik &amp; Modern
      </p>
      <h1 class="font-display text-[24px] sm:text-[30px] lg:text-[32px] font-bold tracking-tight leading-[1.15] mt-3" style="color:var(--text)">
        Temukan buku yang<br>ingin kamu <span style="color:var(--accent)">baca.</span>
      </h1>
      <p class="text-[14px] leading-6 mt-3 max-w-xl" style="color:var(--text-muted)">Jelajahi koleksi pilihan kami. Cari judul, penulis, ISBN atau penerbit — lalu buka detail untuk melihat rak dan sinopsis lengkap.</p>

      <form method="get" action="" class="mt-6 bg-white rounded-xl border p-2.5 shadow-sm" style="border-color:var(--border)">
        <div class="flex flex-col sm:flex-row gap-2">
          <div class="relative flex-1">
            <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-sm" style="color:var(--text-faint-2)"></i>
            <input type="text" name="q" value="<?= e($kata_kunci) ?>" placeholder="Cari judul, penulis, ISBN, atau penerbit..." class="w-full h-11 border rounded-lg pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)">
          </div>
          <div class="sm:w-48">
            <select name="kategori" class="w-full h-11 border rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface-2)">
              <option value="0">Semua kategori</option>
              <?php foreach ($kategori_list as $kat): ?>
                <option value="<?= (int)$kat['id_kategori'] ?>" <?= $id_kategori === (int)$kat['id_kategori'] ? 'selected' : '' ?>><?= e($kat['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="h-11 px-6 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold inline-flex items-center justify-center gap-2 shrink-0">
            <i class="bi bi-search text-xs"></i> Cari
          </button>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 pt-3 mt-3 border-t" style="border-color:var(--border-faint)">
          <label class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-semibold cursor-pointer transition" style="background:<?= $filter_tersedia ? '#ecfdf5' : 'var(--surface)' ?>; border-color:<?= $filter_tersedia ? '#a7f3d0' : 'var(--border)' ?>; color:<?= $filter_tersedia ? '#065f46' : 'var(--text-muted)' ?>">
            <input type="checkbox" name="tersedia" value="1" <?= $filter_tersedia ? 'checked' : '' ?> class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 w-3.5 h-3.5"> Hanya tersedia
          </label>
          <div class="flex items-center gap-2 ml-auto">
            <span class="text-xs font-medium hidden sm:inline" style="color:var(--text-faint)">Urutkan</span>
            <select name="sort" onchange="this.form.submit()" class="h-8 border rounded-lg px-2.5 text-xs font-semibold focus:outline-none" style="border-color:var(--border); background:var(--surface-2); color:var(--text-muted)">
              <option value="" <?= $sort==='' ? 'selected' : '' ?>>Judul A–Z</option>
              <option value="terbaru" <?= $sort==='terbaru' ? 'selected' : '' ?>>Terbaru</option>
              <option value="stok_desc" <?= $sort==='stok_desc' ? 'selected' : '' ?>>Stok terbanyak</option>
            </select>
          </div>
        </div>
      </form>

      <div class="flex items-center gap-3 mt-4 text-xs" style="color:var(--text-faint)">
        <span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full" style="background:var(--accent)"></span> <?= $stats_total_buku ?> buku</span>
        <span>·</span>
        <span><?= $stats_total_kategori ?> kategori</span>
        <span class="hidden sm:inline">·</span>
        <span class="hidden sm:inline"><?= $stats_tersedia ?> tersedia</span>
      </div>
    </div>

    <!-- Right illustration -->
    <div class="hero-illust hidden lg:flex">
      <svg viewBox="0 0 360 260" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true" class="select-none">
        <!-- soft blobs -->
        <ellipse cx="180" cy="220" rx="110" ry="18" fill="#243a5e" opacity="0.04"/>
        <ellipse cx="180" cy="36" rx="90" ry="40" fill="#243a5e" opacity="0.035"/>
        <!-- book stack -->
        <g opacity="0.96">
          <!-- book 1 -->
          <rect x="72" y="56" width="132" height="152" rx="10" fill="white" stroke="#e7e5e4" stroke-width="1.2"/>
          <rect x="72" y="56" width="14" height="152" rx="4" fill="#243a5e"/>
          <rect x="92" y="78" width="78" height="9" rx="4.5" fill="#f7f2eb"/>
          <rect x="92" y="96" width="58" height="6" rx="3" fill="#eef2f7"/>
          <rect x="92" y="108" width="72" height="6" rx="3" fill="#f7f2eb"/>
          <!-- book 2 tilted -->
          <g transform="rotate(-6 210 120)">
            <rect x="138" y="54" width="136" height="146" rx="10" fill="#fdfbf7" stroke="#e7e5e4" stroke-width="1.2"/>
            <rect x="138" y="54" width="14" height="146" rx="4" fill="#3d6a94"/>
            <rect x="162" y="80" width="84" height="8" rx="4" fill="#243a5e" opacity="0.08"/>
            <rect x="162" y="98" width="64" height="6" rx="3" fill="#243a5e" opacity="0.06"/>
          </g>
          <!-- small floating book -->
          <g transform="rotate(8 240 70)">
            <rect x="218" y="42" width="84" height="64" rx="8" fill="white" stroke="#e7e5e4"/>
            <rect x="218" y="42" width="10" height="64" rx="3" fill="#8fb4d6"/>
            <rect x="238" y="62" width="44" height="5" rx="2.5" fill="#243a5e" opacity="0.07"/>
          </g>
        </g>
        <!-- decorative dots -->
        <g opacity="0.18">
          <circle cx="74" cy="38" r="1.6" fill="#243a5e"/><circle cx="94" cy="32" r="1.2" fill="#243a5e"/><circle cx="268" cy="44" r="1.4" fill="#243a5e"/><circle cx="286" cy="68" r="1.1" fill="#243a5e"/>
        </g>
        <g opacity="0.45">
          <path d="M 40 200 Q 90 188 140 200" stroke="#e7e5e4" stroke-width="1" stroke-linecap="round" stroke-dasharray="3 5" fill="none"/>
        </g>
      </svg>
    </div>
  </div>
  <!-- Mobile illustration subtle -->
  <div class="lg:hidden px-5 pb-3 -mt-2 opacity-[0.035]" aria-hidden="true">
    <svg viewBox="0 0 360 60" class="w-full h-10" fill="none"><rect x="20" y="10" width="46" height="36" rx="5" fill="#243a5e"/><rect x="72" y="10" width="46" height="36" rx="5" fill="#3d6a94" opacity=".7"/><rect x="124" y="10" width="46" height="36" rx="5" fill="#8fb4d6" opacity=".6"/></svg>
  </div>
</section>

<!-- QUICK CATEGORIES -->
<section class="mb-6">
  <div class="flex items-end justify-between gap-3 mb-3">
    <div>
      <h2 class="font-display text-[15px] font-bold tracking-tight" style="color:var(--text)">Jelajahi Koleksi</h2>
      <p class="text-xs mt-1" style="color:var(--text-faint)">Temukan buku berdasarkan minatmu</p>
    </div>
    <span class="hidden sm:inline text-xs" style="color:var(--text-faint-2)"><?= count($kategori_list) ?> kategori</span>
  </div>
  <div class="cat-row">
    <?php
      $base_qs_cat = ($kata_kunci!=='' ? '&q='.urlencode($kata_kunci) : '') . ($filter_tersedia ? '&tersedia=1' : '') . ($sort!=='' ? '&sort='.urlencode($sort) : '');
      $cat_icon_map = [
        'fiksi'=>'bi-book','non'=>'bi-journal-text','sains'=>'bi-cpu','teknologi'=>'bi-cpu','sejarah'=>'bi-clock-history','agama'=>'bi-moon-stars','anak'=>'bi-emoji-smile','remaja'=>'bi-people','pendidikan'=>'bi-mortarboard','geografi'=>'bi-globe','fantasi'=>'bi-stars','umum'=>'bi-collection'
      ];
      $is_all_active = $id_kategori===0;
    ?>
    <a href="?kategori=0<?= $base_qs_cat ?>" class="cat-chip <?= $is_all_active ? 'is-active' : '' ?>">
      <span class="cat-icon"><i class="bi bi-grid"></i></span> Semua <span class="opacity-60 text-[11px]"><?= $stats_total_buku ?></span>
    </a>
    <?php foreach ($kategori_list as $kat):
      $kid = (int)$kat['id_kategori'];
      $active = $id_kategori===$kid;
      $lower = strtolower($kat['nama_kategori']);
      $icon = 'bi-tag';
      foreach ($cat_icon_map as $key=>$bi) if (strpos($lower,$key)!==false) { $icon=$bi; break; }
      $cnt = $kategori_counts[$kid] ?? 0;
    ?>
      <a href="?kategori=<?= $kid ?><?= $base_qs_cat ?>" class="cat-chip <?= $active ? 'is-active' : '' ?>">
        <span class="cat-icon"><i class="bi <?= $icon ?>"></i></span> <?= e($kat['nama_kategori']) ?> <span class="opacity-60 text-[11px]"><?= $cnt ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- FEATURED BOOK -->
<?php if (!empty($featured)): 
  $f_is_fav = in_array($featured['id_buku'], $favorit_ids);
?>
<section class="featured-card mb-6 p-4 sm:p-6">
  <div class="relative flex flex-col sm:flex-row gap-5 sm:gap-6">
    <div class="flex gap-4 sm:gap-5">
      <img src="<?= e(cover_thumb_url($featured['cover'] ?? null)) ?>" alt="Cover <?= e($featured['judul']) ?>" loading="lazy" width="300" height="420" onerror="this.src='<?= BASE_URL ?>/assets/img/no-cover.svg'" class="featured-cover">
      <div class="sm:hidden flex-1 min-w-0">
        <p class="text-[11px] font-bold tracking-[.12em] uppercase flex items-center gap-1.5" style="color:var(--accent-text)"><span class="w-6 h-px" style="background:var(--accent)"></span> Pilihan Minggu Ini</p>
        <h3 class="font-display text-[16px] font-bold leading-5 mt-1.5 line-clamp-3" style="color:var(--text)"><?= e($featured['judul']) ?></h3>
        <p class="text-xs mt-1 truncate" style="color:var(--text-faint)"><?= e($featured['penulis']) ?></p>
        <div class="mt-2 flex flex-wrap gap-1.5">
          <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold" style="background:var(--surface); border-color:var(--border); color:var(--text-muted)"><i class="bi bi-tag text-[11px] mr-1"></i><?= e($featured['nama_kategori'] ?? 'Tanpa Kategori') ?></span>
          <?php if (!empty($featured_tahun)): ?><span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-semibold" style="background:var(--surface); border-color:var(--border); color:var(--text-muted)"><?= e($featured_tahun) ?></span><?php endif; ?>
        </div>
      </div>
    </div>
    <div class="flex-1 min-w-0 flex flex-col">
      <div class="hidden sm:block">
        <p class="text-[11px] font-bold tracking-[.14em] uppercase inline-flex items-center gap-2" style="color:var(--accent-text)"><span class="inline-flex w-8 h-px" style="background:var(--accent)"></span> Pilihan Minggu Ini · Rekomendasi Untukmu</p>
        <h3 class="font-display text-[20px] lg:text-[22px] font-bold leading-tight mt-2" style="color:var(--text)"><?= e($featured['judul']) ?></h3>
        <p class="text-sm mt-1" style="color:var(--text-faint)"><?= e($featured['penulis']) ?> <?php if (!empty($featured['penerbit'])): ?><span style="color:var(--text-faint-2)">· <?= e($featured['penerbit']) ?></span><?php endif; ?></p>
        <?php if (!empty($featured_desc)): ?>
          <p class="text-[13px] leading-6 mt-3 line-clamp-3 max-w-2xl" style="color:var(--text-muted)"><?= e(mb_strimwidth(strip_tags($featured_desc),0,220,'…')) ?></p>
        <?php else: ?>
          <p class="text-[13px] leading-6 mt-3 max-w-2xl" style="color:var(--text-muted)">Koleksi pilihan minggu ini — cocok untuk menambah wawasan dan menemani waktu membaca santaimu.</p>
        <?php endif; ?>
        <div class="flex flex-wrap items-center gap-2 mt-4">
          <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold" style="background:var(--surface); border-color:var(--border); color:var(--text-muted)"><i class="bi bi-bookmark text-[11px]"></i><?= e($featured['nama_kategori'] ?? 'Tanpa Kategori') ?></span>
          <?php if (!empty($featured_tahun)): ?><span class="inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold" style="background:var(--surface); border-color:var(--border); color:var(--text-muted)"><i class="bi bi-calendar3 text-[11px]"></i><?= e($featured_tahun) ?></span><?php endif; ?>
          <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold <?= (int)$featured['tersedia']>0 ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700' ?>" style="<?= (int)$featured['tersedia']>0 ? 'background:#ecfdf5; border-color:#a7f3d0; color:#065f46' : 'background:#fef2f2; border-color:#fecaca; color:#991b1b' ?>"><span class="w-1.5 h-1.5 rounded-full" style="background:<?= (int)$featured['tersedia']>0 ? '#059669' : '#dc2626' ?>"></span><?= (int)$featured['tersedia']>0 ? 'Tersedia '.$featured['tersedia'] : 'Habis' ?></span>
        </div>
      </div>
      <!-- Mobile desc + actions -->
      <div class="sm:hidden">
        <?php if (!empty($featured_desc)): ?><p class="text-[13px] leading-6 line-clamp-3" style="color:var(--text-muted)"><?= e(mb_strimwidth(strip_tags($featured_desc),0,160,'…')) ?></p><?php endif; ?>
        <div class="mt-3 flex items-center gap-2">
          <?= badge_ketersediaan((int)$featured['tersedia'], !empty($featured['is_arsip'])) ?>
          <span class="text-xs" style="color:var(--text-faint-2)">·</span>
          <span class="text-xs" style="color:var(--text-faint)"><?= (int)($featured['jumlah_dipinjam'] ?? 0) ?>× dipinjam</span>
        </div>
      </div>
      <div class="mt-6 flex gap-2">
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$featured['id_buku'] ?>" class="inline-flex items-center justify-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg">Lihat Detail <i class="bi bi-arrow-right text-xs"></i></a>
        <?php if (is_anggota()): ?>
          <form method="post" action="<?= BASE_URL ?>/anggota/toggle_favorit.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id_buku" value="<?= (int)$featured['id_buku'] ?>">
            <input type="hidden" name="redirect" value="katalog">
            <button type="submit" class="fav-btn inline-flex items-center justify-center gap-1.5 border bg-white hover:bg-slate-50 text-sm font-semibold px-4 py-2.5 rounded-lg" style="border-color:var(--border); color:<?= $f_is_fav ? '#dc2626' : 'var(--text-muted)' ?>"><i class="bi <?= $f_is_fav ? 'bi-heart-fill' : 'bi-heart' ?>"></i> <?= $f_is_fav ? 'Favorit' : 'Simpan' ?></button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($buku_populer)): ?>
<section class="mb-6" aria-labelledby="buku-terpopuler">
  <div class="flex items-end justify-between gap-3 mb-3 border-b pb-3" style="border-color:var(--border)">
    <div>
      <h2 id="buku-terpopuler" class="font-display text-[16px] sm:text-[18px] font-bold tracking-tight flex items-center gap-2" style="color:var(--text)"><span>🔥</span> Sedang Banyak Dibaca</h2>
      <p class="text-xs mt-1" style="color:var(--text-faint)">Buku yang paling sering dipilih oleh pembaca kami minggu ini.</p>
    </div>
    <a href="#koleksi" class="hidden sm:inline-flex items-center gap-1 text-xs font-semibold hover:underline" style="color:var(--accent-text)">Lihat Semua <i class="bi bi-arrow-right text-[11px]"></i></a>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
    <?php foreach ($buku_populer as $peringkat => $buku):
        $is_fav_populer = in_array($buku['id_buku'], $favorit_ids);
    ?>
      <article class="book-card group bg-white rounded-lg border overflow-hidden relative flex flex-col" style="border-color:var(--border)">
        <div class="absolute top-2 left-2 z-10 inline-flex items-center gap-1 rounded-full bg-white border px-2.5 py-1 text-[11px] font-bold shadow-sm" style="border-color:var(--border); color:var(--text-muted)">
          <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black" style="background:<?= $peringkat===0 ? '#243a5e' : ($peringkat===1 ? '#3d6a94' : '#6a9ac4') ?>; color:#fff">#<?= $peringkat + 1 ?></span> Top
        </div>
        <?php if (is_anggota()): ?>
          <form method="post" action="<?= BASE_URL ?>/anggota/toggle_favorit.php" class="absolute top-2 right-2 z-10">
      <?= csrf_field() ?>
            <input type="hidden" name="id_buku" value="<?= (int)$buku['id_buku'] ?>">
            <input type="hidden" name="redirect" value="katalog">
            <button type="submit" title="<?= $is_fav_populer ? 'Hapus dari favorit' : 'Tambah ke favorit' ?>" class="fav-btn w-7 h-7 flex items-center justify-center rounded-full bg-white border text-xs <?= $is_fav_populer ? 'text-red-600' : 'text-slate-400' ?>" style="border-color:var(--border); background:var(--surface)">
              <i class="bi <?= $is_fav_populer ? 'bi-heart-fill' : 'bi-heart' ?> text-[13px]"></i>
            </button>
          </form>
        <?php else: ?>
          <button type="button" onclick="showNoticeModal('Silakan login sebagai anggota untuk menambah buku ke Favorit.')" title="Login untuk favorit" class="absolute top-2 right-2 z-10 w-7 h-7 flex items-center justify-center rounded-full bg-white border text-slate-400 text-xs fav-btn" style="border-color:var(--border); background:var(--surface)">
            <i class="bi bi-heart text-[13px]"></i>
          </button>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$buku['id_buku'] ?>" class="block flex flex-col flex-1">
          <div class="aspect-[3/4] overflow-hidden relative" style="background:var(--paper-2)">
            <img src="<?= e(cover_thumb_url($buku['cover'])) ?>" alt="Cover <?= e($buku['judul']) ?>" loading="lazy" decoding="async" width="400" height="600" onerror="this.src='<?= BASE_URL ?>/assets/img/no-cover.svg'" class="w-full h-full object-cover">
            <div class="absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
          </div>
          <div class="p-3 flex flex-col flex-1 border-t" style="border-color:var(--border-faint)">
            <p class="text-[10px] font-bold uppercase tracking-wide truncate mb-1" style="color:var(--text-faint)"><?= e($buku['nama_kategori'] ?? 'Tanpa Kategori') ?></p>
            <h3 class="font-semibold text-[13px] leading-4 line-clamp-2 mb-1" style="color:var(--text)"><?= e($buku['judul']) ?></h3>
            <p class="text-xs truncate mb-2.5" style="color:var(--text-faint)"><?= e($buku['penulis']) ?></p>
            <div class="flex items-center justify-between gap-2 text-[11px] border-t pt-2 mt-auto" style="border-color:var(--border-faint)">
              <span class="inline-flex items-center gap-1 font-medium" style="color:var(--text-faint)"><i class="bi bi-fire text-[11px]" style="color:#b45309"></i> <?= (int)$buku['jumlah_dipinjam'] ?>× dipinjam</span>
              <?= badge_ketersediaan((int)$buku['tersedia']) ?>
            </div>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>
  <div class="sm:hidden mt-3 text-center">
    <a href="#koleksi" class="inline-flex items-center gap-1 text-xs font-semibold px-4 py-2 rounded-full border bg-white" style="border-color:var(--border); color:var(--text-muted)">Lihat Semua Koleksi <i class="bi bi-arrow-right text-[11px]"></i></a>
  </div>
</section>
<?php endif; ?>

<!-- EDITORIAL BREAK / STATS -->
<section class="stats-editorial flex flex-col sm:flex-row mb-6">
  <div class="stat flex-1">
    <p class="font-display text-[26px] font-bold tracking-tight" style="color:var(--text)"><?= $stats_total_buku ?></p>
    <p class="text-[11px] font-bold uppercase tracking-wide mt-1" style="color:var(--text-faint-2)">Buku</p>
    <p class="text-xs mt-1" style="color:var(--text-faint)">koleksi aktif</p>
  </div>
  <div class="stat flex-1">
    <p class="font-display text-[26px] font-bold tracking-tight" style="color:var(--accent)"><?= $stats_total_kategori ?></p>
    <p class="text-[11px] font-bold uppercase tracking-wide mt-1" style="color:var(--text-faint-2)">Kategori</p>
    <p class="text-xs mt-1" style="color:var(--text-faint)">pilihan minat</p>
  </div>
  <div class="stat flex-1">
    <p class="font-display text-[26px] font-bold tracking-tight" style="color:#065f46"><?= $stats_tersedia ?></p>
    <p class="text-[11px] font-bold uppercase tracking-wide mt-1" style="color:var(--text-faint-2)">Tersedia</p>
    <p class="text-xs mt-1" style="color:var(--text-faint)">siap dipinjam</p>
  </div>
  <div class="hidden sm:flex flex-col justify-center px-6 py-4 text-left max-w-[280px]" style="background:var(--paper-2)">
    <p class="font-display italic text-[13px] leading-5" style="color:var(--text-muted)">“Books are a uniquely portable magic.”</p>
    <p class="text-[11px] font-bold uppercase tracking-wide mt-2" style="color:var(--text-faint-2)">— Stephen King</p>
  </div>
</section>

<div id="koleksi" class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-4 border-b pb-3" style="border-color:var(--border)">
  <div>
    <p class="text-[11px] font-bold tracking-[.14em] uppercase" style="color:var(--text-faint-2)">Koleksi buku</p>
    <h2 class="font-display text-[18px] sm:text-[20px] font-bold tracking-tight mt-1" style="color:var(--text)">Jelajahi katalog</h2>
    <p class="text-xs mt-1" style="color:var(--text-faint)"><?= $total_data ?> buku ditemukan<?php if ($id_kategori>0): ?> · <?= e($kategori_list[array_search($id_kategori, array_column($kategori_list,'id_kategori'))]['nama_kategori'] ?? '') ?><?php endif; ?></p>
  </div>
  <div class="flex items-center gap-2">
    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-medium bg-white" style="border-color:var(--border); color:var(--text-muted)">
      <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span><?= $total_data ?> buku
    </span>
    <?php if ($kata_kunci !== '' || $id_kategori > 0 || $filter_tersedia || $sort !== ''): ?>
      <a href="<?= BASE_URL ?>/index.php" class="inline-flex items-center gap-1 rounded-full bg-slate-900 text-white px-3 py-1 text-xs font-medium hover:bg-slate-800"><i class="bi bi-x text-xs"></i> Reset</a>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($daftar_buku)): ?>
  <div class="text-center py-14 border rounded-xl bg-white" style="border-color:var(--border)">
    <div class="w-12 h-12 mx-auto rounded-lg flex items-center justify-center text-xl mb-3" style="background:var(--surface-2); color:var(--text-faint)"><i class="bi bi-search"></i></div>
    <h3 class="font-display font-bold text-sm" style="color:var(--text)">Buku tidak ditemukan</h3>
    <p class="text-sm mt-1 px-6" style="color:var(--text-faint)">Coba gunakan kata kunci lain atau pilih kategori yang berbeda.</p>
  </div>
<?php else: ?>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
    <?php foreach ($daftar_buku as $buku):
        $is_fav = in_array($buku['id_buku'], $favorit_ids);
    ?>
      <article class="book-card group bg-white rounded-lg border overflow-hidden relative flex flex-col" style="border-color:var(--border)">
        <?php if (is_anggota()): ?>
          <form method="post" action="<?= BASE_URL ?>/anggota/toggle_favorit.php" class="absolute top-2 right-2 z-10">
      <?= csrf_field() ?>
            <input type="hidden" name="id_buku" value="<?= (int)$buku['id_buku'] ?>">
            <input type="hidden" name="redirect" value="katalog">
            <button type="submit" title="<?= $is_fav ? 'Hapus dari favorit' : 'Tambah ke favorit' ?>" class="fav-btn w-7 h-7 flex items-center justify-center rounded-full bg-white border text-xs <?= $is_fav ? 'text-red-600' : 'text-slate-400' ?>" style="border-color:var(--border); background:var(--surface)">
              <i class="bi <?= $is_fav ? 'bi-heart-fill' : 'bi-heart' ?> text-[13px]"></i>
            </button>
          </form>
        <?php else: ?>
          <button type="button" onclick="showNoticeModal('Silakan login sebagai anggota untuk menambah buku ke Favorit.')" title="Login untuk favorit" class="absolute top-2 right-2 z-10 w-7 h-7 flex items-center justify-center rounded-full bg-white border text-slate-400 text-xs fav-btn" style="border-color:var(--border); background:var(--surface)">
            <i class="bi bi-heart text-[13px]"></i>
          </button>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$buku['id_buku'] ?>" class="block flex flex-col flex-1">
          <div class="aspect-[3/4] overflow-hidden relative" style="background:var(--paper-2)">
            <img src="<?= e(cover_thumb_url($buku['cover'])) ?>" alt="Cover <?= e($buku['judul']) ?>" loading="lazy" decoding="async" width="400" height="600" onerror="this.src='<?= BASE_URL ?>/assets/img/no-cover.svg'" class="w-full h-full object-cover">
          </div>
          <div class="p-3 flex flex-col flex-1 border-t" style="border-color:var(--border-faint)">
            <p class="text-[10px] font-bold uppercase tracking-wide truncate mb-1" style="color:var(--text-faint)"><?= e($buku['nama_kategori'] ?? 'Tanpa Kategori') ?></p>
            <h3 class="font-semibold text-[13px] leading-4 line-clamp-2 mb-1" style="color:var(--text)"><?= e($buku['judul']) ?></h3>
            <p class="text-xs truncate mb-2.5" style="color:var(--text-faint)"><?= e($buku['penulis']) ?></p>
            <div class="mt-auto pt-2 border-t flex items-center justify-between gap-2" style="border-color:var(--border-faint)">
              <?= badge_ketersediaan((int)$buku['tersedia']) ?>
              <span class="hidden sm:inline-flex items-center gap-1 text-[11px] font-medium opacity-0 group-hover:opacity-100 transition-opacity" style="color:var(--accent-text)">Lihat <i class="bi bi-arrow-right text-[11px]"></i></span>
            </div>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($total_halaman > 1): ?>
    <div class="flex justify-center items-center gap-1 mt-6 overflow-x-auto py-1">
      <?php
        $base_qs = 'q=' . urlencode($kata_kunci) . '&kategori=' . $id_kategori . ($filter_tersedia ? '&tersedia=1' : '') . ($sort !== '' ? '&sort=' . urlencode($sort) : '');
        $pages = [];
        if ($total_halaman <= 7) {
          for ($i=1;$i<=$total_halaman;$i++) $pages[]=$i;
        } else {
          $pages[] = 1;
          if ($halaman > 4) $pages[] = '...';
          $start = max(2, $halaman - 2);
          $end = min($total_halaman-1, $halaman + 2);
          for ($i=$start;$i<=$end;$i++) $pages[]=$i;
          if ($halaman < $total_halaman - 3) $pages[] = '...';
          $pages[] = $total_halaman;
        }
        foreach ($pages as $p):
          if ($p === '...'): ?>
            <span class="shrink-0 w-9 h-9 flex items-center justify-center text-xs" style="color:var(--text-faint)">…</span>
          <?php else: ?>
            <a href="?<?= $base_qs ?>&halaman=<?= $p ?>"
               class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-sm font-semibold transition <?= $p === $halaman ? 'bg-brand-600 text-white' : 'bg-white border text-slate-600 hover:bg-slate-50' ?>" style="<?= $p === $halaman ? '' : 'border-color:var(--border)' ?>">
              <?= $p ?>
            </a>
          <?php endif; ?>
        <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
