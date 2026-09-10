<?php
require_once __DIR__ . '/config/bootstrap.php';

// Jika sudah login, arahkan ke dashboard masing-masing
if (is_admin()) redirect('/admin/dashboard.php');
if (is_anggota()) redirect('/anggota/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $rl_key = rate_limit_key('login');
    $rl = rate_limit_check($rl_key, 5, 900);
    if (!$rl['allowed']) {
        $error = 'Terlalu banyak percobaan login. Coba lagi dalam ' . rate_limit_format_retry($rl['retry_after']) . '.';
    } else {
        $email_username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email_username === '' || $password === '') {
            $error = 'Username/email dan password wajib diisi.';
        } else {
            // Coba cocokkan sebagai admin (berdasarkan username)
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :u");
            $stmt->execute([':u' => $email_username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                csrf_regenerate();
                rate_limit_reset($rl_key);
                $_SESSION['role']     = 'admin';
                $_SESSION['id_admin'] = $admin['id_admin'];
                $_SESSION['nama']     = $admin['nama'];
                $_SESSION['foto']     = $admin['foto'];
                redirect('/admin/dashboard.php');
            }

            // Coba cocokkan sebagai anggota (berdasarkan email)
            $stmt = $pdo->prepare("SELECT * FROM anggota WHERE email = :e");
            $stmt->execute([':e' => $email_username]);
            $anggota = $stmt->fetch();

            if ($anggota && password_verify($password, $anggota['password'])) {
                if ($anggota['status'] !== 'aktif') {
                    // Jangan bocorkan status akun (S-01) — pakai pesan generik sama dengan kredensial salah
                    $error = 'Username/email atau password yang kamu masukkan salah.';
                    rate_limit_hit($rl_key, 900);
                    error_log('Login nonaktif blocked (generik) untuk email: ' . $anggota['email']);
                } else {
                    session_regenerate_id(true);
                    csrf_regenerate();
                    rate_limit_reset($rl_key);
                    $_SESSION['role']       = 'anggota';
                    $_SESSION['id_anggota'] = $anggota['id_anggota'];
                    $_SESSION['nama']       = $anggota['nama'];
                    $_SESSION['foto']       = $anggota['foto'];
                    redirect('/anggota/dashboard.php');
                }
            }

            if (!$error) {
                $error = 'Username/email atau password yang kamu masukkan salah.';
                rate_limit_hit($rl_key, 900);
            }
        }
    }
}

$page_title = 'Masuk';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-xl border p-6 sm:p-7 mt-6" style="border-color: var(--border)">
  <p class="text-[11px] font-bold tracking-widest uppercase mb-1" style="color: var(--text-faint-2)">Masuk</p>
  <h1 class="font-display text-[20px] font-bold mb-1" style="color: var(--text)">Masuk ke Akun</h1>
  <p class="text-sm mb-5" style="color: var(--text-faint)">Gunakan akun admin atau anggota untuk masuk.</p>

  <?php if ($error): ?>
    <div class="mb-4 rounded-lg border px-3.5 py-3 text-sm" style="background:#fef2f2; border-color:#fecaca; color:#991b1b"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="" class="space-y-3.5">
      <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-semibold mb-1" style="color: var(--text-muted)">Username (admin) / Email (anggota)</label>
      <input type="text" name="username" required autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"
             class="w-full border rounded-lg px-3.5 py-2.5 focus:outline-none text-sm" style="border-color: var(--border); background: var(--surface)">
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1" style="color: var(--text-muted)">Password</label>
      <div class="relative">
        <input type="password" name="password" required autocomplete="current-password"
               class="w-full border rounded-lg pl-3.5 pr-10 py-2.5 focus:outline-none text-sm" style="border-color: var(--border); background: var(--surface)">
        <button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1"
                class="absolute right-3 top-1/2 -translate-y-1/2 hover:opacity-70" style="color: var(--text-faint)"><i class="bi bi-eye text-sm"></i></button>
      </div>
      <div class="text-right mt-1.5">
        <a href="<?= BASE_URL ?>/lupa_password.php" class="text-xs font-medium hover:underline" style="color: var(--accent)">Lupa password?</a>
      </div>
    </div>
    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">Masuk</button>
  </form>

  <p class="text-sm mt-5 text-center" style="color: var(--text-faint)">
    Belum punya akun? <a href="<?= BASE_URL ?>/register.php" class="font-semibold hover:underline" style="color: var(--accent)">Daftar sebagai anggota</a>
  </p>
  <a href="<?= BASE_URL ?>/index.php" class="block text-center text-sm hover:underline mt-3" style="color: var(--accent)">&larr; Kembali ke Katalog</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
