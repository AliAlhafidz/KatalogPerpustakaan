<?php
require_once __DIR__ . '/config/bootstrap.php';

// Jika sudah login, arahkan ke dashboard masing-masing
if (is_admin()) redirect('/admin/dashboard.php');
if (is_anggota()) redirect('/anggota/dashboard.php');

$errors = [];
$input = ['nama' => '', 'email' => '', 'no_hp' => '', 'alamat' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $rl_key_reg = rate_limit_key('register');
    $rl_reg = rate_limit_check($rl_key_reg, 5, 900);
    if (!$rl_reg['allowed']) {
        $errors[] = 'Terlalu banyak percobaan pendaftaran. Coba lagi dalam ' . rate_limit_format_retry($rl_reg['retry_after']) . '.';
    } else {
        $input['nama']   = clean($_POST['nama'] ?? '');
        $input['email']  = clean($_POST['email'] ?? '');
        $input['no_hp']  = clean($_POST['no_hp'] ?? '');
        $input['alamat'] = clean($_POST['alamat'] ?? '');
        $password        = $_POST['password'] ?? '';
        $password_ulang   = $_POST['password_ulang'] ?? '';

        if ($input['nama'] === '') $errors[] = 'Nama lengkap wajib diisi.';
        elseif (mb_strlen($input['nama']) > 150) $errors[] = 'Nama terlalu panjang (maks 150 karakter).';
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
        elseif (mb_strlen($input['email']) > 150) $errors[] = 'Email terlalu panjang (maks 150 karakter).';
        if ($input['no_hp'] !== '' && !preg_match('/^08[0-9]{8,11}$/', $input['no_hp'])) $errors[] = 'Format No. HP tidak valid. Gunakan 08xxxxxxxxxx (10-13 digit, angka saja).';
        elseif (mb_strlen($input['no_hp']) > 20) $errors[] = 'No. HP terlalu panjang (maks 20 karakter).';
        if (mb_strlen($input['alamat']) > 255) $errors[] = 'Alamat terlalu panjang (maks 255 karakter).';
        if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
        if ($password !== $password_ulang) $errors[] = 'Konfirmasi password tidak cocok.';

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE email = :email");
            $stmt->execute([':email' => $input['email']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'Email ini sudah terdaftar. Silakan masuk atau gunakan email lain.';
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                $nomor_anggota = generate_nomor_anggota($pdo);
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO anggota (nomor_anggota, nama, email, password, no_hp, alamat, status)
                                        VALUES (:no, :nama, :email, :pass, :hp, :alamat, 'aktif')");
                $stmt->execute([
                    ':no' => $nomor_anggota, ':nama' => $input['nama'], ':email' => $input['email'],
                    ':pass' => $hash, ':hp' => $input['no_hp'] ?: null, ':alamat' => $input['alamat'] ?: null,
                ]);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                // Duplikat email race (UNIQUE) atau nomor anggota race
                if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 23000) {
                    $errors[] = 'Email ini sudah terdaftar (percobaan bersamaan). Silakan gunakan email lain.';
                } else {
                    $errors[] = 'Pendaftaran gagal. Silakan coba lagi.';
                }
                error_log('Register gagal: ' . $e->getMessage());
            }
        }

        if (empty($errors)) {
            session_regenerate_id(true);
            csrf_regenerate();
            rate_limit_reset($rl_key_reg);
            // Langsung masuk (auto-login) setelah registrasi berhasil
            $id_anggota_baru = (int) $pdo->lastInsertId();
            $_SESSION['role']       = 'anggota';
            $_SESSION['id_anggota'] = $id_anggota_baru;
            $_SESSION['nama']       = $input['nama'];
            $_SESSION['foto']       = null;

            set_flash('sukses', "Pendaftaran berhasil! Nomor anggota kamu: $nomor_anggota. Selamat datang, {$input['nama']}!");
            redirect('/anggota/dashboard.php');
        } else {
            rate_limit_hit($rl_key_reg, 900);
        }
    }
}

