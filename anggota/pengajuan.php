<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id_anggota = (int)$_SESSION['id_anggota'];
$errors = [];
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = clean($_POST['judul'] ?? '');
    $penulis = clean($_POST['penulis'] ?? '');
    $isbn = clean($_POST['isbn'] ?? '');
    $alasan = clean($_POST['alasan'] ?? '');

    if ($judul === '') $errors[] = 'Judul buku wajib diisi.';
    elseif (mb_strlen($judul) > 200) $errors[] = 'Judul terlalu panjang (maks 200).';
    if ($penulis === '') $errors[] = 'Penulis wajib diisi.';
    elseif (mb_strlen($penulis) > 150) $errors[] = 'Penulis terlalu panjang (maks 150).';
    if ($isbn !== '') {
        $isbn_test = preg_replace('/[^0-9Xx]/', '', $isbn);
        if (!in_array(strlen($isbn_test), [10,13], true)) $errors[] = 'ISBN harus 10 atau 13 digit.';
        else $isbn = $isbn_test;
        if (mb_strlen($isbn) > 30) $errors[] = 'ISBN terlalu panjang.';
    } else {
        $isbn = null;
    }
    if ($alasan === '') $errors[] = 'Alasan pengajuan wajib diisi.';
    elseif (mb_strlen($alasan) > 500) $errors[] = 'Alasan terlalu panjang (maks 500).';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pengajuan_buku (anggota_id, judul, penulis, isbn, alasan, status) VALUES (:aid, :judul, :penulis, :isbn, :alasan, 'menunggu')");
            $stmt->execute([':aid' => $id_anggota, ':judul' => $judul, ':penulis' => $penulis, ':isbn' => $isbn, ':alasan' => $alasan]);
            set_flash('sukses', 'Pengajuan buku berhasil dikirim. Menunggu persetujuan admin.');
            redirect('/anggota/pengajuan.php');
        } catch (Throwable $e) {
            // Jika tabel belum ada (migrasi belum dijalankan)
            if (strpos($e->getMessage(), 'pengajuan_buku') !== false) {
                $errors[] = 'Fitur pengajuan belum siap (tabel belum ada). Hubungi admin untuk migrasi.';
            } else {
                $errors[] = 'Gagal mengirim pengajuan: ' . $e->getMessage();
            }
            error_log('Pengajuan buku error: ' . $e->getMessage());
        }
    }
}

// List pengajuan milik anggota ini (pagination clamp reuse)
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 10;
$offset = ($halaman - 1) * $per_halaman;
$total = 0;
$daftar = [];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengajuan_buku WHERE anggota_id = :id");
    $stmt->execute([':id' => $id_anggota]);
    $total = (int)$stmt->fetchColumn();
    $total_halaman = max(1, (int)ceil($total / $per_halaman));
    if ($halaman > $total_halaman) {
        $halaman = $total_halaman;
        $offset = ($halaman - 1) * $per_halaman;
    }
    $stmt = $pdo->prepare("SELECT * FROM pengajuan_buku WHERE anggota_id = :id ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':id', $id_anggota, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $daftar = $stmt->fetchAll();
    $total_halaman = max(1, (int)ceil($total / $per_halaman));
} catch (Throwable $e) {
    $daftar = [];
    $total_halaman = 1;
    if (strpos($e->getMessage(), 'pengajuan_buku') === false) {
        error_log('List pengajuan error: ' . $e->getMessage());
    }
}

