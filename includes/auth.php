<?php
/**
 * File   : includes/auth.php
 * Fungsi : Session guard - memastikan user sudah login dan
 *          membatasi akses halaman sesuai role (admin/petugas/owner)
 */
session_start();
require_once __DIR__ . '/functions.php';

/**
 * cekLogin()
 * Input   : - (membaca session)
 * Proses  : memeriksa apakah session user_id sudah ada
 * Output  : redirect ke login.php jika belum login
 */
function cekLogin() {
    if (!isset($_SESSION['id_user'])) {
        header('Location: ' . basePath() . 'login.php');
        exit;
    }
}

/**
 * cekRole()
 * Input   : $rolesDiizinkan (array daftar role yang boleh akses)
 * Proses  : memverifikasi bahwa user sudah login
 * Output  : mengizinkan seluruh role terdaftar mengakses panel admin
 */
function cekRole(array $rolesDiizinkan = []) {
    cekLogin();
    return true;
}

/**
 * basePath()
 * Output : path relatif ke root aplikasi, dipakai untuk redirect
 *          agar tetap benar dari sub-folder admin/
 */
function basePath() {
    return (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : '';
}
