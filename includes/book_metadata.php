<?php
/**
 * Book Metadata — orchestrator multi-provider ISBN lookup
 *
 * Alur:
 * ISBN → Google Books → Open Library → merge → deteksi kategori → cache → return
 *
 * Semua deskripsi melewati clean_book_description() dan tidak pernah memakai first_sentence.
 */

require_once __DIR__ . '/isbn.php';

if (!defined('ISBN_CACHE_TTL')) define('ISBN_CACHE_TTL', 86400);

// ---------------------------------------------------------------------------
// Helpers: ISBN normalize & konversi
// ---------------------------------------------------------------------------

if (!function_exists('isbn_normalize')) {
    function isbn_normalize(string $isbn): string {
        return strtoupper(trim(preg_replace('/[^0-9Xx]/', '', $isbn)));
    }
}

if (!function_exists('isbn10_to_isbn13')) {
    function isbn10_to_isbn13(string $isbn10): ?string {
        $isbn10 = isbn_normalize($isbn10);
        if (strlen($isbn10) !== 10) return null;
        // Validasi karakter terakhir boleh X
        $core = substr($isbn10, 0, 9);
        if (!ctype_digit($core)) return null;
        // Hitung ISBN-13: 978 + core + check digit
        $isbn13base = '978' . $core;
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $d = (int)$isbn13base[$i];
            $sum += ($i % 2 === 0) ? $d : $d * 3;
        }
        $check = (10 - ($sum % 10)) % 10;
        return $isbn13base . $check;
    }
}

if (!function_exists('isbn13_to_isbn10')) {
    function isbn13_to_isbn10(string $isbn13): ?string {
        $isbn13 = isbn_normalize($isbn13);
        if (strlen($isbn13) !== 13) return null;
        if (substr($isbn13, 0, 3) !== '978') return null;
        $core = substr($isbn13, 3, 9);
        if (!ctype_digit($core)) return null;
        // Hitung check digit ISBN-10
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$core[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $check = (11 - $remainder) % 11;
        $checkChar = $check === 10 ? 'X' : (string)$check;
        return $core . $checkChar;
    }
}

// ---------------------------------------------------------------------------
// Deskripsi cleaning — prioritas ketat, jangan pakai first_sentence/subject
// ---------------------------------------------------------------------------

if (!function_exists('clean_book_description')) {
    function clean_book_description(?string $raw): string {
        if ($raw === null) return '';
        // Pastikan string
        if (!is_string($raw)) {
            if (is_array($raw)) {
                $raw = $raw['value'] ?? ($raw[0] ?? '');
                $raw = (string)$raw;
            } else {
                $raw = (string)$raw;
            }
        }
        $raw = trim($raw);
        if ($raw === '') return '';

        // Tolak jika terlihat seperti array/object mentah
        if ($raw === 'Array' || $raw === 'Object' || str_starts_with($raw, 'a:') || str_starts_with($raw, 'O:')) return '';

        // Ganti tag block menjadi newline sebelum strip
        // <br>, <br/>, </p>, </div>, </li>, </h1>... </h6>, <p>
        $withBreaks = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $raw);
        $withBreaks = preg_replace('/<\s*\/(p|div|li|h[1-6]|tr|blockquote)\s*>/i', "\n", $withBreaks);
        $withBreaks = preg_replace('/<\s*(p|div|li|h[1-6]|tr|blockquote)[^>]*>/i', "\n", $withBreaks);
        // Juga ganti </ul> </ol> jadi newline
        $withBreaks = preg_replace('/<\s*\/(ul|ol)\s*>/i', "\n", $withBreaks);

        // Hapus semua tag HTML
        $text = strip_tags($withBreaks);
        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normalisasi whitespace: \r\n -> \n, multiple \n -> \n\n, multiple spaces -> single
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        // Hapus whitespace berlebihan per baris
        $lines = explode("\n", $text);
        $cleanLines = [];
        foreach ($lines as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line));
            // Pertahankan baris kosong sebagai paragraf, tapi jangan duplikasi
            if ($line === '') {
                // Hanya tambah kosong jika sebelumnya tidak kosong
                if (!empty($cleanLines) && end($cleanLines) !== '') $cleanLines[] = '';
                continue;
            }
            $cleanLines[] = $line;
        }
        // Gabung, maksimal \n\n antar paragraf
        $text = implode("\n", $cleanLines);
        // Bersihkan triple newline
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);

        if ($text === '') return '';

        // Validasi: tolak string yang terlalu pendek (<30 char) — bukan sinopsis
        // Kecuali memang deskripsi pendek tapi mengandung kalimat utuh? Tetap tolak di bawah 30
        if (mb_strlen($text, 'UTF-8') < 30) return '';

        // Tolak jika terlihat seperti daftar subject/comma-separated tanpa kalimat
        // Ciri: banyak koma (>=4) tapi tidak ada titik atau kalimat
        $commaCount = substr_count($text, ',');
        $dotCount = substr_count($text, '.');
        if ($commaCount >= 4 && $dotCount === 0) {
            if (mb_strlen($text, 'UTF-8') < 300) return '';
        }

        // Tolak jika string hanya berisi subject-like: "Fiction, Science Fiction, History"
        // Heuristik: jika tidak mengandung kata kerja umum dan hanya comma-separated capitalized words
        // Sederhana: jika teks mengandung 'Subjects:' atau 'Categories:' di awal
        if (preg_match('/^\s*(subjects?|categories?|keywords?|tags?)\s*:/i', $text)) return '';

        // Tolak jika teks terlihat seperti daftar BISAC: "Fiction / Science Fiction"
        // Tapi Google categories mengandung slash, namun itu akan masuk via subjects, bukan deskripsi.
        // Jika deskripsi mengandung slash banyak dan pendek, tolak
        if (mb_strlen($text, 'UTF-8') < 80 && substr_count($text, '/') >= 2 && $dotCount === 0) return '';

        // Tolak jika hasil masih mengandung pola object mentah
        if (preg_match('/^\s*[\[\{].*[\]\}]\s*$/s', $text) && mb_strlen($text, 'UTF-8') < 200) {
            // JSON-like pendek
            return '';
        }

        // Batasi panjang berlebihan untuk textarea? Tidak, biarkan, tapi trim max 5000 char
        if (mb_strlen($text, 'UTF-8') > 5000) {
            $text = mb_substr($text, 0, 5000, 'UTF-8') . '…';
        }

        return $text;
    }
}

