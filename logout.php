<?php
/**
 * File   : logout.php
 * Modul  : Proses Logout Utama
 * Proses : Catat log aktivitas "Logout dari sistem", hancurkan session, dan redirect ke login.php
 */
session_start();
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['id_user'])) {
    catatLog($pdo, (int) $_SESSION['id_user'], 'Logout dari sistem');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header('Location: login.php');
exit;
