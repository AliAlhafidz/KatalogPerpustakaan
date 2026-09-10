<?php
/**
 * Simulasi Email H-3/H-1 — fallback demo tanpa SMTP/cron
 * Reuse tabel notifikasi existing (buat_notifikasi_anggota) dengan kunci_unik 'jatuh_tempo:<id>:<tgl>' (sama dengan sinkronkan_notifikasi_anggota)
 * Tidak bikin tabel baru, tidak kirim mail() sungguhan. Dedup lintas mekanisme via kunci_unik yang sama.
 */

function proses_notifikasi_h3_h1(PDO $pdo): array {
    $hari_ini = date('Y-m-d');
    $h3 = date('Y-m-d', strtotime('+3 days'));
    $h1 = date('Y-m-d', strtotime('+1 days'));

    // Cari peminjaman aktif jatuh tempo H-3 atau H-1
    $stmt = $pdo->prepare("SELECT p.id_peminjaman, p.id_anggota, p.tanggal_jatuh_tempo, b.judul, a.nama, a.email
                           FROM peminjaman p
                           JOIN buku b ON b.id_buku = p.id_buku
                           JOIN anggota a ON a.id_anggota = p.id_anggota
                           WHERE p.status = 'dipinjam' AND p.tanggal_jatuh_tempo IN (:h3, :h1)
                           ORDER BY p.tanggal_jatuh_tempo ASC");
    $stmt->execute([':h3' => $h3, ':h1' => $h1]);
    $rows = $stmt->fetchAll();

    $ringkasan = ['h3_dibuat' => 0, 'h1_dibuat' => 0, 'skip_duplicate' => 0];
    $preview = [];

    foreach ($rows as $r) {
        $is_h3 = $r['tanggal_jatuh_tempo'] === $h3;
        $is_h1 = $r['tanggal_jatuh_tempo'] === $h1;
        $tipe_hari = $is_h3 ? 'H-3' : ($is_h1 ? 'H-1' : '');
        if ($tipe_hari === '') continue;

        // Dedup lintas mekanisme: pakai format SAMA seperti sinkronkan_notifikasi_anggota() — 'jatuh_tempo:<id>:<tgl>'
        // Bukan 'email_h3:' terpisah, supaya trigger manual dan lazy sync tidak dobel untuk peminjaman yang sama di hari sama
        $kunci = 'jatuh_tempo:' . $r['id_peminjaman'] . ':' . $r['tanggal_jatuh_tempo'];

        // Cek duplikat sejenis hari ini (kunci_unik unik per anggota+ kunci)
        $cek = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE id_anggota = :a AND kunci_unik = :k");
        $cek->execute([':a' => $r['id_anggota'], ':k' => $kunci]);
        if ((int)$cek->fetchColumn() > 0) {
            $ringkasan['skip_duplicate']++;
            continue;
        }

        $judul = 'Pengingat jatuh tempo ' . $tipe_hari;
        $pesan = 'Buku "' . $r['judul'] . '" jatuh tempo ' . format_tanggal($r['tanggal_jatuh_tempo']) . ' (' . $tipe_hari . '). Segera kembalikan untuk menghindari denda.';
        // Reuse notifikasi in-app (warning) — simulasi email
        buat_notifikasi_anggota($pdo, (int)$r['id_anggota'], $judul, $pesan, 'warning', '/anggota/peminjaman.php', $kunci);

        // Cek apakah insert berhasil (jika duplicate race, tidak terhitung)
        $cek2 = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE id_anggota = :a AND kunci_unik = :k");
        $cek2->execute([':a' => $r['id_anggota'], ':k' => $kunci]);
        $ada = (int)$cek2->fetchColumn() > 0;
        if ($ada) {
            if ($is_h3) $ringkasan['h3_dibuat']++; else $ringkasan['h1_dibuat']++;
            $preview[] = [
                'anggota' => $r['nama'] . ' (' . $r['email'] . ')',
                'buku' => $r['judul'],
                'jatuh_tempo' => $r['tanggal_jatuh_tempo'],
                'tipe' => $tipe_hari,
                'subjek' => '[SIMULASI - tidak benar-benar terkirim, mode demo] ' . $judul . ' — ' . $r['judul'],
                'body' => 'Halo ' . $r['nama'] . ', buku "' . $r['judul'] . '" jatuh tempo ' . format_tanggal($r['tanggal_jatuh_tempo']) . ' (' . $tipe_hari . '). Harap kembalikan tepat waktu. Denda Rp ' . number_format(DENDA_PER_HARI, 0, ',', '.') . '/hari.',
                'kunci' => $kunci,
            ];
        } else {
            $ringkasan['skip_duplicate']++;
        }
    }

    return ['ringkasan' => $ringkasan, 'preview' => $preview, 'h3' => $h3, 'h1' => $h1];
}
