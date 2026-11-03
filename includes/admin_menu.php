<?php
$menu_aktif = $menu_aktif ?? '';
$pending_perpanjangan_menu = isset($pdo) ? jumlah_perpanjangan_menunggu($pdo) : 0;
$pending_pengajuan_pinjam_menu = isset($pdo) ? jumlah_pengajuan_peminjaman_menunggu($pdo) : 0;
$items = admin_menu_items();
?>

<!-- Desktop admin sidebar -->
<?php if (isset($menu_aktif) && $menu_aktif !== ''): ?>
<aside class="hidden min-[900px]:flex fixed left-0 top-[56px] bottom-0 z-30 w-[270px] flex-col border-r bg-white" style="border-color: var(--border)" aria-label="Panel Pengelola">
  <div class="flex-1 overflow-y-auto px-3 py-4">
    <div class="px-3 pb-3">
      <div class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--accent-soft); color: var(--accent-text)">
          <i class="bi bi-grid-1x2-fill text-sm"></i>
        </span>
        <div class="min-w-0">
          <div class="font-display font-bold text-sm" style="color: var(--text)">Panel Pengelola</div>
          <div class="text-xs" style="color: var(--text-faint)">Navigasi administrasi</div>
        </div>
      </div>
    </div>

    <div class="space-y-1">
      <?php foreach ($items as $key => $item): ?>
        <a href="<?= BASE_URL . $item['url'] ?>"
           class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition <?= $menu_aktif === $key ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-700' ?>">
          <i class="bi <?= e($item['icon']) ?> text-base w-5 text-center <?= $menu_aktif === $key ? 'text-white' : 'text-slate-400 group-hover:text-brand-600' ?>"></i>
          <span class="flex-1"><?= e($item['label']) ?></span>
          <?php if ($key === 'perpanjangan' && $pending_perpanjangan_menu > 0): ?>
            <span class="min-w-5 h-5 px-1 rounded-full <?= $menu_aktif === $key ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' ?> text-[10px] font-bold inline-flex items-center justify-center">
              <?= $pending_perpanjangan_menu > 99 ? '99+' : $pending_perpanjangan_menu ?>
            </span>
          <?php endif; ?>
          <?php if ($key === 'pengajuan_peminjaman' && $pending_pengajuan_pinjam_menu > 0): ?>
            <span class="min-w-5 h-5 px-1 rounded-full <?= $menu_aktif === $key ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-700' ?> text-[10px] font-bold inline-flex items-center justify-center">
              <?= $pending_pengajuan_pinjam_menu > 99 ? '99+' : $pending_pengajuan_pinjam_menu ?>
            </span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="p-3 border-t" style="border-color: var(--border)">
    <a href="<?= BASE_URL ?>/logout.php" data-confirm="Yakin ingin keluar dari akun?" data-confirm-text="Keluar" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg bg-slate-900 text-white font-semibold text-sm hover:bg-slate-800 transition">
      <i class="bi bi-box-arrow-right"></i>
      Keluar
    </a>
  </div>
</aside>
<?php endif; ?>

<!-- The mobile drawer lives in header.php. This file intentionally has no desktop-in-flow menu. -->
