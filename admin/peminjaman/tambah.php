<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$errors = [];
$hari_ini = date('Y-m-d');
$tanggal_pinjam_input = $_POST['tanggal_pinjam'] ?? $hari_ini;
$id_anggota_input = (int) ($_POST['id_anggota'] ?? 0);

// Support multi-buku: terima id_buku sebagai array (id_buku[]) atau single value legacy
$raw_buku = $_POST['id_buku'] ?? [];
if (!is_array($raw_buku)) {
    $raw_buku = $raw_buku !== '' && $raw_buku !== null ? [$raw_buku] : [];
}
$id_buku_inputs = array_values(array_filter(array_map('intval', $raw_buku), fn($v) => $v > 0));
$id_buku_inputs = array_values(array_unique($id_buku_inputs));

// Untuk kompatibilitas tampilan awal / fallback display single
$id_buku_input_legacy = $id_buku_inputs[0] ?? 0;

// Jatuh tempo dihitung setelah validasi, fallback untuk tampilan awal
$jatuh_tempo_preview = date('Y-m-d', strtotime('+' . LAMA_PINJAM_HARI . ' days', strtotime($tanggal_pinjam_input)));
$jatuh_tempo = $jatuh_tempo_preview;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($id_anggota_input <= 0) $errors[] = 'Pilih anggota terlebih dahulu.';
    if (empty($id_buku_inputs)) $errors[] = 'Pilih minimal satu buku terlebih dahulu.';
    // cegah duplikat: jika raw count != unique count (user mengirim duplikat via manipulasi)
    $raw_count = is_array($_POST['id_buku'] ?? null) ? count(array_filter(array_map('intval', $_POST['id_buku']), fn($v)=>$v>0)) : 0;
    if ($raw_count > 0 && $raw_count !== count($id_buku_inputs)) {
        $errors[] = 'Terdapat buku duplikat dalam pilihan. Pilih setiap buku hanya sekali.';
    }

    // Validasi tanggal pinjam agar admin hanya dapat mencatat transaksi hari ini atau tanggal sebelumnya.
    $tanggal_valid = DateTime::createFromFormat('Y-m-d', $tanggal_pinjam_input);
    if (!$tanggal_valid || $tanggal_valid->format('Y-m-d') !== $tanggal_pinjam_input) {
        $errors[] = 'Tanggal pinjam tidak valid.';
    } elseif ($tanggal_pinjam_input > $hari_ini) {
        $errors[] = 'Tanggal pinjam tidak boleh melebihi hari ini.';
    } elseif (is_hari_minggu($tanggal_pinjam_input)) {
        $errors[] = 'Peminjaman tidak dapat dicatat pada hari Minggu karena perpustakaan tutup.';
    }

    if (empty($errors)) {
        // Pastikan anggota aktif
        $stmt = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = :id");
        $stmt->execute([':id' => $id_anggota_input]);
        $anggota = $stmt->fetch();
        if (!$anggota || $anggota['status'] !== 'aktif') {
            $errors[] = 'Anggota tidak ditemukan atau berstatus nonaktif.';
        }

        // Validasi setiap buku: harus ada, tidak diarsip, stok tersedia
        $buku_map = [];
        if (empty($errors)) {
            // Ambil semua buku yang diminta
            $placeholders = implode(',', array_fill(0, count($id_buku_inputs), '?'));
            $stmt = $pdo->prepare("SELECT * FROM buku WHERE id_buku IN ($placeholders)");
            $stmt->execute($id_buku_inputs);
            $fetched = $stmt->fetchAll();
            $fetched_map = [];
            foreach ($fetched as $fb) $fetched_map[(int)$fb['id_buku']] = $fb;

            foreach ($id_buku_inputs as $bid) {
                if (!isset($fetched_map[$bid])) {
                    $errors[] = 'Salah satu buku tidak ditemukan (ID: ' . $bid . ').';
                    break;
                }
                $b = $fetched_map[$bid];
                if (!empty($b['is_arsip'])) {
                    $errors[] = 'Buku "' . $b['judul'] . '" sedang diarsipkan dan tidak dapat dipinjam.';
                    break;
                }
                if ((int)$b['tersedia'] < 1) {
                    $errors[] = 'Buku "' . $b['judul'] . '" stok tersedia habis.';
                    break;
                }
                $buku_map[$bid] = $b;
            }
        }

        // Batasi jumlah peminjaman aktif per anggota (maks 3 buku)
        // Hitung aktif saat ini + yang akan dipinjam
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id AND status = 'dipinjam'");
            $stmt->execute([':id' => $id_anggota_input]);
            $aktif = (int) $stmt->fetchColumn();
            $akan = count($id_buku_inputs);
            if ($aktif + $akan > 3) {
                $sisa = max(0, 3 - $aktif);
                if ($sisa === 0) {
                    $errors[] = 'Anggota ini sudah meminjam 3 buku (batas maksimal) dan belum mengembalikannya.';
                } else {
                    $errors[] = 'Anggota ini sudah meminjam ' . $aktif . ' buku aktif. Hanya bisa menambah ' . $sisa . ' buku lagi (maks 3). Kamu memilih ' . $akan . ' buku.';
                }
            }
        }
    }

    if (empty($errors)) {
        $tanggal_pinjam = $tanggal_pinjam_input;
        $jatuh_tempo = date('Y-m-d', strtotime('+' . LAMA_PINJAM_HARI . ' days', strtotime($tanggal_pinjam)));

        $pdo->beginTransaction();
        try {
            // Kunci semua baris buku yang akan dipinjam
            $locked_books = [];
            // Sort ids untuk cegah deadlock (konsisten order)
            $sorted_ids = $id_buku_inputs;
            sort($sorted_ids);
            foreach ($sorted_ids as $bid) {
                $stmt = $pdo->prepare("SELECT * FROM buku WHERE id_buku = :id FOR UPDATE");
                $stmt->execute([':id' => $bid]);
                $bt = $stmt->fetch();
                if (!$bt || (int)$bt['tersedia'] < 1) {
                    throw new Exception('Stok buku "' . ($bt['judul'] ?? 'ID '.$bid) . '" sudah habis. Silakan pilih buku lain.');
                }
                if (!empty($bt['is_arsip'])) {
                    throw new Exception('Buku "' . $bt['judul'] . '" sedang diarsipkan dan tidak dapat dipinjam.');
                }
                $locked_books[$bid] = $bt;
            }

            // Re-check batas setelah lock (cek ulang jumlah aktif)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM peminjaman WHERE id_anggota = :id AND status = 'dipinjam'");
            $stmt->execute([':id' => $id_anggota_input]);
            if ((int)$stmt->fetchColumn() + count($id_buku_inputs) > 3) {
                throw new Exception('Anggota ini sudah mencapai batas maksimal 3 buku aktif.');
            }

            $inserted_ids = [];
            foreach ($id_buku_inputs as $bid) {
                $stmt = $pdo->prepare("INSERT INTO peminjaman (id_anggota, id_buku, tanggal_pinjam, tanggal_jatuh_tempo, status, diproses_oleh)
                                        VALUES (:anggota, :buku, :pinjam, :tempo, 'dipinjam', :admin)");
                $stmt->execute([
                    ':anggota' => $id_anggota_input, ':buku' => $bid,
                    ':pinjam' => $tanggal_pinjam, ':tempo' => $jatuh_tempo, ':admin' => $_SESSION['id_admin'],
                ]);
                $inserted_ids[] = $pdo->lastInsertId();
                $stmt = $pdo->prepare("UPDATE buku SET tersedia = tersedia - 1 WHERE id_buku = :id AND tersedia > 0");
                $stmt->execute([':id' => $bid]);
                if ($stmt->rowCount() !== 1) {
                    throw new Exception('Stok buku "' . $locked_books[$bid]['judul'] . '" berubah. Peminjaman dibatalkan, silakan coba lagi.');
                }
            }
            $pdo->commit();

            // Notifikasi: satu notifikasi ringkas untuk multi-buku agar tidak spam, tetap kompatibel
            $judul_notif = count($id_buku_inputs) === 1
                ? 'Peminjaman berhasil dicatat'
                : 'Peminjaman ' . count($id_buku_inputs) . ' buku berhasil dicatat';
            if (count($id_buku_inputs) === 1) {
                $b = $locked_books[$id_buku_inputs[0]];
                $pesan_notif = 'Buku "' . $b['judul'] . '" berhasil dipinjam. Jatuh tempo: ' . format_tanggal($jatuh_tempo) . '.';
            } else {
                $judul_list = array_map(fn($bid) => $locked_books[$bid]['judul'], $id_buku_inputs);
                $pesan_notif = 'Berhasil meminjam ' . count($id_buku_inputs) . ' buku: "' . implode('", "', array_slice($judul_list, 0, 3)) . '"' . (count($judul_list) > 3 ? ' dan ' . (count($judul_list)-3) . ' lainnya' : '') . '. Jatuh tempo: ' . format_tanggal($jatuh_tempo) . '.';
            }
            // kunci_unik per transaksi batch agar tidak duplikat jika di-retry
            $kunci_unik = 'pinjam:batch:' . $id_anggota_input . ':' . $tanggal_pinjam . ':' . implode('-', $sorted_ids) . ':' . time();
            buat_notifikasi_anggota($pdo, $id_anggota_input, $judul_notif, $pesan_notif, 'success', '/anggota/peminjaman.php', $kunci_unik);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getMessage();
        }

        if (empty($errors)) {
            $msg = count($id_buku_inputs) === 1
                ? 'Peminjaman berhasil dicatat. Jatuh tempo: ' . format_tanggal($jatuh_tempo) . '.'
                : 'Peminjaman ' . count($id_buku_inputs) . ' buku berhasil dicatat. Jatuh tempo: ' . format_tanggal($jatuh_tempo) . '.';
            set_flash('sukses', $msg);
            redirect('/admin/peminjaman/index.php');
        }
    }
}

