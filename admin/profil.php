<?php
require_once __DIR__ . '/../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id_admin = $_SESSION['id_admin'];

$stmt = $pdo->prepare("SELECT * FROM admin WHERE id_admin = :id");
$stmt->execute([':id' => $id_admin]);
$admin = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = clean($_POST['nama'] ?? '');
    $pass_lama = $_POST['password_lama'] ?? '';
    $pass_baru = $_POST['password_baru'] ?? '';
    $pass_ulang = $_POST['password_ulang'] ?? '';

    if ($nama === '') {
        set_flash('error', 'Nama tidak boleh kosong.');
    } else {
        try {
            $foto_baru = upload_foto_profil($_FILES['foto'] ?? null);
            $foto_final = $admin['foto'];
            $foto_lama = $admin['foto'];
            if ($foto_baru) {
                $foto_final = $foto_baru;
            }

            if ($pass_baru !== '' || $pass_ulang !== '' || $pass_lama !== '') {
                if (!password_verify($pass_lama, $admin['password'])) throw new Exception('Password lama yang kamu masukkan salah.');
                if (strlen($pass_baru) < 6) throw new Exception('Password baru minimal 6 karakter.');
                if ($pass_baru !== $pass_ulang) throw new Exception('Konfirmasi password baru tidak cocok.');
                $hash = password_hash($pass_baru, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin SET nama=:nama, foto=:foto, password=:pass WHERE id_admin=:id");
                $stmt->execute([':nama'=>$nama, ':foto'=>$foto_final, ':pass'=>$hash, ':id'=>$id_admin]);
            } else {
                $stmt = $pdo->prepare("UPDATE admin SET nama=:nama, foto=:foto WHERE id_admin=:id");
                $stmt->execute([':nama'=>$nama, ':foto'=>$foto_final, ':id'=>$id_admin]);
            }
            if ($foto_baru && $foto_lama && $foto_lama !== $foto_final) {
                hapus_foto_profil($foto_lama);
            }
            $_SESSION['nama'] = $nama;
            $_SESSION['foto'] = $foto_final;
            set_flash('sukses', 'Profil berhasil diperbarui.');
            redirect('/admin/profil.php');
        } catch (Exception $e) {
            set_flash('error', $e->getMessage());
        }
    }
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id_admin = :id");
    $stmt->execute([':id' => $id_admin]);
    $admin = $stmt->fetch();
}

$menu_aktif = 'profil';
$page_title = 'Profil Admin';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/admin_menu.php';
?>

