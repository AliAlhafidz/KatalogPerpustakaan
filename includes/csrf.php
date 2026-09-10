<?php
/**
 * CSRF Protection helper
 * Dipakai di semua form POST.
 */
if (session_status() === PHP_SESSION_NONE) {
    // Jangan start session di sini — bootstrap yang akan start.
    // File ini hanya mendefinisikan fungsi.
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_regenerate() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function validate_csrf($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf($token)) {
        set_flash('error', 'Sesi kadaluarsa atau token tidak valid. Silakan coba lagi.');
        // Redirect back ke referer aman jika ada, fallback ke index
        $fallback = BASE_URL . '/index.php';
        // Gunakan HTTP_REFERER hanya jika masih dalam BASE_URL
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref !== '') {
            $parsed = parse_url($ref);
            $path = $parsed['path'] ?? '';
            if (strpos($path, BASE_URL) === 0) {
                $fallback = $path . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
            }
        }
        header('Location: ' . $fallback);
        exit;
    }
}