$anggota_list_raw = $pdo->query("SELECT id_anggota, nomor_anggota, nama, foto, status FROM anggota WHERE status = 'aktif' ORDER BY nama ASC")->fetchAll();
$buku_list_raw = $pdo->query("SELECT id_buku, kode_buku, judul, penulis, cover, stok, tersedia, is_arsip FROM buku WHERE is_arsip = 0 ORDER BY judul ASC")->fetchAll();

// Precompute URLs for JS
$anggota_list = [];
foreach ($anggota_list_raw as $a) {
    $anggota_list[] = [
        'id_anggota' => (int)$a['id_anggota'],
        'nomor_anggota' => $a['nomor_anggota'],
        'nama' => $a['nama'],
        'foto' => $a['foto'],
        'foto_url' => foto_profil_url($a['foto'] ?? null),
    ];
}
$buku_list = [];
foreach ($buku_list_raw as $b) {
    $buku_list[] = [
        'id_buku' => (int)$b['id_buku'],
        'kode_buku' => $b['kode_buku'],
        'judul' => $b['judul'],
        'penulis' => $b['penulis'],
        'cover' => $b['cover'],
        'cover_url' => cover_url($b['cover']),
        'cover_thumb' => cover_thumb_url($b['cover']),
        'stok' => (int)$b['stok'],
        'tersedia' => (int)$b['tersedia'],
        'is_arsip' => !empty($b['is_arsip']),
    ];
}

