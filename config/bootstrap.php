<?php
/**
 * Bootstrap tunggal — dipakai di semua entry point.
 * Menggantikan 3 baris berulang: session_start + require database + functions + auth
 */

// Secure session cookie params sebelum session_start
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    // PHP 7.3+ array syntax
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/audit.php';

// Tangkap exception tak tertangani agar tidak bocor detail error ke pengguna.
set_exception_handler(function (Throwable $e): void {
    error_log('[unhandled] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    die('<title>Terjadi Kesalahan</title><style>body{font-family:system-ui,sans-serif;background:#f7f5f4;color:#1c1917;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}div{text-align:center;padding:2rem}h1{font-size:1.75rem;margin:0 0 .5rem}p{color:#78716c;margin:0 0 1.25rem}a{color:#b45309;font-weight:600;text-decoration:none}</style><div><h1>Terjadi kesalahan</h1><p>Terjadi kendala teknis pada server. Silakan coba lagi beberapa saat.</p><a href="' . BASE_URL . '">Kembali ke beranda</a></div>');
});
