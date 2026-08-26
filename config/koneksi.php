<?php
/**
 * File   : config/koneksi.php
 * Fungsi : Membuka koneksi ke database MySQL menggunakan PDO
 */

date_default_timezone_set('Asia/Jakarta');

$DB_HOST = 'localhost';
$DB_NAME = 'parkir';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}
