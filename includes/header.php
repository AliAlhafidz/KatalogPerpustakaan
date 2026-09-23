<?php
$page_title = $page_title ?? 'Perpustakaan Umum Sejahtera';
$jumlah_notifikasi = 0;
if (function_exists('is_anggota') && is_anggota() && isset($pdo, $_SESSION['id_anggota'])) {
    try {
        sinkronkan_notifikasi_anggota($pdo, (int)$_SESSION['id_anggota']);
        $jumlah_notifikasi = jumlah_notifikasi_belum_dibaca($pdo, (int)$_SESSION['id_anggota']);
    } catch (Throwable $e) {
        $jumlah_notifikasi = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= e($page_title) ?> | Perpustakaan Umum Sejahtera</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          brand: {
            50:'#eef2f7',100:'#dbe6f2',200:'#bfcfe4',300:'#8fb4d6',400:'#6a9ac4',
            500:'#3d6a94',600:'#243a5e',700:'#1a2c4a',800:'#13223a',900:'#0f1b2e'
          }
        },
        boxShadow: {
          soft: '0 1px 3px rgba(16, 24, 40, .06)',
          float: '0 4px 16px rgba(16, 24, 40, .08)'
        },
        fontFamily: {
          display: ['EB Garamond','Georgia','serif']
        }
      }
    }
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:opsz,wght@6..12,600;6..12,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="icon" href="<?= BASE_URL ?>/assets/img/pwa-icon-192.png">
<link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
<meta name="theme-color" content="#243a5e">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Perpustakaan">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body class="min-h-screen flex flex-col" style="background: var(--paper); color: var(--text);">

<!-- Top navigation — quiet, bordered, no blur -->
<nav class="sticky top-0 z-40 border-b bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 <?= ((isset($menu_aktif) && $menu_aktif !== '') || (is_anggota() && isset($member_menu_aktif) && $member_menu_aktif !== '')) ? 'area-nav-inner' : '' ?>">
    <div class="flex items-center justify-between h-[56px]">
      <a href="<?= BASE_URL ?>/index.php" class="flex items-center gap-2.5 min-w-0">
        <span class="w-8 h-8 shrink-0 rounded-lg bg-brand-600 text-white flex items-center justify-center">
          <i class="bi bi-book-half text-[15px]"></i>
        </span>
        <span class="min-w-0">
          <span class="block font-display font-bold tracking-tight leading-none" style="font-size: 15px; color: var(--text);">Perpustakaan Umum</span>
          <span class="hidden sm:block text-[11px] font-medium" style="color: var(--text-faint); letter-spacing: .02em;">Sejahtera · Ruang baca</span>
        </span>
      </a>

      <div class="hidden min-[900px]:flex items-center gap-1 text-sm font-semibold">
        <?php if (is_anggota()): ?>
          <a href="<?= BASE_URL ?>/anggota/dashboard.php" class="nav-link px-2.5 py-1.5 rounded-lg <?= ($member_menu_aktif ?? '') === 'dashboard' ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:text-brand-700 hover:bg-brand-50' ?>">Dashboard</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/index.php" class="nav-link px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-brand-700 hover:bg-brand-50">Katalog</a>
        <a href="<?= BASE_URL ?>/tentang.php" class="nav-link px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-brand-700 hover:bg-brand-50">Tentang</a>
        <?php if (is_admin()): ?>
          <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-brand-700 hover:bg-brand-50">Dashboard Admin</a>
        <?php elseif (is_anggota()): ?>
          <a href="<?= BASE_URL ?>/anggota/notifikasi.php" class="relative w-8 h-8 flex items-center justify-center rounded-lg text-slate-600 hover:text-brand-700 hover:bg-brand-50" aria-label="Notifikasi">
            <i class="bi bi-bell text-[16px]"></i>
            <?php if ($jumlah_notifikasi > 0): ?><span class="absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center"><?= $jumlah_notifikasi > 99 ? '99+' : $jumlah_notifikasi ?></span><?php endif; ?>
          </a>
          <a href="<?= BASE_URL ?>/anggota/profil.php" class="nav-link ml-1 flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-slate-700 hover:bg-slate-100">
            <img src="<?= e(foto_profil_url($_SESSION['foto'] ?? null)) ?>" class="w-7 h-7 rounded-full object-cover" style="border:1px solid var(--border)" alt="Profil">
            <span class="text-sm font-semibold">Profil</span>
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/register.php" class="nav-link ml-1 px-3 py-1.5 rounded-lg text-slate-600 hover:bg-slate-100 text-sm">Daftar</a>
          <a href="<?= BASE_URL ?>/login.php" class="ml-1 px-4 py-1.5 rounded-lg bg-brand-600 text-white hover:bg-brand-700 text-sm font-semibold">Masuk</a>
        <?php endif; ?>
      </div>

      <div class="flex items-center gap-1.5">
        <button id="navToggle" type="button" class="min-[900px]:hidden w-9 h-9 rounded-lg border bg-white text-slate-700 flex items-center justify-center hover:bg-slate-50" style="border-color: var(--border)" aria-label="Buka menu" aria-controls="mobileDrawer" aria-expanded="false">
          <i class="bi bi-list text-xl"></i>
        </button>
      </div>
    </div>
  </div>
