<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {
    $nama = clean($_POST['nama_kategori'] ?? '');
    $keterangan = clean($_POST['keterangan'] ?? '');
    if ($nama === '') {
        $errors[] = 'Nama kategori wajib diisi.';
    } else {
        $cek = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE nama_kategori = :nama");
        $cek->execute([':nama' => $nama]);
        if ((int)$cek->fetchColumn() > 0) {
            $errors[] = 'Nama kategori tersebut sudah ada. Gunakan nama lain.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, keterangan) VALUES (:nama, :ket)");
                $stmt->execute([':nama' => $nama, ':ket' => $keterangan ?: null]);
                $new_id = (int)$pdo->lastInsertId();
                catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'tambah', 'kategori', $new_id, ['nama' => $nama]);
                set_flash('sukses', 'Kategori berhasil ditambahkan.');
                redirect('/admin/kategori/index.php');
            } catch (Throwable $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 23000) {
                    $errors[] = 'Nama kategori tersebut sudah ada (percobaan bersamaan).';
                } else {
                    $errors[] = 'Gagal menambahkan kategori: ' . $e->getMessage();
                }
                error_log('Tambah kategori gagal: ' . $e->getMessage());
            }
        }
    }
}

$stmt = $pdo->query("SELECT k.*, (SELECT COUNT(*) FROM buku b WHERE b.id_kategori = k.id_kategori) AS jumlah_buku
                      FROM kategori k ORDER BY k.nama_kategori ASC");
$daftar = $stmt->fetchAll();

$menu_aktif = 'kategori';
$page_title = 'Data Kategori';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<style>
  .kat-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:var(--accent-soft);color:var(--accent-text);border:1px solid var(--accent-soft-2);flex-shrink:0;font-size:14px}
  .kat-badge{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;padding:.28rem .6rem;font-size:.72rem;font-weight:700;border:1px solid var(--border);background:var(--surface-2);color:var(--text-muted);white-space:nowrap}
  .kat-badge.has-books{background:var(--accent-soft);border-color:var(--accent-soft-2);color:var(--accent-text)}
  .kat-menu-btn{width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--text-faint);display:inline-flex;align-items:center;justify-content:center;transition:all 120ms ease}
  .kat-menu-btn:hover{background:var(--surface-2);color:var(--text);border-color:var(--border-strong)}
  .kat-menu-btn[aria-expanded="true"]{background:var(--surface-2);border-color:var(--border-strong);color:var(--text)}
  .kat-dropdown{position:absolute;right:0;top:calc(100% + 6px);min-width:156px;background:var(--surface);border:1px solid var(--border);border-radius:10px;box-shadow:0 8px 24px rgba(16,24,40,.08);padding:4px;z-index:20}
  .kat-dropdown.is-fixed{position:fixed;right:auto;top:auto;z-index:70;max-height:min(50vh,320px);overflow-y:auto;-webkit-overflow-scrolling:touch}
  .kat-dropdown a,.kat-dropdown button{width:100%;text-align:left;display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;font-size:13px;font-weight:600;color:var(--text-muted);transition:background 120ms ease, color 120ms ease}
  .kat-dropdown a:hover,.kat-dropdown button:hover{background:var(--surface-2);color:var(--text)}
  .kat-dropdown button.is-danger{color:#991b1b}
  .kat-dropdown button.is-danger:hover{background:#fef2f2;color:#991b1b}
  .kat-row{transition:background 120ms ease}
  .kat-row:hover{background:var(--surface-2)}
  .kat-mobile-card{border:1px solid var(--border);background:var(--surface);border-radius:10px;padding:12px 14px;display:flex;gap:12px;align-items:flex-start;transition:border-color 120ms ease, background 120ms ease}
  .kat-mobile-card:hover{border-color:var(--border-strong)}
  @media(max-width:767px){
    .kat-desktop-wrap{display:none !important}
  }
  @media(min-width:768px){
    .kat-mobile-wrap{display:none !important}
  }
</style>

<!-- Page header -->
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-4 pb-4" style="border-bottom:1px solid var(--border)">
  <div class="min-w-0">
    <p class="text-[11px] font-bold tracking-[.14em] uppercase" style="color:var(--text-faint-2)">Kelola koleksi</p>
    <h1 class="font-display text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)">Kategori</h1>
    <p class="text-sm mt-1" style="color:var(--text-faint)">Kelola seluruh kategori koleksi buku perpustakaan.</p>
  </div>
  <button id="katToggleForm" type="button" class="inline-flex items-center justify-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shrink-0">
    <i class="bi bi-plus-lg text-xs"></i> Tambah Kategori
  </button>
</div>

<?php
$total_kat = count($daftar);
$total_buku_terkategori = array_sum(array_column($daftar, 'jumlah_buku'));
$kat_kosong = 0;
foreach ($daftar as $kk) if ((int)$kk['jumlah_buku']===0) $kat_kosong++;
?>

<!-- Compact stats -->
<div class="grid grid-cols-3 gap-2.5 mb-4">
  <div class="bg-white rounded-lg border p-3 sm:p-4" style="border-color:var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">Total Kategori</p>
    <p class="text-[20px] sm:text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)"><?= $total_kat ?></p>
    <p class="text-xs mt-0.5" style="color:var(--text-faint)">kategori terdaftar</p>
  </div>
  <div class="bg-white rounded-lg border p-3 sm:p-4" style="border-color:var(--border)">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">Buku Terkategori</p>
    <p class="text-[20px] sm:text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)"><?= $total_buku_terkategori ?></p>
    <p class="text-xs mt-0.5" style="color:var(--text-faint)">eksemplar</p>
  </div>
  <div class="bg-white rounded-lg border p-3 sm:p-4" style="border-color:<?= $kat_kosong>0 ? '#fde68a' : 'var(--border)' ?>; background:<?= $kat_kosong>0 ? '#fffbeb' : 'var(--surface)' ?>">
    <p class="text-[11px] font-bold uppercase tracking-wide" style="color:<?= $kat_kosong>0 ? '#92400e' : 'var(--text-faint-2)' ?>">Kategori Kosong</p>
    <p class="text-[20px] sm:text-[22px] font-bold tracking-tight mt-1" style="color:<?= $kat_kosong>0 ? '#92400e' : 'var(--text)' ?>"><?= $kat_kosong ?></p>
    <p class="text-xs mt-0.5" style="color:<?= $kat_kosong>0 ? '#b45309' : 'var(--text-faint)' ?>"><?= $kat_kosong>0 ? 'perlu perhatian' : 'semua terisi' ?></p>
  </div>
</div>

<!-- Toolbar: search -->
<div class="bg-white rounded-lg border p-2.5 mb-4 flex items-center gap-2" style="border-color:var(--border)">
  <div class="relative flex-1 min-w-0">
    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--text-faint-2)"></i>
    <input id="katSearch" type="text" placeholder="Cari kategori atau keterangan..." autocomplete="off"
           class="w-full h-10 border rounded-lg pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)">
  </div>
  <div class="hidden sm:flex items-center gap-2 shrink-0">
    <span class="text-xs px-2.5 py-2 rounded-lg border bg-white font-medium" style="border-color:var(--border); color:var(--text-faint)"><span id="katVisibleCount"><?= $total_kat ?></span> kategori</span>
  </div>
</div>

<!-- Add form panel (collapsible) -->
<div id="katFormPanel" class="<?= !empty($errors) ? '' : 'hidden' ?> bg-white rounded-lg border p-4 sm:p-5 mb-4" style="border-color:var(--border)">
  <div class="flex items-start justify-between gap-3 mb-3">
    <div>
      <h2 class="font-semibold text-sm" style="color:var(--text)">Tambah Kategori</h2>
      <p class="text-xs mt-1" style="color:var(--text-faint)">Nama harus unik. Keterangan opsional.</p>
    </div>
    <button type="button" id="katCloseForm" class="w-8 h-8 rounded-lg border bg-white hover:bg-slate-50 inline-flex items-center justify-center shrink-0" style="border-color:var(--border); color:var(--text-faint)"><i class="bi bi-x-lg text-xs"></i></button>
  </div>
  <?php if (!empty($errors)): ?>
    <div class="mb-3 rounded-lg border px-3 py-2.5 text-sm" style="background:#fef2f2; border-color:#fecaca; color:#991b1b">
      <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="post" action="" class="space-y-3">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="tambah">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Nama Kategori <span class="text-red-500">*</span></label>
        <input type="text" name="nama_kategori" required placeholder="Mis. Fiksi, Sains..." class="w-full h-10 border rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border)">
      </div>
      <div>
        <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Keterangan</label>
        <input type="text" name="keterangan" placeholder="Deskripsi singkat (opsional)" class="w-full h-10 border rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border)">
      </div>
    </div>
    <div class="flex gap-2 pt-1">
      <button type="submit" class="inline-flex items-center justify-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm">Simpan Kategori</button>
      <button type="button" id="katCancelForm" class="inline-flex items-center justify-center border bg-white hover:bg-slate-50 font-semibold px-5 py-2.5 rounded-lg text-sm" style="border-color:var(--border); color:var(--text-muted)">Batal</button>
    </div>
  </form>
</div>

<?php if (empty($daftar)): ?>
  <div class="text-center py-14 bg-white rounded-lg border" style="border-color:var(--border)">
    <div class="w-11 h-11 mx-auto rounded-lg flex items-center justify-center text-lg mb-3" style="background:var(--surface-2); color:var(--text-faint); border:1px solid var(--border)"><i class="bi bi-tags"></i></div>
    <h3 class="font-display font-semibold text-sm" style="color:var(--text)">Belum ada kategori</h3>
    <p class="text-sm mt-1 px-6 max-w-sm mx-auto" style="color:var(--text-faint)">Tambahkan kategori pertama untuk mulai mengelola koleksi.</p>
    <button type="button" onclick="document.getElementById('katToggleForm')?.click()" class="mt-4 inline-flex items-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg"><i class="bi bi-plus-lg text-xs"></i> Tambah Kategori</button>
  </div>
<?php else: ?>

  <!-- Desktop table -->
  <div class="kat-desktop-wrap bg-white rounded-lg border overflow-hidden" style="border-color:var(--border)">
    <div class="overflow-x-auto">
      <table class="w-full text-sm no-responsive">
        <thead>
          <tr style="background:var(--surface-2); border-bottom:1px solid var(--border)">
            <th class="text-left px-4 py-2.5 text-[11px] font-bold uppercase tracking-widest" style="color:var(--text-faint)">Kategori</th>
            <th class="text-left px-4 py-2.5 text-[11px] font-bold uppercase tracking-widest" style="color:var(--text-faint)">Deskripsi</th>
            <th class="text-left px-4 py-2.5 text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Jumlah Buku</th>
            <th class="text-right px-4 py-2.5 text-[11px] font-bold uppercase tracking-widest" style="color:var(--text-faint)">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y" style="--tw-divide-opacity:1; border-color:var(--border-faint)" id="katTableBody">
          <?php foreach ($daftar as $k): 
            $nama = $k['nama_kategori'];
            $ket = trim($k['keterangan'] ?? '');
            $jml = (int)$k['jumlah_buku'];
            // icon heuristic
            $lower = strtolower($nama);
            if (strpos($lower,'fiksi')!==false) $icon='bi-book';
            elseif (strpos($lower,'sains')!==false || strpos($lower,'teknologi')!==false) $icon='bi-cpu';
            elseif (strpos($lower,'agama')!==false) $icon='bi-moon-stars';
            elseif (strpos($lower,'sejarah')!==false) $icon='bi-clock-history';
            elseif (strpos($lower,'anak')!==false) $icon='bi-emoji-smile';
            elseif (strpos($lower,'non')!==false) $icon='bi-journal-text';
            else $icon='bi-tag';
          ?>
            <tr class="kat-row" data-name="<?= e(strtolower($nama)) ?>" data-desc="<?= e(strtolower($ket)) ?>">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                  <span class="kat-icon"><i class="bi <?= $icon ?>"></i></span>
                  <span class="font-semibold truncate" style="color:var(--text)"><?= e($nama) ?></span>
                </div>
              </td>
              <td class="px-4 py-3 max-w-[360px]">
                <?php if ($ket !== ''): ?>
                  <span class="text-sm truncate block" style="color:var(--text-muted)" title="<?= e($ket) ?>"><?= e($ket) ?></span>
                <?php else: ?>
                  <span class="text-sm italic" style="color:var(--text-faint-2)">Tidak ada deskripsi</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <span class="kat-badge <?= $jml>0 ? 'has-books' : '' ?>"><i class="bi bi-book text-[11px]"></i> <?= $jml ?> Buku</span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="relative inline-block" data-kat-menu>
                  <button type="button" class="kat-menu-btn" aria-label="Aksi kategori <?= e($nama) ?>" aria-haspopup="menu" aria-expanded="false">
                    <i class="bi bi-three-dots-vertical text-sm"></i>
                  </button>
                  <div class="kat-dropdown hidden" role="menu">
                    <a href="<?= BASE_URL ?>/admin/kategori/edit.php?id=<?= (int)$k['id_kategori'] ?>" role="menuitem"><i class="bi bi-pencil-square text-xs"></i> Ubah</a>
                    <form method="post" action="<?= BASE_URL ?>/admin/kategori/hapus.php" data-confirm-danger="true" data-confirm="Yakin ingin menghapus kategori &quot;<?= e($nama) ?>&quot;? Buku terkait akan menjadi tanpa kategori.">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$k['id_kategori'] ?>">
                      <button type="submit" class="is-danger" role="menuitem"><i class="bi bi-trash3 text-xs"></i> Hapus</button>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr id="katNoResultRow" class="hidden">
            <td colspan="4" class="text-center py-10">
              <div class="w-10 h-10 mx-auto rounded-lg flex items-center justify-center text-base mb-2" style="background:var(--surface-2); color:var(--text-faint); border:1px solid var(--border)"><i class="bi bi-search"></i></div>
              <p class="text-sm font-semibold" style="color:var(--text)">Tidak ada hasil</p>
              <p class="text-xs mt-1" style="color:var(--text-faint)">Coba kata kunci lain.</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Mobile compact list -->
  <div class="kat-mobile-wrap space-y-2" id="katMobileList">
    <?php foreach ($daftar as $k): 
      $nama = $k['nama_kategori'];
      $ket = trim($k['keterangan'] ?? '');
      $jml = (int)$k['jumlah_buku'];
      $lower = strtolower($nama);
      if (strpos($lower,'fiksi')!==false) $icon='bi-book';
      elseif (strpos($lower,'sains')!==false || strpos($lower,'teknologi')!==false) $icon='bi-cpu';
      elseif (strpos($lower,'agama')!==false) $icon='bi-moon-stars';
      elseif (strpos($lower,'sejarah')!==false) $icon='bi-clock-history';
      elseif (strpos($lower,'anak')!==false) $icon='bi-emoji-smile';
      elseif (strpos($lower,'non')!==false) $icon='bi-journal-text';
      else $icon='bi-tag';
    ?>
      <div class="kat-mobile-card" data-name="<?= e(strtolower($nama)) ?>" data-desc="<?= e(strtolower($ket)) ?>">
        <span class="kat-icon" style="width:36px;height:36px;border-radius:10px"><i class="bi <?= $icon ?>"></i></span>
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <h3 class="font-semibold text-[13px] leading-5 truncate pr-1" style="color:var(--text)"><?= e($nama) ?></h3>
            <div class="relative shrink-0" data-kat-menu>
              <button type="button" class="kat-menu-btn" aria-label="Aksi" aria-expanded="false"><i class="bi bi-three-dots-vertical text-sm"></i></button>
              <div class="kat-dropdown hidden" role="menu">
                <a href="<?= BASE_URL ?>/admin/kategori/edit.php?id=<?= (int)$k['id_kategori'] ?>" role="menuitem"><i class="bi bi-pencil-square text-xs"></i> Ubah</a>
                <form method="post" action="<?= BASE_URL ?>/admin/kategori/hapus.php" data-confirm-danger="true" data-confirm="Yakin ingin menghapus kategori &quot;<?= e($nama) ?>&quot;?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$k['id_kategori'] ?>">
                  <button type="submit" class="is-danger" role="menuitem"><i class="bi bi-trash3 text-xs"></i> Hapus</button>
                </form>
              </div>
            </div>
          </div>
          <p class="text-xs mt-0.5 truncate" style="color:<?= $ket!=='' ? 'var(--text-faint)' : 'var(--text-faint-2)' ?>; <?= $ket==='' ? 'font-style:italic' : '' ?>"><?= $ket!=='' ? e($ket) : 'Tidak ada deskripsi' ?></p>
          <div class="mt-2">
            <span class="kat-badge <?= $jml>0 ? 'has-books' : '' ?>"><i class="bi bi-book text-[11px]"></i> <?= $jml ?> Buku</span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <div id="katMobileEmpty" class="hidden text-center py-10 bg-white rounded-lg border" style="border-color:var(--border)">
      <div class="w-10 h-10 mx-auto rounded-lg flex items-center justify-center text-base mb-2" style="background:var(--surface-2); color:var(--text-faint); border:1px solid var(--border)"><i class="bi bi-search"></i></div>
      <p class="text-sm font-semibold" style="color:var(--text)">Tidak ada hasil</p>
      <p class="text-xs mt-1" style="color:var(--text-faint)">Coba kata kunci lain.</p>
    </div>
  </div>

<?php endif; ?>

<script>
(function(){
  const toggle = document.getElementById('katToggleForm');
  const panel = document.getElementById('katFormPanel');
  const closeBtn = document.getElementById('katCloseForm');
  const cancelBtn = document.getElementById('katCancelForm');
  const search = document.getElementById('katSearch');
  function setPanel(show){
    if(!panel) return;
    panel.classList.toggle('hidden', !show);
    if(show){
      const inp = panel.querySelector('input[name="nama_kategori"]');
      setTimeout(()=> inp && inp.focus(), 40);
    }
  }
  toggle?.addEventListener('click', ()=> setPanel(panel.classList.contains('hidden')));
  closeBtn?.addEventListener('click', ()=> setPanel(false));
  cancelBtn?.addEventListener('click', ()=> setPanel(false));

  // dropdown — fixed positioning to escape overflow clipping
  function closeAllMenus(){
    document.querySelectorAll('[data-kat-menu] .kat-dropdown').forEach(el=>{
      el.classList.add('hidden');
      el.classList.remove('is-fixed');
      el.style.left=''; el.style.top=''; el.style.right=''; el.style.position='';
    });
    document.querySelectorAll('[data-kat-menu] .kat-menu-btn').forEach(b=> b.setAttribute('aria-expanded','false'));
  }
  function positionKat(btn, menu){
    menu.classList.remove('hidden');
    menu.classList.add('is-fixed');
    menu.style.visibility='hidden';
    menu.style.left='0'; menu.style.top='0';
    const br = btn.getBoundingClientRect();
    const mr = menu.getBoundingClientRect();
    menu.style.visibility='';
    const gap=6, mar=8;
    let left = br.right - mr.width;
    if(left < mar) left = mar;
    if(left + mr.width > window.innerWidth - mar) left = window.innerWidth - mr.width - mar;
    let top = br.bottom + gap;
    const below = window.innerHeight - br.bottom;
    const above = br.top;
    if(below < mr.height + mar && above > below) top = br.top - mr.height - gap;
    if(top < mar) top = mar;
    if(top + mr.height > window.innerHeight - mar) top = window.innerHeight - mr.height - mar;
    menu.style.left = Math.round(left)+'px';
    menu.style.top = Math.round(top)+'px';
    menu.style.right='auto';
  }
  document.querySelectorAll('[data-kat-menu] .kat-menu-btn').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      e.stopPropagation();
      const wrap = btn.closest('[data-kat-menu]');
      const menu = wrap.querySelector('.kat-dropdown');
      const willOpen = menu.classList.contains('hidden');
      closeAllMenus();
      if(willOpen){
        positionKat(btn, menu);
        btn.setAttribute('aria-expanded','true');
      }
    });
  });
  document.addEventListener('click', closeAllMenus);
  document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeAllMenus(); });
  window.addEventListener('scroll', closeAllMenus, true);
  window.addEventListener('resize', closeAllMenus);

  // search filter
  const visibleCount = document.getElementById('katVisibleCount');
  const tbody = document.getElementById('katTableBody');
  const mobileList = document.getElementById('katMobileList');
  const noRow = document.getElementById('katNoResultRow');
  const mobileEmpty = document.getElementById('katMobileEmpty');
  function filter(q){
    q = (q||'').trim().toLowerCase();
    let shown = 0;
    if(tbody){
      const rows = tbody.querySelectorAll('tr.kat-row');
      rows.forEach(r=>{
        const name = r.getAttribute('data-name')||'';
        const desc = r.getAttribute('data-desc')||'';
        const ok = !q || name.includes(q) || desc.includes(q);
        r.classList.toggle('hidden', !ok);
        if(ok) shown++;
      });
      if(noRow) noRow.classList.toggle('hidden', shown!==0);
    } else {
      // fallback count from mobile if no tbody (edge)
      shown = 0;
    }
    if(mobileList){
      const cards = mobileList.querySelectorAll('.kat-mobile-card');
      let mShown = 0;
      cards.forEach(c=>{
        const name = c.getAttribute('data-name')||'';
        const desc = c.getAttribute('data-desc')||'';
        const ok = !q || name.includes(q) || desc.includes(q);
        c.classList.toggle('hidden', !ok);
        if(ok) mShown++;
      });
      if(mobileEmpty) mobileEmpty.classList.toggle('hidden', mShown!==0);
      // if tbody not present, use mShown for count
      if(!tbody) shown = mShown;
      else if(tbody && cards.length) shown = mShown; // keep sync, table already computed but use mobile for verify
    }
    if(visibleCount) visibleCount.textContent = shown;
  }
  search?.addEventListener('input', e=> filter(e.target.value));
  // debounce not needed for small dataset
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
