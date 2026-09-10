<?php
/**
 * Autentikasi & Otorisasi (role: admin / anggota)
 * File ini harus di-include SETELAH session_start() dan config/database.php
 */

function is_login() {
    return isset($_SESSION['role']);
}

function is_admin() {
    return is_login() && $_SESSION['role'] === 'admin';
}

function is_anggota() {
    return is_login() && $_SESSION['role'] === 'anggota';
}

// Wajib login sebagai admin, kalau tidak -> redirect ke login
function wajib_admin() {
    if (!is_admin()) {
        set_flash('error', 'Silakan login sebagai admin terlebih dahulu.');
        redirect('/login.php');
    }
}

// Wajib login sebagai anggota, kalau tidak -> redirect ke login
function wajib_anggota() {
    if (!is_anggota()) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        redirect('/login.php');
    }
}