// ---------------------------------------------------------------------------
// Load providers setelah helpers didefinisikan (agar provider bisa pakai clean_book_description)
// ---------------------------------------------------------------------------
require_once __DIR__ . '/book_providers/google_books.php';
require_once __DIR__ . '/book_providers/open_library.php';

// ---------------------------------------------------------------------------
// Merge metadata dari beberapa provider
// ---------------------------------------------------------------------------

if (!function_exists('merge_book_metadata')) {
    /**
     * Merge hasil dari beberapa provider.
     * Prioritas field: Google Books > Open Library (atau urutan array).
     * Deskripsi: prioritaskan yang valid (cleaned, >=30 char) dari Google dulu, baru OL.
     * Cover: pertama yang valid https
     * @param array $providerResults associative ['Google Books'=>?array, 'Open Library'=>?array]
     * @param string $isbnSearch isbn yang dicari (untuk field isbn)
     * @return array normalized ['isbn'=>..., 'judul'=>..., 'penulis'=>..., 'penerbit'=>..., 'tahun_terbit'=>..., 'deskripsi'=>..., 'cover_url'=>..., 'subjects'=>[], 'language'=>..., 'source'=>..., 'providers'=>[]]
     */
    function merge_book_metadata(array $providerResults, string $isbnSearch): array {
        $isbn = isbn_normalize($isbnSearch);

        $merged = [
            'isbn' => $isbn,
            'judul' => '',
            'penulis' => '',
            'penerbit' => '',
            'tahun_terbit' => '',
            'deskripsi' => '',
            'cover_url' => null,
            'kategori' => null,
            'subjects' => [],
            'language' => '',
            'source' => '',
            'providers' => [],
        ];

        $orderPriority = ['Google Books', 'Open Library'];
        // Kumpulkan providers yang sukses (tidak null dan punya judul)
        $successful = [];
        foreach ($orderPriority as $name) {
            if (!empty($providerResults[$name]) && is_array($providerResults[$name]) && !empty($providerResults[$name]['judul'])) {
                $successful[$name] = $providerResults[$name];
                $merged['providers'][] = $name;
            }
        }
        // Jika ada provider lain (mis ISBNDB) yang tidak di priority, tambahkan juga
        foreach ($providerResults as $name => $data) {
            if (!in_array($name, $orderPriority, true) && !empty($data) && is_array($data) && !empty($data['judul'])) {
                $successful[$name] = $data;
                $merged['providers'][] = $name;
            }
        }

        if (empty($successful)) {
            return $merged; // kosong, caller akan anggap gagal
        }

        // Helper ambil field terbaik: pertama non-empty sesuai priority
        $pickField = function(string $field) use ($successful, $orderPriority): string {
            foreach ($orderPriority as $name) {
                if (isset($successful[$name][$field])) {
                    $val = trim((string)$successful[$name][$field]);
                    if ($val !== '') return $val;
                }
            }
            // cek provider lain
            foreach ($successful as $data) {
                if (isset($data[$field])) {
                    $val = trim((string)$data[$field]);
                    if ($val !== '') return $val;
                }
            }
            return '';
        };

        $merged['judul'] = $pickField('judul');
        $merged['penulis'] = $pickField('penulis');
        $merged['penerbit'] = $pickField('penerbit');
        $merged['tahun_terbit'] = $pickField('tahun_terbit');
        $merged['language'] = $pickField('language');

        // Deskripsi: prioritaskan yang valid (cleaned) — Google dulu
        $desc = '';
        foreach ($orderPriority as $name) {
            if (!empty($successful[$name]['deskripsi'])) {
                $d = clean_book_description((string)$successful[$name]['deskripsi']);
                if ($d !== '') { $desc = $d; break; }
            }
        }
        // Jika belum ada, cek provider lain
        if ($desc === '') {
            foreach ($successful as $data) {
                if (!empty($data['deskripsi'])) {
                    $d = clean_book_description((string)$data['deskripsi']);
                    if ($d !== '') { $desc = $d; break; }
                }
            }
        }
        $merged['deskripsi'] = $desc; // bisa "" jika tidak ada yang valid

        // Cover: pertama yang valid https
        foreach ($orderPriority as $name) {
            if (!empty($successful[$name]['cover_url'])) {
                $url = trim((string)$successful[$name]['cover_url']);
                if (filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://')) {
                    $merged['cover_url'] = $url;
                    break;
                }
            }
        }
        if ($merged['cover_url'] === null) {
            foreach ($successful as $data) {
                if (!empty($data['cover_url'])) {
                    $url = trim((string)$data['cover_url']);
                    if (filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://')) {
                        $merged['cover_url'] = $url;
                        break;
                    }
                }
            }
        }

        // Subjects: gabungkan semua, unik, maksimal 30 (agar kategori scoring dapat sinyal cukup)
        $allSubjects = [];
        foreach ($successful as $data) {
            if (!empty($data['subjects']) && is_array($data['subjects'])) {
                foreach ($data['subjects'] as $s) {
                    $s = trim((string)$s);
                    if ($s !== '' && !in_array($s, $allSubjects, true)) $allSubjects[] = $s;
                }
            }
        }
        $merged['subjects'] = array_slice($allSubjects, 0, 30);

        // Source primary: provider pertama yang memberi judul+deskripsi atau judul saja
        // Prioritaskan yang memberi deskripsi valid
        $primary = '';
        foreach ($orderPriority as $name) {
            if (isset($successful[$name])) {
                // Jika provider ini memberi deskripsi valid, jadikan primary
                if (!empty($successful[$name]['deskripsi']) && clean_book_description((string)$successful[$name]['deskripsi']) !== '') {
                    $primary = $name;
                    break;
                }
            }
        }
        if ($primary === '' && !empty($successful)) {
            // fallback ke provider pertama yang sukses
            $keys = array_keys($successful);
            $primary = $keys[0];
        }
        $merged['source'] = $primary;

        return $merged;
    }
}