// Untuk repopulate foto/nama anggota terpilih
$anggota_terpilih = null;
if ($id_anggota_input > 0) {
    foreach ($anggota_list as $al) if ($al['id_anggota'] === $id_anggota_input) { $anggota_terpilih = $al; break; }
}
// Untuk repopulate buku terpilih
$buku_terpilih_map = [];
foreach ($buku_list as $bl) {
    if (in_array($bl['id_buku'], $id_buku_inputs, true)) $buku_terpilih_map[$bl['id_buku']] = $bl;
}

$menu_aktif = 'peminjaman';
$page_title = 'Catat Peminjaman';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
?>

<div class="w-full max-w-2xl">
  <div class="flex items-center gap-3 mb-6">
    <a href="<?= BASE_URL ?>/admin/peminjaman/index.php" class="text-gray-400 hover:text-gray-600">&larr;</a>
    <h1 class="text-xl font-bold text-gray-800">Catat Peminjaman Baru</h1>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg border px-4 py-3 text-sm" style="background: var(--badge-red-bg); border-color: var(--badge-red-border); color: var(--badge-red-text)">
      <ul class="list-disc list-inside"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" action="" class="rounded-2xl border shadow-soft p-4 sm:p-6 space-y-4" style="background: var(--surface); border-color: var(--border)" id="peminjamanForm" novalidate>
      <?= csrf_field() ?>

    <!-- Picker Anggota -->
    <div>
      <label class="block text-sm font-medium mb-1" style="color: var(--text-muted)">Anggota *</label>
      <button type="button" id="anggotaPickerBtn" aria-haspopup="dialog" aria-controls="anggotaModal"
              class="w-full flex items-center gap-3 border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition text-left group" style="border-color: var(--border); background: var(--surface); color: var(--text)">
        <span id="anggotaPickerIcon" class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 overflow-hidden border" style="background: var(--surface-2); color: var(--text-faint); border-color: var(--border)">
          <?php if ($anggota_terpilih): ?>
            <img src="<?= e($anggota_terpilih['foto_url']) ?>" alt="Foto <?= e($anggota_terpilih['nama']) ?>" class="w-full h-full object-cover">
          <?php else: ?>
            <i class="bi bi-person text-lg"></i>
          <?php endif; ?>
        </span>
        <span class="flex-1 min-w-0">
          <span id="anggotaPickerLabel" class="block text-sm font-semibold truncate" style="color: var(--text)">
            <?= $anggota_terpilih ? e($anggota_terpilih['nomor_anggota'] . ' — ' . $anggota_terpilih['nama']) : 'Pilih Anggota' ?>
          </span>
          <span id="anggotaPickerSub" class="block text-xs truncate" style="color: var(--text-faint)">
            <?= $anggota_terpilih ? 'Ketuk untuk mengganti anggota' : 'Ketuk untuk memilih anggota' ?>
          </span>
        </span>
        <span class="shrink-0 w-8 h-8 rounded-full border flex items-center justify-center" style="background: var(--surface-2); border-color: var(--border); color: var(--text-faint)">
          <i class="bi bi-chevron-right text-sm"></i>
        </span>
      </button>
      <input type="hidden" name="id_anggota" id="anggotaInput" value="<?= $id_anggota_input ? (int)$id_anggota_input : '' ?>" required>
      <p class="text-xs mt-1.5" style="color: var(--text-faint)">Satu transaksi untuk satu anggota.</p>
    </div>

    <!-- Picker Buku -->
    <div>
      <label class="block text-sm font-medium mb-1" style="color: var(--text-muted)">Buku *</label>
      <button type="button" id="bukuPickerBtn" aria-haspopup="dialog" aria-controls="bukuModal"
              class="w-full flex items-center gap-3 border rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition text-left group" style="border-color: var(--border); background: var(--surface); color: var(--text)">
        <span class="w-10 h-10 rounded-xl border flex items-center justify-center shrink-0" style="background: var(--accent-soft); border-color: var(--accent-soft-2); color: var(--accent-text)">
          <i class="bi bi-book text-lg"></i>
        </span>
        <span class="flex-1 min-w-0">
          <span id="bukuPickerLabel" class="block text-sm font-semibold truncate" style="color: var(--text)">
            <?= empty($id_buku_inputs) ? 'Pilih Buku' : count($id_buku_inputs) . ' buku dipilih' ?>
          </span>
          <span id="bukuPickerSub" class="block text-xs truncate" style="color: var(--text-faint)">
            <?= empty($id_buku_inputs) ? 'Ketuk untuk memilih buku (bisa lebih dari satu)' : 'Ketuk untuk menambah/mengurangi pilihan' ?>
          </span>
        </span>
        <span class="shrink-0 flex items-center gap-2">
          <span id="bukuPickerBadge" class="<?= empty($id_buku_inputs) ? 'hidden' : 'inline-flex' ?> items-center justify-center min-w-6 h-6 px-1.5 rounded-full bg-brand-600 text-white text-xs font-bold"><?= count($id_buku_inputs) ?></span>
          <span class="w-8 h-8 rounded-full border flex items-center justify-center" style="background: var(--surface-2); border-color: var(--border); color: var(--text-faint)"><i class="bi bi-chevron-right text-sm"></i></span>
        </span>
      </button>
      <div id="bukuHiddenInputs">
        <?php foreach ($id_buku_inputs as $bid): ?>
          <input type="hidden" name="id_buku[]" value="<?= (int)$bid ?>">
        <?php endforeach; ?>
      </div>
      <!-- Ringkasan buku terpilih (mobile-friendly chips) -->
      <div id="bukuSelectedList" class="mt-3 <?= empty($id_buku_inputs) ? 'hidden' : '' ?> space-y-2">
        <?php foreach ($id_buku_inputs as $bid):
            $b = $buku_terpilih_map[$bid] ?? null;
            if (!$b) continue;
        ?>
          <div class="buku-chip flex items-center gap-3 p-2.5 rounded-xl border" style="background: var(--surface-2); border-color: var(--border)" data-id="<?= (int)$b['id_buku'] ?>">
            <img src="<?= e($b['cover_url']) ?>" alt="Cover <?= e($b['judul']) ?>" class="w-10 h-14 rounded-lg object-cover border" style="border-color: var(--border); background: var(--surface)" >
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold truncate" style="color: var(--text)"><?= e($b['judul']) ?></p>
              <p class="text-xs truncate" style="color: var(--text-faint)"><?= e($b['kode_buku']) ?> · <?= e($b['penulis']) ?></p>
              <p class="text-[11px] mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full" style="<?= (int)$b['tersedia']>0 ? 'background: var(--badge-emerald-bg); color: var(--badge-emerald-text); border:1px solid var(--badge-emerald-border)' : 'background: var(--badge-red-bg); color: var(--badge-red-text); border:1px solid var(--badge-red-border)' ?>">
                <span class="w-1.5 h-1.5 rounded-full" style="background:<?= (int)$b['tersedia']>0 ? '#10b981' : '#ef4444' ?>"></span>
                Stok tersedia: <?= (int)$b['tersedia'] ?>
              </p>
            </div>
            <button type="button" class="buku-chip-remove shrink-0 w-8 h-8 rounded-full border flex items-center justify-center" style="background: var(--surface); border-color: var(--border); color: var(--text-faint)" data-remove-id="<?= (int)$b['id_buku'] ?>" aria-label="Hapus <?= e($b['judul']) ?>">
              <i class="bi bi-x-lg text-sm"></i>
            </button>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="text-xs mt-1.5" style="color: var(--text-faint)">Bisa memilih beberapa buku sekaligus. Buku stok 0 tidak dapat dipilih.</p>
    </div>

    <div>
      <label class="block text-sm font-medium mb-1" style="color: var(--text-muted)" for="tanggal_pinjam">Tanggal Pinjam *</label>
      <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" value="<?= e($tanggal_pinjam_input) ?>" max="<?= e($hari_ini) ?>" required
             class="w-full border rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brand-500" style="border-color: var(--border); background: var(--surface); color: var(--text)">
      <p class="text-xs mt-1.5" style="color: var(--text-faint)">Bisa memilih tanggal hari ini atau tanggal sebelumnya. Hari Minggu tidak dapat digunakan.</p>
    </div>
    <div class="border rounded-xl p-3 text-xs" style="background: var(--accent-soft); border-color: var(--accent-soft-2); color: var(--accent-text)">
      Jatuh tempo otomatis <?= LAMA_PINJAM_HARI ?> hari setelah tanggal pinjam: <b><?= format_tanggal($jatuh_tempo) ?></b>.
    </div>
    <div class="flex flex-col sm:flex-row gap-3 pt-2">
      <button type="submit" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white font-medium px-6 py-2.5 rounded-xl transition">Simpan Peminjaman</button>
      <a href="<?= BASE_URL ?>/admin/peminjaman/index.php" class="w-full sm:w-auto border px-6 py-2.5 rounded-xl transition text-center" style="border-color: var(--border); background: var(--surface); color: var(--text-muted)">Batal</a>
    </div>
  </form>
