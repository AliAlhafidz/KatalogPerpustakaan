<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

$id_anggota = $_SESSION['id_anggota'];

$stmt = $pdo->prepare("SELECT b.*, k.nama_kategori
                        FROM favorit f
                        JOIN buku b ON b.id_buku = f.id_buku
                        LEFT JOIN kategori k ON k.id_kategori = b.id_kategori
                        WHERE f.id_anggota = :id
                        ORDER BY f.created_at DESC");
$stmt->execute([':id' => $id_anggota]);
$daftar = $stmt->fetchAll();

$page_title = 'Buku Favorit';
$member_menu_aktif = 'favorit';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="text-xl font-bold mb-1" style="color: var(--text)">Buku Favorit Saya</h1>
<p class="text-sm mb-6" style="color: var(--text-faint)">Kumpulan buku yang kamu tandai sebagai favorit.</p>

<?php if (empty($daftar)): ?>
  <div class="rounded-xl border p-10 text-center" style="background: var(--surface); border-color: var(--border); color: var(--text-faint)">
    Belum ada buku favorit. Jelajahi katalog dan tekan ikon <i class="bi bi-heart-fill text-red-500"></i> pada buku yang kamu suka.
    <div class="mt-3"><a href="<?= BASE_URL ?>/index.php" class="font-medium hover:underline" style="color: var(--accent)">Lihat Katalog Buku</a></div>
  </div>
<?php else: ?>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php foreach ($daftar as $buku): ?>
      <div class="rounded-xl border overflow-hidden group relative" style="background: var(--surface); border-color: var(--border)">
        <a href="<?= BASE_URL ?>/detail.php?id=<?= (int)$buku['id_buku'] ?>">
          <div class="aspect-[3/4] overflow-hidden" style="background: var(--surface-2)">
            <img src="<?= e(cover_url($buku['cover'])) ?>" alt="Cover <?= e($buku['judul']) ?>"
                 class="w-full h-full object-cover group-hover:opacity-95 transition duration-200">
          </div>
          <div class="p-3">
            <p class="text-[11px] font-bold uppercase tracking-wide mb-1" style="color: var(--text-faint)"><?= e($buku['nama_kategori'] ?? 'Tanpa Kategori') ?></p>
            <h3 class="font-semibold text-sm line-clamp-2 mb-1" style="color: var(--text)"><?= e($buku['judul']) ?></h3>
            <p class="text-xs mb-2" style="color: var(--text-faint)"><?= e($buku['penulis']) ?></p>
            <?= badge_ketersediaan((int)$buku['tersedia'], !empty($buku['is_arsip'])) ?>
            <?php if (!empty($buku['is_arsip'])): ?><div class="mt-2 text-[11px] rounded-full px-2 py-1 text-center" style="background: var(--badge-amber-bg); color: var(--badge-amber-text); border:1px solid var(--badge-amber-border)">Diarsipkan</div><?php endif; ?>
          </div>
        </a>
        <form method="post" action="<?= BASE_URL ?>/anggota/toggle_favorit.php" class="absolute top-2 right-2">
      <?= csrf_field() ?>
          <input type="hidden" name="id_buku" value="<?= (int)$buku['id_buku'] ?>">
          <input type="hidden" name="redirect" value="favorit">
          <button type="submit" title="Hapus dari favorit"
                  class="w-8 h-8 flex items-center justify-center rounded-full border shadow-sm hover:bg-red-50 transition" style="background: var(--surface); border-color: var(--border); color:#ef4444"><i class="bi bi-heart-fill"></i></button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
