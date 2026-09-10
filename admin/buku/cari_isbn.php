<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/book_metadata.php';
wajib_admin();

header('Content-Type: application/json; charset=utf-8');

// Rate limit ringan untuk endpoint ISBN: 30 request per 10 menit per IP
try {
    $rlKey = rate_limit_key('isbn_lookup');
    $rl = rate_limit_check($rlKey, 30, 600);
    if (!$rl['allowed']) {
        echo json_encode(['success' => false, 'message' => 'Terlalu banyak pencarian ISBN. Coba lagi dalam ' . rate_limit_format_retry($rl['retry_after']) . '.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    // Rate limit gagal tidak menggagalkan lookup
}

$isbn = preg_replace('/[^0-9Xx]/', '', $_GET['isbn'] ?? '');
if ($isbn === '' || !in_array(strlen($isbn), [10, 13], true)) {
    echo json_encode(['success' => false, 'message' => 'ISBN harus berupa 10 atau 13 digit.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Orchestrator multi-provider: Google Books + Open Library + ISBNdb opsional
// TTL 86400 (24 jam) — mudah diubah via parameter ketiga
$result = search_book_by_isbn($pdo, $isbn, ISBN_CACHE_TTL);

// Catat attempt untuk rate limit (hanya hit jika bukan cache hit? tapi tetap hit)
try {
    rate_limit_hit($rlKey, 600);
} catch (Throwable $e) {}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
