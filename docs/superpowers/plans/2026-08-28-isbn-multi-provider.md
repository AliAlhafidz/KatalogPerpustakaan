# ISBN Multi-Provider Metadata Lookup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Perbaiki fitur penambahan buku via ISBN menjadi sistem multi-provider (Google Books + Open Library) dengan fallback, normalisasi metadata, prioritas deskripsi yang ketat (tanpa first_sentence), ISBN matching, auto-kategori scoring, dan hapus duplikasi request browser.

**Architecture:** Server-side orchestrator di `includes/book_metadata.php` yang memanggil provider terpisah (`includes/book_providers/google_books.php`, `open_library.php`), merge field terbaik, validasi deskripsi, scoring kategori dari DB, cache 24 jam hasil ternormalisasi, response JSON dengan sumber. JavaScript hanya call `cari_isbn.php`.

**Tech Stack:** PHP 8+ native, cURL/file_get_contents, MySQL PDO, Vanilla JS, .env untuk API key opsional.

**Spec:** Request user 2026-08-28 — 20 poin perbaikan ISBN, fokus akurasi ISBN, deskripsi, kategori.

## Global Constraints

- Jangan pakai first_sentence sebagai deskripsi; jika tidak ada sinopsis valid maka deskripsi = "".
- Jangan bocorkan API key ke client; key via .env (GOOGLE_BOOKS_API_KEY, ISBNDB_API_KEY) opsional.
- Tetap pertahankan input manual dan kategori manual fallback (kategori null jika tidak cocok).
- Cache TTL default 86400 detik, struktur mudah diubah.
- Timeout API 5s connect / 10s total, SSL verify, handle error/empty/rate-limit tanpa gagalkan keseluruhan.
- Jangan tambah dependency besar; ikuti struktur PHP/JS existing.
- Jangan ubah schema DB kecuali perlu; no migration baru jika tidak perlu.

---

## File Structure

- Modify: `includes/isbn.php` — pertahankan `isbn_api_get`, `isbn_cache_*`; tambah helper `isbn_normalize`, tapi logic utama pindah ke book_metadata.
- Create: `includes/book_metadata.php` — orchestrator, `clean_book_description()`, `normalize_book_metadata()`, `merge_book_metadata()`, `detect_category()`, `search_book_by_isbn()`, `isbn_match_check()`.
- Create: `includes/book_providers/google_books.php` — `google_books_lookup(string $isbn): ?array` normalized, validasi ISBN match via industryIdentifiers.
- Create: `includes/book_providers/open_library.php` — `open_library_lookup(string $isbn): ?array` normalized, multi-endpoint (search, isbn, work, edition).
- Modify: `admin/buku/cari_isbn.php` — hapus logic monolitik, panggil `search_book_by_isbn()`, return normalized JSON dengan `sumber` array.
- Modify: `assets/js/buku-isbn.js` — hapus fetch Google Books dari browser, hanya fetch cari_isbn.php, isi form, sanitasi cover url via DOM, tampil sumber.
- Modify: `includes/functions.php:download_remote_cover()` — izinkan host googleusercontent / google books cover.
- Modify: `.env.example` — tambah `GOOGLE_BOOKS_API_KEY=` `ISBNDB_API_KEY=`.
- Modify: `admin/buku/_form.php` — update help text dari "Open Library" menjadi "Google Books + Open Library".
- Modify: `admin/buku/import.php` — ganti duplikasi logic dengan `search_book_by_isbn()` agar konsisten.
- Test: `tests/IsbnMetadataTest.php` (atau perluas FunctionsTest) — unit untuk clean_description, merge, category scoring, isbn match.

---

### Task 1: Helper Normalisasi & Validasi Deskripsi

**Files:**
- Create: `includes/book_metadata.php` (bagian awal: clean_book_description, helpers)
- Modify: `includes/isbn.php` (tambah normalize helper jika perlu)

**Interfaces:**
- Consumes: raw string dari provider
- Produces: `clean_book_description(string $raw): string` — strip HTML, decode entities, trim whitespace, tolak jika terlalu pendek (<30 char) atau menyerupai subject list, kembalikan "" jika invalid. `normalize_isbn(string $isbn): string` — hapus non-0-9X. `isbn_normalized_match(string $search, array $candidates): bool`