// ---------------------------------------------------------------------------
// Auto-detect kategori dengan scoring
// ---------------------------------------------------------------------------

if (!function_exists('detect_book_category')) {
    /**
     * Deteksi kategori otomatis via scoring.
     * @param PDO $pdo koneksi DB untuk ambil daftar kategori
     * @param array $subjects gabungan subjects/categories dari providers
     * @param string $description deskripsi bersih (opsional, low score)
     * @return array|null ['id'=>int, 'nama'=>string] atau null jika tidak cukup cocok
     */
    function detect_book_category(PDO $pdo, array $subjects = [], string $description = '', array $extraTokens = []): ?array {
        try {
            $stmt = $pdo->query('SELECT id_kategori, nama_kategori FROM kategori ORDER BY nama_kategori ASC');
            $kategoriList = $stmt->fetchAll();
        } catch (Throwable $e) {
            return null;
        }
        if (empty($kategoriList)) return null;
        if (empty($subjects) && trim($description) === '') return null;

        // Default alias mapping untuk kategori yang ada di DB awal
        // Key adalah strtolower nama_kategori
        $defaultAliases = [
            'fiksi' => ['fiction','novel','cerpen','sastra','literature','fantasy','romance','thriller','mystery','crime','horror','science fiction','sci-fi','scifi','sci fi','drama','story','folklore','fabel','prosa','fiksi'],
            'non-fiksi' => ['non-fiction','nonfiction','non fiksi','nonfiksi','pengetahuan umum','general','reference','encyclopedia','non fiction'],
            'nonfiksi' => ['non-fiction','nonfiction','non fiksi'],
            'sains & teknologi' => ['sains','science','teknologi','technology','computer','komputer','computing','programming','program','software','informatics','informatika','engineering','teknik','mathematics','matematika','physics','fisika','chemistry','kimia','biology','biologi','kedokteran','medicine','medical','health','kesehatan','artificial intelligence','ai','machine learning','data','network','jaringan','web development','web','software engineering','information technology','it','stem','astronomy','astronomi','geology','geologi'],
            'sains' => ['science','sains','physics','chemistry','biology'],
            'teknologi' => ['technology','teknologi','computer','komputer'],
            'komputer' => ['computer','komputer','programming','software','informatics'],
            'sejarah' => ['history','sejarah','historical','histori','biography','biografi','autobiography','autobiografi','memoir','chronicle','sejarah dunia','ancient','colonial'],
            'agama' => ['religion','agama','islam','islamic','muslim','christian','kristen','katholik','buddha','buddhism','budhis','hindu','hinduism','spiritual','theology','teologi','religius','quran','alquran','kitab','ibadah'],
            'anak & remaja' => ['children','anak','kids','child','remaja','teen','teenager','young adult','juvenile','picture book','children\'s','toddler','dongeng','youth','remaja'],
            'anak' => ['children','anak','kids','child','dongeng'],
            // Tambahan kategori umum yang mungkin ditambahkan admin
            'pendidikan' => ['education','pendidikan','learning','pembelajaran','school','sekolah','curriculum','pedagogy','edukasi'],
            'bisnis' => ['business','bisnis','entrepreneur','wirausaha','management','manajemen','economics','ekonomi','finance','keuangan','marketing','pemasaran','leadership','kepemimpinan'],
            'psikologi' => ['psychology','psikologi','mental','psych','self help','pengembangan diri','mind','jiwa','counseling','konseling'],
            'novel' => ['novel','fiction','sastra'],
        ];

        // Normalisasi subjects
        $subjectsNorm = [];
        foreach ($subjects as $s) {
            $s = strtolower(trim((string)$s));
            if ($s !== '') $subjectsNorm[] = $s;
        }
        // Tambahkan extraTokens jika ada (mis dari categories)
        foreach ($extraTokens as $t) {
            $t = strtolower(trim((string)$t));
            if ($t !== '' && !in_array($t, $subjectsNorm, true)) $subjectsNorm[] = $t;
        }
        $haystackSubjects = implode(' | ', $subjectsNorm);
        $descLower = strtolower(trim($description));

        $scores = []; // id => score
        $names = []; // id => nama

        foreach ($kategoriList as $kat) {
            $id = (int)$kat['id_kategori'];
            $nama = trim($kat['nama_kategori']);
            $key = strtolower($nama);
            $names[$id] = $nama;

            // Ambil aliases untuk kategori ini
            $aliases = $defaultAliases[$key] ?? null;
            if ($aliases === null) {
                // Fallback: gunakan nama kategori + kata-kata di dalamnya
                $aliases = [$key];
                // pecah nama menjadi kata
                $words = preg_split('/[^a-z0-9]+/', $key);
                foreach ($words as $w) {
                    $w = trim($w);
                    if (strlen($w) > 2 && !in_array($w, $aliases, true)) $aliases[] = $w;
                }
                // Jika nama mengandung '&' atau 'dan', tambahkan versi tanpa simbol
                $clean = str_replace(['&','dan'], ' ', $key);
                $clean = trim(preg_replace('/\s+/', ' ', $clean));
                if ($clean !== '' && $clean !== $key && !in_array($clean, $aliases, true)) $aliases[] = $clean;
            } else {
                // Tambahkan nama asli dan variannya agar selalu terdeteksi
                if (!in_array($key, $aliases, true)) $aliases[] = $key;
            }

            // Unik dan sort panjang desc agar alias panjang cocok dulu
            $aliases = array_unique(array_map('strtolower', array_map('trim', $aliases)));
            usort($aliases, fn($a,$b) => strlen($b) <=> strlen($a));

            $score = 0;

            // Scoring dari subjects
            foreach ($aliases as $alias) {
                $alias = trim($alias);
                if ($alias === '' || strlen($alias) < 3) continue;
                foreach ($subjectsNorm as $subj) {
                    if ($subj === $alias) {
                        $score += 10; // exact match tinggi
                        // break? tapi bisa ada multiple subjects, biarkan akumulasi tapi cap
                    } elseif (strpos($subj, $alias) !== false || strpos($alias, $subj) !== false) {
                        // substring — tinggi jika alias >=4, sedang jika 3
                        if (strlen($alias) >= 4) $score += 8;
                        else $score += 5;
                    } else {
                        // cek kata terpisah: subject "computer programming" mengandung kata "computer"
                        $subjWords = preg_split('/[^a-z0-9]+/', $subj);
                        $aliasWords = preg_split('/[^a-z0-9]+/', $alias);
                        // Jika alias single word dan ada di subject words
                        if (count($aliasWords) === 1 && in_array($alias, $subjWords, true)) {
                            $score += 7;
                        } elseif (count($subjWords) === 1 && in_array($subj, $aliasWords, true)) {
                            $score += 7;
                        }
                    }
                }
            }

            // Scoring dari deskripsi (low, per alias, cap 6 agar tidak mendominasi subjects)
            if ($descLower !== '' && mb_strlen($descLower, 'UTF-8') > 30) {
                $descFound = 0;
                foreach ($aliases as $alias) {
                    if (strlen($alias) < 4) continue;
                    if (strpos($descLower, $alias) !== false) {
                        $descFound += 2;
                        if ($descFound >= 6) break;
                    }
                }
                $score += $descFound;
            }

            // Cap per kategori untuk menghindari overflow jika banyak subjects cocok sama
            if ($score > 100) $score = 100;

            $scores[$id] = $score;
        }

        // Cari skor tertinggi
        $maxScore = 0;
        $bestId = null;
        foreach ($scores as $id => $sc) {
            if ($sc > $maxScore) {
                $maxScore = $sc;
                $bestId = $id;
            }
        }

        // Threshold: jika skor terlalu rendah, jangan paksa
        if ($bestId === null || $maxScore < 5) return null;

        // Jika ada tie, pilih yang skor sama tertinggi pertama (sudah by id order ASC via query ASC)
        // Tapi kita sudah iterasi sesuai array kategoriList ASC, jadi bestId sudah pertama tertinggi
        // Pastikan tie-breaking tidak random
        return ['id' => $bestId, 'nama' => $names[$bestId], 'score' => $maxScore];
    }
}