<style>
  .profile-hero { background:linear-gradient(135deg,var(--theme-gradient-from),var(--theme-gradient-to)); }
  .profile-shell { max-width:980px; margin:0 auto; }
  .profile-card { background:#fff; border:1px solid #e7edf3; border-radius:1.5rem; box-shadow:0 12px 34px rgba(15,23,42,.06); }
  .profile-input { width:100%; border:1px solid #dbe3ec; background:#fff; border-radius:.9rem; padding:.75rem 1rem; color:#1e293b; outline:none; }
  .profile-input:focus { border-color:var(--theme-primary); box-shadow:0 0 0 3px var(--theme-ring); }
  .profile-avatar { width:104px; height:104px; border:4px solid rgba(255,255,255,.85); box-shadow:0 12px 26px rgba(15,23,42,.16); }
</style>

<div class="profile-shell space-y-5">
  <section class="profile-hero rounded-[1.7rem] p-5 sm:p-7 text-white shadow-lg shadow-brand-600/15">
    <div class="flex flex-col sm:flex-row items-center sm:items-end gap-5">
      <img src="<?= e(foto_profil_url($admin['foto'])) ?>" alt="Foto profil <?= e($admin['nama']) ?>" class="profile-avatar rounded-[1.6rem] object-cover bg-white/20">
      <div class="flex-1 text-center sm:text-left min-w-0">
        <div class="text-xs font-bold uppercase tracking-[.16em] text-white/70">Profil Pengelola</div>
        <h1 class="mt-1 text-2xl sm:text-3xl font-extrabold truncate"><?= e($admin['nama']) ?></h1>
        <p class="mt-1 text-sm text-white/80">@<?= e($admin['username']) ?></p>
        <div class="mt-3 flex flex-wrap justify-center sm:justify-start gap-2">
          <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold backdrop-blur-sm"><i class="bi bi-shield-check mr-1"></i>Administrator</span>
          <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-bold backdrop-blur-sm"><i class="bi bi-person-check mr-1"></i>Akun Aktif</span>
        </div>
      </div>
    </div>
  </section>

  <form method="post" action="" enctype="multipart/form-data" class="space-y-5">
      <?= csrf_field() ?>
    <section class="profile-card p-5 sm:p-7">
      <div class="flex items-center gap-3 mb-5">
        <span class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:var(--theme-soft);color:var(--theme-primary)"><i class="bi bi-person-badge"></i></span>
        <div><h2 class="font-extrabold text-slate-900">Informasi Pengelola</h2><p class="text-xs text-slate-500">Kelola identitas yang digunakan di panel administrasi.</p></div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-1.5">Nama Lengkap</label>
          <input type="text" name="nama" required value="<?= e($admin['nama']) ?>" class="profile-input">
        </div>
        <div>
          <label class="block text-sm font-bold text-slate-700 mb-1.5">Username</label>
          <input type="text" value="<?= e($admin['username']) ?>" class="profile-input bg-slate-50 text-slate-400" disabled>
        </div>
        <div class="md:col-span-2 rounded-2xl p-4" style="background:var(--theme-soft);border:1px solid var(--theme-soft-2)">
          <div class="flex items-center gap-3">
            <img src="<?= e(foto_profil_url($admin['foto'])) ?>" class="w-14 h-14 rounded-2xl object-cover bg-white border border-white shadow-sm">
            <div class="flex-1 min-w-0"><div class="text-sm font-extrabold text-slate-800">Foto Profil</div><div class="text-xs text-slate-500">JPG, PNG, atau WEBP · maksimal 2MB</div></div>
          </div>
          <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="mt-3 w-full text-sm text-slate-600">
        </div>
      </div>
    </section>

    <section class="profile-card p-5 sm:p-7">
      <div class="flex items-center gap-3 mb-5">
        <span class="w-10 h-10 rounded-xl flex items-center justify-center bg-slate-100 text-slate-600"><i class="bi bi-shield-lock"></i></span>
        <div><h2 class="font-extrabold text-slate-900">Keamanan Akun</h2><p class="text-xs text-slate-500">Kosongkan jika tidak ingin mengganti password.</p></div>
      </div>
      <div class="space-y-4">
        <div><label class="block text-sm font-bold text-slate-700 mb-1.5">Password Lama</label><div class="relative"><input type="password" name="password_lama" class="profile-input pr-11"><button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"><i class="bi bi-eye"></i></button></div></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><label class="block text-sm font-bold text-slate-700 mb-1.5">Password Baru</label><div class="relative"><input type="password" name="password_baru" class="profile-input pr-11"><button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"><i class="bi bi-eye"></i></button></div></div>
          <div><label class="block text-sm font-bold text-slate-700 mb-1.5">Ulangi Password Baru</label><div class="relative"><input type="password" name="password_ulang" class="profile-input pr-11"><button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"><i class="bi bi-eye"></i></button></div></div>
        </div>
      </div>
    </section>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pb-2">
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50">Batal</a>
      <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-extrabold text-white shadow-lg transition hover:-translate-y-0.5" style="background:var(--theme-primary);box-shadow:0 10px 22px color-mix(in srgb,var(--theme-primary) 20%,transparent)"><i class="bi bi-check2-circle"></i>Simpan Perubahan</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