- [ ] **Step 1: Buat failing test untuk clean_book_description**

```php
// tests/IsbnMetadataTest.php
require 'includes/book_metadata.php';
assert(clean_book_description('<p>Hello<br>World &amp; test</p>') === "Hello\nWorld & test");
assert(clean_book_description('') === "");
assert(clean_book_description('Subject: Fiction, Science') === "" || strlen <30);
assert(clean_book_description(str_repeat('a', 20)) === ""); // terlalu pendek
```

- [ ] **Step 2: Run test -> FAIL**

Run: `php tests/IsbnMetadataTest.php`
Expected: FAIL function not found

- [ ] **Step 3: Implement clean_book_description**

- strip_tags dengan preserve paragraf: ganti <br>, </p>, </div> jadi \n sebelum strip
- html_entity_decode ENT_QUOTES|ENT_HTML5
- preg_replace whitespace berlebihan, trim
- tolak jika panjang <30 atau mengandung pola "Subjects:" atau 80% comma-separated capitals
- jangan kembalikan array/object

- [ ] **Step 4: Run test -> PASS**

- [ ] **Step 5: Commit**

---

### Task 2: Provider Google Books

**Files:**
- Create: `includes/book_providers/google_books.php`

**Interfaces:**
- Consumes: `$isbn` string bersih
- Produces: `google_books_lookup(string $isbn): ?array` — return normalized subset `['judul'=>..., 'penulis'=>..., 'penerbit'=>..., 'tahun_terbit'=>..., 'deskripsi'=>cleaned, 'cover_url'=>https, 'subjects'=>[], 'language'=>..., 'source'=>'Google Books', 'raw_isbns'=>[]]` atau null jika gagal/tidak match. Validasi ISBN match via volumeInfo.industryIdentifiers.

- [ ] **Step 1: Test lookup dengan mock json**

```php
$sample = json_decode('{"items":[{"volumeInfo":{"title":"Test","authors":["A"],"publisher":"P","publishedDate":"2020-05-01","description":"<p>Desc</p>","industryIdentifiers":[{"type":"ISBN_13","identifier":"9781234567890"}],"categories":["Computers"],"imageLinks":{"thumbnail":"http://books.google.com/thumb.jpg"}}}]}', true);
// expect normalized judul Test, deskripsi cleaned, tahun 2020, isbn match
```

- [ ] **Step 2: Run fail**

- [ ] **Step 3: Implement**
- URL: `https://www.googleapis.com/books/v1/volumes?q=isbn:ISBN&maxResults=1` + `&key=KEY` jika env ada
- Use isbn_api_get with timeout
- Validasi industryIdentifiers normalized vs search isbn (handle ISBN-10 vs 13)
- If mismatch and more than 1 item? Check items[0] only but validate; if mismatch return null
- Extract fields, clean description via clean_book_description, subjects = categories, cover https replace http, language

- [ ] **Step 4: Verify pass**

- [ ] **Step 5: Commit**

---

### Task 3: Provider Open Library (tanpa first_sentence)

**Files:**
- Create: `includes/book_providers/open_library.php`

**Interfaces:**
- Produces: `open_library_lookup(string $isbn): ?array` — multi-endpoint fetch, return normalized atau null.

- [ ] **Step 1: Test dengan mock search/work/edition**

```php
// mock search doc tanpa first_sentence -> deskripsi harus "" bukan first_sentence
$doc = ['title'=>'T','author_name'=>['A'],'publisher'=>['P'],'first_publish_year'=>2020,'subject'=>['Fiction'],'first_sentence'=>['fake sentence should be ignored']];
// expect deskripsi "" sebelum work fetch, bukan fake sentence
```

- [ ] **Step 2: Run fail**

- [ ] **Step 3: Implement**
- Fetch search.json?isbn=ISBN&limit=1 via isbn_api_get
- If doc null => return null
- judul, penulis (implode 3), penerbit [0], tahun first_publish_year / publish_year[0], subjects slice 20, cover_i => covers.openlibrary.org, language ?
- JANGAN pakai first_sentence.
- Fetch edition /isbn/ISBN.json => lengkapi penerbit, tahun, description via extractDescription, covers
- Fetch workKey (edition works[0] key atau doc key /works/) => description dari work, covers
- Fetch edition_key if deskripsi masih "" => books/editionKey.json
- All description via clean_book_description? At least trim then clean later in merge; but provider should return cleaned already.
- Validate isbn? Open Library search already by isbn, so assume match, but check edition identifiers if present.
- subjects gabung

