<?php
/**
 * Open Library Provider
 * Mengambil metadata via beberapa endpoint Open Library:
 * - search.json?isbn=
 * - /isbn/{isbn}.json
 * - /works/{key}.json
 * - /books/{edition_key}.json
 * Tidak pernah menggunakan first_sentence sebagai deskripsi.
 */

if (!function_exists('open_library_lookup')) {

    if (!function_exists('isbn_normalize')) {
        function isbn_normalize(string $isbn): string {
            return strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn));
        }
    }

    function open_library_extract_description($value): string {
        if (is_array($value)) {
            $value = $value['value'] ?? ($value[0] ?? '');
        }
        $str = trim((string)$value);
        if ($str === '') return '';
        if (function_exists('clean_book_description')) {
            return clean_book_description($str);
        }
        return $str;
    }

    function open_library_lookup(string $isbn): ?array {
        $isbn = isbn_normalize($isbn);
        if ($isbn === '' || !in_array(strlen($isbn), [10,13], true)) return null;

        // 1. Search endpoint — sumber utama judul/penulis/subject/cover_i
        $searchData = isbn_api_get('https://openlibrary.org/search.json?isbn=' . rawurlencode($isbn) . '&limit=1');
        $doc = $searchData['docs'][0] ?? null;
        if (!$doc) {
            // Coba tetap via edition endpoint jika search kosong
            $doc = null;
        } else {
            // Validasi minimal: judul harus ada, jika tidak ada doc tetap bisa lanjut via edition
            if (empty($doc['title'])) {
                // Biarkan saja, nanti coba edition
            }
        }

        $judul = '';
        $penulis = '';
        $penerbit = '';
        $tahun = '';
        $subjects = [];
        $coverUrl = null;
        $language = '';

        if (is_array($doc)) {
            $judul = trim((string)($doc['title'] ?? ''));
            if (!empty($doc['author_name']) && is_array($doc['author_name'])) {
                $penulis = implode(', ', array_slice(array_map('trim', $doc['author_name']), 0, 3));
            }
            $penerbit = trim((string)(($doc['publisher'] ?? [])[0] ?? ''));
            $tahunInt = (int)($doc['first_publish_year'] ?? 0);
            if (!$tahunInt && !empty($doc['publish_year'][0])) $tahunInt = (int)$doc['publish_year'][0];
            $tahun = $tahunInt ? (string)$tahunInt : '';
            $subjects = array_values(array_filter(array_map('trim', array_slice($doc['subject'] ?? [], 0, 30))));
            if (!empty($doc['cover_i'])) {
                $coverUrl = 'https://covers.openlibrary.org/b/id/' . (int)$doc['cover_i'] . '-L.jpg?default=false';
            }
            // Language dari search (kadang ada)
            if (!empty($doc['language'][0])) {
                $lang = $doc['language'][0];
                $language = is_array($lang) ? ($lang[0] ?? '') : (string)$lang;
            }
        }

        // Jika search gagal total (doc null), kita tetap coba edition/work tapi judul mungkin dari edition
        $deskripsi = '';

        // 2. Edition via ISBN
        $edition = isbn_api_get('https://openlibrary.org/isbn/' . rawurlencode($isbn) . '.json');
        $workKey = null;
        if (is_array($edition)) {
            // Judul dari edition jika belum ada
            if ($judul === '' && !empty($edition['title'])) $judul = trim((string)$edition['title']);
            // Penulis dari edition (kadang hanya key, tidak nama — skip jika sudah ada dari search)
            // Penerbit
            if ($penerbit === '' && !empty($edition['publishers'][0])) {
                $pub = $edition['publishers'][0];
                $penerbit = trim((string)($pub['name'] ?? $pub));
            }
            // Tahun
            if ($tahun === '' && !empty($edition['publish_date']) && preg_match('/(\d{4})/', (string)$edition['publish_date'], $m)) {
                $tahun = $m[1];
            }
            // Deskripsi dari edition — valid karena ini field description, bukan first_sentence
            if ($deskripsi === '' && !empty($edition['description'])) {
                $deskripsi = open_library_extract_description($edition['description']);
            }
            // Cover dari edition
            if (!$coverUrl && !empty($edition['covers'][0]) && (int)$edition['covers'][0] > 0) {
                $coverUrl = 'https://covers.openlibrary.org/b/id/' . (int)$edition['covers'][0] . '-L.jpg?default=false';
            }
            // Work key
            if (!empty($edition['works'][0]['key'])) {
                $workKey = $edition['works'][0]['key'];
            }
            // Bahasa
            if ($language === '' && !empty($edition['languages'][0]['key'])) {
                $language = basename((string)$edition['languages'][0]['key']);
            }
            // Subjects tambahan dari edition (rare)
            if (!empty($edition['subjects']) && is_array($edition['subjects'])) {
                foreach (array_slice($edition['subjects'], 0, 15) as $s) {
                    $s = trim(is_array($s) ? ($s['name'] ?? '') : (string)$s);
                    if ($s !== '' && !in_array($s, $subjects, true)) $subjects[] = $s;
                }
                $subjects = array_slice($subjects, 0, 30);
            }
            // ISBN validation: edition memiliki isbn_10 / isbn_13 — cek kecocokan
            // Jika edition ada tapi isbn tidak cocok, anggap mismatch? Tapi endpoint /isbn/{isbn} sudah by isbn, jadi pasti cocok jika sukses
        }

        // 3. Work endpoint — sumber paling andal untuk deskripsi & subjects
        // Selalu coba ambil Work jika tersedia, karena Work menyimpan subjects & deskripsi terbaik.
        // Deskripsi hanya diisi jika masih kosong, subjects selalu digabungkan.
        if (!$workKey && is_array($doc) && !empty($doc['key']) && strpos((string)$doc['key'], '/works/') === 0) {
            $workKey = $doc['key'];
        }
        if ($workKey) {
            $work = isbn_api_get('https://openlibrary.org' . $workKey . '.json');
            if (is_array($work)) {
                if ($deskripsi === '' && !empty($work['description'])) {
                    $deskripsi = open_library_extract_description($work['description']);
                }
                // Cover dari Work jika edition tidak punya
                if (!$coverUrl && !empty($work['covers'][0]) && (int)$work['covers'][0] > 0) {
                    $coverUrl = 'https://covers.openlibrary.org/b/id/' . (int)$work['covers'][0] . '-L.jpg?default=false';
                }
                // Subjects dari Work (subject, subject_places, subject_people, subject_times)
                $workSubjects = [];
                foreach (['subjects','subject_places','subject_people','subject_times'] as $field) {
                    if (!empty($work[$field]) && is_array($work[$field])) {
                        foreach ($work[$field] as $s) {
                            $s = trim((string)$s);
                            if ($s !== '' && !in_array($s, $workSubjects, true)) $workSubjects[] = $s;
                        }
                    }
                }
                if (!empty($workSubjects)) {
                    // Prioritaskan subjects yang mengandung kata kunci kategori agar deteksi lebih akurat.
                    // Untuk Harry Potter, Fantasy/Magic ada di posisi 70, jika hanya ambil 15 pertama akan terlewat.
                    $priorityKeywords = ['fiction','fantasy','magic','wizard','children','juvenile','novel','story','science','history','biography','technology','computer','programming','education','business','psychology','religion','islam','christian','physics','chemistry','biology','mathematics','engineering','health','medicine','art','music','poetry','drama','adventure','romance','mystery','thriller','horror','crime','sastra','sejarah','agama','anak','remaja'];
                    usort($workSubjects, function($a,$b) use ($priorityKeywords) {
                        $aLow = strtolower($a);
                        $bLow = strtolower($b);
                        $aScore = 0; $bScore = 0;
                        foreach ($priorityKeywords as $kw) {
                            if (strpos($aLow, $kw) !== false) $aScore++;
                            if (strpos($bLow, $kw) !== false) $bScore++;
                        }
                        if ($aScore !== $bScore) return $bScore <=> $aScore;
                        // Jika skor sama, prioritaskan yang lebih pendek dan English-like (hanya ASCII)
                        $aAscii = preg_match('/^[\x20-\x7E]+$/', $a) ? 1 : 0;
                        $bAscii = preg_match('/^[\x20-\x7E]+$/', $b) ? 1 : 0;
                        if ($aAscii !== $bAscii) return $bAscii <=> $aAscii;
                        return strlen($a) <=> strlen($b);
                    });
                    foreach (array_slice($workSubjects, 0, 30) as $s) {
                        if (!in_array($s, $subjects, true)) $subjects[] = $s;
                    }
                    $subjects = array_slice($subjects, 0, 30);
                }
            }
        }

        // 4. Fallback edition_key dari search
        if ($deskripsi === '' && is_array($doc) && !empty($doc['edition_key'][0])) {
            $editionKey = $doc['edition_key'][0];
            $edition2 = isbn_api_get('https://openlibrary.org/books/' . rawurlencode($editionKey) . '.json');
            if (is_array($edition2)) {
                if ($penerbit === '' && !empty($edition2['publishers'][0])) {
                    $penerbit = trim((string)($edition2['publishers'][0]['name'] ?? $edition2['publishers'][0]));
                }
                if ($tahun === '' && !empty($edition2['publish_date']) && preg_match('/(\d{4})/', (string)$edition2['publish_date'], $m)) {
                    $tahun = $m[1];
                }
                if (!empty($edition2['description'])) {
                    $tmp = open_library_extract_description($edition2['description']);
                    if ($tmp !== '') $deskripsi = $tmp;
                }
                if (!$coverUrl && !empty($edition2['covers'][0]) && (int)$edition2['covers'][0] > 0) {
                    $coverUrl = 'https://covers.openlibrary.org/b/id/' . (int)$edition2['covers'][0] . '-L.jpg?default=false';
                }
            }
        }

        // Jika masih belum ada judul, berarti tidak ditemukan
        if ($judul === '') return null;

        // Subjects sudah filtered, pastikan string bersih
        $subjects = array_values(array_filter(array_map('trim', $subjects)));
        // Language normalize
        $language = trim((string)$language);

        return [
            'isbn' => $isbn,
            'judul' => $judul,
            'penulis' => $penulis,
            'penerbit' => $penerbit,
            'tahun_terbit' => $tahun,
            'deskripsi' => $deskripsi ?? '',
            'cover_url' => $coverUrl,
            'kategori' => null,
            'subjects' => $subjects,
            'language' => $language,
            'source' => 'Open Library',
            'raw_isbns' => [$isbn],
        ];
    }
}