</div>

<!-- MODAL PILIH ANGGOTA -->
<div id="anggotaModal" class="picker-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="anggotaModalTitle">
  <div class="picker-backdrop" data-close-anggota></div>
  <div class="picker-panel" role="document">
    <div class="picker-header">
      <div class="flex items-center justify-between gap-3">
        <h2 id="anggotaModalTitle" class="text-base font-bold text-slate-900">Pilih Anggota</h2>
        <button type="button" class="picker-close" data-close-anggota aria-label="Tutup">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="picker-search">
        <i class="bi bi-search picker-search-icon"></i>
        <input type="text" id="anggotaSearch" placeholder="Cari kode atau nama anggota..." autocomplete="off" class="picker-search-input">
      </div>
    </div>
    <div id="anggotaList" class="picker-list" role="listbox" aria-label="Daftar anggota"></div>
    <div class="picker-footer">
      <p id="anggotaSelectedInfo" class="picker-count text-sm text-slate-500">Belum ada anggota dipilih</p>
      <div class="flex gap-2 w-full sm:w-auto">
        <button type="button" class="picker-btn picker-btn-cancel flex-1 sm:flex-none" data-close-anggota>Batal</button>
        <button type="button" id="anggotaConfirm" class="picker-btn picker-btn-confirm flex-1 sm:flex-none">Pilih Anggota</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL PILIH BUKU -->
