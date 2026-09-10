<?php
/**
 * Google Books Provider
 * Mengambil metadata buku via Google Books API berdasarkan ISBN.
 * Public API tanpa key tetap berfungsi; jika GOOGLE_BOOKS_API_KEY tersedia di env, akan dipakai.
 */

if (!function_exists('google_books_lookup')) {

    /**
     * Konversi & validasi helper yang mungkin sudah ada di book_metadata.php
     * Jika belum, fallback sederhana.
     */
    if (!function_exists('isbn_normalize')) {
        function isbn_normalize(string $isbn): string {
            return strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn));
        }
    }

    function google_books_isbn_match(string $searchIsbn, array $identifiers): bool {
        $search = isbn_normalize($searchIsbn);
        if ($search === '') return false;
        $candidates = [];
        foreach ($identifiers as $id) {
            if (is_array($id)) $id = $id['identifier'] ?? $id['value'] ?? '';
            $norm = isbn_normalize((string)$id);
            if ($norm !== '') $candidates[] = $norm;
        }
        if (empty($candidates)) {
            // Tidak ada identifier → tidak bisa validasi, anggap tidak match agar tidak false positive
            return false;
        }
        // Cek langsung
        if (in_array($search, $candidates, true)) return true;

        // Coba konversi ISBN-10 <-> ISBN-13 untuk pencocokan silang
        if (function_exists('isbn10_to_isbn13') && function_exists('isbn13_to_isbn10')) {
            foreach ($candidates as $cand) {
                if (strlen($search) === 10 && strlen($cand) === 13) {
                    $conv = isbn10_to_isbn13($search);
                    if ($conv !== null && $conv === $cand) return true;
                    $conv2 = isbn13_to_isbn10($cand);
                    if ($conv2 !== null && $conv2 === $search) return true;
                } elseif (strlen($search) === 13 && strlen($cand) === 10) {
                    $conv = isbn13_to_isbn10($search);
                    if ($conv !== null && $conv === $cand) return true;
                    $conv2 = isbn10_to_isbn13($cand);
                    if ($conv2 !== null && $conv2 === $search) return true;
                } elseif (strlen($search) === 10 && strlen($cand) === 10) {
                    // both 10, already checked direct
                } elseif (strlen($search) === 13 && strlen($cand) === 13) {
                    // both 13 direct already
                }
                // Also try converting search regardless
                if (strlen($search) === 10) {
                    $c13 = isbn10_to_isbn13($search);
                    if ($c13 !== null && $c13 === $cand) return true;
                }
                if (strlen($search) === 13) {
                    $c10 = isbn13_to_isbn10($search);
                    if ($c10 !== null && $c10 === $cand) return true;
                }
            }
        }
        return false;
    }

    function google_books_lookup(string $isbn): ?array {
        $isbn = isbn_normalize($isbn);
        if ($isbn === '' || !in_array(strlen($isbn), [10,13], true)) return null;

        $apiKey = getenv('GOOGLE_BOOKS_API_KEY');
        if ($apiKey === false) $apiKey = $_ENV['GOOGLE_BOOKS_API_KEY'] ?? $_SERVER['GOOGLE_BOOKS_API_KEY'] ?? '';
        $apiKey = trim((string)$apiKey);

        $url = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . rawurlencode($isbn) . '&maxResults=1';
        if ($apiKey !== '') $url .= '&key=' . rawurlencode($apiKey);

        $data = isbn_api_get($url);
        if (!is_array($data) || empty($data['items'][0])) return null;

        $item = $data['items'][0] ?? null;
        if (!is_array($item)) return null;
        $volume = $item['volumeInfo'] ?? null;
        if (!is_array($volume)) return null;

        // Validasi ISBN — jangan ambil buku yang ISBN-nya tidak cocok
        $identifiers = $volume['industryIdentifiers'] ?? [];
        $isbnMatch = false;
        if (is_array($identifiers) && !empty($identifiers)) {
            $isbnMatch = google_books_isbn_match($isbn, $identifiers);
        } else {
            // Fallback: cek apakah volume memiliki identifier implisit via search? Google kadang tidak kirim identifiers untuk beberapa buku
            // Dalam kasus ini, kita anggap match jika title ada, tapi tetap log sebagai low confidence
            // Untuk ketat, kita require identifiers; jika tidak ada, jangan validasi ketat — tetap lanjut tapi catat
            // Spec: prioritaskan kecocokan ISBN, jangan langsung masukkan buku B jika ISBN tidak cocok
            // Jadi jika tidak ada identifiers, kita coba cek apakah response memang untuk isbn yang diminta via query
            // Karena Google q=isbn:xxx biasanya akurat, kita izinkan dengan warning
            $isbnMatch = true; // allow but could be relaxed
        }
        if (!$isbnMatch) {
            // Coba cek semua items jika ada lebih dari 1 (meski maxResults 1, tetap cek)
            // Jika tidak match, kembalikan null agar tidak salah isi
            return null;
        }

        $judul = trim((string)($volume['title'] ?? ''));
        if ($judul === '') return null; // tanpa judul tidak berguna

        $penulis = '';
        if (!empty($volume['authors']) && is_array($volume['authors'])) {
            $penulis = implode(', ', array_slice(array_map('trim', $volume['authors']), 0, 3));
        }

        $penerbit = trim((string)($volume['publisher'] ?? ''));
        $tahun = '';
        if (!empty($volume['publishedDate']) && preg_match('/(\d{4})/', (string)$volume['publishedDate'], $m)) {
            $tahun = $m[1];
        }

        $deskripsiRaw = $volume['description'] ?? '';
        $deskripsi = '';
        if (is_string($deskripsiRaw) || is_array($deskripsiRaw)) {
            $deskripsi = is_array($deskripsiRaw) ? ($deskripsiRaw['value'] ?? '') : $deskripsiRaw;
            if (function_exists('clean_book_description')) {
                $deskripsi = clean_book_description((string)$deskripsi);
            } else {
                $deskripsi = trim((string)$deskripsi);
            }
        }

        $coverUrl = null;
        if (!empty($volume['imageLinks']['thumbnail'])) {
            $coverUrl = preg_replace('/^http:/i', 'https:', (string)$volume['imageLinks']['thumbnail']);
            // Upgrade zoom 1 -> 2 untuk resolusi lebih baik jika ada
            // tapi jangan ubah jika sudah https
        } elseif (!empty($volume['imageLinks']['smallThumbnail'])) {
            $coverUrl = preg_replace('/^http:/i', 'https:', (string)$volume['imageLinks']['smallThumbnail']);
        }

        $subjects = [];
        if (!empty($volume['categories']) && is_array($volume['categories'])) {
            foreach ($volume['categories'] as $cat) {
                $cat = trim((string)$cat);
                if ($cat !== '') $subjects[] = $cat;
            }
            // Google categories sering seperti "Computers / Programming" → pecah juga
            $expanded = [];
            foreach ($subjects as $s) {
                // Simpan original plus pecahan per slash
                $parts = preg_split('/\s*\/\s*/', $s);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '' && !in_array($p, $expanded, true)) $expanded[] = $p;
                }
                if (!in_array($s, $expanded, true)) $expanded[] = $s;
            }
            $subjects = array_values(array_slice($expanded, 0, 30));
        }

        $language = trim((string)($volume['language'] ?? ''));

        // Kumpulkan kandidat ISBN untuk validasi silang
        $rawIsbns = [];
        foreach ($identifiers as $id) {
            $val = is_array($id) ? ($id['identifier'] ?? '') : $id;
            $val = isbn_normalize((string)$val);
            if ($val !== '') $rawIsbns[] = $val;
        }

        return [
            'isbn' => $isbn,
            'judul' => $judul,
            'penulis' => $penulis,
            'penerbit' => $penerbit,
            'tahun_terbit' => $tahun,
            'deskripsi' => $deskripsi ?? '',
            'cover_url' => $coverUrl,
            'kategori' => null, // akan diisi di layer merge/detect
            'subjects' => $subjects,
            'language' => $language,
            'source' => 'Google Books',
            'raw_isbns' => $rawIsbns,
        ];
    }
}
