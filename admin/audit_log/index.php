<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$filter_tabel = $_GET['tabel'] ?? 'semua';
if (!in_array($filter_tabel, ['semua','buku','kategori','pengajuan_buku'], true)) $filter_tabel = 'semua';
$filter_aksi = $_GET['aksi'] ?? 'semua';
if (!in_array($filter_aksi, ['semua','tambah','edit','hapus'], true)) $filter_aksi = 'semua';
$kata_kunci = clean($_GET['q'] ?? '');
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 15;
$offset = ($halaman - 1) * $per_halaman;

$where = [];
$params = [];
if ($filter_tabel !== 'semua') {
    $where[] = 'a.target_tabel = :tabel';
    $params[':tabel'] = $filter_tabel;
}
if ($filter_aksi !== 'semua') {
    $where[] = 'a.aksi = :aksi';
    $params[':aksi'] = $filter_aksi;
}
if ($kata_kunci !== '') {
    $where[] = '(a.detail LIKE :kw OR adm.nama LIKE :kw2)';
    $params[':kw'] = '%' . $kata_kunci . '%';
    $params[':kw2'] = '%' . $kata_kunci . '%';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = 0;
$daftar = [];
$total_halaman = 1;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log a LEFT JOIN admin adm ON adm.id_admin = a.user_id $where_sql");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $total_halaman = max(1, (int)ceil($total / $per_halaman));
    if ($halaman > $total_halaman) {
        $halaman = $total_halaman;
        $offset = ($halaman - 1) * $per_halaman;
    }

    $sql = "SELECT a.*, adm.nama AS admin_nama, adm.username FROM audit_log a LEFT JOIN admin adm ON adm.id_admin = a.user_id $where_sql ORDER BY a.created_at DESC, a.id DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $daftar = $stmt->fetchAll();
} catch (Throwable $e) {
    $total = 0;
    $daftar = [];
}