</nav>

<!-- Mobile off-canvas navigation -->
<div id="drawerBackdrop" class="drawer-backdrop fixed inset-0 z-50 min-[900px]:hidden" aria-hidden="true"></div>
<aside id="mobileDrawer" class="drawer fixed top-0 left-0 z-[60] h-dvh w-[min(82vw,300px)] bg-white min-[900px]:hidden flex flex-col" aria-label="Menu navigasi" aria-hidden="true">
  <div class="px-4 py-4 border-b flex items-center justify-between" style="border-color: var(--border)">
    <div class="flex items-center gap-2.5">
      <span class="w-8 h-8 rounded-lg bg-brand-600 text-white flex items-center justify-center"><i class="bi bi-book-half text-sm"></i></span>
      <div><div class="font-display font-bold text-sm" style="color: var(--text)">Menu</div><div class="text-xs" style="color: var(--text-faint)">Perpustakaan Umum</div></div>
    </div>
    <button id="navClose" type="button" class="w-8 h-8 rounded-lg text-slate-600 flex items-center justify-center hover:bg-slate-100" style="border:1px solid var(--border); background: var(--surface-2)" aria-label="Tutup menu"><i class="bi bi-x-lg text-sm"></i></button>
  </div>
  <div class="flex-1 overflow-y-auto p-3">
    <div class="text-[10px] font-bold tracking-widest uppercase px-3 mb-2" style="color: var(--text-faint-2)">Navigasi</div>
    <div class="space-y-1">
      <a href="<?= BASE_URL ?>/index.php" class="drawer-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-brand-50 hover:text-brand-700 font-semibold text-sm"><i class="bi bi-grid text-base w-5 text-center"></i>Katalog Buku</a>
      <a href="<?= BASE_URL ?>/tentang.php" class="drawer-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-brand-50 hover:text-brand-700 font-semibold text-sm"><i class="bi bi-info-circle text-base w-5 text-center"></i>Tentang</a>
      <?php if (is_admin()): ?>
        <div class="pt-4 mt-3 border-t" style="border-color: var(--border)">
          <div class="text-[10px] font-bold tracking-widest uppercase px-3 mb-2" style="color: var(--text-faint-2)">Panel Pengelola</div>
          <div class="space-y-1">
            <?php
            $pending_ext = isset($pdo) ? jumlah_perpanjangan_menunggu($pdo) : 0;
            $pending_pinjam_ext = isset($pdo) ? jumlah_pengajuan_peminjaman_menunggu($pdo) : 0;
            foreach (admin_menu_items() as $key => $item):
            ?>
              <a href="<?= BASE_URL . $item['url'] ?>" class="drawer-link flex items-center gap-3 px-3 py-2.5 rounded-lg <?= ($menu_aktif ?? '') === $key ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-brand-50 hover:text-brand-700' ?> font-semibold text-sm">
                <i class="bi <?= e($item['icon']) ?> text-base w-5 text-center"></i>
                <span class="flex-1"><?= e($item['label']) ?></span>
                <?php if ($key === 'perpanjangan' && $pending_ext > 0): ?>
                  <span class="min-w-5 h-5 px-1 rounded-full bg-amber-600 text-white text-[10px] font-bold flex items-center justify-center"><?= $pending_ext > 99 ? '99+' : $pending_ext ?></span>
                <?php endif; ?>
                <?php if ($key === 'pengajuan_peminjaman' && $pending_pinjam_ext > 0): ?>
                  <span class="min-w-5 h-5 px-1 rounded-full bg-amber-600 text-white text-[10px] font-bold flex items-center justify-center"><?= $pending_pinjam_ext > 99 ? '99+' : $pending_pinjam_ext ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php elseif (is_anggota()): ?>
        <div class="pt-4 mt-3 border-t" style="border-color: var(--border)">
          <div class="text-[10px] font-bold tracking-widest uppercase px-3 mb-2" style="color: var(--text-faint-2)">Area Anggota</div>
          <div class="space-y-1">
            <?php foreach (member_menu_items() as $key => $item): ?>
              <a href="<?= BASE_URL . $item['url'] ?>" class="drawer-link flex items-center gap-3 px-3 py-2.5 rounded-lg <?= ($member_menu_aktif ?? '') === $key ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-brand-50 hover:text-brand-700' ?> font-semibold text-sm">
                <i class="bi <?= e($item['icon']) ?> text-base w-5 text-center"></i>
                <span class="flex-1"><?= e($item['label']) ?></span>
                <?php if ($key === 'notifikasi' && $jumlah_notifikasi > 0): ?>
                  <span class="min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center"><?= $jumlah_notifikasi > 99 ? '99+' : $jumlah_notifikasi ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/register.php" class="drawer-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-700 hover:bg-brand-50 hover:text-brand-700 font-semibold text-sm"><i class="bi bi-person-plus text-base w-5 text-center"></i>Daftar</a>
        <a href="<?= BASE_URL ?>/login.php" class="drawer-link flex items-center gap-3 px-3 py-2.5 rounded-lg bg-brand-600 text-white hover:bg-brand-700 font-semibold mt-2 text-sm"><i class="bi bi-box-arrow-in-right text-base w-5 text-center"></i>Masuk</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="px-4 py-3 border-t flex items-center justify-between" style="border-color: var(--border)">
    <div class="flex items-center gap-2 text-sm font-semibold" style="color: var(--text-muted)"><i class="bi bi-circle-half"></i> Menu</div>
  </div>
  <?php if (is_admin() || is_anggota()): ?>
    <div class="p-3 border-t" style="border-color: var(--border)">
      <a href="<?= BASE_URL ?>/logout.php" data-confirm="Yakin ingin keluar dari akun?" data-confirm-text="Keluar" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg bg-slate-900 text-white font-semibold text-sm hover:bg-slate-800"><i class="bi bi-box-arrow-right"></i>Keluar</a>
    </div>
  <?php endif; ?>