<div id="bukuModal" class="picker-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bukuModalTitle">
  <div class="picker-backdrop" data-close-buku></div>
  <div class="picker-panel" role="document">
    <div class="picker-header">
      <div class="flex items-center justify-between gap-3">
        <h2 id="bukuModalTitle" class="text-base font-bold text-slate-900">Pilih Buku</h2>
        <button type="button" class="picker-close" data-close-buku aria-label="Tutup">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="picker-search">
        <i class="bi bi-search picker-search-icon"></i>
        <input type="text" id="bukuSearch" placeholder="Cari kode, judul, atau penulis..." autocomplete="off" class="picker-search-input">
      </div>
    </div>
    <div id="bukuList" class="picker-list" role="listbox" aria-label="Daftar buku"></div>
    <div class="picker-footer">
      <p id="bukuSelectedInfo" class="picker-count text-sm text-slate-500">0 buku dipilih</p>
      <div class="flex gap-2 w-full sm:w-auto">
        <button type="button" class="picker-btn picker-btn-cancel flex-1 sm:flex-none" data-close-buku>Batal</button>
        <button type="button" id="bukuConfirm" class="picker-btn picker-btn-confirm flex-1 sm:flex-none">Pilih Buku</button>
      </div>
    </div>
  </div>
</div>

<style>
/* Picker modal overrides — use global app.css system, remove heavy blur */
.picker-backdrop { background: rgba(15,23,42,.32) !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important; }
html[data-theme="dark"] .picker-backdrop { background: rgba(0,0,0,.48) !important; }
.picker-panel { transform: translateY(6px) !important; transition: transform 150ms ease !important; box-shadow: 0 12px 40px rgba(16,24,40,.12) !important; border-radius: var(--radius-lg) !important; contain: layout paint; }
.picker-modal.is-open .picker-panel { transform: none !important; }
.picker-item, .picker-close, .picker-btn, .picker-search-input { transition: border-color var(--transition-fast), background var(--transition-fast), color var(--transition-fast) !important; }
.picker-item { contain: layout paint; }
@media (prefers-reduced-motion: reduce) {
  .picker-panel { transition: none !important; transform: none !important; }
  .picker-item, .picker-btn { transition: none !important; }
}
</style>

