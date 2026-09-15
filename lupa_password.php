<?php
require_once __DIR__ . '/config/bootstrap.php';

// Jika sudah login, redirect
if (is_admin()) redirect('/admin/dashboard.php');
if (is_anggota()) redirect('/anggota/dashboard.php');

$pesan = '';
$pesan_tipe = 'info';
$token_demo = null;
$link_reset = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $rl_key = rate_limit_key('lupa_password');
    $rl = rate_limit_check($rl_key, 5, 900);
    if (!$rl['allowed']) {
        $pesan = 'Terlalu banyak percobaan. Coba lagi dalam ' . rate_limit_format_retry($rl['retry_after']) . '.';
        $pesan_tipe = 'error';
    } else {
        $email = clean($_POST['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Tetap hit rate limit untuk percobaan invalid agar tidak enumerasi via timing
            rate_limit_hit($rl_key, 900);
            // Pesan generik (tidak bocor validasi spesifik vs tidak terdaftar)
            $pesan = 'Jika email terdaftar, instruksi reset akan ditampilkan.';
            $pesan_tipe = 'info';
        } else {
            // Cari anggota by email (prepared statement, generik response)
            $stmt = $pdo->prepare("SELECT id_anggota FROM anggota WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $anggota = $stmt->fetch();

            if ($anggota) {
                // Generate token plaintext untuk demo, hash sebelum simpan
                $raw_token = bin2hex(random_bytes(32));
                $hashed = hash('sha256', $raw_token);
                $expiry = date('Y-m-d H:i:s', time() + 3600);
                $stmt = $pdo->prepare("UPDATE anggota SET reset_token = :token, reset_expiry = :expiry WHERE id_anggota = :id");
                $stmt->execute([':token' => $hashed, ':expiry' => $expiry, ':id' => $anggota['id_anggota']]);
                $token_demo = $raw_token;
                $link_reset = BASE_URL . '/reset_password.php?token=' . urlencode($raw_token);
                rate_limit_hit($rl_key, 900);
            } else {
                // Email tidak terdaftar — tetap hit & pesan generik (tidak bocor)
                rate_limit_hit($rl_key, 900);
            }
            // Pesan generik selalu sama, baik terdaftar maupun tidak (anti-enumeration)
            if ($pesan === '') {
                $pesan = 'Jika email terdaftar, instruksi reset akan ditampilkan.';
                $pesan_tipe = 'info';
            }
        }
    }
}

$page_title = 'Lupa Password';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-2xl border border-gray-100 shadow-sm p-8 my-auto">
  <h1 class="text-xl font-bold text-gray-800 mb-1">Lupa Password</h1>
  <p class="text-sm text-gray-500 mb-6">Masukkan email anggota. Jika terdaftar, token reset akan ditampilkan (mode demo).</p>

  <?php if ($pesan): ?>
    <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?= $pesan_tipe === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>"><?= e($pesan) ?></div>
  <?php endif; ?>

  <?php if ($token_demo): ?>
    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4">
      <p class="text-xs font-bold uppercase tracking-wide text-amber-700 mb-2"><i class="bi bi-exclamation-triangle"></i> MODE DEMO</p>
      <p class="text-sm text-amber-800 mb-3">Di production token ini dikirim via email, bukan ditampilkan di layar. Token berlaku 1 jam, one-time use.</p>
      <div class="bg-white rounded-xl border border-amber-200 p-3 break-all text-xs font-mono text-slate-800"><?= e($token_demo) ?></div>
      <a href="<?= e($link_reset) ?>" class="mt-3 inline-flex items-center gap-2 w-full justify-center rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-4 py-2.5"><i class="bi bi-link-45deg"></i> Buka Reset Password</a>
      <p class="text-xs text-amber-600 mt-2">Link: <?= e($link_reset) ?></p>
    </div>
  <?php endif; ?>

  <form method="post" action="" class="space-y-4">
      <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Email Anggota *</label>
      <input type="email" name="email" required autocomplete="email" placeholder="contoh@email.com"
             class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500">
    </div>
    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-medium py-2.5 rounded-lg transition">Kirim Instruksi Reset</button>
  </form>

  <p class="text-sm text-gray-500 mt-6 text-center">
    Ingat password? <a href="<?= BASE_URL ?>/login.php" class="text-brand-600 font-medium hover:underline">Masuk di sini</a>
  </p>
  <a href="<?= BASE_URL ?>/index.php" class="block text-center text-sm text-brand-600 hover:underline mt-3">&larr; Kembali ke Katalog</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
