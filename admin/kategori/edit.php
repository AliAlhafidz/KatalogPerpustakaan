<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_kategori = :id");
$stmt->execute([':id' => $id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    set_flash('error', 'Kategori tidak ditemukan.');
    redirect('/admin/kategori/index.php');
}

$errors = [];
$nama = $kategori['nama_kategori'];
$keterangan = $kategori['keterangan'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean($_POST['nama_kategori'] ?? '');
    $keterangan = clean($_POST['keterangan'] ?? '');
    if ($nama === '') {
        $errors[] = 'Nama kategori wajib diisi.';
    } else {
        // Cek duplikat, izinkan nama milik sendiri
        $cek = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE nama_kategori = :nama AND id_kategori != :id");
        $cek->execute([':nama' => $nama, ':id' => $id]);
        if ((int)$cek->fetchColumn() > 0) {
            $errors[] = 'Nama kategori tersebut sudah dipakai kategori lain.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = :nama, keterangan = :ket WHERE id_kategori = :id");
                $stmt->execute([':nama' => $nama, ':ket' => $keterangan ?: null, ':id' => $id]);
                $diff = [];
                if ((string)($kategori['nama_kategori'] ?? '') !== (string)$nama) $diff['nama'] = ['dari' => $kategori['nama_kategori'], 'ke' => $nama];
                if ((string)($kategori['keterangan'] ?? '') !== (string)$keterangan) $diff['keterangan'] = ['dari' => $kategori['keterangan'], 'ke' => $keterangan];
                catat_audit($pdo, $_SESSION['id_admin'] ?? null, 'edit', 'kategori', $id, !empty($diff) ? $diff : ['nama' => $nama]);
                set_flash('sukses', 'Kategori berhasil diperbarui.');
                redirect('/admin/kategori/index.php');
            } catch (Throwable $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 23000) {
                    $errors[] = 'Nama kategori tersebut sudah dipakai kategori lain (percobaan bersamaan).';
                } else {
                    $errors[] = 'Gagal memperbarui kategori: ' . $e->getMessage();
                }
                error_log('Edit kategori gagal: ' . $e->getMessage());
            }
        }
    }
}

$menu_aktif = 'kategori';
$page_title = 'Ubah Kategori';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="max-w-lg">
  <div class="flex items-center gap-3 mb-6">
    <a href="<?= BASE_URL ?>/admin/kategori/index.php" class="text-gray-400 hover:text-gray-600">&larr;</a>
    <h1 class="text-xl font-bold text-gray-800">Ubah Kategori</h1>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
      <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
      <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kategori</label>
      <input type="text" name="nama_kategori" required value="<?= e($nama) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
      <textarea name="keterangan" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500"><?= e($keterangan) ?></textarea>
    </div>
    <div class="flex gap-3">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-medium px-6 py-2.5 rounded-lg transition">Simpan Perubahan</button>
      <a href="<?= BASE_URL ?>/admin/kategori/index.php" class="border border-gray-300 hover:bg-gray-50 px-6 py-2.5 rounded-lg transition text-gray-600">Batal</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