$page_title = 'Daftar Anggota';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-xl border p-6 sm:p-7 my-auto" style="border-color: var(--border)">
  <p class="text-[11px] font-bold tracking-widest uppercase mb-1" style="color: var(--text-faint-2)">Pendaftaran</p>
  <h1 class="font-display text-[20px] font-bold mb-1" style="color: var(--text)">Daftar Sebagai Anggota</h1>
  <p class="text-sm mb-5" style="color: var(--text-faint)">Buat akun untuk melihat peminjaman dan menyimpan favorit.</p>

  <?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg border px-3.5 py-3 text-sm" style="background:#fef2f2; border-color:#fecaca; color:#991b1b">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="" class="space-y-3.5">
      <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-semibold mb-1" style="color: var(--text-muted)">Nama Lengkap *</label>
      <input type="text" name="nama" required maxlength="150" value="<?= e($input['nama']) ?>"
             class="w-full border rounded-lg px-3.5 py-2.5 focus:outline-none text-sm" style="border-color: var(--border); background: var(--surface)">
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1" style="color: var(--text-muted)">Email *</label>
      <input type="email" name="email" required maxlength="150" value="<?= e($input['email']) ?>"
             class="w-full border rounded-lg px-3.5 py-2.5 focus:outline-none text-sm" style="border-color: var(--border); background: var(--surface)">
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1" style="color: var(--text-muted)">No. HP</label>
      <input type="text" name="no_hp" value="<?= e($input['no_hp']) ?>" maxlength="20" inputmode="numeric" pattern="08[0-9]{8,11}" placeholder="08xxxxxxxxxx"
             class="w-full border rounded-lg px-3.5 py-2.5 focus:outline-none text-sm" style="border-color: var(--border); background: var(--surface)">
      <p class="text-xs mt-1" style="color: var(--text-faint-2)">Opsional. Format 08xxxxxxxxxx (10–13 digit).</p>
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1" style="color: var(--text-muted)">Alamat</label>
      <textarea name="alamat" rows="2" maxlength="255" class="w-full border rounded-lg px-3.5 py-2.5 focus:outline-none text-sm" style="border-color: var(--border); background: var(--surface)"><?= e($input['alamat']) ?></textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm font-semibold mb-1" style="color: var(--text-muted)">Password *</label>
        <div class="relative">
          <input type="password" name="password" required
                 class="w-full border rounded-lg pl-3.5 pr-10 py-2.5 focus:outline-none text-sm" style="border-color: var(--border); background: var(--surface)">
          <button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1"
                  class="absolute right-3 top-1/2 -translate-y-1/2 hover:opacity-70" style="color: var(--text-faint)"><i class="bi bi-eye text-sm"></i></button>
        </div>
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1" style="color: var(--text-muted)">Ulangi *</label>
        <div class="relative">
          <input type="password" name="password_ulang" required
                 class="w-full border rounded-lg pl-3.5 pr-10 py-2.5 focus:outline-none text-sm" style="border-color: var(--border); background: var(--surface)">
          <button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1"
                  class="absolute right-3 top-1/2 -translate-y-1/2 hover:opacity-70" style="color: var(--text-faint)"><i class="bi bi-eye text-sm"></i></button>
        </div>
      </div>
    </div>
    <p class="text-xs" style="color: var(--text-faint-2)">Password minimal 6 karakter. Nomor anggota otomatis.</p>
    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">Daftar Sekarang</button>
  </form>

  <p class="text-sm mt-5 text-center" style="color: var(--text-faint)">
    Sudah punya akun? <a href="<?= BASE_URL ?>/login.php" class="font-semibold hover:underline" style="color: var(--accent)">Masuk di sini</a>
  </p>
  <a href="<?= BASE_URL ?>/index.php" class="block text-center text-sm hover:underline mt-3" style="color: var(--accent)">&larr; Kembali ke Katalog</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