// ---------------------------------------------------------------------------
// Orchestrator utama
// ---------------------------------------------------------------------------

if (!function_exists('search_book_by_isbn')) {
    /**
     * Cari buku via ISBN dengan multi-provider, merge, kategori, cache.
     * @return array ['success'=>bool, 'data'=>[...], 'message'=>string]
     */
    function search_book_by_isbn(PDO $pdo, string $isbn, int $ttl = 86400): array {
        $isbnNorm = isbn_normalize($isbn);
        if ($isbnNorm === '' || !in_array(strlen($isbnNorm), [10,13], true)) {
            return ['success' => false, 'message' => 'ISBN harus berupa 10 atau 13 digit.'];
        }

        // Cek cache dulu (normalized)
        $cached = isbn_cache_get($isbnNorm, $ttl);
        if (is_array($cached) && isset($cached['success']) && $cached['success'] === true && isset($cached['data'])) {
            // Refresh ttl? tidak perlu, sudah di-cache
            return $cached;
        }

        $results = [];
        $errors = [];

        // Provider 1: Google Books
        try {
            $gb = google_books_lookup($isbnNorm);
            $results['Google Books'] = $gb;
            if ($gb === null) $errors[] = 'Google Books: tidak ditemukan / tidak match';
        } catch (Throwable $e) {
            error_log('Google Books lookup error ISBN ' . $isbnNorm . ': ' . $e->getMessage());
            $results['Google Books'] = null;
            $errors[] = 'Google Books error';
        }

        // Provider 2: Open Library
        try {
            $ol = open_library_lookup($isbnNorm);
            $results['Open Library'] = $ol;
            if ($ol === null) $errors[] = 'Open Library: tidak ditemukan';
        } catch (Throwable $e) {
            error_log('Open Library lookup error ISBN ' . $isbnNorm . ': ' . $e->getMessage());
            $results['Open Library'] = null;
            $errors[] = 'Open Library error';
        }

        // Provider lain opsional: ISBNDB jika API key ada (tidak wajib)
        // Kita cek env ISBNDB_API_KEY, jika ada coba fetch (implementasi sederhana)
        $isbndbKey = getenv('ISBNDB_API_KEY');
        if ($isbndbKey === false) $isbndbKey = $_ENV['ISBNDB_API_KEY'] ?? $_SERVER['ISBNDB_API_KEY'] ?? '';
        $isbndbKey = trim((string)$isbndbKey);
        if ($isbndbKey !== '') {
            try {
                // ISBNdb API: https://api2.isbndb.com/book/{isbn}
                // Butuh header Authorization, kita reuse isbn_api_get dengan header custom?
                // Untuk sekarang skip implementasi penuh, tapi placeholder untuk ekstensi
                // Jika ingin aktif, buat request dengan curl manual
                if (function_exists('curl_init')) {
                    $ch = curl_init('https://api2.isbndb.com/book/' . rawurlencode($isbnNorm));
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 2,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT => 8,
                        CURLOPT_USERAGENT => 'Perpustakaan-Umum-Sejahtera/1.0 (educational)',
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                        CURLOPT_HTTPHEADER => ['Authorization: ' . $isbndbKey, 'Accept: application/json'],
                    ]);
                    $raw = curl_exec($ch);
                    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($raw !== false && $code >=200 && $code <300) {
                        $data = json_decode($raw, true);
                        if (is_array($data) && !empty($data['book'])) {
                            $b = $data['book'];
                            $judul = trim((string)($b['title'] ?? ''));
                            if ($judul !== '') {
                                $penulis = trim((string)($b['authors'][0] ?? ''));
                                if (is_array($b['authors'] ?? null)) $penulis = implode(', ', array_slice($b['authors'], 0, 3));
                                $penerbit = trim((string)($b['publisher'] ?? ''));
                                $tahun = '';
                                if (!empty($b['date_published']) && preg_match('/(\d{4})/', $b['date_published'], $m)) $tahun = $m[1];
                                $deskripsi = '';
                                if (!empty($b['synopsis'])) $deskripsi = clean_book_description((string)$b['synopsis']);
                                $subjects = [];
                                if (!empty($b['subjects']) && is_array($b['subjects'])) {
                                    foreach (array_slice($b['subjects'],0,10) as $s) {
                                        $s = trim((string)$s);
                                        if ($s !== '') $subjects[] = $s;
                                    }
                                }
                                $cover = $b['image'] ?? null;
                                $results['ISBNdb'] = [
                                    'isbn' => $isbnNorm,
                                    'judul' => $judul,
                                    'penulis' => $penulis,
                                    'penerbit' => $penerbit,
                                    'tahun_terbit' => $tahun,
                                    'deskripsi' => $deskripsi,
                                    'cover_url' => $cover,
                                    'kategori' => null,
                                    'subjects' => $subjects,
                                    'language' => trim((string)($b['language'] ?? '')),
                                    'source' => 'ISBNdb',
                                    'raw_isbns' => [$isbnNorm],
                                ];
                            }
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('ISBNdb error: ' . $e->getMessage());
            }
        }

        // Jika semua provider gagal/null
        $hasAny = false;
        foreach ($results as $r) {
            if (is_array($r) && !empty($r['judul'])) { $hasAny = true; break; }
        }
        if (!$hasAny) {
            $msg = 'Buku dengan ISBN tersebut tidak ditemukan. Anda bisa melanjutkan dengan input manual.';
            // Beri detail provider jika debug, tapi jangan leak internal
            return ['success' => false, 'message' => $msg];
        }

        // Merge
        $merged = merge_book_metadata($results, $isbnNorm);

        if (empty($merged['judul'])) {
            return ['success' => false, 'message' => 'Buku dengan ISBN tersebut tidak ditemukan. Anda bisa melanjutkan dengan input manual.'];
        }

        // Auto kategori
        $kategori = detect_book_category($pdo, $merged['subjects'] ?? [], $merged['deskripsi'] ?? '');
        // $kategori dari detect sudah ['id','nama','score'], kita hanya butuh id+nama untuk response
        $kategoriPayload = null;
        if (is_array($kategori) && isset($kategori['id'])) {
            $kategoriPayload = ['id' => (int)$kategori['id'], 'nama' => $kategori['nama']];
            // Simpan juga ke merged untuk cache
            $merged['kategori'] = $kategoriPayload;
        } else {
            $merged['kategori'] = null;
        }

        // Build response payload — struktur konsisten sesuai spec
        $payload = [
            'success' => true,
            'data' => [
                'isbn' => $merged['isbn'],
                'judul' => $merged['judul'],
                'penulis' => $merged['penulis'],
                'penerbit' => $merged['penerbit'],
                'tahun_terbit' => $merged['tahun_terbit'],
                'deskripsi' => $merged['deskripsi'],
                'cover_url' => $merged['cover_url'],
                'kategori' => $kategoriPayload,
                // Sumber: untuk kompatibilitas, 'sumber' string primary, tambah 'providers' array & 'sumber_detail'
                'sumber' => $merged['source'],
                'providers' => $merged['providers'],
                'sumber_detail' => [
                    'primary' => $merged['source'],
                    'providers' => $merged['providers'],
                ],
                // Tambahan metadata untuk debugging / sinyal kategori
                'subjects' => $merged['subjects'],
                'language' => $merged['language'],
            ]
        ];

        // Simpan cache normalized, ttl mudah diubah
        isbn_cache_set($isbnNorm, $payload, $ttl);

        return $payload;
    }
}
