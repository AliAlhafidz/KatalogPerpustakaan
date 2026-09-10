<?php
/**
 * Audit log helper — catat aksi buku & kategori
 * Side-effect only, tidak boleh menggagalkan aksi utama.
 */
function catat_audit(PDO $pdo, $user_id, string $aksi, string $target_tabel, int $target_id, $detail = null): void {
    try {
        if (!in_array($aksi, ['tambah','edit','hapus'], true)) return;
        if (!in_array($target_tabel, ['buku','kategori','pengajuan_buku','pengajuan_peminjaman'], true)) return;
        if ($target_id <= 0) return;
        // Detail boleh array (akan json_encode) atau string
        if (is_array($detail)) {
            $detail = json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($detail !== null) $detail = substr((string)$detail, 0, 2000);
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, aksi, target_tabel, target_id, detail, created_at) VALUES (:uid, :aksi, :tabel, :tid, :detail, NOW())");
        $stmt->execute([
            ':uid' => $user_id ?: null,
            ':aksi' => $aksi,
            ':tabel' => $target_tabel,
            ':tid' => $target_id,
            ':detail' => $detail,
        ]);
    } catch (Throwable $e) {
        error_log('audit_log gagal: ' . $e->getMessage());
        // Jangan throw — audit adalah side-effect
    }
}