- [ ] **Step 4: Pass**

- [ ] **Step 5: Commit**

---

### Task 4: Merge & Kategori Scoring

**Files:**
- Modify: `includes/book_metadata.php` — tambah `merge_book_metadata(array $results): array`, `detect_category(PDO $pdo, array $subjects, string $description, array $categories): ?array`

**Interfaces:**
- Consumes: array of provider normalized results `['Google Books'=>dataOrNull, 'Open Library'=>dataOrNull]`
- Produces: `['isbn'=>..., 'judul'=>..., 'penulis'=>..., 'penerbit'=>..., 'tahun_terbit'=>..., 'deskripsi'=>..., 'cover_url'=>..., 'kategori'=>['id'=>, 'nama'=>] or null, 'subjects'=>[], 'language'=>..., 'sumber'=>['primary'=>..., 'providers'=>[]]]`

Kategori scoring:
- Fetch kategori list dari DB (SELECT id_kategori, nama_kategori)
- Build keyword mapping fleksibel: normalization lower, alias map (contoh: "science fiction" => fiksi, "computer programming" => sains & teknologi, etc.) tapi general scoring via token match.
- Scoring: subject exact (+10), category exact (+10), genre/keyword (+5), description contains (+2). Implement tokenized.
- If no score > threshold (misal 5), return null.
- Mapping alias: buat array alias hardcoded untuk kategori default (Fiksi, Non-Fiksi, Sains & Teknologi, Sejarah, Agama, Anak & Remaja) dengan synonyms expand.

- [ ] **Step 1: Test merge - google has deskripsi, OL has judul**
```php
$g = ['judul'=>'G Title','deskripsi'=>'G Desc clean','penerbit'=>'','cover_url'=>'g.jpg','subjects'=>['Computers']];
$o = ['judul'=>'O Title','deskripsi'=>'','penerbit'=>'OL Pub','cover_url'=>'','subjects'=>['Fiction']];
// merged judul G Title (priority google?), deskripsi G Desc, penerbit OL Pub, cover g.jpg
```
Prioritas field: judul google > OL, penulis google > OL, penerbit google? Actually spec: choose best available; but for simplicity: first non-empty dari urutan Google -> OpenLibrary. For deskripsi: google valid > OL work > empty.

Cover: first valid https.

- [ ] **Step 2: Test category scoring**

```php
// subjects ["Computer Programming","Software"] should map to Sains & Teknologi
// subjects ["Fiction / Science Fiction"] -> Fiksi
// subjects ["Cooking"] -> null (no match)
```

- [ ] **Step 3: Implement merge + detect**

- [ ] **Step 4: Verify**

- [ ] **Step 5: Commit**

---

### Task 5: Orchestrator search_book_by_isbn + Cache + Cari_isbn.php

**Files:**
- Modify: `includes/book_metadata.php` — tambah `search_book_by_isbn(PDO $pdo, string $isbn, int $ttl=86400): array`
- Modify: `admin/buku/cari_isbn.php` — refactor to call orchestrator
- Modify: `includes/isbn.php` — ensure cache helpers support ttl param easily changed; keep isbn_api_get

Flow:
```
search_book_by_isbn:
  normalize isbn, validate 10/13
  cache_get(ttl) -> if hit return cached (with sumber)
  try google_books_lookup (try/catch, null on fail)
  try open_library_lookup (try/catch)
  if both null => return success false "tidak ditemukan"
  merge => normalized
  detect kategori via DB (pdo query)
  build payload success true data + sumber
  cache_set payload
  return payload
```

Error handling: jangan throw ke caller, tangkap Throwable per provider, log error_log.

