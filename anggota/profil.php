<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_anggota();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id_anggota = $_SESSION['id_anggota'];

$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = :id");
$stmt->execute([':id' => $id_anggota]);
$anggota = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean($_POST['nama'] ?? '');
    $no_hp = clean($_POST['no_hp'] ?? '');
    $alamat = clean($_POST['alamat'] ?? '');
    $pass_lama = $_POST['password_lama'] ?? '';
    $pass_baru = $_POST['password_baru'] ?? '';
    $pass_ulang = $_POST['password_ulang'] ?? '';

    if ($nama === '') {
        set_flash('error', 'Nama tidak boleh kosong.');
    } else {
        try {
            $foto_baru = upload_foto_profil($_FILES['foto'] ?? null);
            $foto_final = $anggota['foto'];
            $foto_lama = $anggota['foto'];
            if ($foto_baru) {
                $foto_final = $foto_baru;
            }

            if ($pass_baru !== '' || $pass_ulang !== '' || $pass_lama !== '') {
                if (!password_verify($pass_lama, $anggota['password'])) throw new Exception('Password lama yang kamu masukkan salah.');
                if (strlen($pass_baru) < 6) throw new Exception('Password baru minimal 6 karakter.');
                if ($pass_baru !== $pass_ulang) throw new Exception('Konfirmasi password baru tidak cocok.');
                $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE anggota SET nama=:nama, no_hp=:hp, alamat=:alamat, foto=:foto, password=:pass WHERE id_anggota=:id");
                $stmt->execute([':nama'=>$nama, ':hp'=>$no_hp, ':alamat'=>$alamat, ':foto'=>$foto_final, ':pass'=>$hash, ':id'=>$id_anggota]);
            } else {
                $stmt = $pdo->prepare("UPDATE anggota SET nama=:nama, no_hp=:hp, alamat=:alamat, foto=:foto WHERE id_anggota=:id");
                $stmt->execute([':nama'=>$nama, ':hp'=>$no_hp, ':alamat'=>$alamat, ':foto'=>$foto_final, ':id'=>$id_anggota]);
            }
            if ($foto_baru && $foto_lama && $foto_lama !== $foto_final) {
                hapus_foto_profil($foto_lama);
            }
            $_SESSION['nama'] = $nama;
            $_SESSION['foto'] = $foto_final;
            set_flash('sukses', 'Profil berhasil diperbarui.');
            redirect('/anggota/profil.php');
        } catch (Exception $e) {
            set_flash('error', $e->getMessage());
        }
    }
    $stmt = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = :id");
    $stmt->execute([':id' => $id_anggota]);
    $anggota = $stmt->fetch();
}

$page_title = 'Profil Saya';
$member_menu_aktif = 'profil';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
  .profile-hero { background: var(--surface); border:1px solid var(--border); }
  .profile-shell { max-width:980px; margin:0 auto; }
  .profile-card { background: var(--surface); border:1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-xs); }
  .profile-input { width:100%; border:1px solid var(--border); background: var(--surface); border-radius: var(--radius); padding:.75rem 1rem; color: var(--text); outline:none; transition: border-color var(--transition-fast), box-shadow var(--transition-fast), background var(--transition-fast); }
  .profile-input:focus { border-color: var(--accent); box-shadow:0 0 0 3px var(--accent-ring); background: var(--surface); }
  .profile-input:disabled { background: var(--surface-2); color: var(--text-faint); }
  .profile-input::placeholder { color: var(--text-faint-2); }
  .profile-avatar { width:104px; height:104px; border:3px solid var(--surface); box-shadow: var(--shadow); background: var(--surface-2); }
</style>

