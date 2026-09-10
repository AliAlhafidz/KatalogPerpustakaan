<?php
// Variabel yang diharapkan tersedia: $input (array data form), $errors (array),
// dan opsional $anggota (untuk mode edit, berisi data lama termasuk nomor_anggota).
$mode = isset($anggota) ? 'edit' : 'tambah';
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

<div class="w-full max-w-2xl">
  <div class="flex items-center gap-3 mb-4">
    <a href="<?= BASE_URL ?>/admin/anggota/index.php" class="w-8 h-8 rounded-lg border bg-white flex items-center justify-center hover:bg-slate-50" style="border-color:var(--border); color:var(--text-faint)"><i class="bi bi-arrow-left text-sm"></i></a>
    <div>
      <p class="text-[11px] font-bold tracking-[.14em] uppercase" style="color:var(--text-faint-2)"><?= $mode==='edit' ? 'Manajemen anggota' : 'Tambah data' ?></p>
      <h1 class="font-display text-[20px] font-bold tracking-tight" style="color:var(--text)">
        <?= $mode === 'edit' ? 'Ubah Anggota' : 'Tambah Anggota' ?>
      </h1>
    </div>
  </div>

  <?php if ($mode==='edit' && !empty($anggota)): 
    $initials = initials_from_name($anggota['nama'] ?? $input['nama'] ?? '');
    $hasFoto = !empty($anggota['foto']);
  ?>
    <div class="bg-white rounded-lg border p-4 sm:p-5 mb-4 flex gap-4 items-center" style="border-color:var(--border)">
      <?php if ($hasFoto): ?>
        <img src="<?= e(foto_profil_url($anggota['foto'])) ?>" alt="" class="w-14 h-14 rounded-full object-cover border" style="border-color:var(--border)">
      <?php else: ?>
        <span class="w-14 h-14 rounded-full flex items-center justify-center text-[15px] font-bold shrink-0" style="background:var(--accent-soft); color:var(--accent-text); border:1px solid var(--accent-soft-2)"><?= e($initials) ?></span>
      <?php endif; ?>
      <div class="min-w-0 flex-1">
        <p class="text-sm font-bold truncate" style="color:var(--text)"><?= e($anggota['nama']) ?></p>
        <p class="text-xs truncate" style="color:var(--text-faint)"><?= e($anggota['email']) ?></p>
        <div class="flex items-center gap-2 mt-1.5">
          <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold" style="background:var(--surface-2); border-color:var(--border); color:var(--text-muted)"><?= e($anggota['nomor_anggota']) ?></span>
          <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold border <?= ($anggota['status']??'aktif')==='aktif' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-red-50 text-red-700 border-red-200' ?>" style="<?= ($anggota['status']??'aktif')==='aktif' ? 'background:#ecfdf5; color:#065f46; border-color:#a7f3d0' : 'background:#fef2f2; color:#991b1b; border-color:#fecaca' ?>"><?= e(ucfirst($anggota['status']??'aktif')) ?></span>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm">
      <ul class="list-disc list-inside"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" action="" class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4 sm:p-6 space-y-4">
      <?= csrf_field() ?>
    <?php if ($mode === 'edit'): ?>
      <input type="hidden" name="id" value="<?= (int) $anggota['id_anggota'] ?>">
    <?php endif; ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
      <input type="text" name="nama" required maxlength="150" value="<?= e($input['nama']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
      <input type="email" name="email" required maxlength="150" value="<?= e($input['email']) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>

    <?php if ($mode === 'tambah'): ?>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password Awal *</label>
        <div class="relative">
          <input type="password" name="password" required
                 class="w-full border border-gray-300 rounded-lg pl-4 pr-10 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
          <button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"><i class="bi bi-eye"></i></button>
        </div>
        <p class="text-xs text-gray-400 mt-1">Minimal 6 karakter. Anggota dapat menggantinya nanti di halaman profil.</p>
      </div>
    <?php endif; ?>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">No. HP</label>
      <input type="text" name="no_hp" value="<?= e($input['no_hp']) ?>" maxlength="20" inputmode="numeric" pattern="08[0-9]{8,11}" placeholder="08xxxxxxxxxx" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
      <p class="text-xs text-gray-400 mt-1">Opsional. Format 08xxxxxxxxxx (10-13 digit).</p>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
      <textarea name="alamat" rows="2" maxlength="255" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500"><?= e($input['alamat']) ?></textarea>
    </div>

    <?php if ($mode === 'edit'): ?>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status Keanggotaan</label>
        <select name="status" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
          <option value="aktif" <?= $input['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="nonaktif" <?= $input['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Reset Password (opsional)</label>
        <div class="relative">
          <input type="password" name="password_baru" placeholder="Kosongkan jika tidak ingin mengubah"
                 class="w-full border border-gray-300 rounded-lg pl-4 pr-10 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
          <button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"><i class="bi bi-eye"></i></button>
        </div>
      </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row gap-3 pt-2">
      <button type="submit" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white font-medium px-6 py-2.5 rounded-xl transition"><?= $mode === 'edit' ? 'Simpan Perubahan' : 'Simpan Anggota' ?></button>
      <a href="<?= BASE_URL ?>/admin/anggota/index.php" class="w-full sm:w-auto border border-gray-300 hover:bg-gray-50 px-6 py-2.5 rounded-xl transition text-gray-600 text-center">Batal</a>
    </div>
  </form>
</div>
