<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$kata_kunci = clean($_GET['q'] ?? '');
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 10;
$offset = ($halaman - 1) * $per_halaman;

$where = '';
$params = [];
if ($kata_kunci !== '') {
    $where = "WHERE nama LIKE :kw OR email LIKE :kw OR nomor_anggota LIKE :kw";
    $params[':kw'] = '%' . $kata_kunci . '%';
}

$total = $pdo->prepare("SELECT COUNT(*) FROM anggota $where");
$total->execute($params);
$total_data = (int) $total->fetchColumn();
$total_halaman = max(1, (int) ceil($total_data / $per_halaman));
if ($halaman > $total_halaman) {
    $halaman = $total_halaman;
    $offset = ($halaman - 1) * $per_halaman;
}

// Ambil data anggota dengan hitungan peminjaman aktif untuk card
$stmt = $pdo->prepare("SELECT a.*, (SELECT COUNT(*) FROM peminjaman p WHERE p.id_anggota=a.id_anggota AND p.status='dipinjam') AS aktif_pinjam FROM anggota a $where ORDER BY a.id_anggota DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftar = $stmt->fetchAll();

// Stats untuk header
try {
    $stat_aktif = (int)$pdo->query("SELECT COUNT(*) FROM anggota WHERE status='aktif'")->fetchColumn();
    $stat_non = $total_data - $stat_aktif;
} catch (Throwable $e) { $stat_aktif = 0; $stat_non = 0; }

if (!function_exists('initials_from_name')) {
    function initials_from_name($nama) {
        $nama = trim((string)$nama);
        if ($nama === '') return '?';
        $parts = preg_split('/\s+/', $nama);
        $parts = array_filter($parts, fn($p)=> $p!=='');
        $parts = array_values($parts);
        if (count($parts)===1) return mb_strtoupper(mb_substr($parts[0],0,2));
        return mb_strtoupper(mb_substr($parts[0],0,1) . mb_substr(end($parts),0,1));
    }
}

$menu_aktif = 'anggota';
$page_title = 'Data Anggota';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<style>
  .angg-avatar{width:40px;height:40px;border-radius:999px;object-fit:cover;border:1px solid var(--border);flex-shrink:0;background:var(--surface)}
  .angg-avatar-sm{width:36px;height:36px;border-radius:999px;object-fit:cover;border:1px solid var(--border);flex-shrink:0}
  .angg-fallback{width:40px;height:40px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;background:var(--accent-soft);color:var(--accent-text);border:1px solid var(--accent-soft-2);flex-shrink:0;letter-spacing:.02em}
  .angg-fallback-sm{width:36px;height:36px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:var(--accent-soft);color:var(--accent-text);border:1px solid var(--accent-soft-2);flex-shrink:0}
  .angg-badge{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.28rem .6rem;font-size:.70rem;font-weight:700;border:1px solid var(--border);white-space:nowrap}
  .angg-badge-safe{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}
  .angg-badge-danger{background:#fef2f2;color:#991b1b;border-color:#fecaca}
  .angg-card{border:1px solid var(--border);background:var(--surface);border-radius:10px;padding:12px;display:flex;gap:12px;align-items:flex-start;transition:border-color 120ms ease}
  .angg-card:hover{border-color:var(--border-strong)}
  .angg-menu-btn{width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--text-faint);display:inline-flex;align-items:center;justify-content:center;transition:all 120ms ease}
  .angg-menu-btn:hover{background:var(--surface-2);color:var(--text);border-color:var(--border-strong)}
  .angg-dropdown{position:absolute;right:0;top:calc(100% + 6px);min-width:148px;background:var(--surface);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(16,24,40,.08);padding:4px;z-index:20}
  .angg-dropdown.is-fixed{position:fixed;right:auto;top:auto;z-index:70;max-height:min(50vh,320px);overflow-y:auto;-webkit-overflow-scrolling:touch}
  .angg-dropdown a,.angg-dropdown button{width:100%;text-align:left;display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;font-size:13px;font-weight:600;color:var(--text-muted);transition:background 120ms ease}
  .angg-dropdown a:hover,.angg-dropdown button:hover{background:var(--surface-2);color:var(--text)}
  .angg-dropdown button.is-danger{color:#991b1b}
  .angg-dropdown button.is-danger:hover{background:#fef2f2}
  @media(max-width:767px){
    .angg-desktop{display:none !important}
  }
  @media(min-width:768px){
    .angg-mobile{display:none !important}
  }
  @media print{
    .angg-mobile{display:none !important}
    .angg-desktop{display:block !important}
  }
</style>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-4 pb-4" style="border-bottom:1px solid var(--border)">
  <div class="min-w-0">
    <p class="text-[11px] font-bold tracking-[.14em] uppercase" style="color:var(--text-faint-2)">Manajemen anggota</p>
    <h1 class="font-display text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)">Data Anggota</h1>
    <p class="text-sm mt-1" style="color:var(--text-faint)">Kelola identitas dan status keanggotaan perpustakaan.</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/anggota/tambah.php" class="inline-flex items-center justify-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shrink-0"><i class="bi bi-person-plus text-xs"></i> Tambah Anggota</a>
</div>

<!-- Stats compact -->
<div class="grid grid-cols-3 gap-2.5 mb-4">
  <div class="bg-white rounded-lg border p-3 sm:p-4" style="border-color:var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">Total Anggota</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)"><?= $total_data ?></p>
    <p class="text-xs mt-0.5" style="color:var(--text-faint)">terdaftar</p>
  </div>
  <div class="bg-white rounded-lg border p-3 sm:p-4" style="border-color:var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">Aktif</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color:#065f46"><?= $stat_aktif ?></p>
    <p class="text-xs mt-0.5" style="color:var(--text-faint)">dapat meminjam</p>
  </div>
  <div class="bg-white rounded-lg border p-3 sm:p-4" style="border-color:<?= $stat_non>0 ? '#fecaca' : 'var(--border)' ?>; background:<?= $stat_non>0 ? '#fef2f2' : 'var(--surface)' ?>">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color:<?= $stat_non>0 ? '#991b1b' : 'var(--text-faint-2)' ?>">Nonaktif</p>
    <p class="text-[22px] font-bold tracking-tight mt-1" style="color:<?= $stat_non>0 ? '#991b1b' : 'var(--text)' ?>"><?= $stat_non ?></p>
    <p class="text-xs mt-0.5" style="color:<?= $stat_non>0 ? '#b42318' : 'var(--text-faint)' ?>"><?= $stat_non>0 ? 'perlu perhatian' : 'tidak ada' ?></p>
  </div>
</div>

<!-- Search -->
<form method="get" class="mb-4 bg-white rounded-lg border p-2.5 flex gap-2 items-center" style="border-color:var(--border)">
  <div class="relative flex-1">
    <i class="bi bi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-sm" style="color:var(--text-faint-2)"></i>
    <input type="text" name="q" value="<?= e($kata_kunci) ?>" placeholder="Cari nama, email, atau nomor anggota..." class="w-full h-10 border rounded-lg pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)">
  </div>
  <button class="h-10 px-5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm inline-flex items-center justify-center">Cari</button>
  <?php if ($kata_kunci !== ''): ?>
    <a href="<?= BASE_URL ?>/admin/anggota/index.php" class="h-10 px-4 rounded-lg border bg-white hover:bg-slate-50 font-semibold text-sm inline-flex items-center justify-center" style="border-color:var(--border); color:var(--text-muted)">Reset</a>
  <?php endif; ?>
</form>

<div class="flex items-center justify-between gap-3 mb-3">
  <p class="text-xs" style="color:var(--text-faint)"><?= $total_data ?> anggota ditemukan</p>
  <span class="text-xs hidden sm:inline" style="color:var(--text-faint-2)"><?= $kata_kunci!=='' ? 'Hasil pencarian' : 'Semua anggota' ?></span>
</div>

<?php if (empty($daftar)): ?>
  <div class="text-center py-14 bg-white rounded-lg border" style="border-color:var(--border)">
    <div class="w-11 h-11 mx-auto rounded-lg flex items-center justify-center text-lg mb-3" style="background:var(--surface-2); color:var(--text-faint); border:1px solid var(--border)"><i class="bi bi-people"></i></div>
    <h3 class="font-display font-semibold text-sm" style="color:var(--text)">Tidak ada anggota</h3>
    <p class="text-sm mt-1 px-6" style="color:var(--text-faint)"><?= $kata_kunci!=='' ? 'Coba kata kunci lain.' : 'Belum ada data anggota terdaftar.' ?></p>
  </div>
<?php else: ?>

  <!-- Desktop table with avatar -->
  <div class="angg-desktop bg-white rounded-lg border overflow-hidden" style="border-color:var(--border)">
    <div class="overflow-x-auto">
      <table class="w-full text-sm no-responsive">
        <thead>
          <tr style="background:var(--surface-2); border-bottom:1px solid var(--border)">
            <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest" style="color:var(--text-faint)">Anggota</th>
            <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">ID Anggota</th>
            <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Kontak</th>
            <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Pinjaman</th>
            <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Status</th>
            <th class="px-4 py-2.5 text-right text-[11px] font-bold uppercase tracking-widest" style="color:var(--text-faint)">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y" style="border-color:var(--border-faint)">
          <?php foreach ($daftar as $a):
            $initials = initials_from_name($a['nama']);
            $hasFoto = !empty($a['foto']);
            $aktifPinjam = (int)($a['aktif_pinjam'] ?? 0);
          ?>
            <tr class="hover:bg-[var(--surface-2)] transition-colors">
              <td class="px-4 py-3">
                <div class="flex gap-3 items-center min-w-0">
                  <?php if ($hasFoto): ?>
                    <img src="<?= e(foto_profil_url($a['foto'])) ?>" alt="" loading="lazy" class="angg-avatar-sm">
                  <?php else: ?>
                    <span class="angg-fallback-sm"><?= e($initials) ?></span>
                  <?php endif; ?>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold truncate max-w-[200px]" style="color:var(--text)" title="<?= e($a['nama']) ?>"><?= e($a['nama']) ?></p>
                    <p class="text-xs truncate max-w-[220px]" style="color:var(--text-faint)" title="<?= e($a['email']) ?>"><?= e($a['email']) ?></p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap font-semibold text-xs" style="color:var(--text-muted)"><?= e($a['nomor_anggota']) ?></td>
              <td class="px-4 py-3 whitespace-nowrap text-xs" style="color:var(--text-muted)"><?= e($a['no_hp'] ?: '-') ?></td>
              <td class="px-4 py-3 whitespace-nowrap">
                <?php if ($aktifPinjam>0): ?>
                  <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border" style="background:var(--accent-soft); color:var(--accent-text); border-color:var(--accent-soft-2)"><i class="bi bi-bookmark text-[11px]"></i> <?= $aktifPinjam ?> aktif</span>
                <?php else: ?>
                  <span class="text-xs" style="color:var(--text-faint-2)">-</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 whitespace-nowrap"><span class="angg-badge <?= $a['status']==='aktif' ? 'angg-badge-safe' : 'angg-badge-danger' ?>"><?= e(ucfirst($a['status'])) ?></span></td>
              <td class="px-4 py-3 text-right">
                <div class="relative inline-block" data-angg-menu>
                  <button type="button" class="angg-menu-btn" aria-label="Aksi" aria-expanded="false"><i class="bi bi-three-dots-vertical text-sm"></i></button>
                  <div class="angg-dropdown hidden" role="menu">
                    <a href="<?= BASE_URL ?>/admin/anggota/edit.php?id=<?= (int)$a['id_anggota'] ?>" role="menuitem"><i class="bi bi-pencil-square text-xs"></i> Ubah</a>
                    <form method="post" action="<?= BASE_URL ?>/admin/anggota/hapus.php" data-confirm-danger="true" data-confirm="Yakin ingin menghapus anggota &quot;<?= e($a['nama']) ?>&quot;?">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$a['id_anggota'] ?>">
                      <button type="submit" class="is-danger" role="menuitem"><i class="bi bi-trash3 text-xs"></i> Hapus</button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Mobile compact member cards -->
  <div class="angg-mobile grid gap-2.5">
    <?php foreach ($daftar as $a):
      $initials = initials_from_name($a['nama']);
      $hasFoto = !empty($a['foto']);
      $aktifPinjam = (int)($a['aktif_pinjam'] ?? 0);
    ?>
      <article class="angg-card">
        <?php if ($hasFoto): ?>
          <img src="<?= e(foto_profil_url($a['foto'])) ?>" alt="" loading="lazy" class="angg-avatar">
        <?php else: ?>
          <span class="angg-fallback"><?= e($initials) ?></span>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm font-semibold truncate pr-2" style="color:var(--text)" title="<?= e($a['nama']) ?>"><?= e($a['nama']) ?></h3>
            <span class="angg-badge <?= $a['status']==='aktif' ? 'angg-badge-safe' : 'angg-badge-danger' ?> shrink-0 text-[11px]"><?= e(ucfirst($a['status'])) ?></span>
          </div>
          <p class="text-xs truncate" style="color:var(--text-faint)" title="<?= e($a['email']) ?>"><?= e($a['email']) ?></p>
          <p class="text-[11px] font-semibold mt-1.5 flex items-center gap-1.5 flex-wrap" style="color:var(--text-faint-2)">
            <span><?= e($a['nomor_anggota']) ?></span>
            <span class="w-1 h-1 rounded-full" style="background:var(--border-strong)"></span>
            <span><?= e($a['no_hp'] ?: 'Tanpa HP') ?></span>
          </p>
          <div class="flex items-center justify-between gap-2 mt-2.5 pt-2.5 border-t" style="border-color:var(--border-faint)">
            <span class="text-xs" style="color:var(--text-faint)">
              <?php if ($aktifPinjam>0): ?><span class="font-semibold" style="color:var(--accent-text)"><?= $aktifPinjam ?> pinjaman aktif</span><?php else: ?>Tidak ada pinjaman aktif<?php endif; ?>
            </span>
            <div class="relative" data-angg-menu>
              <button type="button" class="angg-menu-btn" aria-label="Aksi" aria-expanded="false"><i class="bi bi-three-dots-vertical text-sm"></i></button>
              <div class="angg-dropdown hidden" role="menu">
                <a href="<?= BASE_URL ?>/admin/anggota/edit.php?id=<?= (int)$a['id_anggota'] ?>" role="menuitem"><i class="bi bi-pencil-square text-xs"></i> Ubah</a>
                <form method="post" action="<?= BASE_URL ?>/admin/anggota/hapus.php" data-confirm-danger="true" data-confirm="Yakin ingin menghapus anggota &quot;<?= e($a['nama']) ?>&quot;?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$a['id_anggota'] ?>">
                  <button type="submit" class="is-danger" role="menuitem"><i class="bi bi-trash3 text-xs"></i> Hapus</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($total_halaman > 1): ?>
    <div class="flex justify-center items-center gap-1 mt-6 flex-wrap">
      <?php
        $pages=[];
        if($total_halaman<=7){ for($i=1;$i<=$total_halaman;$i++) $pages[]=$i; }
        else { $pages[]=1; if($halaman>4) $pages[]='...'; $start=max(2,$halaman-2); $end=min($total_halaman-1,$halaman+2); for($i=$start;$i<=$end;$i++) $pages[]=$i; if($halaman<$total_halaman-3) $pages[]='...'; $pages[]=$total_halaman; }
        foreach($pages as $p):
          if($p==='...'): ?><span class="shrink-0 w-9 h-9 flex items-center justify-center text-xs" style="color:var(--text-faint)">…</span>
          <?php else: ?><a href="?q=<?= urlencode($kata_kunci) ?>&halaman=<?= $p ?>" class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-sm font-semibold transition <?= $p===$halaman ? 'bg-brand-600 text-white' : 'bg-white border hover:bg-slate-50' ?>" style="<?= $p===$halaman?'':'border-color:var(--border); color:var(--text-muted)' ?>"><?= $p ?></a><?php endif;
        endforeach;
      ?>
    </div>
  <?php endif; ?>

<?php endif; ?>

<script>
(function(){
  function closeAll(){
    document.querySelectorAll('[data-angg-menu] .angg-dropdown').forEach(el=>{
      el.classList.add('hidden');
      el.classList.remove('is-fixed');
      el.style.left=''; el.style.top=''; el.style.right=''; el.style.position='';
    });
    document.querySelectorAll('[data-angg-menu] .angg-menu-btn').forEach(b=>b.setAttribute('aria-expanded','false'));
  }
  function positionFixed(btn, menu){
    // make measurable
    menu.classList.remove('hidden');
    menu.classList.add('is-fixed');
    menu.style.visibility='hidden';
    menu.style.left='0';
    menu.style.top='0';
    // force layout
    const btnRect = btn.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    menu.style.visibility='';
    const gap = 6;
    const margin = 8;
    let left = btnRect.right - menuRect.width;
    if(left < margin) left = margin;
    if(left + menuRect.width > window.innerWidth - margin) left = window.innerWidth - menuRect.width - margin;
    let top = btnRect.bottom + gap;
    const spaceBelow = window.innerHeight - btnRect.bottom;
    const spaceAbove = btnRect.top;
    if(spaceBelow < menuRect.height + margin && spaceAbove > spaceBelow){
      top = btnRect.top - menuRect.height - gap;
    }
    if(top < margin) top = margin;
    if(top + menuRect.height > window.innerHeight - margin) top = window.innerHeight - menuRect.height - margin;
    menu.style.left = Math.round(left) + 'px';
    menu.style.top = Math.round(top) + 'px';
    menu.style.right = 'auto';
  }
  document.querySelectorAll('[data-angg-menu] .angg-menu-btn').forEach(btn=>{
    btn.addEventListener('click', e=>{
      e.stopPropagation();
      const wrap=btn.closest('[data-angg-menu]');
      const menu=wrap.querySelector('.angg-dropdown');
      const willOpen=menu.classList.contains('hidden');
      // close others first
      const wasOpen = !willOpen;
      closeAll();
      if(willOpen){
        positionFixed(btn, menu);
        btn.setAttribute('aria-expanded','true');
      }
    });
  });
  document.addEventListener('click', closeAll);
  document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeAll(); });
  window.addEventListener('scroll', closeAll, true);
  window.addEventListener('resize', closeAll);
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