// ---- Audit detail helpers (no backend change, only presentation) ----
if (!function_exists('audit_friendly_label')) {
    function audit_friendly_label($key) {
        $map = [
            'judul' => 'Judul', 'kode' => 'Kode', 'kode_buku' => 'Kode Buku',
            'isbn' => 'ISBN', 'nama' => 'Nama', 'nama_kategori' => 'Nama Kategori',
            'keterangan' => 'Keterangan', 'penulis' => 'Penulis', 'penerbit' => 'Penerbit',
            'tahun_terbit' => 'Tahun Terbit', 'tahun' => 'Tahun', 'stok' => 'Stok',
            'tersedia' => 'Tersedia', 'id_kategori' => 'Kategori', 'kategori' => 'Kategori',
            'lokasi_rak' => 'Lokasi Rak', 'rak' => 'Rak', 'deskripsi' => 'Deskripsi',
            'cover' => 'Cover', 'status' => 'Status', 'alasan' => 'Alasan',
        ];
        $k = strtolower(trim((string)$key));
        return $map[$k] ?? ucwords(str_replace(['_', '-'], ' ', $k));
    }
}
if (!function_exists('audit_format_value')) {
    function audit_format_value($v) {
        if ($v === null || $v === '') return '—';
        if (is_bool($v)) return $v ? 'Ya' : 'Tidak';
        if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
        $s = (string)$v;
        if ($s === '') return '—';
        // Truncate very long values
        if (mb_strlen($s) > 120) $s = mb_strimwidth($s, 0, 120, '…');
        return $s;
    }
}
if (!function_exists('audit_parse_detail')) {
    function audit_parse_detail($raw) {
        if ($raw === null || trim((string)$raw) === '') return ['type'=>'empty','items'=>[]];
        $str = trim((string)$raw);
        // Try JSON
        $decoded = json_decode($str, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $items = [];
            $isChange = false;
            foreach ($decoded as $k => $v) {
                if (is_array($v) && array_key_exists('dari', $v) && array_key_exists('ke', $v)) {
                    $isChange = true;
                    $items[] = [
                        'key' => $k,
                        'label' => audit_friendly_label($k),
                        'dari' => audit_format_value($v['dari']),
                        'ke' => audit_format_value($v['ke']),
                        'type' => 'change'
                    ];
                } elseif (is_array($v) && count($v)===2 && isset($v['dari'])===false) {
                    // fallback: nested array not change, stringify
                    $items[] = ['key'=>$k,'label'=>audit_friendly_label($k),'value'=>audit_format_value($v),'type'=>'value'];
                } else {
                    $items[] = ['key'=>$k,'label'=>audit_friendly_label($k),'value'=>audit_format_value($v),'type'=>'value'];
                }
            }
            return ['type'=> $isChange ? 'change' : 'value', 'items'=>$items, 'raw'=>$str];
        }
        // Not JSON, treat as plain text
        return ['type'=>'text','items'=>[['label'=>'Detail','value'=>audit_format_value($str),'type'=>'value']],'raw'=>$str];
    }
}
if (!function_exists('audit_summary_line')) {
    function audit_summary_line($row, $parsed) {
        $aksi = $row['aksi'] ?? '';
        $target = $row['target_tabel'] ?? '';
        $detailRaw = $row['detail'] ?? '';
        // Try to extract most human info from parsed
        if (!empty($parsed['items'])) {
            $items = $parsed['items'];
            // For tambah/hapus with judul/kode/nama, build "Judul · Kode"
            $byKey = [];
            foreach ($items as $it) $byKey[$it['key']] = $it;
            // Priority keys
            if (isset($byKey['judul']) && $byKey['judul']['type']==='value') {
                $judul = $byKey['judul']['value'];
                $kode = $byKey['kode']['value'] ?? $byKey['kode_buku']['value'] ?? null;
                if ($judul && $kode && $kode!=='—') return $judul . ' · ' . $kode;
                if ($judul) return $judul;
            }
            if (isset($byKey['nama']) && $byKey['nama']['type']==='value') {
                $nama = $byKey['nama']['value'];
                if ($nama && $nama!=='—') return $nama;
            }
            // For edit diff, show first change e.g. "Stok 1 → 5" or "Kategori: Fiksi → Sejarah"
            if ($parsed['type']==='change' && !empty($items)) {
                $first = $items[0];
                // build concise: Label: dari → ke (if more than 1, add "+ n perubahan")
                $base = $first['label'] . ': ' . $first['dari'] . ' → ' . $first['ke'];
                if (count($items) > 1) $base .= ' · +' . (count($items)-1) . ' perubahan';
                return $base;
            }
            // fallback: first value
            foreach ($items as $it) {
                if ($it['type']==='value' && !empty($it['value']) && $it['value']!=='—') {
                    return $it['label'] . ': ' . $it['value'];
                }
            }
        }
        // Fallback to raw truncated (without JSON braces)
        $raw = trim((string)$detailRaw);
        if ($raw === '') return $aksi==='hapus' ? 'Data dihapus' : ($aksi==='edit' ? 'Perubahan disimpan' : 'Data ditambahkan');
        // Strip JSON chars for preview
        $clean = trim($raw, "{} \t\n\r\"'");
        if (mb_strlen($clean) > 80) $clean = mb_strimwidth($clean, 0, 80, '…');
        return $clean ?: 'Aktivitas dicatat';
    }
}
if (!function_exists('audit_waktu_format')) {
    function audit_waktu_format($ts) {
        if (empty($ts)) return '—';
        $t = strtotime($ts);
        if (!$t) return e($ts);
        // Format: 28 Aug 2026, 21:37
        return date('d M Y, H:i', $t);
    }
}
if (!function_exists('audit_relative')) {
    function audit_relative($ts) {
        if (empty($ts)) return '';
        $t = strtotime($ts);
        if (!$t) return '';
        $diff = time() - $t;
        if ($diff < 0) $diff = 0;
        if ($diff < 60) return 'baru saja';
        if ($diff < 3600) return floor($diff/60) . ' menit lalu';
        if ($diff < 86400) return floor($diff/3600) . ' jam lalu';
        if ($diff < 604800) return floor($diff/86400) . ' hari lalu';
        return '';
    }
}

