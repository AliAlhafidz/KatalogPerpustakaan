<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

$jenis = $_GET['jenis'] ?? 'buku';
$dari  = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$jenis_valid = ['buku', 'anggota', 'peminjaman', 'pengembalian', 'denda'];
if (!in_array($jenis, $jenis_valid, true)) $jenis = 'buku';

$valid_dari = DateTime::createFromFormat('Y-m-d', $dari);
$valid_sampai = DateTime::createFromFormat('Y-m-d', $sampai);
if (!$valid_dari || $valid_dari->format('Y-m-d') !== $dari) $dari = date('Y-m-01');
if (!$valid_sampai || $valid_sampai->format('Y-m-d') !== $sampai) $sampai = date('Y-m-d');
if ($dari > $sampai) { [$dari, $sampai] = [$sampai, $dari]; }

$data = [];
$kolom = [];
$judul_laporan = '';
$limit_laporan = 500;
$total_all = 0;

switch ($jenis) {
    case 'buku':
        $judul_laporan = 'Laporan Data Buku';
        $kolom = ['Cover', 'Kode', 'Judul', 'Penulis', 'Kategori', 'Rak', 'Stok', 'Tersedia', 'Status'];
        $stmt = $pdo->query("SELECT b.kode_buku, b.judul, b.penulis, b.cover, k.nama_kategori, b.lokasi_rak, b.stok, b.tersedia, b.is_arsip
                              FROM buku b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori ORDER BY b.judul ASC LIMIT $limit_laporan");
        $data = $stmt->fetchAll();
        $total_all = (int) $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
        break;

    case 'anggota':
        $judul_laporan = 'Laporan Data Anggota';
        $kolom = ['No. Anggota', 'Nama', 'Email', 'No. HP', 'Status', 'Terdaftar'];
        $stmt = $pdo->query("SELECT nomor_anggota, nama, email, no_hp, status, created_at, foto, (SELECT COUNT(*) FROM peminjaman p WHERE p.id_anggota=anggota.id_anggota AND p.status='dipinjam') AS aktif_pinjam FROM anggota ORDER BY nama ASC LIMIT $limit_laporan");
        $data = $stmt->fetchAll();
        $total_all = (int) $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn();
        break;

    case 'peminjaman':
        $judul_laporan = 'Laporan Transaksi Peminjaman (' . format_tanggal($dari) . ' – ' . format_tanggal($sampai) . ')';
        $kolom = ['Cover', 'Anggota', 'Buku', 'Tgl Pinjam', 'Jatuh Tempo', 'Status'];
        $stmt = $pdo->prepare("SELECT a.nama AS nama_anggota, b.judul, b.cover, p.tanggal_pinjam, p.tanggal_jatuh_tempo, p.status
                                FROM peminjaman p JOIN anggota a ON a.id_anggota=p.id_anggota LEFT JOIN buku b ON b.id_buku=p.id_buku
                                WHERE p.tanggal_pinjam BETWEEN :dari AND :sampai ORDER BY p.tanggal_pinjam DESC LIMIT $limit_laporan");
        $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
        $data = $stmt->fetchAll();
        $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM peminjaman p WHERE p.tanggal_pinjam BETWEEN :dari AND :sampai");
        $stmt_c->execute([':dari' => $dari, ':sampai' => $sampai]);
        $total_all = (int) $stmt_c->fetchColumn();
        break;

    case 'pengembalian':
        $judul_laporan = 'Laporan Transaksi Pengembalian (' . format_tanggal($dari) . ' – ' . format_tanggal($sampai) . ')';
        $kolom = ['Cover', 'Anggota', 'Buku', 'Tgl Kembali', 'Terlambat', 'Denda'];
        $stmt = $pdo->prepare("SELECT a.nama AS nama_anggota, b.judul, b.cover, p.tanggal_kembali, p.tanggal_jatuh_tempo, p.denda
                                FROM peminjaman p JOIN anggota a ON a.id_anggota=p.id_anggota LEFT JOIN buku b ON b.id_buku=p.id_buku
                                WHERE p.status='dikembalikan' AND p.tanggal_kembali BETWEEN :dari AND :sampai
                                ORDER BY p.tanggal_kembali DESC LIMIT $limit_laporan");
        $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
        $data = $stmt->fetchAll();
        $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM peminjaman p WHERE p.status='dikembalikan' AND p.tanggal_kembali BETWEEN :dari AND :sampai");
        $stmt_c->execute([':dari' => $dari, ':sampai' => $sampai]);
        $total_all = (int) $stmt_c->fetchColumn();
        break;

    case 'denda':
        $judul_laporan = 'Laporan Denda Keterlambatan (' . format_tanggal($dari) . ' – ' . format_tanggal($sampai) . ')';
        $kolom = ['Cover', 'Anggota', 'Buku', 'Tgl Kembali', 'Denda'];
        $stmt = $pdo->prepare("SELECT a.nama AS nama_anggota, b.judul, b.cover, p.tanggal_kembali, p.denda
                                FROM peminjaman p JOIN anggota a ON a.id_anggota=p.id_anggota LEFT JOIN buku b ON b.id_buku=p.id_buku
                                WHERE p.status='dikembalikan' AND p.denda > 0 AND p.tanggal_kembali BETWEEN :dari AND :sampai
                                ORDER BY p.tanggal_kembali DESC LIMIT $limit_laporan");
        $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
        $data = $stmt->fetchAll();
        $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM peminjaman p WHERE p.status='dikembalikan' AND p.denda > 0 AND p.tanggal_kembali BETWEEN :dari AND :sampai");
        $stmt_c->execute([':dari' => $dari, ':sampai' => $sampai]);
        $total_all = (int) $stmt_c->fetchColumn();
        break;
}
$is_terpotong = $total_all > $limit_laporan;

// ---- Chart & extra stats data ----
$chart1 = ['title'=>'', 'labels'=>[], 'values'=>[], 'type'=>'bar'];
$chart2 = ['title'=>'', 'labels'=>[], 'values'=>[], 'type'=>'doughnut'];
$extra_stats = [];
try {
    if ($jenis === 'buku') {
        // Distribusi kategori
        $stmtKat = $pdo->query("SELECT COALESCE(k.nama_kategori,'(Tanpa Kategori)') AS kat, COUNT(b.id_buku) AS jml FROM kategori k LEFT JOIN buku b ON b.id_kategori=k.id_kategori GROUP BY k.id_kategori, kat ORDER BY jml DESC");
        $katRows = $stmtKat->fetchAll();
        // Jika ada buku tanpa kategori (id_kategori IS NULL and not counted), hitung manual
        $tanpa = (int)$pdo->query("SELECT COUNT(*) FROM buku WHERE id_kategori IS NULL")->fetchColumn();
        // katRows already includes Tanpa Kategori via left join? left join from kategori misses null, so handle
        $labels = [];
        $values = [];
        foreach ($katRows as $r) {
            if ($r['kat']==='(Tanpa Kategori)') continue; // will handle below if tanpa==0 skip
            $labels[] = $r['kat'];
            $values[] = (int)$r['jml'];
        }
        if ($tanpa>0) { $labels[] = 'Tanpa Kategori'; $values[] = $tanpa; }
        // filter zero? keep but limit 6, merge rest as Lainnya
        if (count($labels) > 6) {
            $other = array_sum(array_slice($values,6));
            $labels = array_slice($labels,0,6);
            $values = array_slice($values,0,6);
            $labels[] = 'Lainnya'; $values[] = $other;
        }
        $chart1 = ['title'=>'Distribusi Kategori', 'labels'=>$labels, 'values'=>$values, 'type'=>'bar'];
        // Status ketersediaan
        $arsip = (int)$pdo->query("SELECT COUNT(*) FROM buku WHERE is_arsip=1")->fetchColumn();
        $aktif = $total_all - $arsip;
        $chart2 = ['title'=>'Status Koleksi', 'labels'=>['Aktif','Diarsipkan'], 'values'=>[$aktif,$arsip], 'type'=>'doughnut'];
        // extra stats for cards
        $stokSum = (int)$pdo->query("SELECT COALESCE(SUM(stok),0) FROM buku")->fetchColumn();
        $tersediaSum = (int)$pdo->query("SELECT COALESCE(SUM(tersedia),0) FROM buku")->fetchColumn();
        $katCount = (int)$pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
        $extra_stats = [
            ['label'=>'Eksemplar','value'=>$stokSum,'sub'=>'total stok'],
            ['label'=>'Tersedia','value'=>$tersediaSum,'sub'=>'siap pinjam'],
            ['label'=>'Arsip','value'=>$arsip,'sub'=>'disembunyikan'],
            ['label'=>'Kategori','value'=>$katCount,'sub'=>'kategori aktif'],
        ];
    } elseif ($jenis === 'anggota') {
        $aktif = (int)$pdo->query("SELECT COUNT(*) FROM anggota WHERE status='aktif'")->fetchColumn();
        $non = $total_all - $aktif;
        $chart1 = ['title'=>'Status Anggota', 'labels'=>['Aktif','Nonaktif'], 'values'=>[$aktif,$non], 'type'=>'doughnut'];
        // Tren pendaftaran 6 bulan terakhir
        $trend = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS bln, COUNT(*) AS jml FROM anggota WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY bln ORDER BY bln")->fetchAll(PDO::FETCH_KEY_PAIR);
        $tLabels=[]; $tValues=[];
        for($i=5;$i>=0;$i--){
            $d = date('Y-m', strtotime("-$i months"));
            $tLabels[] = date('M Y', strtotime($d.'-01'));
            $tValues[] = (int)($trend[$d] ?? 0);
        }
        $chart2 = ['title'=>'Pendaftaran 6 Bulan', 'labels'=>$tLabels, 'values'=>$tValues, 'type'=>'line'];
        $extra_stats = [
            ['label'=>'Aktif','value'=>$aktif,'sub'=>'dapat meminjam'],
            ['label'=>'Nonaktif','value'=>$non,'sub'=>'ditangguhkan'],
            ['label'=>'Baru 30 Hari','value'=>(int)$pdo->query("SELECT COUNT(*) FROM anggota WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),'sub'=>'pendaftar baru'],
            ['label'=>'Total','value'=>$total_all,'sub'=>'anggota terdaftar'],
        ];
    } elseif (in_array($jenis, ['peminjaman','pengembalian','denda'])) {
        // Tren harian sesuai periode
        $field = $jenis==='peminjaman' ? 'tanggal_pinjam' : 'tanggal_kembali';
        $whereExtra = $jenis==='denda' ? " AND denda > 0" : ($jenis==='pengembalian' ? " AND status='dikembalikan'" : "");
        // Gunakan prepared untuk rentang
        $stmtTrend = $pdo->prepare("SELECT $field AS tgl, COUNT(*) AS jml FROM peminjaman WHERE $field BETWEEN :dari AND :sampai $whereExtra GROUP BY $field ORDER BY tgl");
        $stmtTrend->execute([':dari'=>$dari, ':sampai'=>$sampai]);
        $raw = $stmtTrend->fetchAll(PDO::FETCH_KEY_PAIR);
        $period = new DatePeriod(new DateTime($dari), new DateInterval('P1D'), (new DateTime($sampai))->modify('+1 day'));
        $labels=[]; $values=[];
        $cnt=0;
        foreach($period as $dt){
            if($cnt>=31) break; // batasi 31 titik agar chart readable; jika lebih, sampling mingguan
            $k = $dt->format('Y-m-d');
            $labels[] = $dt->format('d M');
            $values[] = (int)($raw[$k] ?? 0);
            $cnt++;
        }
        // Jika periode >31 hari, switch ke agregasi mingguan
        if (count($labels) >= 31 && (new DateTime($sampai))->diff(new DateTime($dari))->days > 31) {
            $stmtWeek = $pdo->prepare("SELECT YEARWEEK($field,1) AS wk, MIN($field) AS tgl, COUNT(*) AS jml FROM peminjaman WHERE $field BETWEEN :dari AND :sampai $whereExtra GROUP BY wk ORDER BY wk");
            $stmtWeek->execute([':dari'=>$dari, ':sampai'=>$sampai]);
            $weekRows = $stmtWeek->fetchAll();
            $labels=[]; $values=[];
            foreach($weekRows as $rw){
                $labels[] = date('d M', strtotime($rw['tgl']));
                $values[] = (int)$rw['jml'];
            }
        }
        $chart1 = ['title'=> $jenis==='peminjaman' ? 'Tren Peminjaman' : ($jenis==='pengembalian' ? 'Tren Pengembalian' : 'Tren Denda'), 'labels'=>$labels, 'values'=>$values, 'type'=>'line'];
        // Status breakdown untuk peminjaman
        if ($jenis==='peminjaman') {
            $st = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE tanggal_pinjam BETWEEN :dari AND :sampai AND status='dipinjam'");
            $st->execute([':dari'=>$dari, ':sampai'=>$sampai]); $dipinjam=(int)$st->fetchColumn();
            $selesai = $total_all - $dipinjam;
            $chart2 = ['title'=>'Status','labels'=>['Dipinjam','Selesai'],'values'=>[$dipinjam,$selesai],'type'=>'doughnut'];
            $st2=$pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE tanggal_pinjam BETWEEN :dari AND :sampai AND status='dipinjam' AND tanggal_jatuh_tempo < CURDATE()");
            $st2->execute([':dari'=>$dari, ':sampai'=>$sampai]); $terlambat=(int)$st2->fetchColumn();
            $extra_stats = [
                ['label'=>'Total','value'=>$total_all,'sub'=>'transaksi'],
                ['label'=>'Dipinjam','value'=>$dipinjam,'sub'=>'berjalan'],
                ['label'=>'Selesai','value'=>$selesai,'sub'=>'dikembalikan'],
                ['label'=>'Terlambat','value'=>$terlambat,'sub'=>'jatuh tempo lewat'],
            ];
        } elseif ($jenis==='pengembalian' || $jenis==='denda') {
            $totalDenda = array_sum(array_column($data,'denda'));
            $telatRows = 0;
            foreach($data as $r){ if(hitung_keterlambatan($r['tanggal_jatuh_tempo'],$r['tanggal_kembali'])>0) $telatRows++; }
            $chart2 = ['title'=>'Keterlambatan','labels'=>['Tepat Waktu','Terlambat'],'values'=>[$total_all - $telatRows, $telatRows],'type'=>'doughnut'];
            $extra_stats = [
                ['label'=>'Total','value'=>$total_all,'sub'=>'pengembalian'],
                ['label'=>'Terlambat','value'=>$telatRows,'sub'=>'melewati tempo'],
                ['label'=>'Denda','value'=>format_rupiah($totalDenda),'sub'=>'total denda','isCurrency'=>true],
                ['label'=>'Periode','value'=> (new DateTime($dari))->diff(new DateTime($sampai))->days + 1 .' hari','sub'=>format_tanggal($dari).' – '.format_tanggal($sampai),'isSmall'=>true],
            ];
        }
    }
} catch (Throwable $e) {
    // chart failures are non-fatal
    error_log('Laporan chart gagal: '.$e->getMessage());
}

// Handler Export CSV — N3
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_data = [];
    $export_kolom = $kolom;
    switch ($jenis) {
        case 'buku':
            $stmt = $pdo->query("SELECT b.kode_buku, b.judul, b.penulis, b.cover, k.nama_kategori, b.lokasi_rak, b.stok, b.tersedia, b.is_arsip FROM buku b LEFT JOIN kategori k ON k.id_kategori=b.id_kategori ORDER BY b.judul ASC");
            $export_data = $stmt->fetchAll();
            break;
        case 'anggota':
            $stmt = $pdo->query("SELECT nomor_anggota, nama, email, no_hp, status, created_at FROM anggota ORDER BY nama ASC");
            $export_data = $stmt->fetchAll();
            break;
        case 'peminjaman':
            $stmt = $pdo->prepare("SELECT a.nama AS nama_anggota, b.judul, b.cover, p.tanggal_pinjam, p.tanggal_jatuh_tempo, p.status FROM peminjaman p JOIN anggota a ON a.id_anggota=p.id_anggota LEFT JOIN buku b ON b.id_buku=p.id_buku WHERE p.tanggal_pinjam BETWEEN :dari AND :sampai ORDER BY p.tanggal_pinjam DESC");
            $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
            $export_data = $stmt->fetchAll();
            break;
        case 'pengembalian':
            $stmt = $pdo->prepare("SELECT a.nama AS nama_anggota, b.judul, b.cover, p.tanggal_kembali, p.tanggal_jatuh_tempo, p.denda FROM peminjaman p JOIN anggota a ON a.id_anggota=p.id_anggota LEFT JOIN buku b ON b.id_buku=p.id_buku WHERE p.status='dikembalikan' AND p.tanggal_kembali BETWEEN :dari AND :sampai ORDER BY p.tanggal_kembali DESC");
            $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
            $export_data = $stmt->fetchAll();
            break;
        case 'denda':
            $stmt = $pdo->prepare("SELECT a.nama AS nama_anggota, b.judul, b.cover, p.tanggal_kembali, p.denda FROM peminjaman p JOIN anggota a ON a.id_anggota=p.id_anggota LEFT JOIN buku b ON b.id_buku=p.id_buku WHERE p.status='dikembalikan' AND p.denda > 0 AND p.tanggal_kembali BETWEEN :dari AND :sampai ORDER BY p.tanggal_kembali DESC");
            $stmt->execute([':dari' => $dari, ':sampai' => $sampai]);
            $export_data = $stmt->fetchAll();
            break;
    }
    $filename = 'laporan-' . $jenis . '-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_merge(['#'], $export_kolom));
    foreach ($export_data as $idx => $row) {
        $num = $idx + 1;
        if ($jenis === 'buku') {
            fputcsv($out, [$num, $row['cover'] ?? '', $row['kode_buku'], $row['judul'], $row['penulis'], $row['nama_kategori'] ?? '', $row['lokasi_rak'] ?? '', $row['stok'], $row['tersedia'], !empty($row['is_arsip']) ? 'Diarsipkan' : 'Aktif']);
        } elseif ($jenis === 'anggota') {
            fputcsv($out, [$num, $row['nomor_anggota'], $row['nama'], $row['email'], $row['no_hp'] ?? '', ucfirst($row['status']), format_tanggal($row['created_at'])]);
        } elseif ($jenis === 'peminjaman') {
            fputcsv($out, [$num, $row['cover'] ?? '', $row['nama_anggota'], $row['judul'], format_tanggal($row['tanggal_pinjam']), format_tanggal($row['tanggal_jatuh_tempo']), $row['status'] === 'dipinjam' ? 'Sedang Dipinjam' : 'Selesai']);
        } elseif ($jenis === 'pengembalian') {
            $telat = hitung_keterlambatan($row['tanggal_jatuh_tempo'], $row['tanggal_kembali']);
            fputcsv($out, [$num, $row['cover'] ?? '', $row['nama_anggota'], $row['judul'], format_tanggal($row['tanggal_kembali']), $telat . ' hari', format_rupiah($row['denda'])]);
        } elseif ($jenis === 'denda') {
            fputcsv($out, [$num, $row['cover'] ?? '', $row['nama_anggota'], $row['judul'], format_tanggal($row['tanggal_kembali']), format_rupiah($row['denda'])]);
        }
    }
    fclose($out);
    exit;
}

$menu_aktif = 'laporan';
$page_title = 'Laporan';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<style>
  .lap-cover{width:40px;height:54px;border-radius:8px;object-fit:cover;background:var(--paper-2);border:1px solid var(--border)}
  .lap-badge{display:inline-flex;align-items:center;gap:.35rem;border-radius:999px;padding:.28rem .6rem;font-size:.70rem;font-weight:700;border:1px solid var(--border);white-space:nowrap}
  .lap-badge-theme{background:var(--accent-soft);color:var(--accent-text);border-color:var(--accent-soft-2)}
  .lap-badge-safe{background:#ecfdf5;color:#065f46;border-color:#a7f3d0}
  .lap-badge-danger{background:#fef2f2;color:#991b1b;border-color:#fecaca}
  .lap-badge-warn{background:#fffbeb;color:#92400e;border-color:#fde68a}
  .lap-stat{border:1px solid var(--border);background:var(--surface);border-radius:10px;padding:14px 16px}
  .lap-chart{border:1px solid var(--border);background:var(--surface);border-radius:10px;padding:16px}
  .lap-sheet{border:1px solid var(--border);background:var(--surface);border-radius:10px;padding:16px}
  /* Book card - consistent with Data Buku & Katalog hierarchy */
  .lap-buku-desktop{display:block}
  .lap-buku-mobile{display:none}
  .lap-buku-cover{width:44px;height:60px;border-radius:8px;object-fit:cover;background:var(--paper-2);border:1px solid var(--border);flex-shrink:0}
  .lap-buku-title{font-size:13px;font-weight:650;line-height:1.35;color:var(--text);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .lap-buku-author{font-size:12px;color:var(--text-faint);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .lap-buku-meta{font-size:11px;color:var(--text-faint-2);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
  .lap-buku-dot{width:4px;height:4px;border-radius:999px;background:var(--border-strong);display:inline-block}
  .lap-buku-stock{font-size:11px;font-weight:600;color:var(--text-muted);display:inline-flex;align-items:center;gap:6px}
  .lap-buku-stock-dot{width:6px;height:6px;border-radius:999px;display:inline-block}
  /* Compact book card mobile */
  .buku-card{border:1px solid var(--border);background:var(--surface);border-radius:10px;display:flex;gap:12px;padding:12px;transition:border-color 120ms ease}
  .buku-card:hover{border-color:var(--border-strong)}
  .buku-card-cover{width:64px;height:86px;border-radius:8px;object-fit:cover;background:var(--paper-2);border:1px solid var(--border);flex-shrink:0}
  .buku-card-fallback{width:64px;height:86px;border-radius:8px;background:var(--paper-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-faint-2);font-size:18px;flex-shrink:0}
  /* Member avatar */
  .member-avatar{width:40px;height:40px;border-radius:999px;object-fit:cover;border:1px solid var(--border);flex-shrink:0;background:var(--surface)}
  .member-avatar-sm{width:36px;height:36px;border-radius:999px;object-fit:cover;border:1px solid var(--border);flex-shrink:0}
  .member-fallback{width:40px;height:40px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;background:var(--accent-soft);color:var(--accent-text);border:1px solid var(--accent-soft-2);flex-shrink:0;letter-spacing:.02em}
  .member-fallback-sm{width:36px;height:36px;border-radius:999px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:var(--accent-soft);color:var(--accent-text);border:1px solid var(--accent-soft-2);flex-shrink:0}
  .member-card{border:1px solid var(--border);background:var(--surface);border-radius:10px;padding:12px;display:flex;gap:12px;align-items:flex-start;transition:border-color 120ms ease}
  .member-card:hover{border-color:var(--border-strong)}
  @media(max-width:767px){
    .lap-sheet{padding:14px}
    /* Hide desktop buku table on mobile, show cards */
    .lap-buku-desktop{display:none !important}
    .lap-buku-mobile{display:grid !important;gap:10px}
    .lap-anggota-desktop{display:none !important}
    .lap-anggota-mobile{display:grid !important;gap:10px}
    /* Generic tables (peminjaman etc) keep stacked but more compact */
    .lap-table-card{overflow:visible !important;border:0 !important;background:transparent !important;box-shadow:none !important}
    .lap-table{display:block;width:100%}
    .lap-table thead{display:none}
    .lap-table tbody,.lap-table tr,.lap-table td{display:block;width:100%}
    .lap-table tbody tr{margin-bottom:10px;padding:14px;border:1px solid var(--border);border-radius:10px;background:var(--surface)}
    .lap-table tbody tr:last-child{margin-bottom:0}
    .lap-table td{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:9px 0 !important;border-bottom:1px solid var(--border-faint);text-align:right}
    .lap-table td:last-child{border-bottom:0}
    .lap-table td::before{content:attr(data-label);flex:0 0 36%;text-align:left;color:var(--text-faint);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;line-height:1.3}
    .lap-table td[data-label="Cover"]{display:block;padding-top:0 !important;border-bottom:0;text-align:left}
    .lap-table td[data-label="Cover"]::before{display:none}
    .lap-table td[colspan]{display:block;text-align:center;border:0}
    .lap-table td[colspan]::before{display:none}
  }
  @media(min-width:768px){
    .lap-buku-mobile{display:none !important}
    .lap-anggota-mobile{display:none !important}
  }
  @media print{
    @page{size:A4 landscape;margin:12mm}
    html,body{background:#fff !important}
    body{color:#111827 !important;font-size:10px !important}
    nav,footer,#navToggle,.no-print,#drawerBackdrop,#mobileDrawer{display:none !important}
    main{padding:0 !important;margin:0 !important;max-width:100% !important;width:100% !important}
    .lap-stat,.lap-sheet,.lap-chart{box-shadow:none !important;border:1px solid #e5e7eb !important}
    .lap-chart{break-inside:avoid}
    .lap-buku-mobile,.lap-anggota-mobile{display:none !important}
    .lap-buku-desktop,.lap-anggota-desktop{display:block !important}
    .lap-table-card{overflow:visible !important;border:0 !important;box-shadow:none !important;background:#fff !important}
    .lap-table{display:table !important;width:100% !important;border-collapse:collapse !important}
    .lap-table thead{display:table-header-group !important}
    .lap-table tbody{display:table-row-group !important}
    .lap-table tfoot{display:table-footer-group !important}
    .lap-table tr{display:table-row !important;margin:0 !important;padding:0 !important;border:0 !important;background:transparent !important;box-shadow:none !important;page-break-inside:avoid !important}
    .lap-table th,.lap-table td{display:table-cell !important;padding:5px 6px !important;border:1px solid #d1d5db !important;text-align:left !important;vertical-align:middle !important;white-space:normal !important;color:#111827 !important}
    .lap-table td::before{display:none !important;content:none !important}
    .lap-cover{width:32px !important;height:44px !important}
  }
</style>

<!-- Page header -->
<div class="flex flex-col lg:flex-row lg:items-end justify-between gap-3 mb-4 pb-4" style="border-bottom:1px solid var(--border)">
  <div class="min-w-0">
    <p class="text-[11px] font-bold tracking-[.14em] uppercase" style="color:var(--text-faint-2)">Panel laporan</p>
    <h1 class="font-display text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)">Laporan</h1>
    <p class="text-sm mt-1" style="color:var(--text-faint)">Pantau statistik koleksi dan aktivitas perpustakaan.</p>
  </div>
  <div class="no-print flex gap-2 shrink-0">
    <a href="?jenis=<?= e($jenis) ?><?= in_array($jenis, ['peminjaman','pengembalian','denda']) ? '&dari=' . e($dari) . '&sampai=' . e($sampai) : '' ?>&export=csv" class="inline-flex items-center justify-center gap-1.5 border bg-white hover:bg-slate-50 text-sm font-semibold px-4 py-2 rounded-lg" style="border-color:var(--border); color:var(--text-muted)"><i class="bi bi-download text-xs"></i> Export CSV</a>
    <button onclick="window.print()" class="inline-flex items-center justify-center gap-1.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold px-4 py-2 rounded-lg"><i class="bi bi-printer text-xs"></i> Cetak</button>
  </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 mb-4">
  <?php
    $total_data = count($data);
    if (!empty($extra_stats)) {
        foreach ($extra_stats as $st) {
            $isCurr = !empty($st['isCurrency']);
            $isSmall = !empty($st['isSmall']);
            echo '<div class="lap-stat">';
            echo '<p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">'.e($st['label']).'</p>';
            if ($isSmall) {
                echo '<p class="text-sm font-bold mt-1.5 truncate" style="color:var(--text)" title="'.e($st['value']).'">'.e($st['value']).'</p>';
            } elseif ($isCurr) {
                echo '<p class="text-[15px] font-bold mt-1.5" style="color:'.(strpos($st['value'],'Rp')!==false && $st['value']!=='Rp 0' ? '#991b1b' : 'var(--text)').'">'.e($st['value']).'</p>';
            } else {
                echo '<p class="text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)">'.e((string)$st['value']).'</p>';
            }
            echo '<p class="text-xs mt-0.5 truncate" style="color:var(--text-faint)">'.e($st['sub']).'</p>';
            echo '</div>';
        }
    } else {
        // fallback generic
        $label_total = $jenis === 'buku' ? 'Total Buku' : ($jenis === 'anggota' ? 'Total Anggota' : ($jenis === 'peminjaman' ? 'Total Peminjaman' : ($jenis === 'pengembalian' ? 'Total Pengembalian' : 'Total Denda')));
        $sub_total = $jenis === 'buku' ? 'data katalog' : ($jenis === 'anggota' ? 'anggota terdaftar' : 'sesuai periode');
        $denda_total = in_array($jenis, ['pengembalian','denda']) ? array_sum(array_column($data, 'denda')) : 0;
        echo '<div class="lap-stat"><p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">'.e($label_total).'</p><p class="text-[22px] font-bold tracking-tight mt-1" style="color:var(--text)">'.$total_data.'</p><p class="text-xs mt-0.5" style="color:var(--text-faint)">'.e($sub_total).'</p></div>';
        echo '<div class="lap-stat"><p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">Periode</p><p class="text-sm font-bold mt-2 truncate" style="color:var(--text)">'.(in_array($jenis,['peminjaman','pengembalian','denda']) ? e(format_tanggal($dari).' – '.format_tanggal($sampai)) : 'Semua waktu').'</p><p class="text-xs mt-0.5" style="color:var(--text-faint)">filter laporan</p></div>';
        echo '<div class="lap-stat"><p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">Tampilan</p><p class="text-sm font-bold mt-2" style="color:var(--text)">Kartu + tabel</p><p class="text-xs mt-0.5" style="color:var(--text-faint)">Responsif di HP</p></div>';
        echo '<div class="lap-stat"><p class="text-[11px] font-bold uppercase tracking-wide" style="color:var(--text-faint-2)">Total Denda</p><p class="text-[15px] font-bold mt-1.5" style="color:'.($denda_total>0?'#991b1b':'var(--text)').'">'.format_rupiah($denda_total).'</p><p class="text-xs mt-0.5" style="color:var(--text-faint)">'.($denda_total>0?'ada keterlambatan':'tidak ada denda').'</p></div>';
    }
  ?>
</div>

<!-- Filter -->
<form method="get" class="no-print bg-white rounded-lg border p-3 mb-4" style="border-color:var(--border)">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
    <div class="sm:col-span-2 lg:col-span-1">
      <label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Jenis Laporan</label>
      <select name="jenis" onchange="this.form.submit()" class="w-full h-10 border rounded-lg px-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)">
        <option value="buku" <?= $jenis==='buku'?'selected':'' ?>>Data Buku</option>
        <option value="anggota" <?= $jenis==='anggota'?'selected':'' ?>>Data Anggota</option>
        <option value="peminjaman" <?= $jenis==='peminjaman'?'selected':'' ?>>Peminjaman</option>
        <option value="pengembalian" <?= $jenis==='pengembalian'?'selected':'' ?>>Pengembalian</option>
        <option value="denda" <?= $jenis==='denda'?'selected':'' ?>>Denda</option>
      </select>
    </div>
    <?php if (in_array($jenis, ['peminjaman', 'pengembalian', 'denda'])): ?>
      <div><label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Dari Tanggal</label><input type="date" name="dari" value="<?= e($dari) ?>" class="w-full h-10 border rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)"></div>
      <div><label class="block text-xs font-bold mb-1.5" style="color:var(--text-muted)">Sampai Tanggal</label><input type="date" name="sampai" value="<?= e($sampai) ?>" class="w-full h-10 border rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color:var(--border); background:var(--surface)"></div>
      <button class="w-full h-10 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-sm font-semibold">Terapkan Filter</button>
    <?php endif; ?>
  </div>
</form>

<?php if ($is_terpotong): ?>
<div class="mb-4 rounded-lg border px-4 py-3 flex gap-3 items-start" style="background:#fffbeb; border-color:#fde68a">
  <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center text-sm" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a"><i class="bi bi-exclamation-triangle"></i></span>
  <div class="min-w-0 flex-1">
    <h3 class="text-sm font-bold" style="color:#92400e">Laporan terpotong</h3>
    <p class="text-sm mt-1 leading-6" style="color:#92400e">Menampilkan <b><?= $limit_laporan ?></b> dari <b><?= $total_all ?></b> data (<?= $total_all - $limit_laporan ?> tidak ditampilkan). Persempit rentang tanggal untuk data spesifik.</p>
  </div>
</div>
<?php endif; ?>

<!-- Visual data -->
<?php if (!empty($chart1['labels']) || !empty($chart2['labels'])): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4 no-print">
  <?php if (!empty($chart1['labels'])): ?>
  <div class="lap-chart">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-sm" style="color:var(--text)"><?= e($chart1['title']) ?></h3>
      <span class="text-[11px] font-bold uppercase tracking-wide px-2 py-1 rounded-full border" style="background:var(--surface-2); color:var(--text-faint); border-color:var(--border)"><?= count($chart1['labels']) ?> data</span>
    </div>
    <div class="relative h-56"><canvas id="lapChart1"></canvas></div>
  </div>
  <?php endif; ?>
  <?php if (!empty($chart2['labels'])): ?>
  <div class="lap-chart">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-sm" style="color:var(--text)"><?= e($chart2['title']) ?></h3>
      <span class="text-[11px] font-bold uppercase tracking-wide px-2 py-1 rounded-full border" style="background:var(--surface-2); color:var(--text-faint); border-color:var(--border)"><?= count($chart2['labels']) ?> data</span>
    </div>
    <div class="relative h-56"><canvas id="lapChart2"></canvas></div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
// Helpers for avatar initials
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
?>
<!-- Detail -->
<div class="lap-sheet">
  <div class="flex items-start justify-between gap-3 mb-4">
    <div class="min-w-0">
      <p class="text-[11px] font-bold uppercase tracking-[.08em]" style="color:var(--text-faint-2)">Hasil laporan</p>
      <h2 class="font-display font-semibold text-[17px] leading-tight mt-1" style="color:var(--text)"><?= e($judul_laporan) ?></h2>
      <?php if ($is_terpotong): ?><p class="text-xs mt-1 font-medium" style="color:#b45309">Menampilkan <?= count($data) ?> dari <?= $total_all ?> data</p><?php endif; ?>
    </div>
    <span class="hidden sm:inline-flex lap-badge <?= $is_terpotong ? 'lap-badge-warn' : 'lap-badge-theme' ?>"><i class="bi bi-file-earmark-text text-xs"></i><?= $is_terpotong ? $total_all.' total' : count($data).' data' ?></span>
  </div>

  <?php if (empty($data)): ?>
    <div class="text-center py-10" style="color:var(--text-faint)"><i class="bi bi-inbox text-2xl block mb-2"></i><p class="font-semibold text-sm" style="color:var(--text)">Belum ada data</p><p class="text-xs mt-1">Tidak ada data untuk jenis/periode laporan ini.</p></div>
  <?php else: ?>

    <?php if ($jenis === 'buku'): ?>
      <!-- Desktop modern table for buku -->
      <div class="lap-buku-desktop overflow-hidden rounded-lg border" style="border-color:var(--border)">
        <div class="overflow-x-auto">
          <table class="w-full text-sm no-responsive">
            <thead>
              <tr style="background:var(--surface-2); border-bottom:1px solid var(--border)">
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">#</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest" style="color:var(--text-faint)">Buku</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Kategori</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Rak</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Stok</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y" style="border-color:var(--border-faint)">
              <?php foreach ($data as $i => $row):
                $isArsip = !empty($row['is_arsip']);
                $habis = (int)$row['tersedia'] <= 0 && !$isArsip;
              ?>
                <tr class="hover:bg-[var(--surface-2)] transition-colors">
                  <td class="px-3 py-3 whitespace-nowrap text-xs" style="color:var(--text-faint-2)"><?= $i+1 ?></td>
                  <td class="px-3 py-3">
                    <div class="flex gap-3 min-w-0">
                      <img src="<?= e(cover_thumb_url($row['cover'] ?? null)) ?>" alt="" loading="lazy" onerror="this.src='<?= BASE_URL ?>/assets/img/no-cover.svg'" class="lap-buku-cover">
                      <div class="min-w-0 flex-1">
                        <p class="lap-buku-title" title="<?= e($row['judul']) ?>"><?= e($row['judul']) ?></p>
                        <p class="lap-buku-author"><?= e($row['penulis']) ?></p>
                        <p class="text-[11px] mt-1" style="color:var(--text-faint-2)"><span class="font-semibold" style="color:var(--text-faint)"><?= e($row['kode_buku']) ?></span> <span class="mx-1">·</span> <?= e($row['nama_kategori'] ?: 'Tanpa Kategori') ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="px-3 py-3 whitespace-nowrap"><span class="lap-badge lap-badge-theme"><?= e($row['nama_kategori'] ?: '-') ?></span></td>
                  <td class="px-3 py-3 whitespace-nowrap text-sm" style="color:var(--text-muted)"><?= e($row['lokasi_rak'] ?: '-') ?></td>
                  <td class="px-3 py-3 whitespace-nowrap">
                    <div class="text-xs font-semibold" style="color:var(--text)">Stok <?= (int)$row['stok'] ?></div>
                    <div class="text-xs flex items-center gap-1.5 mt-1" style="color:<?= $habis ? '#991b1b' : '#065f46' ?>"><span class="w-1.5 h-1.5 rounded-full" style="background:<?= $habis ? '#dc2626' : '#059669' ?>"></span><?= $habis ? 'Habis' : 'Tersedia '.(int)$row['tersedia'] ?></div>
                  </td>
                  <td class="px-3 py-3 whitespace-nowrap"><?php if ($isArsip): ?><span class="lap-badge lap-badge-danger">Diarsipkan</span><?php else: ?><span class="lap-badge lap-badge-safe">Aktif</span><?php endif; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Mobile compact book cards -->
      <div class="lap-buku-mobile">
        <?php foreach ($data as $row):
          $isArsip = !empty($row['is_arsip']);
          $habis = (int)$row['tersedia'] <= 0 && !$isArsip;
        ?>
          <article class="buku-card">
            <?php if (!empty($row['cover'])): ?>
              <img src="<?= e(cover_thumb_url($row['cover'])) ?>" alt="" loading="lazy" onerror="this.src='<?= BASE_URL ?>/assets/img/no-cover.svg'" class="buku-card-cover">
            <?php else: ?>
              <div class="buku-card-fallback"><i class="bi bi-book"></i></div>
            <?php endif; ?>
            <div class="flex-1 min-w-0 flex flex-col">
              <h3 class="text-[13px] font-semibold leading-5 line-clamp-2" style="color:var(--text)" title="<?= e($row['judul']) ?>"><?= e($row['judul']) ?></h3>
              <p class="text-xs truncate mt-0.5" style="color:var(--text-faint)"><?= e($row['penulis']) ?></p>
              <div class="lap-buku-meta mt-1.5">
                <span><?= e($row['nama_kategori'] ?: 'Tanpa Kategori') ?></span>
                <span class="lap-buku-dot"></span>
                <span><?= e($row['lokasi_rak'] ?: 'Tanpa Rak') ?></span>
              </div>
              <p class="text-[11px] font-semibold mt-1" style="color:var(--text-faint-2)"><?= e($row['kode_buku']) ?></p>
              <div class="flex items-center justify-between gap-2 mt-auto pt-2.5 border-t" style="border-color:var(--border-faint)">
                <span class="lap-buku-stock">
                  <span class="lap-buku-stock-dot" style="background:<?= $habis ? '#dc2626' : '#059669' ?>"></span>
                  <?= $habis ? 'Habis' : 'Tersedia '.(int)$row['tersedia'] ?>
                  <span style="color:var(--text-faint-2); font-weight:400">· Stok <?= (int)$row['stok'] ?></span>
                </span>
                <?php if ($isArsip): ?><span class="lap-badge lap-badge-danger text-[11px]">Diarsipkan</span><?php else: ?><span class="lap-badge lap-badge-safe text-[11px]">Aktif</span><?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

    <?php elseif ($jenis === 'anggota'): ?>
      <!-- Desktop member table with avatar -->
      <div class="lap-anggota-desktop overflow-hidden rounded-lg border" style="border-color:var(--border)">
        <div class="overflow-x-auto">
          <table class="w-full text-sm no-responsive">
            <thead>
              <tr style="background:var(--surface-2); border-bottom:1px solid var(--border)">
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest" style="color:var(--text-faint)">Anggota</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">ID Anggota</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Kontak</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Pinjaman</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Status</th>
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">Terdaftar</th>
              </tr>
            </thead>
            <tbody class="divide-y" style="border-color:var(--border-faint)">
              <?php foreach ($data as $row):
                $initials = initials_from_name($row['nama']);
                $hasFoto = !empty($row['foto']);
                $aktifPinjam = (int)($row['aktif_pinjam'] ?? 0);
              ?>
                <tr class="hover:bg-[var(--surface-2)] transition-colors">
                  <td class="px-3 py-3">
                    <div class="flex gap-3 items-center min-w-0">
                      <?php if ($hasFoto): ?>
                        <img src="<?= e(foto_profil_url($row['foto'])) ?>" alt="" loading="lazy" class="member-avatar-sm">
                      <?php else: ?>
                        <span class="member-fallback-sm"><?= e($initials) ?></span>
                      <?php endif; ?>
                      <div class="min-w-0">
                        <p class="text-sm font-semibold truncate max-w-[180px]" style="color:var(--text)" title="<?= e($row['nama']) ?>"><?= e($row['nama']) ?></p>
                        <p class="text-xs truncate max-w-[200px]" style="color:var(--text-faint)" title="<?= e($row['email']) ?>"><?= e($row['email']) ?></p>
                      </div>
                    </div>
                  </td>
                  <td class="px-3 py-3 whitespace-nowrap font-semibold text-xs" style="color:var(--text-muted)"><?= e($row['nomor_anggota']) ?></td>
                  <td class="px-3 py-3 whitespace-nowrap text-xs" style="color:var(--text-muted)"><?= e($row['no_hp'] ?: '-') ?></td>
                  <td class="px-3 py-3 whitespace-nowrap">
                    <?php if ($aktifPinjam > 0): ?>
                      <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full border" style="background:var(--accent-soft); color:var(--accent-text); border-color:var(--accent-soft-2)"><i class="bi bi-bookmark text-[11px]"></i> <?= $aktifPinjam ?> aktif</span>
                    <?php else: ?>
                      <span class="text-xs" style="color:var(--text-faint-2)">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-3 py-3 whitespace-nowrap"><span class="lap-badge <?= $row['status']==='aktif' ? 'lap-badge-safe' : 'lap-badge-danger' ?>"><?= e(ucfirst($row['status'])) ?></span></td>
                  <td class="px-3 py-3 whitespace-nowrap text-xs" style="color:var(--text-faint)"><?= format_tanggal($row['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Mobile member cards -->
      <div class="lap-anggota-mobile">
        <?php foreach ($data as $row):
          $initials = initials_from_name($row['nama']);
          $hasFoto = !empty($row['foto']);
          $aktifPinjam = (int)($row['aktif_pinjam'] ?? 0);
        ?>
          <article class="member-card">
            <?php if ($hasFoto): ?>
              <img src="<?= e(foto_profil_url($row['foto'])) ?>" alt="" loading="lazy" class="member-avatar">
            <?php else: ?>
              <span class="member-fallback"><?= e($initials) ?></span>
            <?php endif; ?>
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <h3 class="text-sm font-semibold truncate pr-2" style="color:var(--text)" title="<?= e($row['nama']) ?>"><?= e($row['nama']) ?></h3>
                <span class="lap-badge <?= $row['status']==='aktif' ? 'lap-badge-safe' : 'lap-badge-danger' ?> shrink-0 text-[11px]"><?= e(ucfirst($row['status'])) ?></span>
              </div>
              <p class="text-xs truncate" style="color:var(--text-faint)" title="<?= e($row['email']) ?>"><?= e($row['email']) ?></p>
              <p class="text-[11px] font-semibold mt-1.5" style="color:var(--text-faint-2)"><?= e($row['nomor_anggota']) ?> <span style="color:var(--border-strong)">·</span> <?= e($row['no_hp'] ?: 'Tanpa HP') ?></p>
              <div class="flex items-center justify-between gap-2 mt-2.5 pt-2.5 border-t" style="border-color:var(--border-faint)">
                <span class="text-xs" style="color:var(--text-faint)">
                  <?php if ($aktifPinjam > 0): ?><span class="font-semibold" style="color:var(--accent-text)"><?= $aktifPinjam ?> peminjaman aktif</span><?php else: ?>Tidak ada pinjaman aktif<?php endif; ?>
                </span>
                <span class="text-xs" style="color:var(--text-faint-2)"><?= format_tanggal($row['created_at']) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <!-- Generic table for peminjaman/pengembalian/denda (keep modern but compact) -->
      <div class="lap-table-card overflow-hidden rounded-lg border" style="border-color:var(--border)">
        <div class="overflow-x-auto">
          <table class="w-full text-sm lap-table no-responsive">
            <thead>
              <tr style="background:var(--surface-2); border-bottom:1px solid var(--border)">
                <th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)">#</th>
                <?php foreach ($kolom as $k): ?><th class="px-3 py-2.5 text-left text-[11px] font-bold uppercase tracking-widest whitespace-nowrap" style="color:var(--text-faint)"><?= e($k) ?></th><?php endforeach; ?>
              </tr>
            </thead>
            <tbody class="divide-y" style="border-color:var(--border-faint)">
              <?php foreach ($data as $i => $row): ?>
                <tr class="hover:bg-[var(--surface-2)] transition-colors">
                  <td class="px-3 py-2.5 whitespace-nowrap" style="color:var(--text-faint-2)" data-label="#"><?= $i+1 ?></td>
                  <?php if ($jenis === 'peminjaman'): ?>
                    <td class="px-3 py-2.5" data-label="Cover"><img src="<?= e(cover_url($row['cover'] ?? null)) ?>" alt="" class="lap-cover"></td>
                    <td class="px-3 py-2.5 font-semibold max-w-[150px] truncate" data-label="Anggota" title="<?= e($row['nama_anggota']) ?>"><?= e($row['nama_anggota']) ?></td>
                    <td class="px-3 py-2.5 font-semibold max-w-[180px] truncate" style="color:var(--text)" data-label="Buku" title="<?= e($row['judul'] ?? 'Buku telah dihapus dari katalog') ?>"><?= e($row['judul'] ?? 'Buku telah dihapus dari katalog') ?><?php if (empty($row['judul'])): ?> <span class="ml-1 text-[11px] px-1.5 py-0.5 rounded-full" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca">Tidak tersedia</span><?php endif; ?></td>
                    <td class="px-3 py-2.5 whitespace-nowrap" data-label="Tgl Pinjam"><?= format_tanggal($row['tanggal_pinjam']) ?></td>
                    <td class="px-3 py-2.5 whitespace-nowrap" data-label="Jatuh Tempo"><?= format_tanggal($row['tanggal_jatuh_tempo']) ?></td>
                    <td class="px-3 py-2.5" data-label="Status"><span class="lap-badge <?= $row['status']==='dipinjam' ? 'lap-badge-theme' : 'lap-badge-safe' ?>"><?= $row['status']==='dipinjam' ? 'Dipinjam' : 'Selesai' ?></span></td>
                  <?php elseif ($jenis === 'pengembalian'): ?>
                    <?php $telat=hitung_keterlambatan($row['tanggal_jatuh_tempo'],$row['tanggal_kembali']); ?>
                    <td class="px-3 py-2.5" data-label="Cover"><img src="<?= e(cover_url($row['cover'] ?? null)) ?>" alt="" class="lap-cover"></td>
                    <td class="px-3 py-2.5 font-semibold max-w-[140px] truncate" data-label="Anggota"><?= e($row['nama_anggota']) ?></td>
                    <td class="px-3 py-2.5 font-semibold max-w-[160px] truncate" style="color:var(--text)" data-label="Buku"><?= e($row['judul'] ?? 'Buku telah dihapus dari katalog') ?><?php if (empty($row['judul'])): ?> <span class="ml-1 text-[11px] px-1.5 py-0.5 rounded-full" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca">Tidak tersedia</span><?php endif; ?></td>
                    <td class="px-3 py-2.5 whitespace-nowrap" data-label="Tgl Kembali"><?= format_tanggal($row['tanggal_kembali']) ?></td>
                    <td class="px-3 py-2.5" data-label="Terlambat"><span class="lap-badge <?= $telat>0 ? 'lap-badge-danger' : 'lap-badge-safe' ?>"><?= $telat ?> hari</span></td>
                    <td class="px-3 py-2.5 whitespace-nowrap font-bold" style="color:<?= $row['denda']>0 ? '#991b1b' : '#065f46' ?>" data-label="Denda"><?= format_rupiah($row['denda']) ?></td>
                  <?php elseif ($jenis === 'denda'): ?>
                    <td class="px-3 py-2.5" data-label="Cover"><img src="<?= e(cover_url($row['cover'] ?? null)) ?>" alt="" class="lap-cover"></td>
                    <td class="px-3 py-2.5 font-semibold max-w-[150px] truncate" data-label="Anggota"><?= e($row['nama_anggota']) ?></td>
                    <td class="px-3 py-2.5 font-semibold max-w-[180px] truncate" style="color:var(--text)" data-label="Buku"><?= e($row['judul'] ?? 'Buku telah dihapus dari katalog') ?><?php if (empty($row['judul'])): ?> <span class="ml-1 text-[11px] px-1.5 py-0.5 rounded-full" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca">Tidak tersedia</span><?php endif; ?></td>
                    <td class="px-3 py-2.5 whitespace-nowrap" data-label="Tgl Kembali"><?= format_tanggal($row['tanggal_kembali']) ?></td>
                    <td class="px-3 py-2.5 whitespace-nowrap font-bold" style="color:#991b1b" data-label="Denda"><?= format_rupiah($row['denda']) ?></td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <?php if (in_array($jenis,['pengembalian','denda']) && !empty($data)): ?><tfoot><tr class="font-bold" style="background:var(--surface-2); border-top:1px solid var(--border)"><td colspan="<?= count($kolom) ?>" class="px-3 py-2.5 text-right" style="color:var(--text)">Total Denda</td><td class="px-3 py-2.5" style="color:#991b1b"><?= format_rupiah(array_sum(array_column($data,'denda'))) ?></td></tr></tfoot><?php endif; ?>
          </table>
        </div>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php if (!empty($chart1['labels']) || !empty($chart2['labels'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function(){
  const cs = getComputedStyle(document.documentElement);
  const accent = cs.getPropertyValue('--accent').trim() || '#243a5e';
  const surface2 = cs.getPropertyValue('--border').trim() || '#e7e5e4';
  const palette = ['#243a5e','#3d6a94','#6a9ac4','#8fb4d6','#a8a29e','#78716c','#1c1917','#44403c'];
  const paletteSoft = ['rgba(36,58,94,.14)','rgba(61,106,148,.14)','rgba(106,154,196,.14)','rgba(143,180,214,.14)','rgba(168,162,158,.14)','rgba(120,113,108,.14)'];
  function mkChart(id, cfg){
    const el = document.getElementById(id);
    if(!el || typeof Chart==='undefined' || !cfg.labels.length) return;
    const type = cfg.type;
    const gridColor = 'rgba(0,0,0,.06)';
    const tickColor = '#78716c';
    let datasets, options;
    if(type==='doughnut'){
      datasets=[{data:cfg.values, backgroundColor: palette.slice(0,cfg.values.length), borderWidth: 0, hoverOffset: 6}];
      options={responsive:true, maintainAspectRatio:false, cutout:'62%', plugins:{legend:{position:'bottom', labels:{usePointStyle:true, pointStyle:'circle', padding:14, color: tickColor, font:{size:11, weight:600}}}}, animation:false};
    } else if(type==='line'){
      datasets=[{label:cfg.title, data:cfg.values, borderColor: accent, backgroundColor: 'rgba(36,58,94,.08)', fill:true, tension:.32, pointRadius:2, pointHoverRadius:4, borderWidth:2}];
      options={responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{grid:{display:false}, ticks:{color:tickColor, maxTicksLimit:6, maxRotation:0}}, y:{beginAtZero:true, grid:{color:gridColor}, ticks:{color:tickColor, precision:0}}}, animation:false};
    } else {
      datasets=[{label:cfg.title, data:cfg.values, backgroundColor: cfg.values.map((_,i)=> palette[i%palette.length]), borderRadius:6, borderSkipped:false, barThickness: 14}];
      options={responsive:true, maintainAspectRatio:false, indexAxis: cfg.labels.length<=6 ? 'y' : 'x', plugins:{legend:{display:false}}, scales: cfg.labels.length<=6 ? {x:{beginAtZero:true, grid:{color:gridColor}, ticks:{color:tickColor, precision:0}}, y:{grid:{display:false}, ticks:{color:tickColor}}} : {x:{grid:{display:false}, ticks:{color:tickColor, maxTicksLimit:6}}, y:{beginAtZero:true, grid:{color:gridColor}, ticks:{color:tickColor, precision:0}}}, animation:false};
      // for horizontal bar, use y axis labels
    }
    new Chart(el, {type: type==='bar' ? 'bar' : type, data:{labels:cfg.labels, datasets:datasets}, options:options});
  }
  mkChart('lapChart1', <?= json_encode($chart1, JSON_UNESCAPED_UNICODE) ?>);
  mkChart('lapChart2', <?= json_encode($chart2, JSON_UNESCAPED_UNICODE) ?>);
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
