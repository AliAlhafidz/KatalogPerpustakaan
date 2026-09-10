<?php
/**
 * Helpers umum — validasi, pagination, sanitize
 */

function validate_tanggal_ymd($tanggal) {
    if (empty($tanggal)) return false;
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    return $d && $d->format('Y-m-d') === $tanggal;
}

function paginate_params($default_per_halaman = 10) {
    $halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
    $per_halaman = $default_per_halaman;
    $offset = ($halaman - 1) * $per_halaman;
    return [$halaman, $per_halaman, $offset];
}

function total_halaman($total_data, $per_halaman) {
    return max(1, (int) ceil($total_data / $per_halaman));
}
