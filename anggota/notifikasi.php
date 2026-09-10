<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id_anggota = (int)$_SESSION['id_anggota'];
sinkronkan_notifikasi_anggota($pdo, $id_anggota);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['tandai_semua'])) {
        $stmt = $pdo->prepare("UPDATE notifikasi SET dibaca = 1 WHERE id_anggota = :id");
        $stmt->execute([':id' => $id_anggota]);
        set_flash('sukses', 'Semua notifikasi sudah ditandai sebagai dibaca.');
        redirect('/anggota/notifikasi.php');
    }
    $id = (int)($_POST['id_notifikasi'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT link FROM notifikasi WHERE id_notifikasi = :notif AND id_anggota = :id");
        $stmt->execute([':notif' => $id, ':id' => $id_anggota]);
        $link = $stmt->fetchColumn() ?: '/anggota/notifikasi.php';
        $stmt = $pdo->prepare("UPDATE notifikasi SET dibaca = 1 WHERE id_notifikasi = :notif AND id_anggota = :id");
        $stmt->execute([':notif' => $id, ':id' => $id_anggota]);
        if (strpos($link, '/') !== 0 || strpos($link, '//') === 0) $link = '/anggota/notifikasi.php';
        redirect($link);
    }
}

$stmt = $pdo->prepare("SELECT * FROM notifikasi WHERE id_anggota = :id ORDER BY created_at DESC, id_notifikasi DESC");
$stmt->execute([':id' => $id_anggota]);
$notifikasi = $stmt->fetchAll();

$page_title = 'Notifikasi';
$member_menu_aktif = 'notifikasi';
require_once __DIR__ . '/../includes/header.php';
$warna = [
    'info' => ['bg'=>'bg-blue-50','text'=>'text-blue-600','icon'=>'bi-info-circle'],
    'success' => ['bg'=>'bg-emerald-50','text'=>'text-emerald-600','icon'=>'bi-check-circle'],
    'warning' => ['bg'=>'bg-amber-50','text'=>'text-amber-600','icon'=>'bi-exclamation-triangle'],
    'danger' => ['bg'=>'bg-red-50','text'=>'text-red-600','icon'=>'bi-exclamation-circle'],
];
?>

<div class="max-w-3xl mx-auto">
  <div class="flex items-end justify-between gap-3 mb-6">
    <div>
      <p class="text-sm font-semibold mb-1" style="color: var(--accent)">Pusat Informasi</p>
      <h1 class="text-2xl font-bold tracking-tight" style="color: var(--text)">Notifikasi</h1>
      <p class="text-sm mt-1" style="color: var(--text-faint)">Pengingat jatuh tempo, status perpanjangan, dan informasi penting akunmu.</p>
    </div>
    <?php if (!empty($notifikasi)): ?>
      <form method="post">
      <?= csrf_field() ?><button name="tandai_semua" value="1" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Tandai semua dibaca</button></form>
    <?php endif; ?>
  </div>

  <?php if (empty($notifikasi)): ?>
    <div class="border rounded-2xl p-10 text-center" style="background: var(--surface); border-color: var(--border)">
      <div class="w-14 h-14 mx-auto rounded-xl flex items-center justify-center" style="background: var(--surface-2); color: var(--text-faint); border:1px solid var(--border)"><i class="bi bi-bell-slash text-2xl"></i></div>
      <h2 class="mt-4 font-bold" style="color: var(--text)">Belum ada notifikasi</h2>
      <p class="mt-1 text-sm" style="color: var(--text-faint)">Kami akan memberi tahu kamu jika ada pengingat atau perubahan status.</p>
    </div>
  <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($notifikasi as $n): $v = $warna[$n['tipe']] ?? $warna['info']; ?>
        <div class="border rounded-2xl p-4 sm:p-5" style="background: var(--surface); border-color: <?= $n['dibaca'] ? 'var(--border)' : 'var(--accent-soft-2)' ?>; <?= !$n['dibaca'] ? 'box-shadow: 0 0 0 1px var(--accent-ring);' : '' ?>">
          <div class="flex gap-4">
            <div class="w-11 h-11 shrink-0 rounded-xl <?= $v['bg'] ?> <?= $v['text'] ?> flex items-center justify-center"><i class="bi <?= $v['icon'] ?> text-lg"></i></div>
            <div class="min-w-0 flex-1">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h2 class="font-bold" style="color: var(--text)"><?= e($n['judul']) ?></h2>
                  <p class="text-sm leading-6 mt-1" style="color: var(--text-muted)"><?= e($n['pesan']) ?></p>
                </div>
                <?php if (!$n['dibaca']): ?><span class="w-2.5 h-2.5 shrink-0 rounded-full bg-brand-600 mt-2" title="Belum dibaca"></span><?php endif; ?>
              </div>
              <div class="mt-3 flex items-center gap-3 text-xs" style="color: var(--text-faint)">
                <span><?= format_tanggal(date('Y-m-d', strtotime($n['created_at']))) ?></span>
                <?php if ($n['link']): ?>
                  <form method="post" class="inline">
      <?= csrf_field() ?><input type="hidden" name="id_notifikasi" value="<?= (int)$n['id_notifikasi'] ?>"><button class="font-semibold text-brand-600 hover:text-brand-700">Lihat detail →</button></form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
