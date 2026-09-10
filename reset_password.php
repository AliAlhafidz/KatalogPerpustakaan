<?php
require_once __DIR__ . '/config/bootstrap.php';

if (is_admin()) redirect('/admin/dashboard.php');
if (is_anggota()) redirect('/anggota/dashboard.php');

$token_raw = trim($_GET['token'] ?? $_POST['token'] ?? '');
$token_raw = preg_replace('/[^0-9a-fA-F]/', '', $token_raw);
$error = '';
$sukses = false;
$anggota = null;
$valid = false;

// Validasi token: hash, cocokkan, cek expiry > NOW() dan one-time
if ($token_raw !== '') {
    $hashed = hash('sha256', $token_raw);
    $stmt = $pdo->prepare("SELECT id_anggota, email, reset_expiry FROM anggota WHERE reset_token = :token LIMIT 1");
    $stmt->execute([':token' => $hashed]);
    $anggota = $stmt->fetch();
    if ($anggota) {
        $expiry = $anggota['reset_expiry'] ?? null;
        if ($expiry && strtotime($expiry) > time()) {
            $valid = true;
        } else {
            $error = 'Token tidak valid atau sudah kadaluarsa (1 jam). Silakan minta reset baru.';
        }
    } else {
        $error = 'Token tidak valid atau sudah dipakai. Silakan minta reset baru.';
    }
} else {
    $error = 'Token tidak ditemukan. Silakan minta reset melalui halaman lupa password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    require_csrf();
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password_ulang'] ?? '';

    if (strlen($pass1) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($pass1 !== $pass2) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Update password, hapus token one-time
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE anggota SET password = :pass, reset_token = NULL, reset_expiry = NULL WHERE id_anggota = :id AND reset_token = :token");
        $hashed = hash('sha256', $token_raw);
        $stmt->execute([':pass' => $hash, ':id' => $anggota['id_anggota'], ':token' => $hashed]);
        if ($stmt->rowCount() === 1) {
            set_flash('sukses', 'Password berhasil direset. Silakan login dengan password baru.');
            redirect('/login.php');
        } else {
            $error = 'Token sudah dipakai atau tidak valid. Silakan minta reset baru.';
            $valid = false;
        }
    }
}

$page_title = 'Reset Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-2xl border border-gray-100 shadow-sm p-8 mt-4">
  <h1 class="text-xl font-bold text-gray-800 mb-1">Reset Password</h1>
  <p class="text-sm text-gray-500 mb-6">Buat password baru untuk akun anggota.</p>

  <?php if ($error): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-700 px-4 py-3 text-sm"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if (!$valid): ?>
    <div class="text-center">
      <a href="<?= BASE_URL ?>/lupa_password.php" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-5 py-2.5"><i class="bi bi-arrow-counterclockwise"></i> Minta Reset Baru</a>
      <a href="<?= BASE_URL ?>/login.php" class="block text-center text-sm text-brand-600 hover:underline mt-3">Kembali ke Login</a>
    </div>
  <?php else: ?>
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 px-4 py-3 text-sm">
      Reset untuk: <b><?= e($anggota['email']) ?></b> — token valid 1 jam, one-time use.
    </div>
    <form method="post" action="" class="space-y-4">
        <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token_raw) ?>">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru *</label>
        <div class="relative">
          <input type="password" name="password" required autocomplete="new-password"
                 class="w-full border border-gray-300 rounded-lg pl-4 pr-10 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
          <button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ulangi Password Baru *</label>
        <div class="relative">
          <input type="password" name="password_ulang" required autocomplete="new-password"
                 class="w-full border border-gray-300 rounded-lg pl-4 pr-10 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
          <button type="button" onclick="togglePasswordVisibility(this)" tabindex="-1" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"><i class="bi bi-eye"></i></button>
        </div>
      </div>
      <p class="text-xs text-gray-400">Minimal 6 karakter. Token hanya sekali pakai.</p>
      <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg transition">Reset Password</button>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
