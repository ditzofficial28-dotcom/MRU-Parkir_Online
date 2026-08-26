<?php
/**
 * File   : includes/header.php
 * Fungsi : Shell tampilan bersama (sidebar navigasi + topbar) yang dipakai
 *          oleh seluruh halaman admin/petugas/owner setelah login.
 * Input  : $judulHalaman (string), $breadcrumb (string, opsional)
 */
require_once __DIR__ . '/icons.php';

$role = $_SESSION['role'] ?? '';
$halamanAktif = basename($_SERVER['PHP_SELF']);

$navItems = [
    ['dashboard.php', 'dashboard', 'Panel Administrator'],
    ['parkir.php',    'map',       'Manajemen Parkir'],
    ['analitik.php',  'chart',     'Analitik Kinerja'],
    ['user.php',      'users',     'Manajemen Pengguna'],
    ['log.php',       'activity',  'Laporan & Audit'],
    ['keluar.php',    'ticket',    'Proses Keluar (Pos)'],
];

$tabGroupAdmin = [
    'dashboard.php' => 0, 'parkir.php' => 1, 'area.php' => 1, 'tarif.php' => 1, 'kendaraan.php' => 1,
    'analitik.php' => 2, 'user.php' => 3, 'log.php' => 4, 'keluar.php' => 5,
];
$activeTabAdmin = $tabGroupAdmin[$halamanAktif] ?? -1;

$roleLabel = ['admin' => 'Administrator', 'petugas' => 'Petugas Parkir', 'owner' => 'Owner'][$role] ?? 'Administrator';
$inisial = strtoupper(substr($_SESSION['nama'] ?? '?', 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($judulHalaman ?? 'Aplikasi Parkir') ?> · MRu-Parkir</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= basePath() ?>assets/style.css">
</head>
<body>
<div class="app-shell">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand" style="display:flex; align-items:center; gap:12px;">
      <img src="<?= basePath() ?>assets/logo_mru.jpg" alt="Logo MRu-Parkir" style="width:34px; height:34px; border-radius:10px; object-fit:cover; box-shadow:0 3px 10px rgba(16, 185, 129, 0.4);">
      <div class="brand-text">
        <strong style="font-family:var(--font-display); font-size:16px;">MRu-Parkir</strong>
        <small style="display:block; font-size:11px; color:var(--ink-muted);">Enterprise Parking System</small>
      </div>
    </div>
    <div class="brand-stripe" aria-hidden="true"></div>

    <nav class="sidebar-nav">
      <span class="nav-label">Menu Utama</span>
      <?php foreach ($navItems as [$href, $ic, $label]): ?>
        <a href="<?= $href ?>" class="nav-link <?= $halamanAktif === $href ? 'active' : '' ?>">
          <span class="nav-icon"><?= icon($ic) ?></span>
          <span><?= htmlspecialchars($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="user-chip">
        <span class="avatar"><?= htmlspecialchars($inisial) ?></span>
        <div class="user-meta">
          <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong>
          <small><?= htmlspecialchars($roleLabel) ?></small>
        </div>
      </div>
      <a href="<?= basePath() ?>logout.php" class="nav-link logout-link">
        <span class="nav-icon"><?= icon('logout') ?></span>
        <span>Keluar</span>
      </a>
    </div>
  </aside>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="main-area">
    <header class="admin-hero">
      <div style="display:flex; align-items:center; gap:14px;">
        <button class="hamburger" id="hamburgerBtn" aria-label="Buka menu" style="color:#fff;"><?= icon('menu') ?></button>
        <div class="admin-hero-title">
          <span class="loc"><?= icon('map') ?> Cibinong, Jawa Barat</span>
          <h1>Administrator Dashboard</h1>
        </div>
      </div>

      <!-- Live Working Indonesian Clock -->
      <div class="live-clock-widget" style="display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); padding:6px 16px; border-radius:999px; backdrop-filter:blur(8px);">
        <span style="font-size:15px; color:#34D399; line-height:1; display:inline-flex; align-items:center;"><?= icon('clock') ?></span>
        <div style="line-height:1.2;">
          <strong class="liveClockTime" style="display:block; font-family:var(--font-mono); font-size:14px; color:#ffffff; font-weight:700; letter-spacing:.04em;">--:--:-- WIB</strong>
          <span class="liveClockDate" style="font-size:10.5px; color:#94A3B8; font-weight:500;">-- -- ----</span>
        </div>
      </div>

      <div class="admin-hero-right">
        <span class="avatar"><?= htmlspecialchars($inisial) ?></span>
        <div style="line-height:1.2;">
          <strong style="display:block; font-size:13px;"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong>
          <small style="font-size:11px; opacity:.8;"><?= htmlspecialchars($roleLabel) ?></small>
        </div>
      </div>
    </header>
    <nav class="tabbar">
      <?php foreach ($navItems as $i => [$href, $ic, $label]): ?>
        <a href="<?= $href ?>" class="<?= $activeTabAdmin === $i ? 'active' : '' ?>">
          <?= icon($ic) ?> <?= htmlspecialchars($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <main class="container">