$page_title = 'Pengajuan Buku';
$member_menu_aktif = 'pengajuan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
  <div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight" style="color: var(--text)">Pengajuan Buku Baru</h1>
    <p class="text-sm mt-1" style="color: var(--text-faint)">Usulkan buku yang ingin ada di perpustakaan. Admin akan meninjau.</p>
  </div>

  <div class="rounded-2xl border p-5 sm:p-6 mb-6" style="background: var(--surface); border-color: var(--border)">
    <h2 class="font-bold mb-3 flex items-center gap-2" style="color: var(--text)"><i class="bi bi-plus-circle" style="color: var(--accent)"></i> Form Pengajuan</h2>
    <?php if (!empty($errors)): ?>
      <div class="mb-4 rounded-lg border px-4 py-3 text-sm" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)">
        <ul class="list-disc list-inside"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endif; ?>
    <form method="post" action="" class="space-y-4">
        <?= csrf_field() ?>
      <div>
        <label class="block text-sm font-medium mb-1" style="color: var(--text-muted)">Judul Buku *</label>
        <input type="text" name="judul" required maxlength="200" class="w-full rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500" style="border:1px solid var(--border); background: var(--surface); color: var(--text)">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" style="color: var(--text-muted)">Penulis *</label>
        <input type="text" name="penulis" required maxlength="150" class="w-full rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500" style="border:1px solid var(--border); background: var(--surface); color: var(--text)">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" style="color: var(--text-muted)">ISBN (opsional)</label>
        <input type="text" name="isbn" maxlength="30" placeholder="10 atau 13 digit" class="w-full rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500" style="border:1px solid var(--border); background: var(--surface); color: var(--text)">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" style="color: var(--text-muted)">Alasan Pengajuan *</label>
        <textarea name="alasan" required maxlength="500" rows="3" placeholder="Kenapa buku ini penting untuk perpustakaan?" class="w-full rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500" style="border:1px solid var(--border); background: var(--surface); color: var(--text)"></textarea>
        <p class="text-xs mt-1" style="color: var(--text-faint)">Maks 500 karakter.</p>
      </div>
      <button type="submit" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white font-bold px-6 py-2.5 rounded-lg">Kirim Pengajuan</button>
    </form>
  </div>

  <div class="rounded-2xl border p-5" style="background: var(--surface); border-color: var(--border)">
    <h2 class="font-bold mb-3" style="color: var(--text)">Riwayat Pengajuan Saya</h2>
    <?php if (empty($daftar)): ?>
      <p class="text-sm" style="color: var(--text-faint)">Belum ada pengajuan.</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php foreach ($daftar as $r): ?>
          <div class="rounded-xl border p-4" style="<?= $r['status']==='menunggu' ? 'background: var(--badge-amber-bg); border-color: var(--badge-amber-border)' : ($r['status']==='disetujui' ? 'background: var(--badge-emerald-bg); border-color: var(--badge-emerald-border)' : 'background: var(--badge-red-bg); border-color: var(--badge-red-border)') ?>">
            <div class="flex flex-wrap items-center gap-2 mb-1">
              <h3 class="font-bold" style="color: var(--text)"><?= e($r['judul']) ?></h3>
              <?php if ($r['status']==='menunggu'): ?><span class="text-xs font-bold rounded-full bg-amber-100 text-amber-700 px-2.5 py-1">Menunggu</span>
              <?php elseif ($r['status']==='disetujui'): ?><span class="text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1">Disetujui</span>
              <?php else: ?><span class="text-xs font-bold rounded-full bg-red-100 text-red-700 px-2.5 py-1">Ditolak</span><?php endif; ?>
            </div>
            <p class="text-sm" style="color: var(--text-muted)">Penulis: <?= e($r['penulis']) ?> <?= $r['isbn'] ? '· ISBN: '.e($r['isbn']) : '' ?></p>
            <p class="text-sm mt-2 rounded-lg border px-3 py-2" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">Alasan: “<?= e($r['alasan']) ?>”</p>
            <?php if (!empty($r['catatan_admin'])): ?><p class="text-sm mt-2" style="color: var(--text-muted)">Catatan admin: <span class="border rounded-lg px-2 py-1 text-xs" style="background: var(--surface-2); border-color: var(--border); color: var(--text-faint)"><?= e($r['catatan_admin']) ?></span></p><?php endif; ?>
            <p class="text-xs mt-2" style="color: var(--text-faint)"><?= e($r['created_at']) ?> · updated <?= e($r['updated_at']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($total_halaman > 1): ?>
        <div class="flex justify-center items-center gap-1 mt-6">
          <?php for ($i=1; $i<=$total_halaman; $i++): ?>
            <a href="?halaman=<?= $i ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition <?= $i===$halaman ? 'bg-brand-600 text-white' : 'border text-slate-600 hover:bg-slate-50' ?>" style="<?= $i===$halaman ? '' : 'background: var(--surface); border-color: var(--border); color: var(--text-muted)' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