</aside>

<?php if (is_anggota() && isset($member_menu_aktif) && $member_menu_aktif !== ''): ?>
<!-- Desktop member sidebar -->
<aside class="hidden min-[900px]:flex fixed left-0 top-[56px] bottom-0 z-30 w-[270px] flex-col border-r bg-white" style="border-color: var(--border)" aria-label="Area Anggota">
  <div class="flex-1 overflow-y-auto px-3 py-4">
    <div class="px-3 pb-3">
      <div class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--accent-soft); color: var(--accent-text)"><i class="bi bi-person-badge-fill text-sm"></i></span>
        <div class="min-w-0"><div class="font-display font-bold text-sm" style="color: var(--text)">Area Anggota</div><div class="text-xs" style="color: var(--text-faint)">Kelola aktivitasmu</div></div>
      </div>
    </div>
    <div class="space-y-1">
      <?php foreach (member_menu_items() as $key => $item): ?>
        <a href="<?= BASE_URL . $item['url'] ?>" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition <?= ($member_menu_aktif ?? '') === $key ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-700' ?>">
          <i class="bi <?= e($item['icon']) ?> text-base w-5 text-center <?= ($member_menu_aktif ?? '') === $key ? 'text-white' : 'text-slate-400 group-hover:text-brand-600' ?>"></i>
          <span class="flex-1"><?= e($item['label']) ?></span>
          <?php if ($key === 'notifikasi' && $jumlah_notifikasi > 0): ?>
            <span class="min-w-5 h-5 px-1 rounded-full <?= ($member_menu_aktif ?? '') === $key ? 'bg-white/20 text-white' : 'bg-red-100 text-red-700' ?> text-[10px] font-bold flex items-center justify-center"><?= $jumlah_notifikasi > 99 ? '99+' : $jumlah_notifikasi ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="px-3 py-2.5 border-t flex items-center justify-between" style="border-color: var(--border)">
    <div class="flex items-center gap-2 text-sm font-semibold" style="color: var(--text-muted)"><i class="bi bi-circle-half"></i> Menu</div>
  </div>
  <div class="p-3 border-t" style="border-color: var(--border)">
    <a href="<?= BASE_URL ?>/logout.php" data-confirm="Yakin ingin keluar dari akun?" data-confirm-text="Keluar" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg bg-slate-900 text-white font-semibold text-sm hover:bg-slate-800 transition"><i class="bi bi-box-arrow-right"></i>Keluar</a>
  </div>
</aside>
<?php endif; ?>

<main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8 <?= (isset($menu_aktif) && $menu_aktif !== '') ? 'admin-main' : ((is_anggota() && isset($member_menu_aktif) && $member_menu_aktif !== '') ? 'member-main' : '') ?>">
<?php tampilkan_flash(); ?>