$menu_aktif = 'audit_log';
$page_title = 'Audit Log';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<style>
  .audit-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px;border:1px solid var(--border)}
  .audit-icon-tambah{background:#ecfdf5;color:#059669;border-color:#a7f3d0}
  .audit-icon-edit{background:var(--accent-soft);color:var(--accent-text);border-color:var(--accent-soft-2)}
  .audit-icon-hapus{background:#fef2f2;color:#991b1b;border-color:#fecaca}
  html[data-theme="dark"] .audit-icon-tambah{background:rgba(6,78,59,.18);color:#6ee7b7;border-color:rgba(52,211,153,.22)}
  html[data-theme="dark"] .audit-icon-hapus{background:rgba(127,29,29,.18);color:#fca5a5;border-color:rgba(248,113,113,.22)}
  .audit-badge{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.28rem .6rem;font-size:.70rem;font-weight:700;border:1px solid var(--border);white-space:nowrap}
  .audit-badge-tambah{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}
  .audit-badge-edit{background:var(--accent-soft);color:var(--accent-text);border-color:var(--accent-soft-2)}
  .audit-badge-hapus{background:#fef2f2;color:#991b1b;border-color:#fecaca}
  html[data-theme="dark"] .audit-badge-tambah{background:rgba(6,78,59,.18);color:#6ee7b7;border-color:rgba(52,211,153,.22)}
  html[data-theme="dark"] .audit-badge-hapus{background:rgba(127,29,29,.18);color:#fca5a5;border-color:rgba(248,113,113,.22)}
  .audit-card{border:1px solid var(--border);background:var(--surface);border-radius:10px;transition:border-color 120ms ease, background 120ms ease}
  .audit-card:hover{border-color:var(--border-strong)}
  .audit-change-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:8px 10px;border-radius:8px;background:var(--surface-2);border:1px solid var(--border)}
  .audit-change-label{font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint);min-width:84px}
  .audit-change-val{font-size:13px;padding:4px 8px;border-radius:6px;background:var(--surface);border:1px solid var(--border);color:var(--text-muted);font-weight:500}
  .audit-change-val.to{font-weight:700;color:var(--text);border-color:var(--border-strong)}
  .audit-detail-grid{display:grid;gap:8px}
  @media(min-width:640px){ .audit-detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))} }
  .audit-detail-item{border:1px solid var(--border);background:var(--surface-2);border-radius:8px;padding:10px 12px}
  .audit-detail-item .lbl{font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--text-faint)}
  .audit-detail-item .val{font-size:13px;font-weight:600;color:var(--text);margin-top:4px;word-break:break-word}
  .audit-wrap{position:relative}
  @media(min-width:640px){
    .audit-wrap::before{content:"";position:absolute;left:17px;top:18px;bottom:18px;width:1px;background:var(--border);border-radius:1px}
  }
</style>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-4 pb-4" style="border-bottom:1px solid var(--border)">
  <div class="min-w-0">
    <p class="text-[11px] font-bold tracking-[.14em] uppercase" style="color:var(--text-faint-2)">Jejak aktivitas</p>
    <h1 class="font-display text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)">Audit Log</h1>
    <p class="text-sm mt-1" style="color:var(--text-faint)">Riwayat aktivitas administrator dalam sistem.</p>
  </div>
  <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold shrink-0" style="background:var(--surface); border-color:var(--border); color:var(--text-muted)"><i class="bi bi-clock-history"></i> <?= $total ?> entri</span>
</div>

<!-- Filters -->
<form method="get" class="bg-white rounded-lg border p-3 mb-4" style="border-color:var(--border)">
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
    <div>
      <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Target</label>
      <select name="tabel" class="w-full h-10 border rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)">
        <option value="semua" <?= $filter_tabel==='semua'?'selected':'' ?>>Semua</option>
        <option value="buku" <?= $filter_tabel==='buku'?'selected':'' ?>>Buku</option>
        <option value="kategori" <?= $filter_tabel==='kategori'?'selected':'' ?>>Kategori</option>
        <option value="pengajuan_buku" <?= $filter_tabel==='pengajuan_buku'?'selected':'' ?>>Pengajuan</option>
      </select>
    </div>
    <div>
      <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Aksi</label>
      <select name="aksi" class="w-full h-10 border rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)">
        <option value="semua" <?= $filter_aksi==='semua'?'selected':'' ?>>Semua</option>
        <option value="tambah" <?= $filter_aksi==='tambah'?'selected':'' ?>>Tambah</option>
        <option value="edit" <?= $filter_aksi==='edit'?'selected':'' ?>>Edit</option>
        <option value="hapus" <?= $filter_aksi==='hapus'?'selected':'' ?>>Hapus</option>
      </select>
    </div>
    <div class="sm:col-span-2">
      <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Cari aktivitas</label>
      <div class="flex gap-2">
        <div class="relative flex-1">
          <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-sm" style="color:var(--text-faint-2)"></i>
          <input type="text" name="q" value="<?= e($kata_kunci) ?>" placeholder="Cari detail atau nama admin..." class="w-full h-10 border rounded-lg pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)">
        </div>
        <button class="h-10 bg-slate-900 hover:bg-slate-800 text-white px-5 rounded-lg text-sm font-semibold shrink-0">Filter</button>
      </div>
    </div>
  </div>
  <?php if ($kata_kunci!=='' || $filter_tabel!=='semua' || $filter_aksi!=='semua'): ?>
    <div class="mt-3 flex items-center gap-2">
      <a href="<?= BASE_URL ?>/admin/audit_log/index.php" class="text-xs font-semibold inline-flex items-center gap-1 hover:underline" style="color:var(--text-faint)"><i class="bi bi-x-circle text-xs"></i> Reset filter</a>
      <span class="text-xs" style="color:var(--text-faint-2)">· <?= $total ?> hasil</span>
    </div>
  <?php endif; ?>
</form>

<?php if (empty($daftar) && $total===0): ?>
  <div class="text-center py-14 bg-white rounded-lg border" style="border-color:var(--border)">
    <div class="w-11 h-11 mx-auto rounded-lg flex items-center justify-center text-lg mb-3" style="background:var(--surface-2); color:var(--text-faint); border:1px solid var(--border)"><i class="bi bi-journal-text"></i></div>
    <h3 class="font-display font-semibold text-sm" style="color:var(--text)">Belum ada log</h3>
    <p class="text-sm mt-1 px-6 max-w-md mx-auto" style="color:var(--text-faint)">Lakukan tambah, edit, atau hapus pada buku atau kategori. Aktivitas akan tercatat otomatis.</p>
    <?php try { $pdo->query("SELECT 1 FROM audit_log LIMIT 1"); } catch (Throwable $e) { echo '<p class="text-xs mt-3 px-4 py-2 rounded-lg inline-block" style="background:#fffbeb; border:1px solid #fde68a; color:#92400e">Tabel <code>audit_log</code> belum ada — jalankan migrasi database.</p>'; } ?>
  </div>
<?php else: ?>
  <div class="audit-wrap space-y-2.5">
    <?php foreach ($daftar as $idx => $row):
      $aksi = $row['aksi'] ?? '';
      $target = $row['target_tabel'] ?? '';
      $parsed = audit_parse_detail($row['detail'] ?? '');
      $summary = audit_summary_line($row, $parsed);
      $waktu = audit_waktu_format($row['created_at'] ?? '');
      $rel = audit_relative($row['created_at'] ?? '');
      $adminNama = trim($row['admin_nama'] ?? '') ?: '—';
      $username = $row['username'] ?? '';
      $adminDisplay = $adminNama;
      if ($username) $adminDisplay .= ' (' . $username . ')';

      $targetLabel = $target==='pengajuan_buku' ? 'Pengajuan' : ucfirst($target);
      $aksiLabel = ucfirst($aksi);
      // verb
      $verb = $aksi==='tambah' ? 'menambahkan' : ($aksi==='edit' ? 'memperbarui' : 'menghapus');
      $iconClass = $aksi==='tambah' ? 'audit-icon-tambah' : ($aksi==='edit' ? 'audit-icon-edit' : 'audit-icon-hapus');
      $badgeClass = $aksi==='tambah' ? 'audit-badge-tambah' : ($aksi==='edit' ? 'audit-badge-edit' : 'audit-badge-hapus');
      $icon = $aksi==='tambah' ? 'bi-plus-lg' : ($aksi==='edit' ? 'bi-pencil-square' : 'bi-trash3');
      $hasDetail = !empty($parsed['items']);
      $targetId = (int)$row['target_id'];
    ?>
      <article class="audit-card p-3 sm:p-4 relative" style="<?= $idx===0 && count($daftar)>1 ? '' : '' ?>">
        <div class="flex gap-3">
          <!-- Icon -->
          <span class="audit-icon <?= $iconClass ?> mt-0.5 hidden sm:flex"><i class="bi <?= $icon ?>"></i></span>
          <span class="audit-icon <?= $iconClass ?> mt-0.5 flex sm:hidden" style="width:32px;height:32px;border-radius:8px;font-size:14px"><i class="bi <?= $icon ?>"></i></span>

          <div class="flex-1 min-w-0">
            <!-- Top row: badge + target + time -->
            <div class="flex flex-wrap items-center gap-2">
              <span class="audit-badge <?= $badgeClass ?>"><?= e($aksiLabel) ?></span>
              <span class="text-xs font-semibold" style="color:var(--text-muted)"><?= e($targetLabel) ?></span>
              <span class="text-xs" style="color:var(--text-faint-2)">#<?= $targetId ?></span>
              <span class="hidden sm:inline-flex items-center gap-1 text-xs ml-auto whitespace-nowrap" style="color:var(--text-faint-2)"><i class="bi bi-clock text-[11px]"></i> <?= e($waktu) ?></span>
            </div>

            <!-- Admin & verb -->
            <p class="text-xs mt-2" style="color:var(--text-faint)">
              <span class="font-semibold" style="color:var(--text-muted)"><?= e($adminDisplay) ?></span>
              <span><?= e($verb) ?></span>
              <span class="font-medium" style="color:var(--text-muted)"><?= e(strtolower($targetLabel)) ?></span>
              <?php if ($rel): ?><span class="sm:hidden" style="color:var(--text-faint-2)"> · <?= e($rel) ?></span><?php endif; ?>
            </p>

            <!-- Summary -->
            <p class="text-sm font-semibold mt-1.5 leading-5" style="color:var(--text)">
              <?= e($summary) ?>
            </p>

            <!-- Sub-meta: if summary was shortened and there are more items, hint -->
            <?php if ($parsed['type']==='change' && count($parsed['items'])>1): ?>
              <p class="text-xs mt-1" style="color:var(--text-faint-2)"><?= count($parsed['items']) ?> perubahan tercatat</p>
            <?php endif; ?>

            <!-- Mobile time -->
            <p class="sm:hidden text-xs mt-2 flex items-center gap-1" style="color:var(--text-faint-2)"><i class="bi bi-clock text-[11px]"></i> <?= e($waktu) ?><?php if ($rel): ?> · <?= e($rel) ?><?php endif; ?></p>

            <!-- Action -->
            <div class="mt-3 flex items-center gap-2">
              <?php if ($hasDetail): ?>
                <button type="button" data-audit-toggle aria-expanded="false" class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1.5 rounded-lg border bg-white hover:bg-slate-50" style="border-color:var(--border); color:var(--text-muted)">
                  <span class="toggle-label">Lihat Detail</span>
                  <i class="bi bi-chevron-down text-[11px] transition-transform"></i>
                </button>
              <?php else: ?>
                <span class="text-xs" style="color:var(--text-faint-2)">Tidak ada detail tambahan</span>
              <?php endif; ?>
              <span class="ml-auto hidden sm:inline text-xs" style="color:var(--text-faint-2)"><?php if ($rel): ?><?= e($rel) ?><?php endif; ?></span>
            </div>

            <!-- Expandable detail -->
            <?php if ($hasDetail): ?>
              <div data-audit-detail class="hidden mt-3 pt-3" style="border-top:1px solid var(--border-faint)">
                <?php if ($parsed['type']==='change'): ?>
                  <div class="space-y-2">
                    <?php foreach ($parsed['items'] as $it): ?>
                      <div class="audit-change-row">
                        <span class="audit-change-label"><?= e($it['label']) ?></span>
                        <span class="flex items-center gap-2 flex-wrap">
                          <span class="audit-change-val"><?= e($it['dari']) ?></span>
                          <i class="bi bi-arrow-right text-xs" style="color:var(--text-faint-2)"></i>
                          <span class="audit-change-val to"><?= e($it['ke']) ?></span>
                        </span>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php elseif ($parsed['type']==='value'): ?>
                  <div class="audit-detail-grid">
                    <?php foreach ($parsed['items'] as $it): ?>
                      <div class="audit-detail-item">
                        <div class="lbl"><?= e($it['label']) ?></div>
                        <div class="val"><?= e($it['value']) ?></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="rounded-lg border p-3 text-sm" style="background:var(--surface-2); border-color:var(--border); color:var(--text-muted)"><?= e($parsed['items'][0]['value'] ?? '') ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Desktop relative time subtle on right edge -->
          <?php if ($rel): ?>
            <span class="hidden lg:block text-xs whitespace-nowrap mt-1 shrink-0" style="color:var(--text-faint-2)"><?= e($rel) ?></span>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <?php if ($total_halaman > 1): ?>
    <div class="flex justify-center items-center gap-1 mt-6 flex-wrap">
      <?php
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
            <a href="?tabel=<?= e($filter_tabel) ?>&aksi=<?= e($filter_aksi) ?>&q=<?= urlencode($kata_kunci) ?>&halaman=<?= $p ?>"
               class="shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-sm font-semibold transition <?= $p===$halaman ? 'bg-brand-600 text-white' : 'bg-white border hover:bg-slate-50' ?>" style="<?= $p===$halaman ? '' : 'border-color:var(--border); color:var(--text-muted)' ?>"><?= $p ?></a>
          <?php endif;
        endforeach;
      ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<script>
(function(){
  document.querySelectorAll('[data-audit-toggle]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const card = btn.closest('.audit-card');
      const panel = card.querySelector('[data-audit-detail]');
      if(!panel) return;
      const isHidden = panel.classList.contains('hidden');
      panel.classList.toggle('hidden', !isHidden);
      btn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
      const label = btn.querySelector('.toggle-label');
      const icon = btn.querySelector('i');
      if(label) label.textContent = isHidden ? 'Tutup Detail' : 'Lihat Detail';
      if(icon){
        icon.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
      }
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