<script>
const anggotaData = <?= json_encode($anggota_list, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;
const bukuData = <?= json_encode($buku_list, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;

let selectedAnggotaId = <?= json_encode($id_anggota_input ?: null) ?>;
let tempAnggotaId = selectedAnggotaId;
let selectedBukuIds = <?= json_encode($id_buku_inputs, JSON_NUMERIC_CHECK) ?>;
let tempBukuIds = [...selectedBukuIds];

const anggotaModal = document.getElementById('anggotaModal');
const bukuModal = document.getElementById('bukuModal');
const anggotaBtn = document.getElementById('anggotaPickerBtn');
const bukuBtn = document.getElementById('bukuPickerBtn');
const anggotaInput = document.getElementById('anggotaInput');
const anggotaSearch = document.getElementById('anggotaSearch');
const bukuSearch = document.getElementById('bukuSearch');
const anggotaListEl = document.getElementById('anggotaList');
const bukuListEl = document.getElementById('bukuList');
const anggotaConfirm = document.getElementById('anggotaConfirm');
const bukuConfirm = document.getElementById('bukuConfirm');
const anggotaSelectedInfo = document.getElementById('anggotaSelectedInfo');
const bukuSelectedInfo = document.getElementById('bukuSelectedInfo');
const anggotaPickerLabel = document.getElementById('anggotaPickerLabel');
const anggotaPickerSub = document.getElementById('anggotaPickerSub');
const anggotaPickerIcon = document.getElementById('anggotaPickerIcon');
const bukuPickerLabel = document.getElementById('bukuPickerLabel');
const bukuPickerSub = document.getElementById('bukuPickerSub');
const bukuPickerBadge = document.getElementById('bukuPickerBadge');
const bukuSelectedList = document.getElementById('bukuSelectedList');
const bukuHiddenInputs = document.getElementById('bukuHiddenInputs');

function lockBody(lock){
  document.documentElement.classList.toggle('modal-open', lock);
  document.body.classList.toggle('overflow-hidden', lock);
}
function openModal(modal){
  modal.classList.add('is-open');
  modal.setAttribute('aria-hidden','false');
  lockBody(true);
}
function closeModal(modal){
  modal.classList.remove('is-open');
  modal.setAttribute('aria-hidden','true');
  if(!document.querySelector('.picker-modal.is-open') && !document.querySelector('.app-modal.is-open') && !document.querySelector('.custom-select-modal.is-open')) lockBody(false);
}

// --- Anggota ---
function filteredAnggota(q){
  const s = (q||'').trim().toLowerCase();
  if(!s) return anggotaData;
  return anggotaData.filter(a => (a.nomor_anggota||'').toLowerCase().includes(s) || (a.nama||'').toLowerCase().includes(s));
}
function renderAnggotaList(){
  const q = anggotaSearch.value || '';
  const list = filteredAnggota(q);
  if(list.length===0){
    anggotaListEl.innerHTML = '<div class="text-center py-8 text-sm" style="color: var(--text-faint)">Tidak ada anggota yang cocok dengan pencarian.</div>';
    return;
  }
  anggotaListEl.innerHTML = list.map(a=>{
    const isSel = tempAnggotaId === a.id_anggota;
    return `
      <button type="button" class="picker-item picker-item-anggota ${isSel?'is-selected':''}" data-id="${a.id_anggota}" role="option" aria-selected="${isSel?'true':'false'}">
        <span class="picker-avatar">${a.foto_url ? `<img src="${escapeHtml(a.foto_url)}" alt="Foto ${escapeHtml(a.nama)}" loading="lazy">` : '<i class="bi bi-person"></i>'}</span>
        <span class="flex-1 min-w-0">
          <span class="block text-sm font-semibold truncate" style="color: var(--text)">${escapeHtml(a.nama)}</span>
          <span class="block text-xs truncate" style="color: var(--text-faint)">${escapeHtml(a.nomor_anggota)}</span>
        </span>
        <span class="shrink-0 w-6 h-6 rounded-full border flex items-center justify-center ${isSel?'bg-brand-600 border-brand-600 text-white':'text-transparent'}" style="${isSel?'':'background:var(--surface); border-color: var(--border)'}"><i class="bi bi-check-lg text-xs"></i></span>
      </button>`;
  }).join('');
}
function updateAnggotaFooter(){
  if(tempAnggotaId){
    const a = anggotaData.find(x=>x.id_anggota===tempAnggotaId);
    anggotaSelectedInfo.textContent = a ? `${a.nomor_anggota} — ${a.nama} dipilih` : '1 anggota dipilih';
    anggotaConfirm.disabled = false;
  } else {
    anggotaSelectedInfo.textContent = 'Belum ada anggota dipilih';
    anggotaConfirm.disabled = true;
  }
}
function updateAnggotaFieldView(){
  const a = anggotaData.find(x=>x.id_anggota===selectedAnggotaId);
  if(a){
    anggotaPickerLabel.textContent = `${a.nomor_anggota} — ${a.nama}`;
    anggotaPickerSub.textContent = 'Ketuk untuk mengganti anggota';
    anggotaPickerIcon.innerHTML = a.foto_url ? `<img src="${escapeHtml(a.foto_url)}" alt="Foto ${escapeHtml(a.nama)}" class="w-full h-full object-cover">` : '<i class="bi bi-person text-lg"></i>';
    anggotaInput.value = a.id_anggota;
  } else {
    anggotaPickerLabel.textContent = 'Pilih Anggota';
    anggotaPickerSub.textContent = 'Ketuk untuk memilih anggota';
    anggotaPickerIcon.innerHTML = '<i class="bi bi-person text-lg"></i>';
    anggotaInput.value = '';
  }
}
function openAnggotaModal(){
  tempAnggotaId = selectedAnggotaId;
  anggotaSearch.value = '';
  renderAnggotaList();
  updateAnggotaFooter();
  openModal(anggotaModal);
  setTimeout(()=> anggotaSearch.focus(), 120);
}
function confirmAnggota(){
  if(!tempAnggotaId){ closeModal(anggotaModal); return; }
  selectedAnggotaId = tempAnggotaId;
  updateAnggotaFieldView();
  closeModal(anggotaModal);
}

// --- Buku ---
function filteredBuku(q){
  const s = (q||'').trim().toLowerCase();
  if(!s) return bukuData;
  return bukuData.filter(b => (b.kode_buku||'').toLowerCase().includes(s) || (b.judul||'').toLowerCase().includes(s) || (b.penulis||'').toLowerCase().includes(s));
}
function renderBukuList(){
  const q = bukuSearch.value || '';
  const list = filteredBuku(q);
  if(list.length===0){
    bukuListEl.innerHTML = '<div class="text-center py-8 text-sm text-slate-400">Tidak ada buku yang cocok dengan pencarian.</div>';
    return;
  }
  bukuListEl.innerHTML = list.map(b=>{
    const isSel = tempBukuIds.includes(b.id_buku);
    const isDisabled = b.tersedia < 1;
    return `
      <button type="button" class="picker-item picker-item-buku ${isSel?'is-selected':''} ${isDisabled?'is-disabled':''}" data-id="${b.id_buku}" ${isDisabled?'disabled aria-disabled="true"':''} role="option" aria-selected="${isSel?'true':'false'}">
        <span class="picker-cover"><img src="${escapeHtml(b.cover_url)}" alt="Cover ${escapeHtml(b.judul)}" loading="lazy"></span>
        <span class="flex-1 min-w-0 text-left">
          <span class="block text-sm font-semibold line-clamp-2 leading-tight" style="color: var(--text)">${escapeHtml(b.judul)}</span>
          <span class="block text-xs truncate mt-0.5" style="color: var(--text-faint)">${escapeHtml(b.kode_buku)} · ${escapeHtml(b.penulis||'-')}</span>
          <span class="mt-1 inline-flex items-center gap-1.5 text-[11px] font-semibold px-2 py-1 rounded-full" style="${b.tersedia>0 ? 'background: var(--badge-emerald-bg); color: var(--badge-emerald-text); border:1px solid var(--badge-emerald-border)' : 'background: var(--badge-red-bg); color: var(--badge-red-text); border:1px solid var(--badge-red-border)'}">
            <span class="w-1.5 h-1.5 rounded-full" style="background:${b.tersedia>0?'#10b981':'#ef4444'}"></span>
            ${b.tersedia>0 ? `Stok tersedia: ${b.tersedia}` : 'Stok habis'}
            ${b.stok ? ` / Stok total: ${b.stok}` : ''}
          </span>
        </span>
        <span class="picker-check shrink-0"><i class="bi bi-check-lg text-xs"></i></span>
      </button>`;
  }).join('');
}
function updateBukuFooter(){
  const n = tempBukuIds.length;
  bukuSelectedInfo.textContent = n===0 ? '0 buku dipilih' : `${n} buku dipilih`;
  bukuConfirm.textContent = n===0 ? 'Pilih Buku' : `Pilih ${n} Buku`;
  // always enabled even 0 ? allow empty to clear? But spec says need at least 1, so disable 0
  bukuConfirm.disabled = n===0;
}
function updateBukuHiddenInputs(){
  bukuHiddenInputs.innerHTML = selectedBukuIds.map(id=>`<input type="hidden" name="id_buku[]" value="${id}">`).join('');
}
function updateBukuFieldView(){
  const n = selectedBukuIds.length;
  if(n===0){
    bukuPickerLabel.textContent = 'Pilih Buku';
    bukuPickerSub.textContent = 'Ketuk untuk memilih buku (bisa lebih dari satu)';
    bukuPickerBadge.classList.add('hidden');
    bukuPickerBadge.classList.remove('inline-flex');
    bukuSelectedList.classList.add('hidden');
    bukuSelectedList.innerHTML = '';
  } else {
    bukuPickerLabel.textContent = `${n} buku dipilih`;
    bukuPickerSub.textContent = 'Ketuk untuk menambah/mengurangi pilihan';
    bukuPickerBadge.textContent = n;
    bukuPickerBadge.classList.remove('hidden');
    bukuPickerBadge.classList.add('inline-flex');
    // render chips
    bukuSelectedList.classList.remove('hidden');
    bukuSelectedList.innerHTML = selectedBukuIds.map(id=>{
      const b = bukuData.find(x=>x.id_buku===id);
      if(!b) return '';
      return `
        <div class="buku-chip flex items-center gap-3 p-2.5 rounded-xl border" style="background: var(--surface-2); border-color: var(--border)" data-id="${b.id_buku}">
          <img src="${escapeHtml(b.cover_url)}" alt="Cover ${escapeHtml(b.judul)}" class="w-10 h-14 rounded-lg object-cover border" style="border-color: var(--border); background: var(--surface)" >
          <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold truncate" style="color: var(--text)">${escapeHtml(b.judul)}</p>
            <p class="text-xs truncate" style="color: var(--text-faint)">${escapeHtml(b.kode_buku)} · ${escapeHtml(b.penulis||'-')}</p>
            <p class="text-[11px] mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full" style="${b.tersedia>0?'background: var(--badge-emerald-bg); color: var(--badge-emerald-text); border:1px solid var(--badge-emerald-border)':'background: var(--badge-red-bg); color: var(--badge-red-text); border:1px solid var(--badge-red-border)'}">
              <span class="w-1.5 h-1.5 rounded-full" style="background:${b.tersedia>0?'#10b981':'#ef4444'}"></span>
              Stok tersedia: ${b.tersedia}
            </p>
          </div>
          <button type="button" class="buku-chip-remove shrink-0 w-8 h-8 rounded-full border flex items-center justify-center" style="background: var(--surface); border-color: var(--border); color: var(--text-faint)" data-remove-id="${b.id_buku}" aria-label="Hapus ${escapeHtml(b.judul)}">
            <i class="bi bi-x-lg text-sm"></i>
          </button>
        </div>`;
    }).join('');
    // delegation for chip remove handled globally
  }
  updateBukuHiddenInputs();
}
function openBukuModal(){
  tempBukuIds = [...selectedBukuIds];
  bukuSearch.value = '';
  renderBukuList();
  updateBukuFooter();
  openModal(bukuModal);
  setTimeout(()=> bukuSearch.focus(), 120);
}
function confirmBuku(){
  if(tempBukuIds.length===0) return;
  selectedBukuIds = [...tempBukuIds];
  // pastikan unik
  selectedBukuIds = [...new Set(selectedBukuIds)];
  updateBukuFieldView();
  closeModal(bukuModal);
}

function escapeHtml(s){
  return String(s||'').replace(/[&<>"']/g, m=> ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
}

// Delegated click for anggota & buku lists — single listener each, no per-item overhead
anggotaListEl.addEventListener('click', e=>{
  const btn = e.target.closest('.picker-item');
  if(!btn || btn.disabled || btn.classList.contains('is-disabled')) return;
  const id = parseInt(btn.dataset.id,10);
  if(!id) return;
  tempAnggotaId = id;
  renderAnggotaList();
  updateAnggotaFooter();
});
bukuListEl.addEventListener('click', e=>{
  const btn = e.target.closest('.picker-item');
  if(!btn || btn.disabled || btn.classList.contains('is-disabled')) return;
  const id = parseInt(btn.dataset.id,10);
  if(!id) return;
  if(tempBukuIds.includes(id)){
    tempBukuIds = tempBukuIds.filter(x=>x!==id);
  } else {
    tempBukuIds.push(id);
  }
  renderBukuList();
  updateBukuFooter();
});
bukuSelectedList.addEventListener('click', e=>{
  const btn = e.target.closest('.buku-chip-remove');
  if(!btn) return;
  const rid = parseInt(btn.dataset.removeId,10);
  if(!rid) return;
  selectedBukuIds = selectedBukuIds.filter(x=>x!==rid);
  updateBukuHiddenInputs();
  updateBukuFieldView();
});

// Events
anggotaBtn.addEventListener('click', openAnggotaModal);
bukuBtn.addEventListener('click', openBukuModal);
anggotaConfirm.addEventListener('click', confirmAnggota);
bukuConfirm.addEventListener('click', confirmBuku);

let searchDebounce = null;
function debouncedRenderAnggota(){ clearTimeout(searchDebounce); searchDebounce = setTimeout(()=> renderAnggotaList(), 80); }
function debouncedRenderBuku(){ clearTimeout(searchDebounce); searchDebounce = setTimeout(()=> renderBukuList(), 80); }
anggotaSearch.addEventListener('input', debouncedRenderAnggota);
bukuSearch.addEventListener('input', debouncedRenderBuku);
document.querySelectorAll('[data-close-anggota]').forEach(el=> el.addEventListener('click', ()=> closeModal(anggotaModal)));
document.querySelectorAll('[data-close-buku]').forEach(el=> el.addEventListener('click', ()=> closeModal(bukuModal)));

document.addEventListener('keydown', e=>{
  if(e.key==='Escape'){
    if(bukuModal.classList.contains('is-open')) closeModal(bukuModal);
    else if(anggotaModal.classList.contains('is-open')) closeModal(anggotaModal);
  }
});

// Click on backdrop handled via data-close, but also ensure body lock removed when clicking backdrop
// Prevent form submit if belum memilih
document.getElementById('peminjamanForm').addEventListener('submit', (e)=>{
  const errs=[];
  if(!selectedAnggotaId) errs.push('Pilih anggota terlebih dahulu.');
  if(selectedBukuIds.length===0) errs.push('Pilih minimal satu buku.');
  if(errs.length){
    e.preventDefault();
    // tampilkan notice modal jika ada showNoticeModal dari footer.php
    if(typeof showNoticeModal==='function'){
      showNoticeModal(errs.join('\n'), {title:'Lengkapi Form'});
    } else {
      alert(errs.join('\n'));
    }
  }
});

// Init views
updateAnggotaFieldView();
updateBukuFieldView();
updateAnggotaFooter();
updateBukuFooter();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
