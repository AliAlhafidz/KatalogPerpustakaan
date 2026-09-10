<?php
/**
 * Rate Limit helper — sederhana tanpa dependency.
 * Dipakai untuk login & register (backend, bukan JS).
 * Penyimpanan: file di sys_get_temp_dir() + fallback session.
 * Tidak menyimpan password/data sensitif, hanya timestamp percobaan.
 */

function rate_limit_key(string $prefix): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    // Normalisasi IP: hapus karakter aneh, batasi panjang
    $ip = preg_replace('/[^0-9a-fA-F:\.]/', '', $ip);
    $ip = substr($ip, 0, 45);
    if ($ip === '' || $ip === null) $ip = 'unknown';
    return $prefix . ':' . $ip;
}

function rate_limit_file(string $key): string {
    // Hash key agar aman untuk nama file
    return rtrim(sys_get_temp_dir(), '/\\') . '/perpustakaan_rl_' . md5($key) . '.json';
}

/**
 * Baca data rate limit dari file (dengan flock) + fallback session.
 * @return int[] array timestamp attempt
 */
function rate_limit_load(string $key, int $window): array {
    $file = rate_limit_file($key);
    $attempts = null;

    // Primary: file. Fallback ke session hanya jika file tidak ada/gagal.
    if (is_file($file)) {
        $fh = @fopen($file, 'r');
        if ($fh) {
            if (flock($fh, LOCK_SH)) {
                $raw = stream_get_contents($fh);
                flock($fh, LOCK_UN);
                $data = json_decode($raw, true);
                if (is_array($data) && isset($data['attempts']) && is_array($data['attempts'])) {
                    $attempts = $data['attempts'];
                }
            }
            fclose($fh);
        }
    }

    if ($attempts === null) {
        $sess_key = 'rl_' . md5($key);
        if (isset($_SESSION[$sess_key]) && is_array($_SESSION[$sess_key])) {
            $attempts = $_SESSION[$sess_key];
        } else {
            $attempts = [];
        }
    }

    // Filter hanya dalam window
    $now = time();
    $attempts = array_filter($attempts, fn($t) => ($now - (int)$t) < $window);
    $attempts = array_map('intval', $attempts);
    sort($attempts);

    return $attempts;
}

function rate_limit_save(string $key, array $attempts): void {
    $file = rate_limit_file($key);
    $data = json_encode(['attempts' => array_values($attempts)], JSON_UNESCAPED_SLASHES);
    $fh = @fopen($file, 'c+');
    if ($fh) {
        if (flock($fh, LOCK_EX)) {
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, $data);
            fflush($fh);
            flock($fh, LOCK_UN);
        }
        fclose($fh);
        // Batasi permission file
        @chmod($file, 0600);
    }
    // Simpan juga di session sebagai fallback
    $sess_key = 'rl_' . md5($key);
    $_SESSION[$sess_key] = array_values($attempts);
}

/**
 * Cek apakah masih diperbolehkan.
 * @return array{allowed:bool, remaining:int, retry_after:int, attempts:int}
 */
function rate_limit_check(string $key, int $max = 5, int $window = 900): array {
    $attempts = rate_limit_load($key, $window);
    $count = count($attempts);
    if ($count < $max) {
        return ['allowed' => true, 'remaining' => $max - $count, 'retry_after' => 0, 'attempts' => $count];
    }
    // Hitung retry_after dari attempt tertua dalam window
    $oldest = $attempts[0] ?? time();
    $retry = ($oldest + $window) - time();
    $retry = max(0, $retry);
    return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retry, 'attempts' => $count];
}

function rate_limit_hit(string $key, int $window = 900): void {
    $attempts = rate_limit_load($key, $window);
    $attempts[] = time();
    // Simpan dengan filter window agar file tidak membengkak
    $now = time();
    $attempts = array_filter($attempts, fn($t) => ($now - (int)$t) < $window);
    rate_limit_save($key, array_values($attempts));
}

function rate_limit_reset(string $key): void {
    $file = rate_limit_file($key);
    @unlink($file);
    $sess_key = 'rl_' . md5($key);
    unset($_SESSION[$sess_key]);
}

function rate_limit_format_retry(int $seconds): string {
    if ($seconds <= 60) return $seconds . ' detik';
    $menit = (int) ceil($seconds / 60);
    return $menit . ' menit';
}