- [ ] **Step 1: Test orchestrator mock - OL fail google success => success true**
- [ ] **Step 2: Implement**
- [ ] **Step 3: Refactor cari_isbn.php to 30 lines: validate, cache via orchestrator, echo json, header json**
- Response format: sama seperti sebelumnya tapi `sumber` jadi `['primary'=> 'Google Books', 'providers'=>['Google Books','Open Library']]` atau string? Spec minta providers list. Keep backward compat: data.sumber string + data.providers array atau sumber object. Usulan: `data['sumber']` tetap string primary untuk kompatibilitas, tambah `data['providers']` array dan `data['sumber_detail']` object. Atau ubah jadi `sumber` => object. Simpler: `sumber` => string primary, tambah `providers` => array. JS akan cek both.
- Ensure payload includes semua field normalized.

- [ ] **Step 4: Manual test via php -l**

- [ ] **Step 5: Commit**

---

### Task 6: JavaScript Cleanup (hapus duplikasi Google Books)

**Files:**
- Modify: `assets/js/buku-isbn.js`
- Modify: `admin/buku/_form.php` help text

JS baru:
- hapus block `if (!d.deskripsi) { fetch google ... }` 20 baris
- fill fields via `fill()` aman
- cover preview: create img via `document.createElement('img')` bukan innerHTML string concat (avoid XSS)
- tampil status sumber: `Data dari ${d.sumber || d.providers.join(', ')}`
- ensure no innerHTML with api data unsanitized
- activate tab, set cover_api_url
- handle error: "Buku tidak ditemukan. Silakan lengkapi manual."

Form: ganti teks "Data akan diambil dari Open Library." -> "Data akan diambil dari Google Books + Open Library (otomatis fallback)."

- [ ] **Step 1: Verify existing JS has google fetch -> harus hilang setelah edit**

```bash
grep -c "googleapis" assets/js/buku-isbn.js  # expected 0 after
```

- [ ] **Step 2: Implement**

- [ ] **Step 3: Test manual browsing**

- [ ] **Step 4: Commit**

---

### Task 7: Security & Cover Host + Env

**Files:**
- Modify: `includes/functions.php:download_remote_cover()` — tambah allow `books.googleusercontent.com`, `books.google.com` (thumbnail domain) plus `covers.openlibrary.org`
- Modify: `.env.example` — add keys
- Modify: `config/env.php` atau `config/database.php` — ensure getenv untuk GOOGLE_BOOKS_API_KEY readable (no leak)
- Modify: `admin/buku/import.php` — hapus duplikasi 100 baris, ganti dengan `search_book_by_isbn()` call

Cover host allow list:
```php
$allowed_hosts = ['covers.openlibrary.org','books.googleusercontent.com','books.google.com','lh3.googleusercontent.com'];
```

Check scheme https only, size limit already.

- [ ] **Step 1: Test download host validation**

```php
assert(download_remote_cover('https://evil.com/cover.jpg') throws)
assert(allowed google host passes host check)
```

- [ ] **Step 2: Implement**

- [ ] **Step 3: Refactor import.php loop to call search_book_by_isbn**

- [ ] **Step 4: php -l all**

- [ ] **Step 5: Commit**

---

### Task 8: Testing & Verifikasi Akhir

**Files:**
- Create/Modify: `tests/IsbnMetadataTest.php` lengkap
- Test scenarios dari spec §18

Checklist:
- [ ] php -l semua file PHP
- [ ] grep "first_sentence" => 0 hasil di logic baru (hanya di test maybe)
- [ ] grep "googleapis" di buku-isbn.js => 0
- [ ] grep "GOOGLE_BOOKS_API_KEY" hanya di server files (book_providers, env) tidak di JS
- [ ] Test ISBN populer (9780134685991 Effective Java) mock atau live jika ada internet — validate judul, penulis, deskripsi ada, kategori terisi
- [ ] Test ISBN invalid 123 => error
- [ ] Test missing description => deskripsi "" bukan first_sentence
- [ ] Test HTML description cleaning
- [ ] Test category scoring: Computer -> Sains & Teknologi, Sci-Fi -> Fiksi
- [ ] Test provider failure simulation (return null) => still success if other provider succeeds
- [ ] Verify cache stores normalized (bukan raw)
- [ ] Verify no duplicate request: JS hanya 1 fetch ke cari_isbn.php
- [ ] Verify .env.example updated
- [ ] Dokumentasi perubahan (list file diubah)

- [ ] **Commit final + ringkasan**

