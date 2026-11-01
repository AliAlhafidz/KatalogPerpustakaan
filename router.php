<?php
/**
 * Router untuk PHP built-in server (development).
 *
 * Contoh pemakaian (docroot = folder di atas project):
 *   php -S 127.0.0.1:8000 -t /path/ke/webroot /path/ke/perpustakaan/router.php
 *
 * PHP built-in server tidak membaca .htaccess, jadi router ini meniru aturan
 * keamanannya agar .env, .git, dump SQL, dan script maintenance tidak bisa
 * diunduh/dieksekusi lewat browser.
 */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

$blocked = basename($path) === 'router.php'
    || preg_match('#(?:^|/)\.(?!well-known(?:/|$))#', $path)
    || preg_match('#(?:^|/)database(?:/|$)#', $path)
    || preg_match('#\.(?:sql|md|log)$#i', $path);

if ($blocked) {
    http_response_code(404);
    exit;
}

// Biarkan server menangani file yang diminta seperti biasa.
return false;