<div class="profile-shell space-y-5">
  <section class="profile-hero rounded-xl p-5 sm:p-6">
    <div class="flex flex-col sm:flex-row items-center sm:items-end gap-5">
      <img src="<?= e(foto_profil_url($anggota['foto'])) ?>" alt="Foto profil <?= e($anggota['nama']) ?>" class="profile-avatar rounded-xl object-cover">
      <div class="flex-1 text-center sm:text-left min-w-0">
        <div class="text-[11px] font-bold uppercase tracking-[.14em]" style="color: var(--text-faint-2)">Profil Anggota</div>
        <h1 class="mt-1 text-2xl sm:text-[26px] font-bold truncate font-display" style="color: var(--text)"><?= e($anggota['nama']) ?></h1>
        <p class="mt-1 text-sm" style="color: var(--text-muted)"><?= e($anggota['email']) ?></p>
        <div class="mt-3 flex flex-wrap justify-center sm:justify-start gap-2">
          <span class="rounded-full px-3 py-1.5 text-xs font-bold" style="background: var(--accent-soft); color: var(--accent-text); border:1px solid var(--border)"><i class="bi bi-person-vcard mr-1"></i><?= e($anggota['nomor_anggota']) ?></span>
          <span class="rounded-full px-3 py-1.5 text-xs font-bold" style="background: var(--accent-soft); color: var(--accent-text); border:1px solid var(--border)"><i class="bi bi-check-circle mr-1"></i><?= e(ucfirst($anggota['status'])) ?></span>
        </div>
      </div>
    </div>
  </section>

  <form method="post" action="" enctype="multipart/form-data" class="space-y-5">
      <?= csrf_field() ?>
    <section class="profile-card p-5 sm:p-7">
      <div class="flex items-center gap-3 mb-5">
        <span class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: var(--accent-soft); color: var(--accent-text); border:1px solid var(--border)"><i class="bi bi-person-lines-fill"></i></span>
        <div><h2 class="font-bold text-sm" style="color: var(--text)">Informasi Pribadi</h2><p class="text-xs" style="color: var(--text-faint)">Perbarui data yang ingin ditampilkan di akunmu.</p></div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-bold mb-1.5" style="color: var(--text-muted)">Nama Lengkap</label>
          <input type="text" name="nama" required value="<?= e($anggota['nama']) ?>" class="profile-input">
        </div>
        <div>
          <label class="block text-sm font-bold mb-1.5" style="color: var(--text-muted)">No. HP</label>
          <input type="text" name="no_hp" value="<?= e($anggota['no_hp']) ?>" class="profile-input" placeholder="Contoh: 08xxxxxxxxxx">
        </div>
        <div>
          <label class="block text-sm font-bold mb-1.5" style="color: var(--text-muted)">Email</label>
          <input type="text" value="<?= e($anggota['email']) ?>" class="profile-input" disabled>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-bold mb-1.5" style="color: var(--text-muted)">Alamat</label>
          <textarea name="alamat" rows="3" class="profile-input" placeholder="Tulis alamat kamu..."><?= e($anggota['alamat']) ?></textarea>
        </div>
        <div class="md:col-span-2 rounded-xl p-4" style="background: var(--accent-soft); border:1px solid var(--border)">
          <div class="flex items-center gap-3">
            <img src="<?= e(foto_profil_url($anggota['foto'])) ?>" class="w-14 h-14 rounded-xl object-cover border" style="border-color: var(--border); background: var(--surface)">
            <div class="flex-1 min-w-0"><div class="text-sm font-bold" style="color: var(--text)">Foto Profil</div><div class="text-xs" style="color: var(--text-faint)">JPG, PNG, atau WEBP · maksimal 2MB</div></div>
          </div>
          <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="mt-3 w-full text-sm" style="color: var(--text-muted)">
        </div>
      </div>
    </section>

    <section class="profile-card p-5 sm:p-7">
      <div class="flex items-center gap-3 mb-5">
        <span class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: var(--surface-2); color: var(--text-faint); border:1px solid var(--border)"><i class="bi bi-shield-lock"></i></span>
        <div><h2 class="font-bold text-sm" style="color: var(--text)">Keamanan Akun</h2><p class="text-xs" style="color: var(--text-faint)">Kosongkan jika tidak ingin mengganti password.</p></div>
      </div>
      <div class="space-y-4">
        <div><label class="block text-sm font-bold mb-1.5" style="color: var(--text-muted)">Password Lama</label><div class="relative"><input type="password" name="password_lama" class="profile-input pr-11"><button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2" style="color: var(--text-faint)"><i class="bi bi-eye"></i></button></div></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-sm font-bold mb-1.5" style="color: var(--text-muted)">Password Baru</label><div class="relative"><input type="password" name="password_baru" class="profile-input pr-11"><button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2" style="color: var(--text-faint)"><i class="bi bi-eye"></i></button></div></div>
          <div><label class="block text-sm font-bold mb-1.5" style="color: var(--text-muted)">Ulangi Password Baru</label><div class="relative"><input type="password" name="password_ulang" class="profile-input pr-11"><button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2" style="color: var(--text-faint)"><i class="bi bi-eye"></i></button></div></div>
        </div>
      </div>
    </section>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pb-2">
      <a href="<?= BASE_URL ?>/anggota/dashboard.php" class="inline-flex items-center justify-center gap-2 rounded-lg border px-5 py-2.5 text-sm font-semibold hover:bg-slate-50" style="background: var(--surface); border-color: var(--border); color: var(--text-muted)">Batal</a>
      <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-bold text-white" style="background: var(--accent)"><i class="bi bi-check2-circle"></i>Simpan Perubahan</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
