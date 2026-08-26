<?php
/**
 * File   : includes/header_public.php
 * Fungsi : Navbar + shell untuk frontend publik (pengunjung, tanpa login).
 *          Membaca data dari database yang sama dengan yang dikelola admin
 *          (read-only), berbeda dari shell sidebar milik staf.
 */
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/functions.php';
$halamanAktif = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($judulHalaman ?? 'MRu-Parkir') ?> · MRu-Parkir</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/public.css">
<script>
  (function() {
    const savedTheme = localStorage.getItem('mru_public_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', savedTheme);
  })();
</script>
</head>
<body class="public-body">

<header class="pub-navbar">
  <div class="pub-navbar-inner">
    <a href="index.php" class="pub-brand">
      <img src="../assets/logo_mru.jpg" alt="Logo MRu-Parkir" class="brand-logo-img">
      <span>MRu-Parkir</span>
    </a>
    <nav class="pub-nav" id="pubNav">
      <a href="index.php" class="<?= $halamanAktif === 'index.php' ? 'active' : '' ?>">Beranda</a>
      <a href="tentang.php" class="<?= $halamanAktif === 'tentang.php' ? 'active' : '' ?>">Tentang Kami</a>
      <a href="peraturan.php" class="<?= $halamanAktif === 'peraturan.php' ? 'active' : '' ?>">Peraturan</a>
      <a href="tarif.php" class="<?= $halamanAktif === 'tarif.php' ? 'active' : '' ?>">Tarif</a>
      <a href="lacak.php" class="<?= $halamanAktif === 'lacak.php' ? 'active' : '' ?>">Lacak Kendaraan</a>
    </nav>
    <div class="pub-navbar-actions" style="display:flex; align-items:center; gap:8px;">
      <a href="index.php#unduh" class="btn-add pub-cta">Unduh Aplikasi</a>

      <!-- Hamburger Button Mobile -->
      <button id="pubHamburgerBtn" class="pub-hamburger-btn" aria-label="Buka Menu Navigation Mobile" title="Buka Menu Navigation Mobile">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
      </button>
    </div>
  </div>
</header>

<main class="pub-main">
