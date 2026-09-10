<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$errors = [];
$input = ['nama' => '', 'email' => '', 'no_hp' => '', 'alamat' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['nama']   = clean($_POST['nama'] ?? '');
    $input['email']  = clean($_POST['email'] ?? '');
    $input['no_hp']  = clean($_POST['no_hp'] ?? '');
    $input['alamat'] = clean($_POST['alamat'] ?? '');
    $password        = $_POST['password'] ?? '';

    if ($input['nama'] === '') $errors[] = 'Nama wajib diisi.';
    elseif (mb_strlen($input['nama']) > 150) $errors[] = 'Nama terlalu panjang (maks 150 karakter).';
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    elseif (mb_strlen($input['email']) > 150) $errors[] = 'Email terlalu panjang (maks 150 karakter).';
    if ($input['no_hp'] !== '' && !preg_match('/^08[0-9]{8,11}$/', $input['no_hp'])) $errors[] = 'Format No. HP tidak valid. Gunakan 08xxxxxxxxxx (10-13 digit, angka saja).';
    elseif (mb_strlen($input['no_hp']) > 20) $errors[] = 'No. HP terlalu panjang (maks 20 karakter).';
    if (mb_strlen($input['alamat']) > 255) $errors[] = 'Alamat terlalu panjang (maks 255 karakter).';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE email = :email");
        $stmt->execute([':email' => $input['email']]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Email sudah terdaftar sebagai anggota lain.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $nomor_anggota = generate_nomor_anggota($pdo);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO anggota (nomor_anggota, nama, email, password, no_hp, alamat, status)
                                    VALUES (:no, :nama, :email, :pass, :hp, :alamat, 'aktif')");
            $stmt->execute([
                ':no' => $nomor_anggota, ':nama' => $input['nama'], ':email' => $input['email'],
                ':pass' => $hash, ':hp' => $input['no_hp'] ?: null, ':alamat' => $input['alamat'] ?: null,
            ]);
            $pdo->commit();
            set_flash('sukses', "Anggota \"{$input['nama']}\" ($nomor_anggota) berhasil ditambahkan.");
            redirect('/admin/anggota/index.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if (strpos($e->getMessage(), 'Duplicate') !== false || $e->getCode() == 23000) {
                if (strpos($e->getMessage(), 'email') !== false) {
                    $errors[] = 'Email sudah terdaftar (percobaan bersamaan).';
                } else {
                    $errors[] = 'Nomor anggota bentrok (percobaan bersamaan). Silakan coba lagi.';
                }
            } else {
                $errors[] = 'Gagal menambahkan anggota: ' . $e->getMessage();
            }
            error_log('Tambah anggota gagal: ' . $e->getMessage());
        }
    }
}

$menu_aktif = 'anggota';
$page_title = 'Tambah Anggota';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
require __DIR__ . '/_form.php';
require_once __DIR__ . '/../../includes/footer.php';
