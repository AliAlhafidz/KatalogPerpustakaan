<?php
require_once __DIR__ . '/../../config/bootstrap.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') require_csrf();

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM anggota WHERE id_anggota = :id");
$stmt->execute([':id' => $id]);
$anggota = $stmt->fetch();

if (!$anggota) {
    set_flash('error', 'Data anggota tidak ditemukan.');
    redirect('/admin/anggota/index.php');
}

$errors = [];
$input = [
    'nama' => $anggota['nama'], 'email' => $anggota['email'],
    'no_hp' => $anggota['no_hp'], 'alamat' => $anggota['alamat'], 'status' => $anggota['status']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['nama']   = clean($_POST['nama'] ?? '');
    $input['email']  = clean($_POST['email'] ?? '');
    $input['no_hp']  = clean($_POST['no_hp'] ?? '');
    $input['alamat'] = clean($_POST['alamat'] ?? '');
    $input['status'] = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
    $password_baru   = $_POST['password_baru'] ?? '';

    if ($input['nama'] === '') $errors[] = 'Nama wajib diisi.';
    elseif (mb_strlen($input['nama']) > 150) $errors[] = 'Nama terlalu panjang (maks 150 karakter).';
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    elseif (mb_strlen($input['email']) > 150) $errors[] = 'Email terlalu panjang (maks 150 karakter).';
    if ($input['no_hp'] !== '' && !preg_match('/^08[0-9]{8,11}$/', $input['no_hp'])) $errors[] = 'Format No. HP tidak valid. Gunakan 08xxxxxxxxxx (10-13 digit, angka saja).';
    elseif (mb_strlen($input['no_hp']) > 20) $errors[] = 'No. HP terlalu panjang (maks 20 karakter).';
    if (mb_strlen($input['alamat']) > 255) $errors[] = 'Alamat terlalu panjang (maks 255 karakter).';
    if ($password_baru !== '' && strlen($password_baru) < 6) $errors[] = 'Password baru minimal 6 karakter.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota WHERE email = :email AND id_anggota != :id");
        $stmt->execute([':email' => $input['email'], ':id' => $id]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Email sudah dipakai anggota lain.';
    }

    if (empty($errors)) {
        if ($password_baru !== '') {
            $hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE anggota SET nama=:nama, email=:email, no_hp=:hp, alamat=:alamat, status=:status, password=:pass WHERE id_anggota=:id");
            $stmt->execute([':nama'=>$input['nama'], ':email'=>$input['email'], ':hp'=>$input['no_hp'] ?: null, ':alamat'=>$input['alamat'] ?: null, ':status'=>$input['status'], ':pass'=>$hash, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare("UPDATE anggota SET nama=:nama, email=:email, no_hp=:hp, alamat=:alamat, status=:status WHERE id_anggota=:id");
            $stmt->execute([':nama'=>$input['nama'], ':email'=>$input['email'], ':hp'=>$input['no_hp'] ?: null, ':alamat'=>$input['alamat'] ?: null, ':status'=>$input['status'], ':id'=>$id]);
        }
        set_flash('sukses', "Data anggota \"{$input['nama']}\" berhasil diperbarui.");
        redirect('/admin/anggota/index.php');
    }
}

$menu_aktif = 'anggota';
$page_title = 'Ubah Anggota';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/admin_menu.php';
require __DIR__ . '/_form.php';
require_once __DIR__ . '/../../includes/footer.php';
