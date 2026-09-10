<?php
/**
 * ISBN helper — dipisah dari admin/buku/cari_isbn.php
 * Agar bisa dipakai ulang (mis. via cron atau API lain).
 */

function isbn_api_get(string $url): ?array {
    // Ganti dengan cURL jika tersedia untuk timeout & SSL verify lebih baik
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Perpustakaan-Umum-Sejahtera/1.0 (educational)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false || $code < 200 || $code >= 300) return null;
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
    $ctx = stream_context_create([
        'http' => ['method'=>'GET','timeout'=>10,'ignore_errors'=>true,'header'=>"User-Agent: Perpustakaan-Umum-Sejahtera/1.0\r\nAccept: application/json\r\n"],
        'https'=> ['method'=>'GET','timeout'=>10,'ignore_errors'=>true,'header'=>"User-Agent: Perpustakaan-Umum-Sejahtera/1.0\r\nAccept: application/json\r\n"],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function isbn_cache_path(string $isbn): string {
    $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);
    return rtrim(sys_get_temp_dir(), '/\\') . '/perpustakaan_isbn_' . md5($isbn) . '.json';
}

function isbn_cache_get(string $isbn, int $ttl = 86400): ?array {
    $path = isbn_cache_path($isbn);
    if (!is_file($path)) return null;
    // Expiration: mtime + ttl < now => expired
    $mtime = @filemtime($path);
    if ($mtime === false || (time() - $mtime) > $ttl) {
        @unlink($path);
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        // Corrupt cache → hapus
        @unlink($path);
        return null;
    }
    return $data;
}

function isbn_cache_set(string $isbn, array $data, int $ttl = 86400): void {
    $path = isbn_cache_path($isbn);
    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) return;
    // Tulis dengan lock
    $fh = @fopen($path, 'c');
    if (!$fh) return;
    if (flock($fh, LOCK_EX)) {
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, $payload);
        fflush($fh);
        flock($fh, LOCK_UN);
    }
    fclose($fh);
    @chmod($path, 0600);
}

function cari_buku_via_isbn(PDO $pdo, string $isbn): array {
    // Wrapper yang mengembalikan data terstruktur, dipakai oleh api endpoint
    $isbn = preg_replace('/[^0-9Xx]/', '', $isbn);
    if ($isbn === '' || !in_array(strlen($isbn), [10,13], true)) {
        return ['success'=>false, 'message'=>'ISBN harus 10 atau 13 digit.'];
    }
    // Logic dipindah dari cari_isbn.php (disamakan)
    $url = 'https://openlibrary.org/search.json?isbn=' . rawurlencode($isbn) . '&limit=1';
    $data = isbn_api_get($url);
    $doc = $data['docs'][0] ?? null;
    if (!$doc) return ['success'=>false, 'message'=>'Buku tidak ditemukan di Open Library.'];
    // ... singkat: kembalikan doc mentah, biar caller yang format
    return ['success'=>true, 'doc'=>$doc];
}
