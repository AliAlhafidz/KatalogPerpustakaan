<?php
/**
 * Bootstrap tunggal — dipakai di semua entry point.
 * Menggantikan 3 baris berulang: session_start + require database + functions + auth
 */

// Zona waktu aplikasi harus sama dengan zona waktu sesi MySQL (lihat database.php),
// supaya date('Y-m-d') di PHP dan CURDATE()/NOW() di database tidak berbeda hari.
require_once __DIR__ . '/env.php';
$app_timezone = getenv('APP_TIMEZONE') ?: 'Asia/Jakarta';
if (!@date_default_timezone_set($app_timezone)) {
    date_default_timezone_set('Asia/Jakarta');
}

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
